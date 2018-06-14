<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/*
Copyright 2014 PayStand Inc.

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
*/

/**
 * PayStand Payment Gateway
 *
 * Provides a PayStand Payment Gateway for WooCommerce.
 *
 * @class      WC_Gateway_PayStand
 * @extends    WC_Payment_Gateway
 * @version    1.0.4
 * @package    WooCommerce/Classes/Payment
 * @author     PayStand
 */
class WC_Gateway_PayStand extends WC_Payment_Gateway
{
  var $notify_url;
  var $publishable_key;
  var $customer_id;
  var $client_id;
  var $client_secret;
  var $allow_auto_complete = false;
  var $auto_complete;
  var $log_file_hash;
  var $log_file_path;
  var $log_file_url;
  var $debug_description;
  var $testmode_description;

  /**
   * Constructor for the gateway.
   *
   * @access public
   * @return void
   */
  public function __construct()
  {
    $this->id = 'paystand';
    $this->icon = apply_filters('woocommerce_paystand_icon', plugins_url('images/paystand_logo_small.png' , __FILE__));
    $this->has_fields = false;
    $this->title = __('PayStand (Credit Card, eCheck, ACH)', 'woocommerce-paystand');
    $this->method_title = $this->title;
    $this->description = "Use PayStand's modern checkout to pay securely with any major credit card, eCheck, or eCash (Bitcoin).";
    $this->method_description = $this->description;

    $this->order_button_text = __('Pay With Paystand ', 'woocommerce-paystand');
    $this->liveurl = 'https://checkout.paystand.com/v4/';
    $this->testurl = 'https://checkout.paystand.co/v4/';
    $this->live_api_url = 'https://api.paystand.com/v3/';
    $this->test_api_url = 'https://api.paystand.co/v3/';

      $this->notify_url = WC()->api_request_url('wc_gateway_paystand');

    // Note that this parallels the code in WC_Logger since we can't easily
    // get the file name from WC_Logger.
    $this->log_file_hash = sanitize_file_name(wp_hash('paystand'));
    $this->log_file_path = "woocommerce/logs/paystand-" . $this->log_file_hash
        . ".txt";
    $this->log_file_url = plugins_url() . "/" . $this->log_file_path;

    // Because WooCommerce denies access to the logs folder by default,
    // the View Log File link gets a 403 Forbidden
    //$this->debug_description = sprintf(__('Log PayStand events, such as payment requests, in <code>%s</code>.  <a href="%s" target="_blank">View Log File</a>', 'woocommerce-paystand'), $this->log_file_path, $this->log_file_url);
    $this->debug_description = sprintf(__('Log PayStand events, such as payment requests, in <code>%s</code>.', 'woocommerce-paystand'), $this->log_file_path);
    $this->testmode_description = sprintf(__('The PayStand sandbox server can be used to test payments. Contact us for a sandbox account <a href="%s">here</a>.', 'woocommerce-paystand'), 'https://www.paystand.com/');

    // Init settings
    $this->init_form_fields();
    $this->init_settings();

    // User defined
    $this->publishable_key = $this->get_option('publishable_key');
    $this->customer_id = $this->get_option('customer_id');
    $this->client_id = $this->get_option('client_id');
    $this->client_secret = $this->get_option('client_secret');
    $this->testmode = $this->get_option('testmode');
    $this->debug = $this->get_option('debug');
    if ($this->allow_auto_complete) {
      $this->auto_complete = $this->get_option('auto_complete');
    }

    // Logs
    if ('yes' == $this->debug) {
      $this->log = new WC_Logger();
    }

    // Actions
    add_action( 'woocommerce_checkout_order_processed', 'show_paystand_checkout' );
    add_action('woocommerce_update_options_payment_gateways_paystand', array($this, 'process_admin_options'));
    add_action('woocommerce_receipt_paystand', array($this, 'receipt_page'));
    add_action('woocommerce_api_wc_gateway_paystand', array($this, 'paystand_callback'));
    add_action('valid_paystand_callback', array($this, 'valid_paystand_callback'));
    add_action('woocommerce_thankyou_paystand', array($this, 'thankyou_page'));

    if (!$this->is_valid_for_use()) {
      $this->enabled = false;
    }
  }

