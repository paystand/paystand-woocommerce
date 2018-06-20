<?php

//error_reporting(E_ALL);
//ini_set('display_errors', 1);

include( plugin_dir_path( __FILE__ ) . 'includes/httpful.phar');

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


include( plugin_dir_path( __FILE__ ) . 'includes/iso3166.php');

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
  var $payment_status = null;
  var $order_id = null;
  var $paystand_fee = null;

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
    // Used to add fields directly in the checkout screen (for saved cards)    
    $this->has_fields = true;

    // Add support for tokenization
    $this->supports = array('tokenization');

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
    $this->order_id = null;
    $this->paystand_fee = null;
    $this->payment_status = null;

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

    private function isValidStatus($status){
        $allowed_status = array("PAID","FAILED");
        return in_array(strtoupper($status), $allowed_status);
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
   * WooCommerce Function to render saved payment methods
   */
  function payment_fields() {    
    if (count($this->get_tokens()) > 0 ) {
      $this->saved_payment_methods();
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
   * Get the server checkout url
   */
  public function get_paystand_url()
  {
    return ('yes' == $this->testmode) ?  $this->testurl : $this->liveurl;          
  }

  /**
   * Get the server api url
   */
  public function get_paystand_api_url()
  {
    return ('yes' == $this->testmode) ? $this->test_api_url : $this->live_api_url;      
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
    $user_id = get_current_user_id();
    $this->log_message('Generating payment form for order ' . $order->get_order_number() . '. Notify URL: ' . $this->notify_url);    

    $return_url = $order->get_checkout_order_received_url();
    $currency = get_woocommerce_currency();
    
   
    $billing_full_name = trim($order->billing_first_name . ' ' . $order->billing_last_name);
    $billing_email_address = $order->billing_email;
    $billing_street = trim($order->get_billing_address_1() . ' ' . $order->get_billing_address_2());
    $billing_city = $order->billing_city;
    $billing_postalcode = $order->billing_postcode;
    $billing_state = $order->billing_state;    
    $billing_country = getISO3166_3_code($order->billing_country);
 ?>
 
  <div id="ps_container_id">

  <script type="text/javascript">
   // Move PayStand Div to the top of the page
   var psContainer = document.getElementById("ps_container_id");
   psContainer.parentNode.prepend(psContainer);  
  </script>

  <div id="ps_checkout_load" style= " text-align: center" >
  <label for= "savePaymentMethod">
    <input type="checkbox" id="savePaymentMethod", name="savePaymentMethod" value="Save Pament Method"/>
    Save This Payment Method
  </label>
  </div>  
  <script
    type="text/javascript"
    id="ps_checkout"
    src="<?=$paystand_url?>js/paystand.checkout.js"
    ps-viewLogo="hide"
    ps-env="sandbox"
    ps-publishableKey="<?= $this->publishable_key ?>"
    ps-containerId="ps_container_id"
    ps-mode="embed"
    ps-show="true"
    ps-viewCheckout="mobile"
    ps-paymentAmount="<?= $order->order_total ?>"
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
    ps-paymentMeta = '{ "order_id" : "<?=$order_id?>", "user_id":  "<?= $user_id ?>" }'
    ps-paymentCurrency =  "<?= $currency ?>">
  </script>   

   <script type="text/javascript">
    psCheckout.onceLoaded(function() {
      var prevent_multiple_calls = false;
      psCheckout.onceComplete( function(result) {        
        if(prevent_multiple_calls) {  return;  }
        
        prevent_multiple_calls = true;
        // TODO:  Check that payment was completed succesfully (not failed)        
        if (document.getElementById('savePaymentMethod').checked == true) {
          // If "remember me" option is selected, send request to WooCommerce to save card  
          var xhr = new XMLHttpRequest();
          xhr.open('POST', '/?wc-api=wc_gateway_paystand', true);
          xhr.setRequestHeader('Content-type', 'application/json');
          xhr.onload = function () {                            
            // We move to the "complete" screen once we get the response
            window.location.href = "<?= $return_url ?>" ;
          };

          var data = {
            object: "WC_Paystand_Event",
            type:"save_payment",            
            data: result.response.data
          };
          xhr.send(JSON.stringify(data));
        } else {
          window.location.href = "<?= $return_url ?>" ;
        }    
      });
    });     
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
      $this->log_message('check_callback_data Access_Tokens request: ' . print_r(json_encode($request), true));

      $access_token = null;
      // calling Rest Access Token
      try{
          $response = \Httpful\Request::post($endpoint)                  // Build a POST request...
          ->sendsJson()                               // tell it we're sending (Content-Type) JSON...
          ->body(json_encode($request))             // attach a body/payload...
          ->send();
      } catch (Exception $e) {
          $this->log_message('check_callback_data Access_Tokens exception: ' . print_r($e, true));
      }

      $this->log_message('check_callback_data Access_Tokens response: ' . print_r($response->raw_body, true));

      if($response->code!==200){ // Unauthorized or another error
          $this->log_message('check_callback_data Access_Tokens error: '.print_r($response->body, true));
          return false;
      }
      else {
          $access_token = $response->body->access_token;
      }

      // call GET Payments
      $endpoint = $paystand_api_url . 'payments/' . $post_data["sourceId"];
      $header = array('Authorization' => 'Bearer '. $access_token ,
          'X-CUSTOMER-ID' => $this->customer_id,
          'Accept' => 'application/json',
          'Content-Type' => 'application/json'
      );

      $this->log_message('check_callback_data GET_payments endpoint: ' . $endpoint);
      $this->log_message('check_callback_data GET_payments request headers: ' . print_r( $header, true));

      $transaction_id = null;
      $order_id = null;
      try{
        // calling Rest Get Payments
          $response = \Httpful\Request::get($endpoint)   // Build a POST request...
          ->addHeaders($header)                          // headers ...
          ->send();
      } catch (Exception $e) {
        $this->log_message('check_callback_data GET_payments exception: ' . print_r($e, true));
      }

      $this->log_message('check_callback_data GET_payments response: ' . print_r($response->raw_body, true));

      if($response->code!==200){
          $this->log_message('check_callback_data GET_payments error: ' . print_r($response->body, true));
          return false;
      }
      else{
          $transaction_id = $response->body->id;
          $meta = $response->body->meta;
          $this->order_id = $meta->order_id;
          $this->payment_status = $response->body->status;
          $this->paystand_fee = 0;
          if(isset($response->body->feeSplit)){ // apply fees
              $this->paystand_fee = $response->body->feeSplit->payerTotalFees;
          }
          $this->log_message('check_callback_data GET_payments meta: ' . print_r($meta, true));
          $this->log_message('check_callback_data GET_payments order_id: ' . $meta->order_id);

          if(!$this->isValidStatus($this->payment_status)){
              $this->log_message('check_callback_data Invalid Order Status :' . $this->payment_status );
              return false;
          }
      }

    $order = false;
    if (isset($this->order_id)) {
      $order = new WC_Order($this->order_id);
        if($order->get_status()==="completed" ||
            $order->get_status()==="failed"){ // already is a COMPLETE or FAILED
            $this->log_message('check_callback_data already processed :' . $this->payment_status );
            return false;
        }
    }
    if (!$order) {
      $this->log_message('Order not found for order id: ' . $this->order_id);
      return false;
    }

    return true;
  }

  /**
   * Processes a callback from the frontend asking to save a payment method
   */
  function process_payment_save_callback($response){
    if ($response["object"] != "WC_Paystand_Event" || $response["type"] != "save_payment") {
      return;
    }
    $payment_source = $response['data']['source'];

    switch($payment_source['object']) {
      case 'card':
        $token = new WC_Payment_Token_CC();      
        $token->set_token($payment_source['id'] ); 
        $token->set_gateway_id( 'Paystand' );
        $token->set_last4( $payment_source['last4'] );
        $token->set_expiry_year( $payment_source['expirationYear'] );
        $token->set_expiry_month( $payment_source['expirationMonth'] );
        $token->set_card_type( $payment_source['brand'] );
        $token->set_user_id( $response['data']['meta']['user_id'] );
        // Save the new token to the database
        $this->log_message("Saving token...");        
        $token->save();
        break;
      case '':
      default:
        $this->log_message("Unknown payment source cannot be handled: " . $payment_source['object']);
        break;
    }    
    //TODO:  Add code to save the payment method depending depending on Card, ECheck, etc
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
    
    // If we got a WC_Paystand event, we process it separatedly from the standard callback objects
    if($response_webhook['object'] == 'WC_Paystand_Event') {
      $this->process_payment_save_callback($response_webhook);
      return; 
    }

    $this->log_message('WebHook call: ' . print_r($response_webhook->resource, true));

    if ($this->check_callback_data($response_webhook)) { // set status & order_id & fees
      header('HTTP/1.1 200 OK');
      do_action("valid_paystand_callback", $response_webhook);
    } else {
      http_response_code(400);
      wp_die("PayStand Callback Failure", "PayStand", array('response' => 200));
    }
  }

  /**
   * Valid PayStand callback
   * This is called when a valid transaction has been received as a callback from 
   * Paystand Webhooks
   * @access public
   * @param array $data
   * @return void
   */
  function valid_paystand_callback($data)
  {
    $this->log_message('valid_paystand_callback');

    $payment_status = $this->payment_status;
    $STATUS_SUCCESS = "PAID";

    // One of: "paid", "failed"
    $success = ($STATUS_SUCCESS===strtoupper($this->payment_status));

    $this->log_message('Payment success: ' . $success);
    $this->log_message('Payment status: ' . $payment_status);

    $order_id = $this->order_id;
    $order = new WC_Order($order_id);
    if (!$order) {
      $this->log_message('Order not found for order id: ' . $order_id);      
      return;
    }

    if ($success) {
        $total = get_post_meta($order_id, '_order_total', true);
        $fee = $this->paystand_fee;
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
        $order->add_order_note(__('Payment completed', 'woocommerce-paystand'));
        $order->payment_complete();
        // pending to check where is set this configuration
        //if ($this->allow_auto_complete && ('yes' == $this->auto_complete)) {
        $order->update_status('completed', 'Order auto-completed.');
        $this->log_message('Order auto-completed: ' . $order_id);
        //}
    } else {
      $order->update_status('failed', sprintf(__('Payment failed: %s', 'woocommerce-paystand'), $payment_status));
    }
  }

  /**
   * Contains form and logic to add a payment method from the user's settings
   */
  function add_payment_method() {

  }
}

