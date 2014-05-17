<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * PayStand Standard Payment Gateway
 *
 * Provides a PayStand Standard Payment Gateway.
 *
 * @class     WC_Paystand
 * @extends    WC_Gateway_Paystand
 * @version    2.0.0
 * @package    WooCommerce/Classes/Payment
 * @author     WooThemes
 */
class WC_Gateway_Paystand extends WC_Payment_Gateway {

  var $notify_url;

  /**
   * Constructor for the gateway.
   *
   * @access public
   * @return void
   */
  public function __construct() {

    $this->id                = 'paystand';
    $this->icon              = apply_filters( 'woocommerce_paystand_icon', WC()->plugin_url() . '/assets/images/icons/paystand.png' );
    $this->has_fields        = false;
    $this->order_button_text = __( 'Proceed to PayStand', 'woocommerce' );
    $this->liveurl           = 'https://www.paystand.com/cgi-bin/webscr';
    $this->testurl           = 'https://www.sandbox.paystand.com/cgi-bin/webscr';
    $this->method_title      = __( 'PayStand', 'woocommerce' );
    $this->notify_url        = WC()->api_request_url( 'WC_Gateway_Paystand' );

    // Load the settings.
    $this->init_form_fields();
    $this->init_settings();

    // Define user set variables
    $this->title       = $this->get_option( 'title' );
    $this->description     = $this->get_option( 'description' );
    $this->email       = $this->get_option( 'email' );
    $this->receiver_email   = $this->get_option( 'receiver_email', $this->email );
    $this->testmode      = $this->get_option( 'testmode' );
    $this->send_shipping  = $this->get_option( 'send_shipping' );
    $this->address_override  = $this->get_option( 'address_override' );
    $this->debug      = $this->get_option( 'debug' );
    $this->form_submission_method = $this->get_option( 'form_submission_method' ) == 'yes' ? true : false;
    $this->page_style     = $this->get_option( 'page_style' );
    $this->invoice_prefix  = $this->get_option( 'invoice_prefix', 'WC-' );
    $this->paymentaction    = $this->get_option( 'paymentaction', 'sale' );
    $this->identity_token   = $this->get_option( 'identity_token', '' );

    // Logs
    if ( 'yes' == $this->debug ) {
      $this->log = new WC_Logger();
    }

    // Actions
    add_action( 'valid-paystand-standard-ipn-request', array( $this, 'successful_request' ) );
    add_action( 'woocommerce_receipt_paystand', array( $this, 'receipt_page' ) );
    add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
    add_action( 'woocommerce_thankyou_paystand', array( $this, 'pdt_return_handler' ) );

    // Payment listener/API hook
    add_action( 'woocommerce_api_wc_gateway_paystand', array( $this, 'check_ipn_response' ) );

    if ( ! $this->is_valid_for_use() ) {
      $this->enabled = false;
    }
  }

  /**
   * Check if this gateway is enabled and available in the user's country
   *
   * @access public
   * @return bool
   */
  function is_valid_for_use() {
    if ( ! in_array( get_woocommerce_currency(), apply_filters( 'woocommerce_paystand_supported_currencies', array( 'AUD', 'BRL', 'CAD', 'MXN', 'NZD', 'HKD', 'SGD', 'USD', 'EUR', 'JPY', 'TRY', 'NOK', 'CZK', 'DKK', 'HUF', 'ILS', 'MYR', 'PHP', 'PLN', 'SEK', 'CHF', 'TWD', 'THB', 'GBP', 'RMB', 'RUB' ) ) ) ) {
      return false;
    }

    return true;
  }

  /**
   * Admin Panel Options
   * - Options for bits like 'title' and availability on a country-by-country basis
   *
   * @since 1.0.0
   */
  public function admin_options() {

    ?>
    <h3><?php _e( 'PayStand standard', 'woocommerce' ); ?></h3>
    <p><?php _e( 'PayStand standard works by sending the user to PayStand to enter their payment information.', 'woocommerce' ); ?></p>

    <?php if ( $this->is_valid_for_use() ) : ?>

      <table class="form-table">
      <?php
        // Generate the HTML For the settings form.
        $this->generate_settings_html();
      ?>
      </table><!--/.form-table-->

    <?php else : ?>
      <div class="inline error"><p><strong><?php _e( 'Gateway Disabled', 'woocommerce' ); ?></strong>: <?php _e( 'PayStand does not support your store currency.', 'woocommerce' ); ?></p></div>
    <?php
      endif;
  }

