<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * PayStand Payment Gateway
 *
 * Provides a PayStand Payment Gateway.
 *
 * @class      WC_Gateway_PayStand
 * @extends    WC_Payment_Gateway
 * @version    1.0.0
 * @package    WooCommerce/Classes/Payment
 * @author     PayStand
 */
class WC_Gateway_PayStand extends WC_Payment_Gateway {

  var $notify_url;
  var $org_id;
  var $api_key;

  /**
   * Constructor for the gateway.
   *
   * @access public
   * @return void
   */
  public function __construct() {

    $this->id = 'paystand';
    $this->icon = apply_filters('woocommerce_paystand_icon', plugins_url('images/paystand_logo_small.png' , __FILE__));
    $this->has_fields = false;
    $this->title = __('PayStand', 'woocommerce-paystand');
    $this->method_title = __('PayStand', 'woocommerce-paystand');
    $this->description = 'Process payments with the PayStand payment gateway.';
    $this->method_description = 'Process payments with the PayStand payment gateway.';

    $this->order_button_text = __('PayStand Checkout', 'woocommerce-paystand');
    $this->liveurl = 'https://app.paystand.com';
    $this->testurl = 'https://sandbox.paystand.co';
    $this->notify_url = WC()->api_request_url('WC_Gateway_PayStand');

    // Init settings
    $this->init_form_fields();
    $this->init_settings();

    // User defined
    $this->org_id = $this->get_option('org_id');
    $this->api_key = $this->get_option('api_key');
    $this->testmode = $this->get_option('testmode');
    $this->debug = $this->get_option('debug');

    // Logs
    if ('yes' == $this->debug) {
      $this->log = new WC_Logger();
    }

    // Actions
    add_action('woocommerce_update_options_payment_gateways_paystand', array($this, 'process_admin_options'));
    add_action('woocommerce_receipt_paystand', array($this, 'receipt_page'));
    add_action('woocommerce_api_wc_gateway_paystand', array($this, 'paystand_callback'));
    add_action('valid-paystand-callback', array($this, 'valid_paystand_callback'));
    add_action('woocommerce_thankyou_paystand', array($this, 'thankyou_page'));

    if (!$this->is_valid_for_use()) {
      $this->enabled = false;
    }
  }


  /**
   * Initialize Gateway Settings Form Fields
   *
   * @access public
   * @return void
   */
  function init_form_fields() {

    $this->form_fields = array(
        'enabled' => array(
            'title' => __('Enable/Disable', 'woocommerce-paystand'),
            'type' => 'checkbox',
            'label' => __('Enable PayStand', 'woocommerce-paystand'),
            'default' => 'yes'
        ),
        'org_id' => array(
            'title' => __('PayStand Org ID', 'woocommerce-paystand'),
            'type' => 'text',
            'description' => __('Your PayStand organization id.', 'woocommerce-paystand'),
            'default' => '',
            'desc_tip' => true,
        ),
        'api_key' => array(
            'title' => __('PayStand API Key', 'woocommerce-paystand'),
            'type' => 'text',
            'description' => __('Your PayStand public api key used for checkout.', 'woocommerce-paystand'),
            'default' => __('PayStand', 'woocommerce-paystand'),
            'desc_tip' => true,
        ),
        'webhook' => array(
            'title' => __('Webhook', 'woocommerce-paystand'),
            'type' => 'title',
            'description' => 'Set your webhook url to ' . $this->notify_url . ' in your <a href="https://www.paystand.com/login" target="_blank">PayStand dashboard</a> under Settings > Checkout Features',
        ),
        'testing' => array(
            'title' => __('Gateway Testing', 'woocommerce-paystand'),
            'type' => 'title',
            'description' => '',
        ),
        'testmode' => array(
            'title' => __('PayStand Sandbox', 'woocommerce-paystand'),
            'type' => 'checkbox',
            'label' => __('Use PayStand Sandbox Server', 'woocommerce-paystand'),
            'default' => 'no',
            'description' => sprintf(__('The PayStand sandbox server can be used to test payments. Contact us for a developer account <a href="%s">here</a>.', 'woocommerce-paystand'), 'https://www.paystand.com/'),
        ),
        'debug' => array(
            'title' => __('Debug Log', 'woocommerce-paystand'),
            'type' => 'checkbox',
            'label' => __('Enable logging', 'woocommerce-paystand'),
            'default' => 'no',
            'description' => sprintf(__('Log PayStand events, such as requests, inside <code>woocommerce/logs/paystand-%s.txt</code>', 'woocommerce-paystand'), sanitize_file_name(wp_hash('paystand'))),
        )
    );
  }


