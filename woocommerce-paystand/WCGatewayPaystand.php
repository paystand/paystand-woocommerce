<?php

use \Httpful\Request;
include_once (plugin_dir_path( __FILE__ ) . 'includes/bootstrap.php');
include_once( plugin_dir_path( __FILE__ ) . 'PaystandCheckoutFactory.php');
include_once( plugin_dir_path( __FILE__ ) . 'PaystandFormFields.php');


if (!defined('ABSPATH')) exit; // Exit if accessed directly

/*
Copyright 2014 Paystand Inc.

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
 * Paystand Payment Gateway
 *
 * Provides a Paystand Payment Gateway for WooCommerce.
 *
 * @class      WC_Gateway_Paystand
 * @extends    WC_Payment_Gateway
 * @version    1.0.4
 * @package    WooCommerce/Classes/Payment
 * @author     Paystand
 */
class WC_Gateway_PayStand extends WC_Payment_Gateway
{
  var $notify_url;
  var $publishable_key;
  var $customer_id;
  var $client_id;
  var $client_secret;
  var $auto_complete;
  var $log_file_hash;
  var $log_file_path;
  var $log_file_url;
  var $debug_description;
  var $testmode_description;
  var $payment_status = null;
  var $order_id = null;
  var $paystand_fee = null;
  var $transaction_id = null;
  var $view_funds = null;
  var $cardPayment_fee = null;
  var $bankPayment_fee = null;
  var $feeSplitCard = null;
  var $feeSplitBank = null;
  var $total_amount_card = null;
  var $total_amount_bank = null;

  /**
   * Constructor for the gateway.
   *
   * @access public
   * @return void
   */
  public function __construct()
  {
    $this->id = 'paystand';
    $this->icon = apply_filters('woocommerce_paystand_icon', plugins_url('images/paystand_logo_small_new.png' , __FILE__));
    $this->has_fields = false;
    $this->title = __('Paystand (CC, Bank, ACH)', 'woocommerce-paystand');
    $this->method_title = $this->title;
    $this->description = "Use Paystand's modern checkout to pay securely with any major credit card, bank, or ACH.";
    $this->method_description = $this->description;

    $this->order_button_text = __('Pay With Paystand ', 'woocommerce-paystand');
    $this->liveurl = 'https://checkout.paystand.com/v4/';
    $this->testurl = 'https://checkout.paystand.co/v4/';
    $this->live_api_url = 'https://api.paystand.com/v3/';
    $this->test_api_url = 'https://api.paystand.co/v3/';
    $this->notify_url = WC()->api_request_url('wc_gateway_paystand');
    $this->paystand_checkout_config = $this->get_paystand_checkout_config();
    // Used to add fields directly in the checkout screen (for saved cards)
    $this->has_fields = true;

    // Add support for tokenization
    $this->supports = array('tokenization','add_payment_method');

    // Note that this parallels the code in WC_Logger since we can't easily
    // get the file name from WC_Logger.
    $this->log_file_hash = sanitize_file_name(wp_hash('paystand'));
    $this->log_file_path = "woocommerce/logs/paystand-" . $this->log_file_hash
        . ".txt";
    $this->log_file_url = plugins_url() . "/" . $this->log_file_path;

    // Because WooCommerce denies access to the logs folder by default,
    // the View Log File link gets a 403 Forbidden
    //$this->debug_description = sprintf(__('Log Paystand events, such as payment requests, in <code>%s</code>.  <a href="%s" target="_blank">View Log File</a>', 'woocommerce-paystand'), $this->log_file_path, $this->log_file_url);
    $this->debug_description = sprintf(__('Log Paystand events, such as payment requests, in <code>%s</code>.', 'woocommerce-paystand'), $this->log_file_path);
    $this->testmode_description = sprintf(__('The Paystand sandbox server can be used to test payments. Contact us for a sandbox account <a href="%s">here</a>.', 'woocommerce-paystand'), 'https://www.paystand.com/');

    // Init settings
    $this->init_form_fields();
    $this->init_settings();

    // User defined
    $this->publishable_key = $this->get_option('publishable_key');
    $this->customer_id = $this->get_option('customer_id');
    $this->client_id = $this->get_option('client_id');
    $this->client_secret = $this->get_option('client_secret');
    $this->testmode = $this->get_option('testmode');
    $this->view_checkout = $this->get_option('view_checkout');
    $this->render_mode = $this->get_option('render_mode');
    $this->debug = $this->get_option('debug');
    $this->render_width = $this->get_option('width');
    $this->custom_preset = $this->get_option('custom_preset');
    $this->order_id = null;
    $this->paystand_fee = null;
    $this->payment_status = null;
    $this->auto_complete = $this->get_option('auto_complete');
    $this->auto_processing = $this->get_option('auto_processing');
    $this->show_payment_method = $this->get_option('show_payment_method');
    $this->view_funds = $this->get_option('view_funds');

    // Logs
    if ('yes' == $this->debug) {
      $this->log = new WC_Logger();
    }

    $this->cardPayment_fee = 0;
    $this->bankPayment_fee = 0;

    $this->log_message(' Payment method flag = '.$this->show_payment_method);

    // Actions
    add_action('woocommerce_checkout_order_processed', array($this,'order_processed'));
    add_action('woocommerce_update_options_payment_gateways_paystand', array($this, 'process_admin_options'));
    add_action('woocommerce_receipt_paystand', array($this, 'receipt_page'));
    add_action('woocommerce_api_wc_gateway_paystand', array($this, 'paystand_callback'));
    add_action('valid_paystand_callback', array($this, 'valid_paystand_callback'));
    add_action('woocommerce_thankyou_paystand', array($this, 'thankyou_page'));

    $this->enabled = $this->is_valid_for_use() ? 'yes' : 'no';
  }

