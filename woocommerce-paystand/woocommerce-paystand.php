<?php
/*
Plugin Name: Paystand for WooCommerce
Plugin URI: http://www.paystand.com/
Description: Adds Paystand payment gateway to WooCommerce.
Version: 2.5.7
Author: Paystand
Author URI: http://www.paystand.com/
*/

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

if (!defined('PAYSTAND_WC_VERSION')) {
    // Derive from this file's own plugin header (the "Version: 2.5.6" line above) rather than
    // hardcoding a second literal — WordPress's get_file_data() reads the header directly, so
    // this and the header itself are a single source of truth for *this constant specifically*.
    // NOTE: version.txt and readme.txt's "Stable tag"/changelog remain separate, unlinked
    // literals with no programmatic tie to this header — bumping the header does not update
    // them, and vice versa. Fully closing that gap would mean generating version.txt/readme.txt
    // from the header (or the reverse) at build/release time, which is out of scope here.
    if (function_exists('get_file_data')) {
        $paystand_wc_plugin_data = get_file_data(__FILE__, array('Version' => 'Version'));
        define('PAYSTAND_WC_VERSION', !empty($paystand_wc_plugin_data['Version']) ? $paystand_wc_plugin_data['Version'] : 'unknown');
    } else {
        define('PAYSTAND_WC_VERSION', 'unknown');
    }
}

if (!function_exists('add_action')) {
  echo 'No direct access.';
  exit;
}

/**
 * Ajax controller for fundonfile fee, must be declare here due to visibility nature on the wp_ajax_{NAME} API
 */
function fundonfile_fee_ajax() {
  // WooCommerce may be inactive or not yet loaded even though this plugin is active
  if ( ! function_exists('WC') || ! WC() || ! WC()->session ) {
    die();
  }
  if ( isset($_POST['fee']) ){
      WC()->session->set('fee_chosen', $_POST['fee'] );
      echo esc_attr(json_encode( $_POST['fee'] ));
  }
  die();
}

/**
 * Ajax Hooks register fundonfile_fee_ajax
 */
add_action( 'wp_ajax_fundonfile_fee_ajax', 'fundonfile_fee_ajax');
add_action( 'wp_ajax_nopriv_fundonfile_fee_ajax', 'fundonfile_fee_ajax');

function fundonfile_add_fee( $cart ) {
  if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
  if ( ! function_exists('WC') || ! WC() || ! WC()->session ) return;
  $fee = WC()->session->get('fee_chosen');
  if(isset($fee)){
    $cart->add_fee( __('Processing Fee', 'woocommerce'), $fee );
  }
}

/**
 * Inject JS code in footer cart's page
*/
function fundonfile_fee_js() {
  // wp_footer fires on every front-end page; is_checkout() only exists once WooCommerce is loaded
  if ( ! function_exists('is_checkout') || ! is_checkout() ) return;
$js_code="
      <script type='text/javascript'>
      jQuery( function($){
          $('form.checkout').on('click', 'input[name=wc-paystand-payment-token]', function(e){
              var fee = $(this).attr('fee');
              if(fee==undefined){
                fee = 0.0;
              }
              $.ajax({
                type: 'POST',
                url: wc_checkout_params.ajax_url,
                data: {
                  'action': 'fundonfile_fee_ajax',
                  'fee': fee,
                },
                success: function (result) {
                  $('body').trigger('update_checkout');
                },
                error: function (request, status, error) {
                  console.error(error);
                }            
              });
          });
      });
      </script>
";
      echo $js_code;
  }

/**
 * Register hook for calculate fee & footer
 */
add_action( 'woocommerce_cart_calculate_fees', 'fundonfile_add_fee', 20, 1 );
add_action( 'wp_footer', 'fundonfile_fee_js', 30);

function init_paystand_gateway_class()
{
  if (!class_exists('WC_Payment_Gateway')) {
    return;
  }
  load_plugin_textdomain('woocommerce-paystand', false,
      dirname(plugin_basename(__FILE__)) . '/languages');
  include_once('WCGatewayPaystand.php');
}
add_action('plugins_loaded', 'init_paystand_gateway_class');

function add_paystand_gateway_class($methods)
{
  $methods[] = 'WC_Gateway_PayStand';
  return $methods;
}
add_filter('woocommerce_payment_gateways', 'add_paystand_gateway_class');

/**
 * Declare compatibility with the WooCommerce Cart/Checkout Blocks feature.
 */
function paystand_declare_blocks_compatibility()
{
  if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
    \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
  }
}
add_action('before_woocommerce_init', 'paystand_declare_blocks_compatibility');

/**
 * Register the Paystand payment method with the block-based Checkout.
 */
function paystand_register_blocks_support($payment_method_registry)
{
  // Older WooCommerce versions have no Blocks payment API; the require stays behind this
  // guard so the class (whose parent would be missing) is never loaded there.
  if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
    return;
  }
  require_once __DIR__ . '/blocks/class-wc-gateway-paystand-blocks-support.php';
  $payment_method_registry->register(new WC_Gateway_PayStand_Blocks_Support());
}
add_action('woocommerce_blocks_payment_method_type_registration', 'paystand_register_blocks_support');

function paystand_gateway_activate()
{
  $message = '<b>Paystand for WooCommerce is almost ready.</b> Add your Paystand Org ID and Public API Key to get started.';
  if (function_exists('wc_add_notice')) {
    wc_add_notice($message, 'notice');
  }
}
//register_activation_hook(__FILE__, 'paystand_gateway_activate');

/**
 * Hide WooCommerce order action buttons (PAY and CANCEL) ONLY for PayStand orders
 */
function paystand_hide_order_action_buttons() {
  // Add JavaScript to hide action buttons only for PayStand orders
  ?>
  <script type="text/javascript">
  jQuery(document).ready(function($) {
    // Find all rows in the orders table using escaped selectors
    jQuery('.woocommerce-orders-table').find('tr.order').each(function() {
      var $row = jQuery(this);
      var $paymentMethod = $row.find('.woocommerce-orders-table__cell-order-payment-method');
      var paymentText = $paymentMethod.text();
      
      // Safely check if this is a PayStand order
      if (paymentText && paymentText.toLowerCase().indexOf('paystand') > -1) {
        // Hide actions cell using escaped selector
        $row.find('.woocommerce-orders-table__cell-order-actions').hide();
      }
    });
  });
  </script>
  <?php
}
add_action('wp_footer', 'paystand_hide_order_action_buttons');

// Remove action buttons programmatically ONLY for PayStand orders
add_filter('woocommerce_my_account_my_orders_actions', 'paystand_remove_order_actions', 10, 2);
function paystand_remove_order_actions($actions, $order) {
  // Validate that $order is a WC_Order object
  if (!is_a($order, 'WC_Order')) {
    return $actions;
  }

  // Check if the order payment method is PayStand
  if ($order->get_payment_method() === 'paystand') {
    // Remove all actions for PayStand orders
    return array();
  }
  
  // Return original actions for other payment methods
  return $actions;
}

// Hide action buttons in emails ONLY for PayStand orders
add_action('woocommerce_email_before_order_table', 'paystand_remove_actions_from_emails', 10, 1);
function paystand_remove_actions_from_emails($order) {
  // Check if the order payment method is PayStand
  if ($order->get_payment_method() === 'paystand') {
    remove_action('woocommerce_order_details_after_order_table', 'woocommerce_order_again_button');
  }
}