  // Adds a text to the WordPress log object if it is defined
    function log_message($text) {
    if ('yes' == $this->debug) {
      $this->log->add('paystand', $text);
    }
  }
  
  /**
   * Initialize Gateway Settings Form Fields
   *
   * @access public
   * @return void
   */
  function init_form_fields()
  {
    $this->form_fields = array(
      'enabled' => array(
          'title' => __('Enable/Disable', 'woocommerce-paystand'),
          'type' => 'checkbox',
          'label' => __('Enable PayStand', 'woocommerce-paystand'),
          'default' => 'yes'
      ),       
      'publishable_key' => array(
          'title' => __('PayStand Publishable Key', 'woocommerce-paystand'),
          'type' => 'text',
          'description' => __('Your PayStand publishable key from API configuration values in your Paystand Integrations dashboard.', 'woocommerce-paystand'),
          'default' => '',
          'desc_tip' => true,
      ),
      'customer_id' => array(
        'title' => __('PayStand Customer Id ', 'woocommerce-paystand'),
        'type' => 'text',
        'description' => __('Your PayStand customer_id from API configuration values in your Paystand Integrations dashboard.', 'woocommerce-paystand'),
        'default' => '',
        'desc_tip' => true,
      ),
      'client_id' => array(
        'title' => __('PayStand Client Id ', 'woocommerce-paystand'),
        'type' => 'text',
        'description' => __('Your PayStand client_id from API configuration values in your Paystand Integrations dashboard.', 'woocommerce-paystand'),
        'default' => '',
        'desc_tip' => true,
      ),
      'client_secret' => array(
        'title' => __('PayStand Client Secret', 'woocommerce-paystand'),
        'type' => 'text',
        'description' => __('Your PayStand client_secret from API configuration values in your Paystand Integrations dashboard.', 'woocommerce-paystand'),
        'default' => '',
        'desc_tip' => true,
      ),
      'webhook' => array(
          'title' => __('Webhook', 'woocommerce-paystand'),
          'type' => 'title',
          'description' => 'Set your webhook url to <code>' . $this->notify_url . '</code> in your <a href="https://www.paystand.com/login" target="_blank">PayStand dashboard</a> under Settings > Checkout Features',
      ),
      'orders' => array(
          'title' => __('Order Processing', 'woocommerce-paystand'),
          'type' => 'title',
          'description' => '',
      ),    
      'testmode' => array(
          'title' => __('PayStand Sandbox', 'woocommerce-paystand'),
          'type' => 'checkbox',
          'label' => __('Use PayStand Sandbox Server', 'woocommerce-paystand'),
          'default' => 'no',
          'description' => $this->testmode_description,
      ),
      'debug' => array(
          'title' => __('Debug Log', 'woocommerce-paystand'),
          'type' => 'checkbox',
          'label' => __('Enable logging', 'woocommerce-paystand'),
          'default' => 'no',
          'description' => $this->debug_description,
      )         
    );     

    if ($this->allow_auto_complete) { 
      $this->form_fields['auto_complete'] =  array(
        'title' => __('Order auto-completion', 'woocommerce-paystand'),
        'type' => 'checkbox',
        'label' => __('Automatically complete paid orders', 'woocommerce-paystand'),
        'default' => 'no',
        'description' => 'Setting this will cause all orders to be automatically updated from processing to completed upon successful payment.  This is useful for situations where all of your orders do not require fulfillment, such as donations or virtual products.',
      );
    }
  }

  /**
   * Process the payment and return the result
   *
   * @access public
   * @param int $order_id
   * @return array
   */
  function process_payment($order_id)
  {
    $this->log_message('process_payment order_id: ' . $order_id);    
    $order = new WC_Order($order_id);
    return array(
        'result' => 'success',
        'redirect' => $order->get_checkout_payment_url(true)
    );
  }

  /**
   * Check if this gateway is enabled and available in the user's country
   *
   * @access public
   * @return bool
   */
  function is_valid_for_use()
  {
    /* Add other currencies soon
    if (!in_array(get_woocommerce_currency(), apply_filters('woocommerce_paystand_supported_currencies', array('AUD', 'BRL', 'CAD', 'MXN', 'NZD', 'HKD', 'SGD', 'USD', 'EUR', 'JPY', 'TRY', 'NOK', 'CZK', 'DKK', 'HUF', 'ILS', 'MYR', 'PHP', 'PLN', 'SEK', 'CHF', 'TWD', 'THB', 'GBP', 'RMB', 'RUB')))) {
      return false;
    }
    */
    if (!in_array(get_woocommerce_currency(), apply_filters('woocommerce_paystand_supported_currencies', array('USD')))) {
      return false;
    }

    return true;
  }

