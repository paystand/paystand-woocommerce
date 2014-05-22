<?php
/*
Plugin Name: WooCommerce-PayStand
Plugin URI: http://www.paystand.com/
Description: Adds PayStand payment gateway to WooCommerce.
Version: 1.0.0
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
  include_once('class-wc-gateway-paystand.php');
  load_plugin_textdomain('wc-paystand', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'init_paystand_gateway_class');


function add_paystand_gateway_class($methods) {
  $methods[] = 'WC_Gateway_PayStand'; 
  return $methods;
}
add_filter('woocommerce_payment_gateways', 'add_paystand_gateway_class');

?>