  /**
   * clean fee session
   **/
  function order_processed($order_id){
    WC()->session->__unset('fee_chosen');
  }

  // Adds a text to the WordPress log object if it is defined
    function log_message($text) {
    if ('yes' == $this->debug) {
      $this->log->add('paystand', $text);
    }
  }

  private function isValidStatus($status){
       $allowed_status = array("PAID", "FAILED", "CREATED", "PROCESSING", "POSTED");
       return in_array(strtoupper($status), $allowed_status);
  }

  /**
   * Initialize Gateway Settings Form Fields
   *
   **/
  function init_form_fields()
  {
    $this->form_fields = PaystandFormFields::get_init_form_fields(array('notify_url' => $this->notify_url) );
  }

  /**
  *  Validates width setting from init_form_fields(), if it is outside range it is overriden by correct value
  */
  function validate_width_field($key, $val) {
    return ($val > 100 )? 100  : (($val < 1) ? 1 : $val);
  }

  /**
   * WooCommerce Function to render saved payment methods
   */
  function payment_fields() {
    if($this->show_payment_method=='yes'){
      // We only show the available payment methods during Checkout.
      if (is_checkout() &&  count($this->get_tokens()) > 0)  {
        $total_payment =  WC()->cart->get_total($context = '') - WC()->cart->get_fee_total();
        $this->get_split_fees($total_payment);
        $this->saved_payment_methods();
      } else if(isset($_POST['woocommerce_add_payment_method'])  ) {
        // During "add payment method" option, we render Paystand Checkout in Token Saving mode
        $this->render_ps_checkout('checkout_token',null, wc_get_endpoint_url( 'payment-methods' ));
      } else {
        echo $this->description;
      }
    } else {
      echo "<b>*Saving Paystand Payment Method is not allowed.</b>";
    }

  }

  function convertFeeToText($value){
    $fee_message = ' - (added processing fee $%s)';
    $no_fee = ' - (no processing fee)';
    return ($value==0)?$no_fee:sprintf($fee_message, $value);
  }

  /**
   * Payer pays fee calculation function
  */
  public function payer_pays_fees_calculation($type){
    return ($type==="CC")?floatval($this->cardPayment_fee):floatval($this->bankPayment_fee);
  }