  /**
   * Admin Panel Options
   */
  public function admin_options()
  {
    ?>

    <h3>PayStand Checkout for WooCommerce</h3>

    <div class="paystand-banner updated" style="overflow:hidden;">
      <img style="float: right;height: 100px;margin: 10px 10px 10px 50px;" src="<?php echo plugins_url('images/paystand_logo_banner.png' , __FILE__); ?>" />
      <p class="main"><strong>Getting started</strong></p>
      <p>PayStand is your payment processor and gateway rolled into one. Set up PayStand as your WooCommerce checkout solution to get access to your money quickly, make your payments highly secure, and offer a full suite of payment methods for your customers.</p>
      <p>
        <a href="http://www.paystand.com/signup" target="_blank" class="button button-primary">Sign up for PayStand</a>
        <a href="http://go.paystand.com/lp/woocommerce.html" target="_blank" class="button">Learn more</a>
      </p>
    </div>

    <?php if ($this->is_valid_for_use()) : ?>

      <table class="form-table">
      <?php $this->generate_settings_html(); ?>
      </table><!--/.form-table-->

    <?php else : ?>

      <div class="inline error"><p><strong><?php _e('Gateway Disabled', 'woocommerce-paystand'); ?></strong>: <?php _e('PayStand does not support your store currency.', 'woocommerce-paystand'); ?></p></div>
    <?php
      endif;
  }

  /**
   * Limit the length of item names
   * @param  string $item_name
   * @return string
   */
  public function paystand_item_name($item_name)
  {
    if (strlen($item_name) > 127) {
      $item_name = substr($item_name, 0, 124) . '...';
    }
    return html_entity_decode($item_name, ENT_NOQUOTES, 'UTF-8');
  }

  /**
   * Get the server url
   */
  public function get_paystand_url()
  {
    if ('yes' == $this->testmode) {
      return $this->testurl;
    }
    return $this->liveurl;
  }

    /**
     * Get the server url
     */
    public function get_paystand_api_url()
    {
        if ('yes' == $this->testmode) {
            return $this->test_api_url;
        }
        return $this->live_api_url;
    }
  /**
   * Output for the thank you page.
   */
  public function thankyou_page($order_id)
  {
    $this->log_message('thankyou_page order_id: ' . $order_id);    
  }

