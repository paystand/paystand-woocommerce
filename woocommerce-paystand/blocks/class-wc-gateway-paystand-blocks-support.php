<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * WooCommerce Blocks (Cart/Checkout) integration for the Paystand gateway.
 *
 * Redirect-only: the gateway collects nothing inline — process_payment() redirects
 * to the order-pay page where the hosted Paystand checkout renders, so this class
 * only makes the method visible/selectable in the Checkout block. The saved-token
 * fee flow remains classic-checkout only and is intentionally not exposed here.
 */
class WC_Gateway_PayStand_Blocks_Support extends AbstractPaymentMethodType
{
    protected $name = 'paystand';

    public function initialize()
    {
        $this->settings = get_option('woocommerce_paystand_settings', array());
    }

    public function is_active()
    {
        $gateway = $this->get_gateway();
        return $gateway ? $gateway->is_available() : false;
    }

    public function get_payment_method_script_handles()
    {
        $script_path = plugin_dir_path(dirname(__FILE__)) . 'assets/js/blocks.js';
        $script_url = plugins_url('assets/js/blocks.js', dirname(__FILE__));
        wp_register_script(
            'wc-paystand-blocks',
            $script_url,
            array('wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities'),
            file_exists($script_path) ? filemtime($script_path) : '2.5.7',
            true
        );
        return array('wc-paystand-blocks');
    }

    public function get_payment_method_data()
    {
        $gateway = $this->get_gateway();
        return array(
            'title' => $gateway ? $gateway->title : __('Paystand (CC, Bank, ACH)', 'woocommerce-paystand'),
            'description' => $gateway && isset($gateway->checkout_description) ? $gateway->checkout_description : '',
            'icon' => $gateway ? $gateway->icon : '',
            'supports' => $this->get_supported_features(),
        );
    }

    public function get_supported_features()
    {
        $gateway = $this->get_gateway();
        // The gateway's supports list omits 'products' (it overrides the WC default);
        // the Checkout block requires it for standard product purchases.
        $features = $gateway ? (array) $gateway->supports : array();
        return array_values(array_unique(array_merge(array('products'), $features)));
    }

    private function get_gateway()
    {
        if (!function_exists('WC') || !WC()->payment_gateways) {
            return null;
        }
        $gateways = WC()->payment_gateways->payment_gateways();
        return isset($gateways[$this->name]) ? $gateways[$this->name] : null;
    }
}