  /**
   * Added custom field saved method rial with the hint of the fund to be added or not
  */
  public function get_saved_payment_method_option_html( $token ) {
    $fee = $this->payer_pays_fees_calculation($token->get_type());
    $html = sprintf(
        '<li class="woocommerce-SavedPaymentMethods-token">
            <input id="wc-%1$s-payment-token-%2$s" type="radio" fee="%5$s" name="wc-%1$s-payment-token" value="%2$s" style="width:auto;" class="woocommerce-SavedPaymentMethods-tokenInput" %4$s />
            <label for="wc-%1$s-payment-token-%2$s">%3$s %6$s </label>
        </li>',
        esc_attr( $this->id ),
        esc_attr( $token->get_id() ),
        esc_html( $token->get_display_name() ),
        false,
        $fee,
        $this->convertFeeToText($fee)
      );

    return apply_filters( 'woocommerce_payment_gateway_get_saved_payment_method_option_html', $html, $token, $this );
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
    // do standard checkout If we do not have a selected token or the selected payment method is "new"
    if (!isset( $_POST['wc-paystand-payment-token']) || 'new' == $_POST['wc-paystand-payment-token']) {
      return array(
        'result' => 'success',
        'redirect' => $order->get_checkout_payment_url(true)
      );
    }
    else {
      $this->log_message("Processing payment with saved payment method");

      $wc_payment_token = WC_Payment_Tokens::get(wc_clean($_POST['wc-paystand-payment-token']));

      if ($wc_payment_token->get_user_id() !== get_current_user_id()) {
        wc_add_notice(__("The Selected Payment Method is Invalid",'woocommerce-paystand'),'error');
        return array(
          'result' => 'failure',
          'redirect' => $order->get_checkout_payment_url(true)
        );
      }

      $ps_access_token = $this->get_ps_api_token();
      // call POST Payments
      $header = array('Authorization' => 'Bearer '. $ps_access_token ,
          'X-CUSTOMER-ID' => $this->customer_id,
          'Content-Type' => 'application/json'
      );
      $id_key = $wc_payment_token->get_type() == 'CC' ? 'cardId' : 'bankId';
      $body = array(
        'amount' => $order->get_total(),
        $id_key => $wc_payment_token->get_token(),
        'currency' => $currency = get_woocommerce_currency(),
        'payerId' => $wc_payment_token->get_meta('payerId'),
        'meta' => array(
          'order_id' => $order_id ,
          'user_id'  => get_current_user_id()
        ),
        'checkBalance' => true
      );

      $this->get_split_fees($order->get_total());

      $endpoint = $this->get_paystand_api_url() . 'payments/secure';
      $this->log_message("ready to send post payment");
      try{
       $response =  $this->do_http_post($endpoint, $header, $body);
      } catch (Exception $e) {
        $this->log_message('process_payment POST exception: ' . print_r($e, true));
      }
      
      $this->log_message("post payment sent");
      $this->log_message("post payment response code" . $response->code);
      if($response->code!==200){
        $this->log_message('process_payment POST error: ' . print_r($response->body, true));
        return false;
      }
      
      $this->log_message("post payment response status" . $response->body->status);
      $this->log_message('process_payment POST response: ' . print_r($response->body, true));
      if($response->body->status == 'processing') {
        $return_array = array(
          'result' => 'success',
          'redirect' => $order->get_checkout_payment_url(true).'&processing=true&redirectUrl='.urlencode($order->get_checkout_order_received_url())
        );
      } else if ($response->body->status == 'posted') {
        $return_array = array(
          'result' => 'success',
          'redirect' => $order->get_checkout_order_received_url()
        );
      } else {
        $this->log_message('process_payment unknown payment status: ' . print_r($response->body->status, true));
        return false;
      }

      return $return_array;
    }
  }

   function do_http_post($endpoint, $header, $body) {
   return \Httpful\Request::post($endpoint)
    ->addHeaders($header)
    ->body(json_encode($body))
    ->send();
  }
  /**
   * Check if this gateway is enabled and available in the user's country
   *
   * @access public
   * @return bool
   */
  function is_valid_for_use()
  {
    return in_array(get_woocommerce_currency(), apply_filters('woocommerce_paystand_supported_currencies', array('USD','MXN','CAD','EUR','GBP', 'AUD')));
  }

  /**
   * Admin Panel Options
   */
  public function admin_options()
  {
    ?>
    <h3>Paystand Checkout for WooCommerce</h3>
    <div class="paystand-banner updated" style="overflow:hidden;">
      <img style="float: right;height: 100px;margin: 10px 10px 10px 50px;" src="<?php echo plugins_url('images/paystand_logo_banner_new.png' , __FILE__); ?>" />
      <p class="main"><strong>Getting started</strong></p>
      <p>Paystand is your payment processor and gateway rolled into one. Set up Paystand as your WooCommerce checkout solution to get access to your money quickly, make your payments highly secure, and offer a full suite of payment methods for your customers.</p>
      <p>
        <a href="http://www.paystand.com/signup" target="_blank" class="button button-primary">Sign up for Paystand</a>
        <a href="http://www.paystand.com/woocommerce-invoicing" target="_blank" class="button">Learn more</a>
      </p>
    </div>

    <?php if ($this->is_valid_for_use()) : ?>
      <table class="form-table">
      <?php $this->generate_settings_html(); ?>
      </table><!--/.form-table-->
    <?php else : ?>
      <div class="inline error"><p><strong><?php _e('Gateway Disabled', 'woocommerce-paystand'); ?></strong>: <?php _e('Paystand does not support your store currency.', 'woocommerce-paystand'); ?></p></div>
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
    // return ('yes' == $this->testmode) ?  $this->testurl : $this->liveurl;
    return $this->paystand_checkout_config->checkout;
  }

