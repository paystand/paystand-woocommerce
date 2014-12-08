<?php
/*
Plugin Name: PayStand for WooCommerce
Plugin URI: http://www.paystand.com/
Description: Adds PayStand payment gateway to WooCommerce.
Version: 1.0.3
Author: PayStand
Author URI: http://www.paystand.com/
*/

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

if (!function_exists('add_action')) {
  echo 'No direct access.';
  exit;
}


function init_paystand_gateway_class() {
  if (!class_exists('WC_Payment_Gateway')) {
    return;
  }
  load_plugin_textdomain('woocommerce-paystand', false, dirname(plugin_basename(__FILE__)) . '/languages');
  include_once('class-wc-gateway-paystand.php');
}
add_action('plugins_loaded', 'init_paystand_gateway_class');


function add_paystand_gateway_class($methods) {
  $methods[] = 'WC_Gateway_PayStand'; 
  return $methods;
}
add_filter('woocommerce_payment_gateways', 'add_paystand_gateway_class');


function paystand_gateway_activate() {
  $message = '<b>PayStand for WooCommerce is almost ready.</b> Add your PayStand Org ID and Public API Key to get started.';
  if (function_exists('wc_add_notice')) {
    wc_add_notice($message, 'notice');
  }
}
//register_activation_hook(__FILE__, 'paystand_gateway_activate');

?>
