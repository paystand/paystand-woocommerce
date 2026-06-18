<?php
/**
 * Class SampleTest
 *
 * @package Woocommerce_Paystand
 */


/**
 * Tests WCGatewayPaystandTest
 * 
 */
class WCGatewayPaystandTest extends \WP_Mock\Tools\TestCase {
	/**
	 * Setup WP_Mock for each test
	 */
	public function setUp(): void {
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

        $mocked = \Mockery::mock('WC_Gateway_PayStand[do_http_post]');
        $mocked->shouldReceive('do_http_post')->andReturn(new HttpfulResponse(200));

        $result = $mocked->process_payment(1);
        $this->assertEquals('success', $result['result']);
    }

    /**
     * Test that the payment request body includes meta source 'woocommerce'
     */
    public function test_payment_meta_source() {
        $_POST['wc-paystand-payment-token'] = '1';
        \WP_Mock::userFunction('wc_clean', array('return' => 2));
        \WP_Mock::userFunction('get_current_user_id', array('return' => 1));

        $captured_body = null;
        $mocked = \Mockery::mock('WC_Gateway_PayStand[do_http_post]');
        $mocked->shouldReceive('do_http_post')
            ->with(
                \Mockery::any(),
                \Mockery::any(),
                \Mockery::on(function($body) use (&$captured_body) {
                    $captured_body = $body;
                    return true;
                })
            )
            ->andReturn(new HttpfulResponse(200));

        $mocked->process_payment(1);

        $this->assertEquals('woocommerce', $captured_body['meta']['source']);
    }

    // ── send_cf_log tests ────────────────────────────────────────────────────

    /**
     * send_cf_log bails immediately when publishable_key is empty —
     * wp_remote_post must never be called.
     */
    public function test_send_cf_log_skips_when_no_publishable_key() {
        \WP_Mock::userFunction('wp_remote_post')->never();

        // Override the option so publishable_key comes back empty
        \WP_Mock::userFunction('get_option', array('return' => ''));

        $gw = new WC_Gateway_PayStand();
        // Force the property to empty to simulate a mis-configured gateway
        $ref = new \ReflectionProperty(WC_Gateway_PayStand::class, 'publishable_key');
        $ref->setAccessible(true);
        $ref->setValue($gw, '');

        // Call via reflection (private method)
        $method = new \ReflectionMethod(WC_Gateway_PayStand::class, 'send_cf_log');
        $method->setAccessible(true);
        $method->invoke($gw, 'checkout_rendered', array());

        // WP_Mock will fail the test if wp_remote_post was called
        $this->assertTrue(true);
    }

    /**
     * send_cf_log posts to the WooCommerce worker URL (not the Magento one).
     */
    public function test_send_cf_log_uses_woocommerce_worker_url() {
        $captured_url = null;

        \WP_Mock::userFunction('wp_json_encode', array(
            'return' => function($data) { return json_encode($data); },
        ));

        \WP_Mock::userFunction('wp_remote_post', array(
            'times' => 1,
            'return' => function($url, $args) use (&$captured_url) {
                $captured_url = $url;
                return array();
            },
        ));

        $method = new \ReflectionMethod(WC_Gateway_PayStand::class, 'send_cf_log');
        $method->setAccessible(true);
        $method->invoke($this->wc_paystand, 'checkout_rendered', array('order_id' => 42));

        $this->assertStringContainsString(
            'woocommerce-plugin-logs.paystand-core-services.workers.dev',
            $captured_url,
            'send_cf_log must post to the WooCommerce worker, not the Magento one'
        );
        $this->assertStringNotContainsString(
            'magento-plugin-logs',
            $captured_url,
            'send_cf_log must not post to the Magento worker URL'
        );
    }

    /**
     * send_cf_log payload uses order_id and wc_version, not quote_id / magento_version.
     */
    public function test_send_cf_log_payload_uses_woocommerce_field_names() {
        $captured_body = null;

        \WP_Mock::userFunction('wp_json_encode', array(
            'return' => function($data) use (&$captured_body) {
                $captured_body = $data;
                return json_encode($data);
            },
        ));

        \WP_Mock::userFunction('wp_remote_post', array(
            'return' => array(),
        ));

        $method = new \ReflectionMethod(WC_Gateway_PayStand::class, 'send_cf_log');
        $method->setAccessible(true);
        $method->invoke($this->wc_paystand, 'payment_complete', array(
            'order_id'      => '99',
            'payment_id'    => 'pay_abc',
            'error_message' => '',
        ));

        $this->assertArrayHasKey('order_id',    $captured_body, 'payload must contain order_id');
        $this->assertArrayHasKey('wc_version',  $captured_body, 'payload must contain wc_version');
        $this->assertArrayNotHasKey('quote_id',       $captured_body, 'payload must not contain quote_id (Magento field)');
        $this->assertArrayNotHasKey('magento_version', $captured_body, 'payload must not contain magento_version (Magento field)');
        $this->assertEquals('99', $captured_body['order_id']);
    }

    /**
     * send_cf_log uses blocking=false (fire-and-forget).
     */
    public function test_send_cf_log_is_non_blocking() {
        $captured_args = null;

        \WP_Mock::userFunction('wp_json_encode', array(
            'return' => function($data) { return json_encode($data); },
        ));

        \WP_Mock::userFunction('wp_remote_post', array(
            'return' => function($url, $args) use (&$captured_args) {
                $captured_args = $args;
                return array();
            },
        ));

        $method = new \ReflectionMethod(WC_Gateway_PayStand::class, 'send_cf_log');
        $method->setAccessible(true);
        $method->invoke($this->wc_paystand, 'webhook_received', array());

        $this->assertFalse(
            $captured_args['blocking'],
            'send_cf_log must be fire-and-forget (blocking => false)'
        );
    }

    /**
     * send_cf_log maps env=live to ps_env=com, and sandbox to co.
     */
    public function test_send_cf_log_env_mapping() {
        $bodies = array();

        \WP_Mock::userFunction('wp_json_encode', array(
            'return' => function($data) use (&$bodies) {
                $bodies[] = $data;
                return json_encode($data);
            },
        ));

        \WP_Mock::userFunction('wp_remote_post', array(
            'return' => array(),
        ));

        $method = new \ReflectionMethod(WC_Gateway_PayStand::class, 'send_cf_log');
        $method->setAccessible(true);

        // live env → com
        putenv('PAYSTAND_ENV=live');
        $method->invoke($this->wc_paystand, 'checkout_rendered', array());

        // sandbox env → co
        putenv('PAYSTAND_ENV=sandbox');
        $method->invoke($this->wc_paystand, 'checkout_rendered', array());

        putenv('PAYSTAND_ENV='); // reset

        $this->assertEquals('com', $bodies[0]['env'], 'live env should map to com');
        $this->assertEquals('co',  $bodies[1]['env'], 'sandbox env should map to co');
    }

	/**
	 * Clean up after the test is run
	 */
	public function tearDown(): void {
	    $this->addToAssertionCount(
	        \Mockery::getContainer()->mockery_getExpectationCount()
	        );
		\WP_Mock::tearDown();
	}
}