  /**
   * Initialise Gateway Settings Form Fields
   *
   * @access public
   * @return void
   */
  function init_form_fields() {

    $this->form_fields = array(
      'enabled' => array(
        'title'   => __( 'Enable/Disable', 'woocommerce' ),
        'type'    => 'checkbox',
        'label'   => __( 'Enable PayStand standard', 'woocommerce' ),
        'default' => 'yes'
      ),
      'title' => array(
        'title'       => __( 'Title', 'woocommerce' ),
        'type'        => 'text',
        'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
        'default'     => __( 'PayStand', 'woocommerce' ),
        'desc_tip'    => true,
      ),
      'description' => array(
        'title'       => __( 'Description', 'woocommerce' ),
        'type'        => 'textarea',
        'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
        'default'     => __( 'Pay via PayStand; you can pay with your credit card if you don\'t have a PayStand account', 'woocommerce' )
      ),
      'email' => array(
        'title'       => __( 'PayStand Email', 'woocommerce' ),
        'type'        => 'email',
        'description' => __( 'Please enter your PayStand email address; this is needed in order to take payment.', 'woocommerce' ),
        'default'     => '',
        'desc_tip'    => true,
        'placeholder' => 'you@youremail.com'
      ),
      'receiver_email' => array(
        'title'       => __( 'Receiver Email', 'woocommerce' ),
        'type'        => 'email',
        'description' => __( 'If this differs from the email entered above, input your main receiver email for your PayStand account. This is used to validate IPN requests.', 'woocommerce' ),
        'default'     => '',
        'desc_tip'    => true,
        'placeholder' => 'you@youremail.com'
      ),
      'identity_token' => array(
        'title'       => __( 'PayStand Identity Token', 'woocommerce' ),
        'type'        => 'text',
        'description' => __( 'Optionally enable "Payment Data Transfer" (Profile > Website Payment Preferences) and then copy your identity token here. This will allow payments to be verified without the need for PayStand IPN.', 'woocommerce' ),
        'default'     => '',
        'desc_tip'    => true,
        'placeholder' => __( 'Optional', 'woocommerce' )
      ),
      'invoice_prefix' => array(
        'title'       => __( 'Invoice Prefix', 'woocommerce' ),
        'type'        => 'text',
        'description' => __( 'Please enter a prefix for your invoice numbers. If you use your PayStand account for multiple stores ensure this prefix is unique as PayStand will not allow orders with the same invoice number.', 'woocommerce' ),
        'default'     => 'WC-',
        'desc_tip'    => true,
      ),
      'paymentaction' => array(
        'title'       => __( 'Payment Action', 'woocommerce' ),
        'type'        => 'select',
        'description' => __( 'Choose whether you wish to capture funds immediately or authorize payment only.', 'woocommerce' ),
        'default'     => 'sale',
        'desc_tip'    => true,
        'options'     => array(
          'sale'          => __( 'Capture', 'woocommerce' ),
          'authorization' => __( 'Authorize', 'woocommerce' )
        )
      ),
      'form_submission_method' => array(
        'title'       => __( 'Submission method', 'woocommerce' ),
        'type'        => 'checkbox',
        'label'       => __( 'Use form submission method.', 'woocommerce' ),
        'description' => __( 'Enable this to post order data to PayStand via a form instead of using a redirect/querystring.', 'woocommerce' ),
        'default'     => 'no'
      ),
      'page_style' => array(
        'title'       => __( 'Page Style', 'woocommerce' ),
        'type'        => 'text',
        'description' => __( 'Optionally enter the name of the page style you wish to use. These are defined within your PayStand account.', 'woocommerce' ),
        'default'     => '',
        'desc_tip'    => true,
        'placeholder' => __( 'Optional', 'woocommerce' )
      ),
      'shipping' => array(
        'title'       => __( 'Shipping options', 'woocommerce' ),
        'type'        => 'title',
        'description' => '',
      ),
      'send_shipping' => array(
        'title'       => __( 'Shipping details', 'woocommerce' ),
        'type'        => 'checkbox',
        'label'       => __( 'Send shipping details to PayStand instead of billing.', 'woocommerce' ),
        'description' => __( 'PayStand allows us to send 1 address. If you are using PayStand for shipping labels you may prefer to send the shipping address rather than billing.', 'woocommerce' ),
        'default'     => 'no'
      ),
      'address_override' => array(
        'title'       => __( 'Address override', 'woocommerce' ),
        'type'        => 'checkbox',
        'label'       => __( 'Enable "address_override" to prevent address information from being changed.', 'woocommerce' ),
        'description' => __( 'PayStand verifies addresses therefore this setting can cause errors (we recommend keeping it disabled).', 'woocommerce' ),
        'default'     => 'no'
      ),
      'testing' => array(
        'title'       => __( 'Gateway Testing', 'woocommerce' ),
        'type'        => 'title',
        'description' => '',
      ),
      'testmode' => array(
        'title'       => __( 'PayStand sandbox', 'woocommerce' ),
        'type'        => 'checkbox',
        'label'       => __( 'Enable PayStand sandbox', 'woocommerce' ),
        'default'     => 'no',
        'description' => sprintf( __( 'PayStand sandbox can be used to test payments. Sign up for a developer account <a href="%s">here</a>.', 'woocommerce' ), 'https://developer.paystand.com/' ),
      ),
      'debug' => array(
        'title'       => __( 'Debug Log', 'woocommerce' ),
        'type'        => 'checkbox',
        'label'       => __( 'Enable logging', 'woocommerce' ),
        'default'     => 'no',
        'description' => sprintf( __( 'Log PayStand events, such as IPN requests, inside <code>woocommerce/logs/paystand-%s.txt</code>', 'woocommerce' ), sanitize_file_name( wp_hash( 'paystand' ) ) ),
      )
    );
  }