  /**
   * Output for the order received page.
   *
   * @access public
   * @return void
   */
  function receipt_page($order_id)
  {
    $this->log_message('receipt_page order_id: ' . $order_id);    

    $order = new WC_Order($order_id);
    $paystand_url = $this->get_paystand_url();

    $this->log_message('Generating payment form for order ' . $order->get_order_number() . '. Notify URL: ' . $this->notify_url);    

    $return_url = $order->get_checkout_order_received_url();
    $currency = get_woocommerce_currency();

    $item_names = array();
    $items = $order->get_items();
    if (sizeof($items) > 0) {
      foreach ($items as $item) {
        if ($item['qty']) {
          $item_names[] = $item['name'] . ' x ' . $item['qty'];
        }
      }
    }

    $final_item_name = $this->paystand_item_name(sprintf(__('Order %s' , 'woocommerce-paystand'), $order->get_order_number()) . " - " . implode(', ', $item_names));    
   
    $billing_full_name = trim($order->billing_first_name . ' ' . $order->billing_last_name);
    $billing_email_address = $order->billing_email;
    $billing_street = trim($order->get_billing_address_1() . ' ' . $order->get_billing_address_2());
    $billing_city = $order->billing_city;
    $billing_postalcode = $order->billing_postcode;
    $billing_state = $order->billing_state;
    $billing_country = $order->billing_country;

    // json_encode parameters that users can enter to protect against quotes
    // and other troublesome characters
    $api_key_json = json_encode($this->api_key);
    if (is_numeric($this->org_id)) {
      // We want to pass it as a string
      $org_id_json = '"' . $this->org_id . '"';
    } else {
      // Probably bogus but maybe valid in the future
      $org_id_json = json_encode($this->org_id);
    }
    $return_url_json = json_encode($return_url);
    $final_item_name_json = json_encode($final_item_name);
    $billing_full_name_json = json_encode($billing_full_name);
    $billing_email_address_json = json_encode($billing_email_address);
    $billing_street_json = json_encode($billing_street);
    $billing_city_json = json_encode($billing_city);
    $billing_postalcode_json = json_encode($billing_postalcode);
    $billing_state_json = json_encode($billing_state);
    $billing_country_json = json_encode($billing_country);

 ?>
    <div id="ps_container_id"></div>
  <script type="text/javascript">
   // Move PayStand Div to the top of the page
   var psContainer = document.getElementById("ps_container_id");
   psContainer.parentNode.prepend(psContainer);
  </script>

  <div id="ps_checkout_load" ></div>  
  <script
    type="text/javascript"
    id="ps_checkout"
    src="<?=$paystand_url?>js/paystand.checkout.js"
    ps-viewLogo="hide"
    ps-env="sandbox"
    ps-publishableKey="<?= $this->api_key ?>"
    ps-containerId="ps_container_id"
    ps-mode="embed"
    ps-show="true"
    ps-paymentAmount="<?= $order->order_total ?>"
    ps-paymentCurrency="USD"
    ps-viewClose="hide"
    ps-fixedAmount="true"
    ps-payerName="<?=$billing_full_name?>"
    ps-payerEmail="<?=$billing_email_address?>"
    ps-spInterval="month"
    ps-payerAddressStreet = "<?=$billing_street?>"
    ps-payerAddressCity = "<?=$billing_city?>"
    ps-payerAddressCountry = "<?=$billing_country?>"
    ps-payerAddressState = "<?=$billing_state?>"
    ps-payerAddressPostal = "<?=$billing_postalcode?>"
    ps-paymentMeta = '{ "order_id" : "<?=$order_id?>" }'
    >       
  </script>
    
   <?php
  
    
  }

  function check_callback_data($post_data)
  {
      if (empty($post_data) || !is_array($post_data)) {        
        $this->log_message('check_callback_data POST data is empty');        
        return false;
      }

      // get Authorization token
      $paystand_api_url = $this->get_paystand_api_url();
      $endpoint = $paystand_api_url . 'oauth/token';
      $grant_type = "client_credentials";
      $request = array(
        'grant_type' => $grant_type,
        'client_id' => $this->client_id,
        'client_secret' => $this->client_secret,
        'scope' => 'auth'
      );

      $this->log_message('check_callback_data Access_Tokens endpoint: ' . $endpoint);
      $this->log_message('check_callback_data Access_Tokens request: ' . print_r($request, true));    

      $context = stream_context_create(array(
        'http' => array(
            'method' => 'POST',
            'header' => "Accept: application/json\r\n Content-Type: application/json\r\n ",
            'content' => json_encode($request)
        )
      ));

      $access_token = null;
      // calling Rest Access Token
      $response = file_get_contents($endpoint, false, $context);

      $this->log_message('check_callback_data Access_Tokens response: ' . print_r($response, true));      

      if($response === false ){
          $this->log_message('check_callback_data Access_Tokens error: Endpoint not Response');
          return false;
      }
      else{
          if('Unauthorized'===$response){
              $this->log_message('check_callback_data Access_Tokens - Unauthorized');
              return false;
          }
          else{
              $response_data = json_decode($response, true);

              if(isset($response_data['error'])){
                  $this->log_message('check_callback_data Access_Tokens error: ' . print_r($response, true));
                  return false;
              }
              else{
                  $access_token = $response_data['access_token'];
              }
          }
      }

      // call GET Payments
      $endpoint = $paystand_api_url . 'payments/' . $post_data->id;

      $this->log_message('check_callback_data GET_payments endpoint: ' . $endpoint);
      $this->log_message('check_callback_data GET_payments request: ' . print_r($request, true));


      $header = array('Authorization' => 'Bearer '. $access_token ,
          'X-CUSTOMER-ID' => $this->customer_id,
          'Accept' => 'application/json',
          'Content-Type' => 'application/json'
      );

      $context = stream_context_create(array(
          'http' => array(
              'method' => 'GET',
              'header' => $header
          )
      ));

      $order_id = null;
      // calling Rest Get Payments
      $response = file_get_contents($endpoint, false, $context);

      $this->log_message('check_callback_data GET_payments response: ' . print_r($response, true));      

      if($response === false ){
          $this->log_message('check_callback_data GET_payments error: Endpoint not Response');
          return false;
      }
      else{
          $response_data = json_decode($response, true);

          if(isset($response_data['error'])){
              $this->log_message('check_callback_data GET_payments error: ' . print_r($response, true));
              return false;
          }
          else{
              $order_id = $response_data['id'];
              $amount = $response_data['amount'];

              if($order_id === $this->id && $amount === $this->total ){
                  $this->log_message('check_callback_data GET_payments order_id: ' . $order_id);                  
              }
          }

      }

    $order = false;
    if (isset($order_id)) {
      $order = new WC_Order($order_id);
    }
    if (!$order) {      
      $this->log_message('Order not found for order id: ' . $order_id);      
      return false;
    }
    $this->log_message('check_callback_data order: ' . print_r($order, true));

    return true;
  }

