<?php
/*
Plugin Name: Paystand for WooCommerce
Plugin URI: http://www.paystand.com/
Description: Adds Paystand payment gateway to WooCommerce.
Version: 2.4.2
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

if (!function_exists('add_action')) {
  echo 'No direct access.';
  exit;
}

/**
 * Ajax controller for fundonfile fee, must be declare here due to visibility nature on the wp_ajax_{NAME} API
 */
function fundonfile_fee_ajax() {
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
  $fee = WC()->session->get('fee_chosen');
  if(isset($fee)){
    $cart->add_fee( __('Processing Fee', 'woocommerce'), $fee );
  }
}

/**
 * Inject JS code in footer cart's page
*/
function fundonfile_fee_js() {
  if ( ! is_checkout() ) return;
$js_code="
      <script type="text/javascript">
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
"
      echo esc_attr($js_code);
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

function paystand_gateway_activate()
{
  $message = '<b>Paystand for WooCommerce is almost ready.</b> Add your Paystand Org ID and Public API Key to get started.';
  if (function_exists('wc_add_notice')) {
    wc_add_notice($message, 'notice');
  }
}
//register_activation_hook(__FILE__, 'paystand_gateway_activate');

