<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

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
    $this->method_title = __('PayStand', 'wc-paystand');
    $this->method_description = 'Process payments with the PayStand payment gateway.';

    $this->order_button_text = __('PayStand Checkout', 'wc-paystand');
    $this->liveurl = 'https://app.paystand.com';
    $this->testurl = 'https://sandbox.paystand.co';
    $this->notify_url = WC()->api_request_url('WC_Gateway_PayStand');

    // Init settings
    $this->init_form_fields();
    $this->init_settings();

    // User defined
    $this->org_id = $this->get_option('org_id');
    $this->api_key = $this->get_option('api_key');

    $this->title = $this->get_option('title');
    $this->description = $this->get_option('description');
    $this->email = $this->get_option('email');
    $this->testmode = $this->get_option('testmode');
    $this->send_shipping = $this->get_option('send_shipping');
    $this->address_override = $this->get_option('address_override');
    $this->debug = $this->get_option('debug');
    $this->form_submission_method = $this->get_option('form_submission_method') == 'yes' ? true : false;
    $this->page_style = $this->get_option('page_style');
    $this->paymentaction = $this->get_option('paymentaction', 'sale');

    // Logs
    if ('yes' == $this->debug) {
      $this->log = new WC_Logger();
    }

    // Actions
    add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    add_action('woocommerce_receipt_paystand', array($this, 'receipt_page'));

    add_action('valid-paystand-request', array($this, 'successful_request'));
    add_action('woocommerce_thankyou_paystand', array($this, 'pdt_return_handler'));

    // Payment listener/API hook
    add_action('woocommerce_api_wc_gateway_paystand', array($this, 'check_response'));

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
        'title' => array(
            'title' => __('Title', 'wc-paystand'),
            'type' => 'text',
            'description' => __('This controls the title which the user sees during checkout.', 'wc-paystand'),
            'default' => __('PayStand', 'wc-paystand'),
            'desc_tip' => true,
        ),
        'description' => array(
            'title' => __('Description', 'wc-paystand'),
            'type' => 'textarea',
            'description' => __('This controls the description which the user sees during checkout.', 'wc-paystand'),
            'default' => __('Pay via PayStand: You can pay with your credit card, eCheck, or other means.', 'wc-paystand')
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
        'email' => array(
            'title' => __( 'Email', 'wc-paystand'),
            'type' => 'email',
            'description' => __( 'Please enter your email address.  This is needed in order to take payment.', 'wc-paystand'),
            'default' => '',
            'desc_tip' => true,
            'placeholder' => 'you@example.com'
        ),
        'paymentaction' => array(
            'title' => __('Payment Action', 'wc-paystand'),
            'type' => 'select',
            'description' => __('Choose whether you wish to capture funds immediately or authorize payment only.', 'wc-paystand'),
            'default' => 'sale',
            'desc_tip' => true,
            'options' => array(
                'sale' => __('Capture', 'wc-paystand'),
                'authorization' => __('Authorize', 'wc-paystand')
            )
        ),
        'form_submission_method' => array(
            'title' => __('Submission method', 'wc-paystand'),
            'type' => 'checkbox',
            'label' => __('Use form submission method.', 'wc-paystand'),
            'description' => __('Enable this to post order data to PayStand via a form instead of using a redirect/querystring.', 'wc-paystand'),
            'default' => 'no'
        ),
        'page_style' => array(
            'title' => __('Page Style', 'wc-paystand'),
            'type' => 'text',
            'description' => __('Optionally enter the name of the page style you wish to use. These are defined within your PayStand account.', 'wc-paystand'),
            'default' => '',
            'desc_tip' => true,
            'placeholder' => __('Optional', 'wc-paystand')
        ),
        'shipping' => array(
            'title' => __('Shipping options', 'wc-paystand'),
            'type' => 'title',
            'description' => '',
        ),
        'send_shipping' => array(
            'title' => __('Shipping details', 'wc-paystand'),
            'type' => 'checkbox',
            'label' => __('Send shipping details to PayStand instead of billing.', 'wc-paystand'),
            'description' => __('PayStand allows us to send 1 address. If you are using PayStand for shipping labels you may prefer to send the shipping address rather than billing.', 'wc-paystand'),
            'default' => 'no'
        ),
        'address_override' => array(
            'title' => __('Address override', 'wc-paystand'),
            'type' => 'checkbox',
            'label' => __('Enable "address_override" to prevent address information from being changed.', 'wc-paystand'),
            'description' => __('PayStand verifies addresses therefore this setting can cause errors (we recommend keeping it disabled).', 'wc-paystand'),
            'default' => 'no'
        ),
        'testing' => array(
            'title' => __('Gateway Testing', 'wc-paystand'),
            'type' => 'title',
            'description' => '',
        ),
        'testmode' => array(
            'title' => __('PayStand sandbox', 'wc-paystand'),
            'type' => 'checkbox',
            'label' => __('Enable PayStand sandbox', 'wc-paystand'),
            'default' => 'no',
            'description' => sprintf(__('PayStand sandbox can be used to test payments. Contact us for a developer account <a href="%s">here</a>.', 'wc-paystand'), 'https://www.paystand.com/'),
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

    global $woocommerce;
    $order = new WC_Order($order_id);

    $order->update_status('on-hold', __('Payment pending', 'wc-paystand'));

    // XXX do we want this here or after checkout?
    $order->reduce_order_stock();
    $woocommerce->cart->empty_cart();

    // XXX after checkout
    // XXX if payment success
    // XXX reduces stock automatically and sets status
    //$order->payment_complete();
    // XXX else payment failed
    //$woocommerce->add_error(__('Payment error:', 'woothemes') . $error_message);
    //return;

    if (!$this->form_submission_method) {
      $paystand_args = $this->get_paystand_args($order);
      $paystand_args = http_build_query($paystand_args, '', '&');

      if ('yes' == $this->testmode) {
        $paystand_adr = $this->testurl . '?test=1&';
      } else {
        $paystand_adr = $this->liveurl . '?';
      }

      return array(
          'result' => 'success',
          'redirect' => $paystand_adr . $paystand_args
      );

    } else {

      return array(
          'result' => 'success',
          /*'redirect' => $this->get_return_url( $order )*/
          'redirect' => $order->get_checkout_payment_url(true)
      );
    }
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
   * Options for bits like 'title' and availability on a country-by-country
   * basis.
   *
   */
  public function admin_options() {

    ?>
    <h3><?php _e('PayStand', 'wc-paystand'); ?></h3>
    <p><?php _e('PayStand provides modern payment processing solutions.', 'wc-paystand'); ?></p>

    <?php if ($this->is_valid_for_use()) : ?>

      <table class="form-table">
      <?php
        // Generate the HTML For the settings form.
        $this->generate_settings_html();
      ?>
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
   * Get PayStand Args for passing to PP
   *
   * @access public
   * @param mixed $order
   * @return array
   */
  function get_paystand_args($order) {

    $order_id = $order->id;

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

    // PayStand Args
    $paystand_args = array_merge(
        array(
            'cmd' => '_cart',
            'business' => $this->email,
            'no_note' => 1,
            'currency_code' => get_woocommerce_currency(),
            'charset' => 'UTF-8',
            'rm' => is_ssl() ? 2 : 1,
            'upload' => 1,
            'return' => esc_url(add_query_arg('utm_nooverride', '1', $this->get_return_url($order))),
            'cancel_return' => esc_url($order->get_cancel_order_url()),
            'page_style' => $this->page_style,
            'paymentaction' => $this->paymentaction,
            'bn' => 'WooThemes_Cart',

            // Order key + ID
            'invoice' => ltrim($order->get_order_number(), '#'),
            'custom' => serialize(array($order_id, $order->order_key)),

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

    // Shipping
    if ('yes' == $this->send_shipping) {
      $paystand_args['address_override'] = ($this->address_override == 'yes') ? 1 : 0;

      $paystand_args['no_shipping'] = 0;

      // If we are sending shipping, send shipping address instead of billing
      $paystand_args['first_name'] = $order->shipping_first_name;
      $paystand_args['last_name'] = $order->shipping_last_name;
      $paystand_args['company'] = $order->shipping_company;
      $paystand_args['address1'] = $order->shipping_address_1;
      $paystand_args['address2'] = $order->shipping_address_2;
      $paystand_args['city'] = $order->shipping_city;
      $paystand_args['state'] = $this->get_paystand_state($order->shipping_country, $order->shipping_state);
      $paystand_args['country'] = $order->shipping_country;
      $paystand_args['zip'] = $order->shipping_postcode;
    } else {
      $paystand_args['no_shipping'] = 1;
    }

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
      // No longer using shipping_1 because
      //    a) paystand ignore it if *any* shipping rules are within paystand
      //    b) paystand ignore anything over 5 digits, so 999.99 is the max
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

    $paystand_args = apply_filters('woocommerce_paystand_args', $paystand_args);

    return $paystand_args;
  }


  /**
   * Generate the paystand button link
   *
   * @access public
   * @param mixed $order_id
   * @return string
   */
  function generate_paystand_form($order_id) {

    $order = new WC_Order($order_id);

    if ('yes' == $this->testmode) {
      $paystand_adr = $this->testurl . '?test=1&';
    } else {
      $paystand_adr = $this->liveurl . '?';
    }

    $paystand_args = $this->get_paystand_args($order);

    $paystand_args_array = array();

    foreach ($paystand_args as $key => $value) {
      $paystand_args_array[] = '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" />';
    }

    wc_enqueue_js('
        $.blockUI({
            message: "' . esc_js(__('Thank you for your order. We are now going to PayStand to make payment.', 'wc-paystand')) . '",
            baseZ: 99999,
            overlayCSS: {
                background: "#fff",
                opacity: 0.6
            },
            css: {
                padding: "20px",
                zindex: "9999999",
                textAlign: "center",
                color: "#555",
                border: "3px solid #aaa",
                backgroundColor: "#fff",
                cursor: "wait",
                lineHeight: "24px",
            }
        });
      jQuery("#submit_paystand_payment_form").click();
    ' );

    return '<form action="' . esc_url($paystand_adr) . '" method="post" id="paystand_payment_form" target="_top">
        ' . implode('', $paystand_args_array) . '
        <!-- Button Fallback -->
        <div class="payment_buttons">
          <input type="submit" class="button alt" id="submit_paystand_payment_form" value="' . __('Pay via PayStand', 'wc-paystand') . '" /> <a class="button cancel" href="' . esc_url($order->get_cancel_order_url()) . '">' . __('Cancel order &amp; restore cart', 'wc-paystand') . '</a>
        </div>
        <script type="text/javascript">
          jQuery(".payment_buttons").hide();
        </script>
      </form>';

  }


  /**
   * Output for the order received page.
   *
   * @access public
   * @return void
   */
  function receipt_page($order) {
    echo '<p>' . __('Thank you!  Your order is now pending payment.', 'wc-paystand') . '</p>';

    echo $this->generate_paystand_form($order);
  }


  /**
   * Check PayStand validity
   **/
  function check_request_is_valid($response) {

    if ('yes' == $this->testmode) {
      $paystand_adr = $this->testurl;
    } else {
      $paystand_adr = $this->liveurl;
    }

    if ('yes' == $this->debug) {
      $this->log->add('paystand', 'Checking response is valid via ' . $paystand_adr . '...' );
    }

    // Get recieved values from post data
    $validate = array('cmd' => '_notify-validate');
    $validate += stripslashes_deep($response);

    // Send back post vars to paystand
    $params = array(
        'body' => $validate,
        'sslverify' => false,
        'timeout' => 60,
        'httpversion' => '1.1',
        'compress' => false,
        'decompress' => false,
        'user-agent' => 'WooCommerce/' . WC()->version
    );

    if ('yes' == $this->debug) {
      $this->log->add('paystand', 'Request: ' . print_r($params, true));
    }

    // Post back to get a response
    $response = wp_remote_post($paystand_adr, $params);

    if ('yes' == $this->debug) {
      $this->log->add('paystand', 'Response: ' . print_r($response, true));
    }

    // check to see if the request was valid
    if (!is_wp_error($response) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 && (strcmp($response['body'], "VERIFIED") == 0)) {
      if ('yes' == $this->debug) {
        $this->log->add('paystand', 'Received valid response from PayStand');
      }

      return true;
    }

    if ('yes' == $this->debug) {
      $this->log->add('paystand', 'Received invalid response from PayStand');
      if (is_wp_error($response)) {
        $this->log->add('paystand', 'Error response: ' . $response->get_error_message());
      }
    }

    return false;
  }


  /**
   * Check for PayStand IPN Response
   *
   * @access public
   * @return void
   */
  function check_response() {

    @ob_clean();

    $response = !empty($_POST) ? $_POST : false;

    if ($response && $this->check_request_is_valid($response)) {
      header('HTTP/1.1 200 OK');
      do_action("valid-paystand-request", $response);

    } else {

      wp_die("PayStand Request Failure", "PayStand", array('response' => 200));
    }
  }


  /**
   * Successful Payment!
   *
   * @access public
   * @param array $posted
   * @return void
   */
  function successful_request($posted) {

    $posted = stripslashes_deep($posted);

    // Custom holds post ID
    if (!empty($posted['invoice']) && !empty($posted['custom'])) {
      $order = $this->get_paystand_order($posted['custom'], $posted['invoice']);

      if ('yes' == $this->debug) {
        $this->log->add('paystand', 'Found order #' . $order->id);
      }

      // Lowercase returned variables
      $posted['payment_status'] = strtolower($posted['payment_status']);
      $posted['txn_type'] = strtolower($posted['txn_type']);

      // Sandbox fix
      if (1 == $posted['test'] && 'pending' == $posted['payment_status']) {
        $posted['payment_status'] = 'completed';
      }

      if ('yes' == $this->debug) {
        $this->log->add('paystand', 'Payment status: ' . $posted['payment_status']);
      }

      // Check status and do actions
      switch ($posted['payment_status']) {
        case 'completed':
        case 'pending':
          // Check order not already completed
          if ($order->status == 'completed') {
            if ('yes' == $this->debug) {
              $this->log->add('paystand', 'Aborting, Order #' . $order->id . ' is already complete.');
            }
            exit;
          }

          // Check valid txn_type
          // XXX
          $accepted_types = array('cart', 'instant', 'express_checkout', 'web_accept', 'masspay', 'send_money');

          if (!in_array($posted['txn_type'], $accepted_types)) {
            if ('yes' == $this->debug) {
              $this->log->add('paystand', 'Aborting, Invalid type:' . $posted['txn_type']);
            }
            exit;
          }

          // Validate currency
          if ($order->get_order_currency() != $posted['mc_currency']) {
            if ('yes' == $this->debug) {
              $this->log->add('paystand', 'Payment error: Currencies do not match (sent "' . $order->get_order_currency() . '" | returned "' . $posted['mc_currency'] . '")');
            }

            // Put this order on-hold for manual checking
            $order->update_status('on-hold', sprintf(__('Validation error: PayStand currencies do not match (code %s).', 'wc-paystand'), $posted['mc_currency']));
            exit;
          }

          // Validate amount
          if ($order->get_total() != $posted['mc_gross']) {
            if ('yes' == $this->debug) {
              $this->log->add('paystand', 'Payment error: Amounts do not match (gross ' . $posted['mc_gross'] . ')');
            }

            // Put this order on-hold for manual checking
            $order->update_status('on-hold', sprintf(__('Validation error: PayStand amounts do not match (gross %s).', 'wc-paystand'), $posted['mc_gross']));
            exit;
          }

          // Store PP Details
          if (!empty($posted['payer_email'])) {
            update_post_meta($order->id, 'Payer PayStand address', wc_clean($posted['payer_email']));
          }
          if (!empty($posted['txn_id'])) {
            update_post_meta($order->id, 'Transaction ID', wc_clean($posted['txn_id']));
          }
          if (!empty($posted['first_name'])) {
            update_post_meta($order->id, 'Payer first name', wc_clean($posted['first_name']));
          }
          if (!empty($posted['last_name'])) {
            update_post_meta($order->id, 'Payer last name', wc_clean($posted['last_name']));
          }
          if (!empty($posted['payment_type'])) {
            update_post_meta($order->id, 'Payment type', wc_clean( $posted['payment_type']));
          }

          if ($posted['payment_status'] == 'completed') {
            $order->add_order_note(__('Payment completed', 'wc-paystand'));
            $order->payment_complete();
          } else {
            $order->update_status('on-hold', sprintf(__('Payment pending: %s', 'wc-paystand'), $posted['pending_reason']));
          }

          if ('yes' == $this->debug) {
            $this->log->add('paystand', 'Payment complete.');
          }

          break;
        case 'denied':
        case 'expired':
        case 'failed':
        case 'voided':
          // Order failed
          $order->update_status('failed', sprintf(__('Payment %s.', 'wc-paystand'), strtolower($posted['payment_status'])));
          break;
        case 'refunded':
          // Only handle full refunds, not partial
          if ($order->get_total() == ($posted['mc_gross'] * -1)) {
            // Mark order as refunded
            $order->update_status('refunded', sprintf(__('Payment %s.', 'wc-paystand'), strtolower($posted['payment_status'])));

            $mailer = WC()->mailer();

            $message = $mailer->wrap_message(
                __('Order refunded/reversed', 'wc-paystand'),
                sprintf(__('Order %s has been marked as refunded - PayStand reason code: %s', 'wc-paystand'), $order->get_order_number(), $posted['reason_code'])
            );

            $mailer->send(get_option('admin_email'), sprintf(__('Payment for order %s refunded/reversed', 'wc-paystand'), $order->get_order_number()), $message);
          }

          break;
        case 'reversed':
          // Mark order as refunded
          $order->update_status('on-hold', sprintf(__('Payment %s.', 'wc-paystand' ), strtolower($posted['payment_status'])));

          $mailer = WC()->mailer();

          $message = $mailer->wrap_message(
              __('Order reversed', 'wc-paystand'),
              sprintf(__('Order %s has been marked on-hold due to a reversal - PayStand reason code: %s', 'wc-paystand'), $order->get_order_number(), $posted['reason_code'])
          );

          $mailer->send(get_option('admin_email'), sprintf(__('Payment for order %s reversed', 'wc-paystand'), $order->get_order_number()), $message);

          break;
        case 'canceled_reversal':
          $mailer = WC()->mailer();

          $message = $mailer->wrap_message(
              __('Reversal Cancelled', 'wc-paystand'),
              sprintf(__('Order %s has had a reversal cancelled. Please check the status of payment and update the order status accordingly.', 'wc-paystand'), $order->get_order_number())
          );

          $mailer->send(get_option('admin_email'), sprintf(__('Reversal cancelled for order %s', 'wc-paystand'), $order->get_order_number()), $message);

          break;
        default :
          // No action
          break;
      }

      exit;
    }
  }


  /**
   * Return handler
   *
   * Alternative to IPN
   */
  public function pdt_return_handler() {
    $posted = stripslashes_deep($_REQUEST);

    if (!empty($posted['cm'])) {
      $order = $this->get_paystand_order($posted['cm']);

      if ('pending' != $order->status) {
        return false;
      }

      $posted['st'] = strtolower($posted['st']);

      switch ($posted['st']) {
        case 'completed' :
          // Validate transaction
          if ('yes' == $this->testmode) {
            $paystand_adr = $this->testurl;
          } else {
            $paystand_adr = $this->liveurl;
          }

          $pdt = array(
              'body' => array(
                  'cmd' => '_notify-synch',
                  'tx' => $posted['tx']
              ),
              'sslverify' => false,
              'timeout' => 60,
              'httpversion' => '1.1',
              'user-agent' => 'WooCommerce/' . WC_VERSION
          );

          // Post back to get a response
          $response = wp_remote_post($paystand_adr, $pdt);

          if (is_wp_error($response)) {
            return false;
          }

          if (!strpos($response['body'], "SUCCESS") === 0) {
            return false;
          }

          // Validate Amount
          if ($order->get_total() != $posted['amt']) {

            if ('yes' == $this->debug) {
              $this->log->add('paystand', 'Payment error: Amounts do not match (amt ' . $posted['amt'] . ')');
            }

            // Put this order on-hold for manual checking
            $order->update_status('on-hold', sprintf(__('Validation error: PayStand amounts do not match (amt %s).', 'wc-paystand'), $posted['amt']));
            return true;

          } else {

            // Store PP Details
            update_post_meta($order->id, 'Transaction ID', wc_clean($posted['tx']));

            $order->add_order_note(__('PDT payment completed', 'wc-paystand'));
            $order->payment_complete();
            return true;
          }

        break;
      }
    }

    return false;
  }


  /**
   * get_paystand_order function.
   *
   * @param  string $custom
   * @param  string $invoice
   * @return WC_Order object
   */
  private function get_paystand_order($custom, $invoice = '') {
    $custom = maybe_unserialize($custom);

    if (is_numeric($custom)) {
      $order_id = (int) $custom;
      $order_key = $invoice;
    } else if (is_string($custom)) {
      $order_id = (int) $custom;
      $order_key = $custom;
    } else {
      list($order_id, $order_key) = $custom;
    }

    $order = new WC_Order($order_id);

    if (!isset($order->id)) {
      $order_id = wc_get_order_id_by_order_key($order_key);
      $order = new WC_Order($order_id);
    }

    // Validate key
    if ($order->order_key !== $order_key) {
      if ('yes' == $this->debug) {
        $this->log->add('paystand', 'Error: Order Key does not match invoice.');
      }
      exit;
    }

    return $order;
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

