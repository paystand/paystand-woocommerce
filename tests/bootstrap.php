<?php
/**
 * Autoload the composer items.
 */
require_once 'vendor/autoload.php';
WP_Mock::bootstrap();

// Test customer
function getCustomer() {
    $customer = array(
        'publishable_key' => 'd8igrhm8jo2xontiqcud1lqg',
        'customer_id' => '04ow0qsfuk5tipbvf1bpwht4',
        'client_id' => '2268e140efb819893913338715f7fb56',
        'client_secret' => 'd022ad3b7c44ab3aeb006e6d88eee798667c7004',
        'cardId' => '6c4xzr4wd9gxm5dv4g5l2163',
        'payerId' => 'dg6hzwi56959qay9458n5w3o'
    );

    return (object)$customer;
}

/**
 * Mocked WooCommerce payment gateway for tests
 * 
 */
class WC_Payment_Gateway {
    public function init_settings() {}
    public function get_option($option) {
        $options = new stdClass;
        $options->publishable_key = getCustomer()->publishable_key;
        $options->customer_id = getCustomer()->customer_id;
        $options->client_id= getCustomer()->client_id;
        $options->client_secret = getCustomer()->client_secret;
        if (property_exists($options, $option)) {
            return $options->$option;
        }
        return null;
    }
}
/**
 * Mocked WooCommerce Order for tests
 * All its methods should be mocked using Mockery to run tests
 */
class WC_Order {
    public function __construct($id) {
        $this->id = $id;
    }
    public function get_checkout_payment_url(){
        return 'http://example.com/checkout-payment';
    }
    public function get_checkout_order_received_url() {
        return 'http://example.com/checkout-payment';
    }
    public function get_user_id() {
        return 1;
    }
    public function get_total() {
        return 10;
    }
    public function get_subtotal() {
        return 1;
    }
 }

/**
 * Mocked WooCommerce WC object for tests
 */
class WC {
    function api_request_url() {
        return '';
    }
}

class WC_Token {
    public function __construct($id) {
        $this->id = $id;
    }
    public function get_token() {
        return getCustomer()->cardId;
    }
    public function get_user_id() {
        return 1;
    }
    public function get_type() {
        return 'CC';
    }
    public function get_meta() {
        return getCustomer()->payerId;
    }
}
class WC_Payment_Tokens {
    static public function get($token_id) {
        return new WC_Token($token_id);
    }
}
class HttpfulResponse {
    public function __construct($response) {
        $this->response = $response;
    }
    
}