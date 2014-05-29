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
  // XXX
  var $stageurl;

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
    $this->title = __('PayStand', 'wc-paystand');
    $this->method_title = __('PayStand', 'wc-paystand');
    $this->description = 'Process payments with the PayStand payment gateway.';
    $this->method_description = 'Process payments with the PayStand payment gateway.';

    $this->order_button_text = __('PayStand Checkout', 'wc-paystand');
    $this->liveurl = 'https://app.paystand.com';
    $this->testurl = 'https://sandbox.paystand.co';
    // XXX
    $this->stageurl = 'https://staging.paystand.us';
    $this->notify_url = WC()->api_request_url('WC_Gateway_PayStand');

    // Init settings
    $this->init_form_fields();
    $this->init_settings();

    // User defined
    $this->org_id = $this->get_option('org_id');
    $this->api_key = $this->get_option('api_key');
    $this->testmode = $this->get_option('testmode');
    // XXX
    $this->stagemode = $this->get_option('stagemode');
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
    // XXX
    //add_action('woocommerce_thankyou_paystand', array($this, 'thankyou_page'));

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
            'title' => __('Enable/Disable', 'wc-paystand'),
            'type' => 'checkbox',
            'label' => __('Enable PayStand', 'wc-paystand'),
            'default' => 'yes'
        ),
        'org_id' => array(
            'title' => __('Org ID', 'wc-paystand'),
            'type' => 'text',
            'description' => __('Your PayStand organization id.', 'wc-paystand'),
            'default' => '',
            'desc_tip' => true,
        ),
        'api_key' => array(
            'title' => __('API Key', 'wc-paystand'),
            'type' => 'text',
            'description' => __('Your PayStand public api key used for checkout.', 'wc-paystand'),
            'default' => __('PayStand', 'wc-paystand'),
            'desc_tip' => true,
        ),
        'testing' => array(
            'title' => __('Gateway Testing', 'wc-paystand'),
            'type' => 'title',
            'description' => '',
        ),
        'stagemode' => array(
            'title' => __('XXX PayStand Staging', 'wc-paystand'),
            'type' => 'checkbox',
            'label' => __('Use PayStand Staging Server', 'wc-paystand'),
            'default' => 'no',
            'description' => sprintf(__('XXX The PayStand staging server can be used to test payments.', 'wc-paystand'), 'https://www.paystand.com/'),
        ),
        'testmode' => array(
            'title' => __('PayStand Sandbox', 'wc-paystand'),
            'type' => 'checkbox',
            'label' => __('Use PayStand Sandbox Server', 'wc-paystand'),
            'default' => 'no',
            'description' => sprintf(__('The PayStand sandbox server can be used to test payments. Contact us for a developer account <a href="%s">here</a>.', 'wc-paystand'), 'https://www.paystand.com/'),
        ),
        'debug' => array(
            'title' => __('Debug Log', 'wc-paystand'),
            'type' => 'checkbox',
            'label' => __('Enable logging', 'wc-paystand'),
            'default' => 'no',
            'description' => sprintf(__('Log PayStand events, such as requests, inside <code>woocommerce/logs/paystand-%s.txt</code>', 'wc-paystand'), sanitize_file_name(wp_hash('paystand'))),
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
    /* XXX Add other currencies
    if (!in_array(get_woocommerce_currency(), apply_filters('woocommerce_paystand_supported_currencies', array('AUD', 'BRL', 'CAD', 'MXN', 'NZD', 'HKD', 'SGD', 'USD', 'EUR', 'JPY', 'TRY', 'NOK', 'CZK', 'DKK', 'HUF', 'ILS', 'MYR', 'PHP', 'PLN', 'SEK', 'CHF', 'TWD', 'THB', 'GBP', 'RMB', 'RUB')))) {
      return false;
    }
    XXX */
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
    <h3><?php _e('PayStand', 'wc-paystand'); ?></h3>
    <p><?php _e('PayStand - Modern commerce with multiple payment methods and no transaction fees.', 'wc-paystand'); ?></p>

    <?php if ($this->is_valid_for_use()) : ?>

      <table class="form-table">
      <?php $this->generate_settings_html(); ?>
      </table><!--/.form-table-->

    <?php else : ?>

      <div class="inline error"><p><strong><?php _e('Gateway Disabled', 'wc-paystand'); ?></strong>: <?php _e('PayStand does not support your store currency.', 'wc-paystand'); ?></p></div>
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
   * Output for the thank you page.
   */
/* XXX
  public function thankyou_page($order_id) {
      $this->log->add('paystand', 'XXX thankyou_page: ' . $order_id);
echo "<script>console.log('XXX thankyou_page');</script>";
echo '<h1>thankyou_page</h1>';
  }
XXX */


  /**
   * Output for the order received page.
   *
   * @access public
   * @return void
   */
  function receipt_page($order_id) {
      $this->log->add('paystand', 'XXX receipt_page: ' . $order_id);
echo "<script>console.log('XXX receipt_page');</script>";
echo '<h1>receipt_page</h1>';
    echo '<p>' . __('Thank you!  Your order is now pending payment.', 'wc-paystand') . '</p>';

    $order = new WC_Order($order_id);

    // XXX
    if ('yes' == $this->stagemode) {
      $paystand_url = $this->stageurl;
    } else
    if ('yes' == $this->testmode) {
      $paystand_url = $this->testurl;
    } else {
      $paystand_url = $this->liveurl;
    }

    if ('yes' == $this->debug) {
      $this->log->add('paystand', 'Generating payment form for order ' . $order->get_order_number() . '. Notify URL: ' . $this->notify_url);
    }

    if (in_array($order->billing_country, array('US','CA'))) {
      $order->billing_phone = str_replace(array('(', '-', ' ', ')', '.'), '', $order->billing_phone);
      $phone_args = array(
          'night_phone_a' => substr($order->billing_phone, 0, 3),
          'night_phone_b' => substr($order->billing_phone, 3, 3),
          'night_phone_c' => substr($order->billing_phone, 6, 4),
          'day_phone_a' => substr($order->billing_phone, 0, 3),
          'day_phone_b' => substr($order->billing_phone, 3, 3),
          'day_phone_c' => substr($order->billing_phone, 6, 4)
      );
    } else {
      $phone_args = array(
        'night_phone_b' => $order->billing_phone,
        'day_phone_b' => $order->billing_phone
      );
    }

    $paystand_args = array_merge(
        array(
            'org_id' => $this->org_id,
            'api_key' => $this->api_key,
            'currency' => get_woocommerce_currency(),
            /*'return' => esc_url(add_query_arg('utm_nooverride', '1', $this->get_return_url($order))),*/
            /*'return' => esc_url($order->get_checkout_order_received_url()),*/
            'return' => esc_url($order->get_view_order_url()),
            'cancel_return' => esc_url($order->get_cancel_order_url()),
            'order_id' => $order->id,
            'notify_url' => $this->notify_url,
            // Billing Address info
            'first_name' => $order->billing_first_name,
            'last_name' => $order->billing_last_name,
            'company' => $order->billing_company,
            'address1' => $order->billing_address_1,
            'address2' => $order->billing_address_2,
            'city' => $order->billing_city,
            'state' => $this->get_paystand_state($order->billing_country, $order->billing_state),
            'zip' => $order->billing_postcode,
            'country' => $order->billing_country,
            'email' => $order->billing_email
        ),
        $phone_args
    );

    // If prices include tax or have order discounts, send the whole order as a single item
    if (get_option('woocommerce_prices_include_tax') == 'yes' || $order->get_order_discount() > 0 || (sizeof($order->get_items()) + sizeof($order->get_fees())) >= 9) {

      // Discount
      $paystand_args['discount_amount_cart'] = $order->get_order_discount();

      // Don't pass items. Pass 1 item for the order items overall
      $item_names = array();

      if (sizeof($order->get_items()) > 0) {
        foreach ($order->get_items() as $item) {
          if ($item['qty']) {
            $item_names[] = $item['name'] . ' x ' . $item['qty'];
          }
        }
      }

      $paystand_args['item_name_1'] = $this->paystand_item_name(sprintf(__('Order %s' , 'wc-paystand'), $order->get_order_number()) . " - " . implode(', ', $item_names));
      $paystand_args['quantity_1'] = 1;
      $paystand_args['amount_1'] = number_format($order->get_total() - $order->get_total_shipping() - $order->get_shipping_tax() + $order->get_order_discount(), 2, '.', '');

      // Shipping Cost
      // XXX
      // No longer using shipping_1
      if (($order->get_total_shipping() + $order->get_shipping_tax()) > 0) {
        $paystand_args['item_name_2'] = $this->paystand_item_name(__('Shipping via', 'wc-paystand') . ' ' . ucwords($order->get_shipping_method()));
        $paystand_args['quantity_2'] = '1';
        $paystand_args['amount_2'] = number_format($order->get_total_shipping() + $order->get_shipping_tax(), 2, '.', '');
      }

    } else {

      // Tax
      $paystand_args['tax_cart'] = $order->get_total_tax();

      // Cart Contents
      $item_loop = 0;
      if (sizeof($order->get_items()) > 0) {
        foreach ($order->get_items() as $item) {
          if ($item['qty']) {
            $item_loop++;
            $product = $order->get_product_from_item($item);
            $item_name = $item['name'];

            $item_meta = new WC_Order_Item_Meta($item['item_meta']);
            if ($meta = $item_meta->display(true, true)) {
              $item_name .= ' ( ' . $meta . ' )';
            }

            $paystand_args['item_name_' . $item_loop] = $this->paystand_item_name($item_name);
            $paystand_args['quantity_' . $item_loop] = $item['qty'];
            $paystand_args['amount_' . $item_loop] = $order->get_item_subtotal($item, false);

            if ($product->get_sku()) {
              $paystand_args['item_number_' . $item_loop] = $product->get_sku();
            }
          }
        }
      }

      // Discount
      if ($order->get_cart_discount() > 0) {
        $paystand_args['discount_amount_cart'] = round($order->get_cart_discount(), 2);
      }

      // Fees
      if (sizeof($order->get_fees()) > 0) {
        foreach ($order->get_fees() as $item) {
          $item_loop++;

          $paystand_args['item_name_' . $item_loop] = $this->paystand_item_name($item['name']);
          $paystand_args['quantity_' . $item_loop] = 1;
          $paystand_args['amount_' . $item_loop] = $item['line_total'];
        }
      }

      // XXX
      // Shipping Cost item - paystand only allows shipping per item, we want to send shipping for the order
      if ($order->get_total_shipping() > 0) {
        $item_loop++;
        $paystand_args['item_name_' . $item_loop] = $this->paystand_item_name(sprintf(__('Shipping via %s', 'wc-paystand'), $order->get_shipping_method()));
        $paystand_args['quantity_' . $item_loop] = '1';
        $paystand_args['amount_' . $item_loop] = number_format($order->get_total_shipping(), 2, '.', '');
      }
    }

    // Convert to pennies
    $amount = $order->order_total * 100;

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
console.log('XXX!!!');
    console.log('checkoutComplete called! Setting locatino to: ' + "{$paystand_args['return']}");
    window.location = "{$paystand_args['return']}"
  }

  var checkout = {
    api_key: "{$paystand_args['api_key']}",
    org_id: "{$paystand_args['org_id']}",
    element_ids: ["paystand_element_id"],
    data_source: "org_defined",
    checkout_type: "button",
    amount: "{$amount}",
    shipping_handling: "0",
    tax: "0",
    items: [
      {
        title: "PayStand Payment",
        quantity: "1",
        item_price: "{$amount}"
      }
    ],
    meta: {
      order_id: "{$order->id}",
      callback: "{$paystand_args['notify_url']}"
    }
  }

  PayStand.onLoad = function() {
    PayStand.execute(checkout);
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

echo '<h1>XXX</h1>';
echo 'return: ' . esc_url(add_query_arg('utm_nooverride', '1', $this->get_return_url($order)));
echo 'order_received: ' . esc_url($order->get_checkout_order_received_url());
echo 'view_order: ' . esc_url($order->get_view_order_url());
  }


  function check_callback_data($data) {
    if (empty($data) || !is_array($data)) {
      return false;
    }
    $defined = array(
        'txn_id', 'org_id', 'consumer_id', 'order_id', 'pre_fee_total',
        'fee_merchant_owes', 'rate_merchant_owes', 'fee_consumer_owes',
        'rate_consumer_owes', 'total_amount', 'tax', 'shipping_handling',
        'amount', 'payment_status', 'success', 'currency', 'created',
        'changed'
    );
    $numerics = array(
        'pre_fee_total', 'fee_merchant_owes', 'rate_merchant_owes',
        'fee_consumer_owes', 'rate_consumer_owes', 'total_amount',
        'tax', 'shipping_handling', 'amount', 'txn_id', 'org_id',
        'consumer_id', 'order_id', 'created', 'changed'
    );
    $status = array(
        'failed', 'voided', 'pending', 'achpending', 'responded', 'paid',
        'shipped', 'downloaded', 'donated', 'refunded', 'chargeback'
    );

    foreach ($defined as $def) {
      if (!isset($data[$def])) {
        $this->log->add('paystand', 'PSN validation error: ' . $def . ' is not defined or is empty');
        return false;
      }
    }

    foreach ($numerics as $numeric) {
      if (!is_numeric($data[$numeric])) {
        $this->log->add('paystand', 'PSN validation error: ' . $numeric . ' is not numeric');
        return false;
      }
    }

    if (!in_array($data['payment_status'], $status)) {
      $this->log->add('paystand', 'PSN validation error: invalid payment status (' . $data["payment_status"] . ')');
      // XXX
      //return false;
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
$this->log->add('paystand', 'paystand_callback');

    $data = !empty($_POST) ? $_POST : false;

    if ($this->check_callback_data($data)) {
      header('HTTP/1.1 200 OK');
      do_action("valid-paystand-callback", $data);
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
$this->log->add('paystand', 'valid_paystand_callback' . print_r($data, true));

    $success = false;
    if (!empty($data['success'])) {
      $success = $data['success'];
    }
    if ('yes' == $this->debug) {
      $this->log->add('paystand', 'Payment status: ' . $success);
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
      return;
    }

    if ($success) {
      $order->add_order_note(__('Payment completed', 'wc-paystand'));
      $order->payment_complete();
    } else {
      $order->update_status('on-hold', sprintf(__('Payment pending: %s', 'wc-paystand'), $data['payment_status']));
    }
  }


  /**
   * Get the state to send to paystand
   * @param  string $cc
   * @param  string $state
   * @return string
   */
  public function get_paystand_state($cc, $state) {
    if ('US' === $cc) {
      return $state;
    }

    $states = WC()->countries->get_states($cc);
    
    if (isset($states[$state])) {
      return $states[$state];
    }

    return $state;
  }
}