  /**
   * Limit the length of item names
   * @param  string $item_name
   * @return string
   */
  public function paystand_item_name( $item_name ) {
    if ( strlen( $item_name ) > 127 ) {
      $item_name = substr( $item_name, 0, 124 ) . '...';
    }
    return html_entity_decode( $item_name, ENT_NOQUOTES, 'UTF-8' );
  }

  /**
   * Get PayStand Args for passing to PP
   *
   * @access public
   * @param mixed $order
   * @return array
   */
  function get_paystand_args( $order ) {

    $order_id = $order->id;

    if ( 'yes' == $this->debug ) {
      $this->log->add( 'paystand', 'Generating payment form for order ' . $order->get_order_number() . '. Notify URL: ' . $this->notify_url );
    }

    if ( in_array( $order->billing_country, array( 'US','CA' ) ) ) {
      $order->billing_phone = str_replace( array( '(', '-', ' ', ')', '.' ), '', $order->billing_phone );
      $phone_args = array(
        'night_phone_a' => substr( $order->billing_phone, 0, 3 ),
        'night_phone_b' => substr( $order->billing_phone, 3, 3 ),
        'night_phone_c' => substr( $order->billing_phone, 6, 4 ),
        'day_phone_a'   => substr( $order->billing_phone, 0, 3 ),
        'day_phone_b'   => substr( $order->billing_phone, 3, 3 ),
        'day_phone_c'   => substr( $order->billing_phone, 6, 4 )
      );
    } else {
      $phone_args = array(
        'night_phone_b' => $order->billing_phone,
        'day_phone_b'   => $order->billing_phone
      );
    }

    // PayStand Args
    $paystand_args = array_merge(
      array(
        'cmd'           => '_cart',
        'business'      => $this->email,
        'no_note'       => 1,
        'currency_code' => get_woocommerce_currency(),
        'charset'       => 'UTF-8',
        'rm'            => is_ssl() ? 2 : 1,
        'upload'        => 1,
        'return'        => esc_url( add_query_arg( 'utm_nooverride', '1', $this->get_return_url( $order ) ) ),
        'cancel_return' => esc_url( $order->get_cancel_order_url() ),
        'page_style'    => $this->page_style,
        'paymentaction' => $this->paymentaction,
        'bn'            => 'WooThemes_Cart',
        
        // Order key + ID
        'invoice'       => $this->invoice_prefix . ltrim( $order->get_order_number(), '#' ),
        'custom'        => serialize( array( $order_id, $order->order_key ) ),
        
        // IPN
        'notify_url'    => $this->notify_url,
        
        // Billing Address info
        'first_name'    => $order->billing_first_name,
        'last_name'     => $order->billing_last_name,
        'company'       => $order->billing_company,
        'address1'      => $order->billing_address_1,
        'address2'      => $order->billing_address_2,
        'city'          => $order->billing_city,
        'state'         => $this->get_paystand_state( $order->billing_country, $order->billing_state ),
        'zip'           => $order->billing_postcode,
        'country'       => $order->billing_country,
        'email'         => $order->billing_email
      ),
      $phone_args
    );

    // Shipping
    if ( 'yes' == $this->send_shipping ) {
      $paystand_args['address_override'] = ( $this->address_override == 'yes' ) ? 1 : 0;

      $paystand_args['no_shipping'] = 0;

      // If we are sending shipping, send shipping address instead of billing
      $paystand_args['first_name']    = $order->shipping_first_name;
      $paystand_args['last_name']    = $order->shipping_last_name;
      $paystand_args['company']      = $order->shipping_company;
      $paystand_args['address1']    = $order->shipping_address_1;
      $paystand_args['address2']    = $order->shipping_address_2;
      $paystand_args['city']      = $order->shipping_city;
      $paystand_args['state']      = $this->get_paystand_state( $order->shipping_country, $order->shipping_state );
      $paystand_args['country']      = $order->shipping_country;
      $paystand_args['zip']        = $order->shipping_postcode;
    } else {
      $paystand_args['no_shipping'] = 1;
    }

    // If prices include tax or have order discounts, send the whole order as a single item
    if ( get_option( 'woocommerce_prices_include_tax' ) == 'yes' || $order->get_order_discount() > 0 || ( sizeof( $order->get_items() ) + sizeof( $order->get_fees() ) ) >= 9 ) {

      // Discount
      $paystand_args['discount_amount_cart'] = $order->get_order_discount();

      // Don't pass items - paystand borks tax due to prices including tax. PayStand has no option for tax inclusive pricing sadly. Pass 1 item for the order items overall
      $item_names = array();

      if ( sizeof( $order->get_items() ) > 0 ) {
        foreach ( $order->get_items() as $item ) {
          if ( $item['qty'] ) {
            $item_names[] = $item['name'] . ' x ' . $item['qty'];
          }
        }
      }

      $paystand_args['item_name_1']   = $this->paystand_item_name( sprintf( __( 'Order %s' , 'woocommerce'), $order->get_order_number() ) . " - " . implode( ', ', $item_names ) );
      $paystand_args['quantity_1']     = 1;
      $paystand_args['amount_1']     = number_format( $order->get_total() - $order->get_total_shipping() - $order->get_shipping_tax() + $order->get_order_discount(), 2, '.', '' );

      // Shipping Cost
      // No longer using shipping_1 because
      //    a) paystand ignore it if *any* shipping rules are within paystand
      //    b) paystand ignore anything over 5 digits, so 999.99 is the max
      if ( ( $order->get_total_shipping() + $order->get_shipping_tax() ) > 0 ) {
        $paystand_args['item_name_2'] = $this->paystand_item_name( __( 'Shipping via', 'woocommerce' ) . ' ' . ucwords( $order->get_shipping_method() ) );
        $paystand_args['quantity_2']   = '1';
        $paystand_args['amount_2']   = number_format( $order->get_total_shipping() + $order->get_shipping_tax(), 2, '.', '' );
      }

    } else {

      // Tax
      $paystand_args['tax_cart'] = $order->get_total_tax();

      // Cart Contents
      $item_loop = 0;
      if ( sizeof( $order->get_items() ) > 0 ) {
        foreach ( $order->get_items() as $item ) {
          if ( $item['qty'] ) {

            $item_loop++;

            $product = $order->get_product_from_item( $item );

            $item_name   = $item['name'];

            $item_meta = new WC_Order_Item_Meta( $item['item_meta'] );
            if ( $meta = $item_meta->display( true, true ) ) {
              $item_name .= ' ( ' . $meta . ' )';
            }

            $paystand_args[ 'item_name_' . $item_loop ]   = $this->paystand_item_name( $item_name );
            $paystand_args[ 'quantity_' . $item_loop ]   = $item['qty'];
            $paystand_args[ 'amount_' . $item_loop ]     = $order->get_item_subtotal( $item, false );

            if ( $product->get_sku() ) {
              $paystand_args[ 'item_number_' . $item_loop ] = $product->get_sku();
            }
          }
        }
      }

      // Discount
      if ( $order->get_cart_discount() > 0 ) {
        $paystand_args['discount_amount_cart'] = round( $order->get_cart_discount(), 2 );
      }

      // Fees
      if ( sizeof( $order->get_fees() ) > 0 ) {
        foreach ( $order->get_fees() as $item ) {
          $item_loop++;

          $paystand_args[ 'item_name_' . $item_loop ]   = $this->paystand_item_name( $item['name'] );
          $paystand_args[ 'quantity_' . $item_loop ]   = 1;
          $paystand_args[ 'amount_' . $item_loop ]     = $item['line_total'];
        }
      }

      // Shipping Cost item - paystand only allows shipping per item, we want to send shipping for the order
      if ( $order->get_total_shipping() > 0 ) {
        $item_loop++;
        $paystand_args[ 'item_name_' . $item_loop ]   = $this->paystand_item_name( sprintf( __( 'Shipping via %s', 'woocommerce' ), $order->get_shipping_method() ) );
        $paystand_args[ 'quantity_' . $item_loop ]   = '1';
        $paystand_args[ 'amount_' . $item_loop ]     = number_format( $order->get_total_shipping(), 2, '.', '' );
      }

    }

    $paystand_args = apply_filters( 'woocommerce_paystand_args', $paystand_args );

    return $paystand_args;
  }

