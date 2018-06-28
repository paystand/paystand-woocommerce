<?php
/**
 * Autoload the composer items.
 */
require_once 'vendor/autoload.php';
WP_Mock::bootstrap();


/**
 * Mocked WooCommerce payment gateway for tests
 * 
 */
class WC_Payment_Gateway {
    public function init_settings() {}
    public function get_option() {}
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
    public function get_user_id() {
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
        return 'abcd';
    }
    public function get_user_id() {
        return 1;
    }
    public function get_type() {
        return 'CC';
    }
    public function get_meta() {
        return array('payerId'=>1);
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