<?php
/**
 * Class SampleTest
 *
 * @package Woocommerce_Paystand
 */


/**
 * Tests WC_Gateway_Paystand
 * 
 */
class Test_WC_Gateway_Paystand extends \WP_Mock\Tools\TestCase {
	/**
	 * Setup WP_Mock for each test
	 */
	public function setUp() {
        \WP_Mock::setUp();
        \WP_Mock::userFunction('plugin_dir_path', array('return'=>'./woocommerce-paystand/'));
        \WP_Mock::userFunction('plugins_url', array('return'=>'./woocommerce-paystand/'));
        \WP_Mock::userFunction('WC', array('return'=>new WC()));
        \WP_Mock::userFunction('sanitize_file_name', array('return'=>''));
        \WP_Mock::userFunction('wp_hash', array('return'=>''));
        \WP_Mock::userFunction('get_woocommerce_currency', array('return'=>'USD'));
        $this->wc_paystand = new WC_Gateway_PayStand();
    }

    public function test_process_payment() {
        $this->process_payment_simple_checkout_test();
        $this->process_payment_saved_cc_test(); 
    }

    /**
     * Simple test for process_payment returning the checkout URL 
     */
    private function process_payment_simple_checkout_test() {
        $result = $this->wc_paystand->process_payment(1);
        $this->assertEquals('success', $result['result']);
        $this->assertEquals('http://example.com/checkout-payment',$result['redirect']);
    }

    /**
     * Test to process a payment of an order with a mocked existing CC
     */
    private function process_payment_saved_cc_test() {
        $order_id = 2;
        $_POST['wc-paystand-payment-token'] = '1'; // simulate a saved WooCommerce CC token
        \WP_Mock::userFunction('wc_clean', array( 'return'=>$order_id));
        \WP_Mock::userFunction('get_current_user_id',  array('return'=>1));

        $mocked= \Mockery::mock($this->wc_paystand);
        $mocked->shouldReceive('do_http_post')->andReturn(new HttpfulResponse(200));

        $result = $mocked->process_payment(1);
        $this->assertEquals('success', $result['result']);
    }

	/**
	 * Clean up after the test is run
	 */
	public function tearDown() {
	    $this->addToAssertionCount(
	        \Mockery::getContainer()->mockery_getExpectationCount()
	        );
		\WP_Mock::tearDown();
	}
}