  /**
   * Get the server api url
   */
  public function get_paystand_api_url()
  {
    // return ('yes' == $this->testmode) ? $this->test_api_url : $this->live_api_url;
    return $this->paystand_checkout_config->api;
  }
  /**
   * Output for the thank you page.
   */
  public function thankyou_page($order_id)
  {
    $this->log_message('thankyou_page order_id: ' . $order_id);
  }


  /**
   *  Renders the Paystand Checkout according to passed parameters.
   *
   *  @param PS checkout behaviour  values can be checkout_payment|checkout_token|
   *          checkout_scheduled_payment|checkout_token2col
   * @param $order_id WooCommerce order id
   * @param $return_url URL to redirect to once the checkout process is complete
   **/
  function render_ps_checkout($checkout_type, $order_id = null,  $return_url = null) {

      $order = $order_id == null ? null : new WC_Order($order_id);
      $user_id = get_current_user_id();
      $currency = get_woocommerce_currency();
      $checkout_env = $this->get_checkout_env();

      $data['paystand_url']=$this->get_paystand_url();
      $data['publishable_key']=$this->publishable_key;
      $data['checkout_type']=$checkout_type;
      $data['order']=$order;
      $data['user_id']=$user_id;
      $data['currency']=$currency;
      $data['order_id']=$order_id;
      $data['view_checkout'] =  $this->view_checkout;
      $data['render_mode'] =  $this->render_mode;
      $data['render_width'] =  $this->render_width;
      $data['custom_preset'] =  $this->custom_preset;
      $data['testmode'] = $this->testmode;
      $data['show_payment_method'] = $this->show_payment_method;
      $data['view_funds'] = $this->view_funds;

      $ps_checkout = PaystandCheckoutFactory::build($checkout_type, $checkout_env, $data, $return_url);
      $ps_checkout->render();
  }

  /**
   * The receipt_page function is used to show Paystand checkout form.
   * This is where the user will enter their payment information.
   *
   * @param  order_id  WooCommerce id of the order to be paid
   * @access public
   * @return void
   */
  function receipt_page($order_id)
  {
    $this->log_message('receipt_page order_id: ' . $order_id);
    $order = new WC_Order($order_id);
    $this->log_message('Generating payment form for order ' . $order->get_order_number() . '. Notify URL: ' . $this->notify_url);

    $return_url = $order->get_checkout_order_received_url();
    $this->render_ps_checkout('checkout_payment', $order_id, $return_url);
  }