  /**
   * Process the payment and return the result
   *
   * @access public
   * @param int $order_id
   * @return array
   */
  function process_payment($order_id) {

    if ('yes' == $this->debug) {
      $this->log->add('paystand', 'process_payment order_id: ' . $order_id);
    }

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
  function is_valid_for_use() {
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
  public function admin_options() {

    ?>
    <h3><?php _e('PayStand', 'woocommerce-paystand'); ?></h3>
    <p><?php _e('PayStand - Modern commerce with multiple payment methods and no transaction fees.', 'woocommerce-paystand'); ?></p>

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
  public function paystand_item_name($item_name) {
    if (strlen($item_name) > 127) {
      $item_name = substr($item_name, 0, 124) . '...';
    }
    return html_entity_decode($item_name, ENT_NOQUOTES, 'UTF-8');
  }

  /**
   * Get the server url
   */
  public function get_paystand_url() {
    if ('yes' == $this->testmode) {
      return $this->testurl;
    }
    return $this->liveurl;
  }


  /**
   * Output for the thank you page.
   */
  public function thankyou_page($order_id) {
    if ('yes' == $this->debug) {
      $this->log->add('paystand', 'thankyou_page order_id: ' . $order_id);
    }
  }


  /**
   * Output for the order received page.
   *
   * @access public
   * @return void
   */
  function receipt_page($order_id) {
    if ('yes' == $this->debug) {
      $this->log->add('paystand', 'receipt_page order_id: ' . $order_id);
    }
    echo '<p>' . __('Thank you!  Your order has been received.', 'woocommerce-paystand') . '</p>';

    $order = new WC_Order($order_id);
    $paystand_url = $this->get_paystand_url();

    if ('yes' == $this->debug) {
      $this->log->add('paystand', 'Generating payment form for order ' . $order->get_order_number() . '. Notify URL: ' . $this->notify_url);
    }

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

    // Convert to pennies
    $amount = $order->order_total * 100;
    $shipping_handling = $order->get_total_shipping() * 100;
    $tax = $order->get_total_tax() * 100;
    $final_item_amount = $amount - $shipping_handling - $tax;

    $billing_full_name = trim($order->billing_first_name . ' ' . $order->billing_last_name);
    $billing_email_address = $order->billing_email;
    $billing_street = trim($order->billing_address_1 . ' ' . $order->billing_address_2);
    $billing_city = $order->billing_city;
    $billing_postalcode = $order->billing_postcode;
    $billing_state = $order->billing_state;
    $billing_country = $order->billing_country;

    $markup = <<<EOF
<div id="paystand_element_id"></div>
<script type="text/javascript">

  var PayStand = PayStand || {};
  PayStand.checkouts = PayStand.checkouts || [];
  PayStand.load = PayStand.load || function(){};

  PayStand.checkoutUpdated = function() {
    console.log('checkoutUpdated called');
  }

  PayStand.checkoutComplete = function() {
console.log('checkoutComplete called! Setting locatino to: ' + "{$return_url}");
    window.location = "{$return_url}"
  }

  var autoCheckout = {
    api_key: "{$this->api_key}",
    org_id: "{$this->org_id}",
    element_ids: ["paystand_element_id"],
    data_source: "org_defined",
    checkout_type: "button",
    currency: "{$currency}",
    amount: "{$amount}",
    shipping_handling: "{$shipping_handling}",
    tax: "{$tax}",
    items: [
      {
        title: "{$final_item_name}",
        quantity: "1",
        item_price: "{$final_item_amount}"
      }
    ],
    billing: {
      full_name: "{$billing_full_name}",
      email_address: "{$billing_email_address}",
      street: "{$billing_street}",
      city: "{$billing_city}",
      postalcode: "{$billing_postalcode}",
      state: "{$billing_state}",
      country: "{$billing_country}",
      shipping_same: true
    },
    meta: {
      order_id: "{$order->id}",
      order_token: "{$order->order_key}"
    }
  }

  var buttonCheckout = {
    api_key: "{$this->api_key}",
    org_id: "{$this->org_id}",
    element_ids: ["paystand_element_id"],
    data_source: "org_defined",
    checkout_type: "button",
    button_options: {
      button_name: 'Pay with PayStand',
      input: false,
      variants: false
    },
    currency: "{$currency}",
    amount: "{$amount}",
    shipping_handling: "{$shipping_handling}",
    tax: "{$tax}",
    items: [
      {
        title: "{$final_item_name}",
        quantity: "1",
        item_price: "{$final_item_amount}"
      }
    ],
    billing: {
      full_name: "{$billing_full_name}",
      email_address: "{$billing_email_address}",
      street: "{$billing_street}",
      city: "{$billing_city}",
      postalcode: "{$billing_postalcode}",
      state: "{$billing_state}",
      country: "{$billing_country}",
      shipping_same: true
    },
    meta: {
      order_id: "{$order->id}",
      order_token: "{$order->order_key}"
    }
  }

  PayStand.checkouts.push(buttonCheckout);

  PayStand.onLoad = function() {
    PayStand.execute(autoCheckout);
  };

  PayStand.script = document.createElement('script');
  PayStand.script.type = 'text/javascript';
  PayStand.script.async = true;
  PayStand.script.src = '{$paystand_url}/js/checkout.js';
  var s = document.getElementsByTagName('script')[0];
  s.parentNode.insertBefore(PayStand.script, s);
</script>
EOF;

    echo $markup;
  }


  function check_callback_data($psn) {
    if (empty($psn) || !is_array($psn)) {
      $this->log->add('paystand', 'check_callback_data psn is empty');
      return false;
    }

    $paystand_url = $this->get_paystand_url();
    $endpoint = $paystand_url . '/api/v2/orders';

    $request = array(
        'action' => 'verify_psn',
        'api_key' => $this->api_key,
        'order_id' => $psn['txn_id'],
        'psn' => $psn
    );

    if ('yes' == $this->debug) {
      $this->log->add('paystand', 'check_callback_data verify_psn endpoint: ' . $endpoint);
      $this->log->add('paystand', 'check_callback_data verify_psn request: ' . print_r($request, true));
    }

    $context = stream_context_create(array(
        'http' => array(
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => json_encode($request)
        )
    ));

    $response = file_get_contents($endpoint, false, $context);
    if ($response === false) {
      $this->log->add('paystand', 'check_callback_data verify_psn returned false');
      return false;
    }

    $response_data = json_decode($response, true);
    if ('yes' == $this->debug) {
      $this->log->add('paystand', 'check_callback_data verify_psn response: ' . $response_data);
    }

    if (strpos($response_data['data'],'success') !== false) {
      // continue
    } else {
      $this->log->add('paystand', 'check_callback_data verify_psn response was not success');
      return false;
    }

    $defined = array(
        'txn_id', 'org_id', 'consumer_id', 'pre_fee_total',
        'fee_merchant_owes', 'rate_merchant_owes',
        'fee_consumer_owes', 'rate_consumer_owes', 'total_amount',
        'payment_status', 'success'
    );
    $numerics = array(
        'pre_fee_total', 'fee_merchant_owes', 'rate_merchant_owes',
        'fee_consumer_owes', 'rate_consumer_owes', 'total_amount',
        'txn_id', 'org_id', 'consumer_id'
    );
    $status =  array(
        'failed','voided','pending','achpending','responded','paid',
        'shipped','downloaded','donated','refunded','chargeback'
    );

    foreach ($defined as $def) {
      if (!isset($psn[$def])) {
        $this->log->add('paystand', 'PSN validation error: ' . $def . ' is not defined or is empty');
        return false;
      }
    }

    foreach ($numerics as $numeric) {
      if (!is_numeric($psn[$numeric])) {
        $this->log->add('paystand', 'PSN validation error: ' . $numeric . ' is not numeric');
        return false;
      }
    }

    if (!in_array($psn['payment_status'], $status)) {
      $this->log->add('paystand', 'PSN validation error: invalid payment status (' . $psn["payment_status"] . ')');
      return false;
    }

    $order_id = false;
    if (!empty($psn['order_id'])) {
      $order_id = $psn['order_id'];
    }

    if ('yes' == $this->debug) {
      $this->log->add('paystand', 'check_callback_data order_id: ' . $order_id);
    }

    $order = false;
    if ($order_id) {
      $order = new WC_Order($order_id);
    }
    if (!$order) {
      $this->log->add('paystand', 'Order not found for order id: ' . $order_id);
      return false;
    }

    if ('yes' == $this->debug) {
      $this->log->add('paystand', 'check_callback_data order: ' . print_r($order, true));
    }

    $order_token = false;
    if (!empty($psn['order_token'])) {
      $order_token = $psn['order_token'];
    }
    if (!$order->key_is_valid($order_token)) {
      $this->log->add('paystand', 'PSN validation error: Order key not valid: ' . $order_token);
      return false;
    }

    $pre_fee_total = false;
    if (!empty($psn['pre_fee_total'])) {
      $pre_fee_total = $psn['pre_fee_total'];
    }
    if ($pre_fee_total != $order->order_total) {
      $this->log->add('paystand', 'PSN validation error: psn pre_fee_total: ' . $psn['pre_fee_total'] . ' not equal to order_total: ' . $order->order_total);
      return false;
    }

    return true;
  }


  /**
   * Handle callback from PayStand.
   *
   * @access public
   * @return void
   */
  function paystand_callback() {
    if ('yes' == $this->debug) {
      $this->log->add('paystand', 'paystand_callback');
    }

    $psn = $_POST;
    if (empty($psn)) {
      $psn = json_decode(file_get_contents("php://input"), true);
    }
    if ('yes' == $this->debug) {
      $this->log->add('paystand', 'psn: ' . print_r($psn, true));
    }

    if ($this->check_callback_data($psn)) {
      header('HTTP/1.1 200 OK');
      do_action("valid-paystand-callback", $psn);
    } else {
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
  function valid_paystand_callback($data) {
    if ('yes' == $this->debug) {
      $this->log->add('paystand', 'valid_paystand_callback' . print_r($data, true));
    }

    $success = false;
    if (!empty($data['success'])) {
      $success = $data['success'];
    }
    $payment_status = false;
    if (!empty($data['payment_status'])) {
      $payment_status = $data['payment_status'];
    }
    if ('yes' == $this->debug) {
      $this->log->add('paystand', 'Payment success: ' . $success);
      $this->log->add('paystand', 'Payment status: ' . $payment_status);
    }

    $order_id = false;
    if (!empty($data['order_id'])) {
      $order_id = $data['order_id'];
    }
    $order = false;
    if ($order_id) {
      $order = new WC_Order($order_id);
    }
    if (!$order) {
      $this->log->add('paystand', 'Order not found for order id: ' . $order_id);
      return;
    }

    if ($success) {
      $order->add_order_note(__('Payment completed', 'woocommerce-paystand'));
      $order->payment_complete();
    } else {
      $order->update_status('on-hold', sprintf(__('Payment pending: %s', 'woocommerce-paystand'), $payment_status));
    }
  }
}