  /**
   * Handle callback from PayStand.
   *
   * @access public
   * @return void
   */
  function paystand_callback()
  {
    $this->log_message('paystand_callback');

    if (isset($_GET['status'])) {
      wp_die("PayStand Callback Status: " . print_r($this, true), "PayStand", array('response' => 200));
    }

    $response_webhook = $_POST;
    if (empty($response_webhook)) {
        $response_webhook = json_decode(file_get_contents("php://input"), true);
    }
    $this->log_message('psn: ' . print_r($response_webhook, true));    

    if ($this->check_callback_data($response_webhook)) {
      header('HTTP/1.1 200 OK');
      do_action("valid_paystand_callback", $response_webhook);
    } else {
      http_response_code(400);
      wp_die("PayStand Callback Failure", "PayStand", array('response' => 200));
    }
  }

  /**
   * Valid PayStand callback
   *
   * @access public
   * @param array $data
   * @return void
   */
  function valid_paystand_callback($data)
  {
    $this->log_message('valid_paystand_callback' . print_r($data, true));    

    $success = false;
    if (!empty($data['success'])) {
      $success = $data['success'];
    }
    $payment_status = false;
    if (!empty($data['payment_status'])) {
      $payment_status = $data['payment_status'];
    }
    $this->log_message('Payment success: ' . $success);
    $this->log_message('Payment status: ' . $payment_status);
    

    $order_id = false;
    if (!empty($data['order_id'])) {
      $order_id = $data['order_id'];
    }
    $order = false;
    if ($order_id) {
      $order = new WC_Order($order_id);
    }
    if (!$order) {
      $this->log_message('Order not found for order id: ' . $order_id);      
      return;
    }

    if ($success) {
      $fee_added = get_post_meta($order_id, '_ps_fee_added', true);
      if (empty($fee_added)) {
        update_post_meta($order_id, '_ps_fee_added', '1');
        $total = get_post_meta($order_id, '_order_total', true);
        $flat = $data['fee_consumer_owes'];
        $rate = $data['rate_consumer_owes'];
        $fee = $flat + $rate;
        $item = array('order_item_name' => 'Processing Fee',
            'order_item_type' => 'fee');
        $item_id = wc_add_order_item($order_id, $item);
        if ($item_id) {
          wc_add_order_item_meta($item_id, '_tax_class', '0');
          wc_add_order_item_meta($item_id, '_line_total', wc_format_decimal($fee));
          wc_add_order_item_meta($item_id, '_line_tax', wc_format_decimal(0));
        }
        $total += $fee;
        update_post_meta($order_id, '_order_total', wc_format_decimal($total, get_option('woocommerce_price_num_decimals')));
      }
      $order->add_order_note(__('Payment completed', 'woocommerce-paystand'));
      $order->payment_complete();
      if ($this->allow_auto_complete && ('yes' == $this->auto_complete)) {
        $order->update_status('completed', 'Order auto-completed.');        
        $this->log_message('Order auto-completed: ' . $order_id);        
      }
    } else {
      $order->update_status('on-hold', sprintf(__('Payment pending: %s', 'woocommerce-paystand'), $payment_status));
    }
  }
}