  /**
   * Generate the paystand button link
   *
   * @access public
   * @param mixed $order_id
   * @return string
   */
  function generate_paystand_form( $order_id ) {

    $order = new WC_Order( $order_id );

    if ( 'yes' == $this->testmode ) {
      $paystand_adr = $this->testurl . '?test_ipn=1&';
    } else {
      $paystand_adr = $this->liveurl . '?';
    }

    $paystand_args = $this->get_paystand_args( $order );

    $paystand_args_array = array();

    foreach ( $paystand_args as $key => $value ) {
      $paystand_args_array[] = '<input type="hidden" name="'.esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" />';
    }

    wc_enqueue_js( '
      $.blockUI({
          message: "' . esc_js( __( 'Thank you for your order. We are now redirecting you to PayStand to make payment.', 'woocommerce' ) ) . '",
          baseZ: 99999,
          overlayCSS:
          {
            background: "#fff",
            opacity: 0.6
          },
          css: {
            padding:        "20px",
            zindex:         "9999999",
            textAlign:      "center",
            color:          "#555",
            border:         "3px solid #aaa",
            backgroundColor:"#fff",
            cursor:         "wait",
            lineHeight:    "24px",
          }
        });
      jQuery("#submit_paystand_payment_form").click();
    ' );

    return '<form action="' . esc_url( $paystand_adr ) . '" method="post" id="paystand_payment_form" target="_top">
        ' . implode( '', $paystand_args_array ) . '
        <!-- Button Fallback -->
        <div class="payment_buttons">
          <input type="submit" class="button alt" id="submit_paystand_payment_form" value="' . __( 'Pay via PayStand', 'woocommerce' ) . '" /> <a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . __( 'Cancel order &amp; restore cart', 'woocommerce' ) . '</a>
        </div>
        <script type="text/javascript">
          jQuery(".payment_buttons").hide();
        </script>
      </form>';

  }

  /**
   * Process the payment and return the result
   *
   * @access public
   * @param int $order_id
   * @return array
   */
  function process_payment( $order_id ) {

    $order = new WC_Order( $order_id );

    if ( ! $this->form_submission_method ) {

      $paystand_args = $this->get_paystand_args( $order );

      $paystand_args = http_build_query( $paystand_args, '', '&' );

      if ( 'yes' == $this->testmode ) {
        $paystand_adr = $this->testurl . '?test_ipn=1&';
      } else {
        $paystand_adr = $this->liveurl . '?';
      }

      return array(
        'result'   => 'success',
        'redirect'  => $paystand_adr . $paystand_args
      );

    } else {

      return array(
        'result'   => 'success',
        'redirect'  => $order->get_checkout_payment_url( true )
      );

    }

  }

  /**
   * Output for the order received page.
   *
   * @access public
   * @return void
   */
  function receipt_page( $order ) {
    echo '<p>' . __( 'Thank you - your order is now pending payment. You should be automatically redirected to PayStand to make payment.', 'woocommerce' ) . '</p>';

    echo $this->generate_paystand_form( $order );
  }

  /**
   * Check PayStand IPN validity
   **/
  function check_ipn_request_is_valid( $ipn_response ) {

    // Get url
    if ( 'yes' == $this->testmode ) {
      $paystand_adr = $this->testurl;
    } else {
      $paystand_adr = $this->liveurl;
    }

    if ( 'yes' == $this->debug ) {
      $this->log->add( 'paystand', 'Checking IPN response is valid via ' . $paystand_adr . '...' );
    }

    // Get recieved values from post data
    $validate_ipn = array( 'cmd' => '_notify-validate' );
    $validate_ipn += stripslashes_deep( $ipn_response );

    // Send back post vars to paystand
    $params = array(
      'body'       => $validate_ipn,
      'sslverify'   => false,
      'timeout'     => 60,
      'httpversion'   => '1.1',
      'compress'      => false,
      'decompress'    => false,
      'user-agent'  => 'WooCommerce/' . WC()->version
    );

    if ( 'yes' == $this->debug ) {
      $this->log->add( 'paystand', 'IPN Request: ' . print_r( $params, true ) );
    }

    // Post back to get a response
    $response = wp_remote_post( $paystand_adr, $params );

    if ( 'yes' == $this->debug ) {
      $this->log->add( 'paystand', 'IPN Response: ' . print_r( $response, true ) );
    }

    // check to see if the request was valid
    if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 && ( strcmp( $response['body'], "VERIFIED" ) == 0 ) ) {
      if ( 'yes' == $this->debug ) {
        $this->log->add( 'paystand', 'Received valid response from PayStand' );
      }

      return true;
    }

    if ( 'yes' == $this->debug ) {
      $this->log->add( 'paystand', 'Received invalid response from PayStand' );
      if ( is_wp_error( $response ) ) {
        $this->log->add( 'paystand', 'Error response: ' . $response->get_error_message() );
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
  function check_ipn_response() {

    @ob_clean();

    $ipn_response = ! empty( $_POST ) ? $_POST : false;

    if ( $ipn_response && $this->check_ipn_request_is_valid( $ipn_response ) ) {

      header( 'HTTP/1.1 200 OK' );

      do_action( "valid-paystand-standard-ipn-request", $ipn_response );

    } else {

      wp_die( "PayStand IPN Request Failure", "PayStand IPN", array( 'response' => 200 ) );

    }

  }

  /**
   * Successful Payment!
   *
   * @access public
   * @param array $posted
   * @return void
   */
  function successful_request( $posted ) {

    $posted = stripslashes_deep( $posted );

    // Custom holds post ID
    if ( ! empty( $posted['invoice'] ) && ! empty( $posted['custom'] ) ) {

      $order = $this->get_paystand_order( $posted['custom'], $posted['invoice'] );

      if ( 'yes' == $this->debug ) {
        $this->log->add( 'paystand', 'Found order #' . $order->id );
      }

      // Lowercase returned variables
      $posted['payment_status']   = strtolower( $posted['payment_status'] );
      $posted['txn_type']     = strtolower( $posted['txn_type'] );

      // Sandbox fix
      if ( 1 == $posted['test_ipn'] && 'pending' == $posted['payment_status'] ) {
        $posted['payment_status'] = 'completed';
      }

      if ( 'yes' == $this->debug ) {
        $this->log->add( 'paystand', 'Payment status: ' . $posted['payment_status'] );
      }

      // We are here so lets check status and do actions
      switch ( $posted['payment_status'] ) {
        case 'completed' :
        case 'pending' :

          // Check order not already completed
          if ( $order->status == 'completed' ) {
            if ( 'yes' == $this->debug ) {
              $this->log->add( 'paystand', 'Aborting, Order #' . $order->id . ' is already complete.' );
            }
            exit;
          }

          // Check valid txn_type
          $accepted_types = array( 'cart', 'instant', 'express_checkout', 'web_accept', 'masspay', 'send_money' );

          if ( ! in_array( $posted['txn_type'], $accepted_types ) ) {
            if ( 'yes' == $this->debug ) {
              $this->log->add( 'paystand', 'Aborting, Invalid type:' . $posted['txn_type'] );
            }
            exit;
          }

          // Validate currency
          if ( $order->get_order_currency() != $posted['mc_currency'] ) {
            if ( 'yes' == $this->debug ) {
              $this->log->add( 'paystand', 'Payment error: Currencies do not match (sent "' . $order->get_order_currency() . '" | returned "' . $posted['mc_currency'] . '")' );
            }

            // Put this order on-hold for manual checking
            $order->update_status( 'on-hold', sprintf( __( 'Validation error: PayStand currencies do not match (code %s).', 'woocommerce' ), $posted['mc_currency'] ) );
            exit;
          }

          // Validate amount
          if ( $order->get_total() != $posted['mc_gross'] ) {
            if ( 'yes' == $this->debug ) {
              $this->log->add( 'paystand', 'Payment error: Amounts do not match (gross ' . $posted['mc_gross'] . ')' );
            }

            // Put this order on-hold for manual checking
            $order->update_status( 'on-hold', sprintf( __( 'Validation error: PayStand amounts do not match (gross %s).', 'woocommerce' ), $posted['mc_gross'] ) );
            exit;
          }

          // Validate Email Address
          if ( strcasecmp( trim( $posted['receiver_email'] ), trim( $this->receiver_email ) ) != 0 ) {
            if ( 'yes' == $this->debug ) {
              $this->log->add( 'paystand', "IPN Response is for another one: {$posted['receiver_email']} our email is {$this->receiver_email}" );
            }

            // Put this order on-hold for manual checking
            $order->update_status( 'on-hold', sprintf( __( 'Validation error: PayStand IPN response from a different email address (%s).', 'woocommerce' ), $posted['receiver_email'] ) );

            exit;
          }

           // Store PP Details
          if ( ! empty( $posted['payer_email'] ) ) {
            update_post_meta( $order->id, 'Payer PayStand address', wc_clean( $posted['payer_email'] ) );
          }
          if ( ! empty( $posted['txn_id'] ) ) {
            update_post_meta( $order->id, 'Transaction ID', wc_clean( $posted['txn_id'] ) );
          }
          if ( ! empty( $posted['first_name'] ) ) {
            update_post_meta( $order->id, 'Payer first name', wc_clean( $posted['first_name'] ) );
          }
          if ( ! empty( $posted['last_name'] ) ) {
            update_post_meta( $order->id, 'Payer last name', wc_clean( $posted['last_name'] ) );
          }
          if ( ! empty( $posted['payment_type'] ) ) {
            update_post_meta( $order->id, 'Payment type', wc_clean( $posted['payment_type'] ) );
          }

          if ( $posted['payment_status'] == 'completed' ) {
            $order->add_order_note( __( 'IPN payment completed', 'woocommerce' ) );
            $order->payment_complete();
          } else {
            $order->update_status( 'on-hold', sprintf( __( 'Payment pending: %s', 'woocommerce' ), $posted['pending_reason'] ) );
          }

          if ( 'yes' == $this->debug ) {
            $this->log->add( 'paystand', 'Payment complete.' );
          }

        break;
        case 'denied' :
        case 'expired' :
        case 'failed' :
        case 'voided' :
          // Order failed
          $order->update_status( 'failed', sprintf( __( 'Payment %s via IPN.', 'woocommerce' ), strtolower( $posted['payment_status'] ) ) );
        break;
        case 'refunded' :

          // Only handle full refunds, not partial
          if ( $order->get_total() == ( $posted['mc_gross'] * -1 ) ) {

            // Mark order as refunded
            $order->update_status( 'refunded', sprintf( __( 'Payment %s via IPN.', 'woocommerce' ), strtolower( $posted['payment_status'] ) ) );

            $mailer = WC()->mailer();

            $message = $mailer->wrap_message(
              __( 'Order refunded/reversed', 'woocommerce' ),
              sprintf( __( 'Order %s has been marked as refunded - PayStand reason code: %s', 'woocommerce' ), $order->get_order_number(), $posted['reason_code'] )
            );

            $mailer->send( get_option( 'admin_email' ), sprintf( __( 'Payment for order %s refunded/reversed', 'woocommerce' ), $order->get_order_number() ), $message );

          }

        break;
        case 'reversed' :

          // Mark order as refunded
          $order->update_status( 'on-hold', sprintf( __( 'Payment %s via IPN.', 'woocommerce' ), strtolower( $posted['payment_status'] ) ) );

          $mailer = WC()->mailer();

          $message = $mailer->wrap_message(
            __( 'Order reversed', 'woocommerce' ),
            sprintf(__( 'Order %s has been marked on-hold due to a reversal - PayStand reason code: %s', 'woocommerce' ), $order->get_order_number(), $posted['reason_code'] )
          );

          $mailer->send( get_option( 'admin_email' ), sprintf( __( 'Payment for order %s reversed', 'woocommerce' ), $order->get_order_number() ), $message );

        break;
        case 'canceled_reversal' :

          $mailer = WC()->mailer();

          $message = $mailer->wrap_message(
            __( 'Reversal Cancelled', 'woocommerce' ),
            sprintf( __( 'Order %s has had a reversal cancelled. Please check the status of payment and update the order status accordingly.', 'woocommerce' ), $order->get_order_number() )
          );

          $mailer->send( get_option( 'admin_email' ), sprintf( __( 'Reversal cancelled for order %s', 'woocommerce' ), $order->get_order_number() ), $message );

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
    $posted = stripslashes_deep( $_REQUEST );

    if ( ! empty( $this->identity_token ) && ! empty( $posted['cm'] ) ) {

      $order = $this->get_paystand_order( $posted['cm'] );

      if ( 'pending' != $order->status ) {
        return false;
      }

      $posted['st'] = strtolower( $posted['st'] );

      switch ( $posted['st'] ) {
        case 'completed' :

          // Validate transaction
          if ( 'yes' == $this->testmode ) {
            $paystand_adr = $this->testurl;
          } else {
            $paystand_adr = $this->liveurl;
          }

          $pdt = array(
            'body'       => array(
              'cmd' => '_notify-synch',
              'tx'  => $posted['tx'],
              'at'  => $this->identity_token
            ),
            'sslverify'   => false,
            'timeout'     => 60,
            'httpversion'   => '1.1',
            'user-agent'  => 'WooCommerce/' . WC_VERSION
          );

          // Post back to get a response
          $response = wp_remote_post( $paystand_adr, $pdt );

          if ( is_wp_error( $response ) ) {
            return false;
          }

          if ( ! strpos( $response['body'], "SUCCESS" ) === 0 ) {
            return false;
          }

          // Validate Amount
          if ( $order->get_total() != $posted['amt'] ) {

            if ( 'yes' == $this->debug ) {
              $this->log->add( 'paystand', 'Payment error: Amounts do not match (amt ' . $posted['amt'] . ')' );
            }

            // Put this order on-hold for manual checking
            $order->update_status( 'on-hold', sprintf( __( 'Validation error: PayStand amounts do not match (amt %s).', 'woocommerce' ), $posted['amt'] ) );
            return true;

          } else {

            // Store PP Details
            update_post_meta( $order->id, 'Transaction ID', wc_clean( $posted['tx'] ) );

            $order->add_order_note( __( 'PDT payment completed', 'woocommerce' ) );
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
  private function get_paystand_order( $custom, $invoice = '' ) {
    $custom = maybe_unserialize( $custom );

    // Backwards comp for IPN requests
    if ( is_numeric( $custom ) ) {
      $order_id  = (int) $custom;
      $order_key = $invoice;
    } elseif( is_string( $custom ) ) {
      $order_id  = (int) str_replace( $this->invoice_prefix, '', $custom );
      $order_key = $custom;
    } else {
      list( $order_id, $order_key ) = $custom;
    }

    $order = new WC_Order( $order_id );

    if ( ! isset( $order->id ) ) {
      // We have an invalid $order_id, probably because invoice_prefix has changed
      $order_id   = wc_get_order_id_by_order_key( $order_key );
      $order     = new WC_Order( $order_id );
    }

    // Validate key
    if ( $order->order_key !== $order_key ) {
      if ( 'yes' == $this->debug ) {
        $this->log->add( 'paystand', 'Error: Order Key does not match invoice.' );
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
  public function get_paystand_state( $cc, $state ) {
    if ( 'US' === $cc ) {
      return $state;
    }

    $states = WC()->countries->get_states( $cc );
    
    if ( isset( $states[ $state ] ) ) {
      return $states[ $state ];
    }

    return $state;
  }
}