  function check_callback_data($post_data)
  {
      if (empty($post_data) || !is_array($post_data)) {
        $this->log_message('check_callback_data POST data is empty');
        return false;
      }

      $COMPLETED = "completed";
      $FAILED = "failed";
      $PROCESSING = "processing";
      $paystand_api_url = $this->get_paystand_api_url();
      $access_token = $this->get_ps_api_token();
      // call GET Payments
      $endpoint = $paystand_api_url . 'payments/' . $post_data["sourceId"];
      $header = array('Authorization' => 'Bearer '. $access_token ,
          'X-CUSTOMER-ID' => $this->customer_id,
          'Accept' => 'application/json',
          'Content-Type' => 'application/json'
      );

      $this->log_message('check_callback_data GET_payments endpoint: ' . $endpoint);
      $this->log_message('check_callback_data GET_payments request headers: ' . print_r( $header, true));

      $order_id = null;
      try{
          $response = \Httpful\Request::get($endpoint)->addHeaders($header)->send();
      } catch (Exception $e) {
        $this->log_message('check_callback_data GET_payments exception: ' . print_r($e, true));
      }
      $this->log_message('check_callback_data GET_payments response: ' . print_r($response->raw_body, true));

      if($response->code!==200) {
          $this->log_message('check_callback_data GET_payments error: ' . print_r($response->body, true));
          return false;
      }

      $this->transaction_id = $response->body->id;
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

    $order = false;
    if (isset($this->order_id)) {
      $order = new WC_Order($this->order_id);
      $order_is_finalized = (($order->get_status()===$COMPLETED || $order->get_status()===$PROCESSING) || ($order->get_status()===$FAILED));
      if($order_is_finalized){
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

      $this->log_message('process_payment_save_callback -'. print_r($response, true));

      if ($response["object"] != "WC_Paystand_Event" || $response["type"] != "save_payment") {
          return;
      }

      // token
      if($response['data']['object'] === 'token' ){
        $payment_source = $response['data'][empty($response['data']['card']) ? 'bank' : 'card'];
      }
      else{ // bank, ach
          $payment_source = $response['data']['source'];
      }

      $token_type = $payment_source['object'];
      $this->saveToken($token_type, $payment_source, $response);
  }

  /**
   * save token for add payment methods
   **/
  private function saveToken($token_type, $payment_source, $response){
      $token = null;

      switch($token_type){
          case 'card':
              $token = new WC_Payment_Token_CC();
              $token->set_expiry_year( $payment_source['expirationYear'] );
              $token->set_expiry_month( $payment_source['expirationMonth'] );
              $token->set_card_type( $payment_source['brand'] );
              break;
          case 'bank':
              $token = new WC_Payment_Token_eCheck();
              break;
          default:
              $this->log_message("Unknown payment source cannot be handled: " . $token_type);
              return;
              break;
      }

      $token->set_token($payment_source['id'] );
      $token->set_gateway_id( 'Paystand' );
      $token->set_user_id( $response['user_id'] );
      $token->add_meta_data('payerId', $response['data']['payerId']);

      $token->set_last4( $payment_source['last4'] );
      $this->log_message("Saving token... with last four: " . $payment_source['last4']);
      $token->save();
  }

  /**
   * Handle callback from Paystand.
   *
   * @access public
   * @return void
   */
  function paystand_callback()
  {
    $this->log_message('paystand_callback');

    if (isset($_GET['status'])) {
      wp_die("Paystand Callback Status: " . print_r($this, true), "Paystand", array('response' => 200));
    }

    if (isset($_GET['action'])) {
      switch($_GET['action']){
          case 'fetch_payment_status':
            $this->log_message('fetch_payment_status');
            if($_GET['order_id']){
              $order = new WC_Order($_GET['order_id']);
              $paymentStatus = $order->get_meta('paymentStatus');
              $this->log_message('paymentStatus '.$paymentStatus);
              header('Content-type: text/plain');
              echo $paymentStatus;
              exit;
            }
            break;
      }
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

    if(is_object($response_webhook)){
      $this->log_message('WebHook call: ' . print_r($response_webhook->resource, true));
    }

    if ($this->check_callback_data($response_webhook)) { // set status & order_id & fees
      header('HTTP/1.1 200 OK');
      do_action("valid_paystand_callback", $response_webhook);
    } else {
      http_response_code(400);
      wp_die("Paystand Callback Failure", "Paystand", array('response' => 200));
    }
  }

  /**
   * Valid Paystand callback
   * This is called when a valid transaction has been received as a callback from
   * Paystand Webhooks
   * @access public
   * @param array $data
   * @return void
   */
  function valid_paystand_callback($data)
  {
    $this->log_message('[valid_paystand_callback] Processing valid paystand callback');

    if ($data['resource']['object'] !='payment') {
      $this->log_message('Received non-payment object. Refusing to process payment');
      return;
    }

    $payment_status = strtoupper($data['resource']['status']);

    $this->log_message("Payment status from request:" . $payment_status);
    $this->log_message("Auto processing is set to: ". $this->auto_processing);
    $success = false;

    $this->log_message('Payment success: ' . $success);
    $this->log_message('Payment status: ' . $payment_status);

    $order_id = $this->order_id;
    $order = new WC_Order($order_id);
    if (!$order) {
      $this->log_message('Order not found for order id: ' . $order_id);
      return;
    }
    $order->add_meta_data('paymentStatus', strtolower($payment_status), true);

    // ignore 'CREATED', 'PROCESSING', and 'FAILED' payment status
    $ignore_array = array('CREATED','PROCESSING','FAILED');

    if ('PAID' === $payment_status) { $success = true; }
    if ('POSTED' === $payment_status) {
        if('yes' === $this->auto_processing) {
            $this->log_message('Payment '.$payment_status.' status arrived and automatic_processing option is selected. Marking payment as success');
            $success = true;
        }
        else {
            // ignore 'POSTED' payment status if the auto_processing flag is not set
            return;
        }
    }
    elseif (in_array($payment_status, $ignore_array)) {
        // just return for payment statuses we should ignore
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
        $order->payment_complete($this->transaction_id);

        if ('yes' == $this->auto_complete) {
            $order->update_status('completed', 'Order auto-completed.');
            $this->log_message('Order auto-completed: ' . $order_id);
        }
    } else {
      $order->update_status('failed', sprintf(__('Payment failed: %s', 'woocommerce-paystand'), $payment_status));
    }
  }

  /**
   * Calls Paystand's API to retrieve an access token to execute authenticated API calls.
   */
  function get_ps_api_token() {
    $paystand_api_url = $this->get_paystand_api_url();
    $endpoint = $paystand_api_url . 'oauth/token';
    $grant_type = "client_credentials";
    $request = array(
      'grant_type' => $grant_type,
      'client_id' => $this->client_id,
      'client_secret' => $this->client_secret,
      'scope' => 'auth'
    );

    $access_token = null;
    // calling Rest Access Token
    try{
        $response = \Httpful\Request::post($endpoint)->sendsJson()->body(json_encode($request))->send();
    } catch (Exception $e) {
        $this->log_message('get_ps_api_token Access_Tokens exception: ' . print_r($e, true));
    }

    $this->log_message('get_ps_api_token Access_Tokens response: ' . print_r($response->raw_body, true));

    if($response->code!==200){ // Unauthorized or another error
        $this->log_message('get_ps_api_token Access_Tokens error: '.print_r($response->body, true));
        return null;
    }
    return $response->body->access_token;
  }

   /**
   * Calls Paystand's API to retrieve fee for current amount
   */
  function get_split_fees($amount){
    $paystand_api_url = $this->get_paystand_api_url();
    $endpoint = $paystand_api_url . 'feeSplits/splitFees/public';
    $header = array(
      'Content-Type' => 'application/json',
      'Accept' => 'application/json',
      'x-publishable-key' => $this->publishable_key
    );

    $request_body = sprintf('{ "subtotal": "%s" }', $amount);
    $response = new stdClass();

    try{
      $this->log_message("get_split_fees call");
      $response =  $this->do_http_post($endpoint, $header, json_decode($request_body));
      $this->cardPayment_fee = $response->body->cardPayments->payerTotalFees;
      $this->bankPayment_fee = $response->body->bankPayments->payerTotalFees;
      $this->buildFeeSplit($response->body);
    } catch (Exception $e) {
        $this->log_message('get_split_fees exception: ' . print_r($e, true));
    }

    if(isset($response->code) && isset($response->body) && $response->code!==200){ // Unauthorized or another error
        $this->log_message('get_split_fees Access_Tokens error: '.print_r($response->body, true));
    }

    $this->log_message('get_split_fees Fees card: ' . $this->cardPayment_fee);
    $this->log_message('get_split_fees Fees bank: ' . $this->bankPayment_fee);
  }

  /**
  * Builds FeeSplit objects from feeSplit response
  */
  function buildFeeSplit($splitFees){
    try{
      $this->feeSplitCard = array(
        'subtotal' => $splitFees->cardPayments->subtotal,
        'feeSplitType' => $splitFees->cardPayments->feeSplitType,
        'customRate' => $splitFees->cardPayments->customRate,
        'customFlat' => $splitFees->cardPayments->customFlat
      );
      $this->feeSplitBank = array(
        'subtotal' => $splitFees->achBankPayments->subtotal,
        'feeSplitType' => $splitFees->achBankPayments->feeSplitType,
        'customRate' => $splitFees->achBankPayments->customRate,
        'customFlat' => $splitFees->achBankPayments->customFlat
      );
      $this->total_amount_card = $splitFees->cardPayments->payerTotal;
      $this->total_amount_bank = $splitFees->achBankPayments->payerTotal;
    } catch (Exception $e) {
      $this->log_message('get_split_fees exception: ' . print_r($e, true));
    }
  }

  /**
   * This function is required by WooCommerce to be here even if it is empty
   * otherwise user gets a "unable to add a payment method" message when trying
   * to add a payment method
   */
  function add_payment_method() {
     $this->log_message("Added new payment Method");
   }
  
  /**
   * Get configurations
   */
  function get_paystand_checkout_config() {
    $env = $this->get_checkout_env();
    $config = json_decode(file_get_contents(plugin_dir_path( __FILE__ ) . 'config.json'));
    
    return $config->psCheckoutUrls->$env;
  }

  function get_checkout_env() {
    $paystandEnv = \getenv('PAYSTAND_ENV');
    $testmode = $this->get_option('testmode');
    $env = ("yes" == $testmode) ? "sandbox" : ($paystandEnv ?: "live");
    return $env;
  }
}

