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

    // ── send_analytics_event tests ───────────────────────────────────────────

    /**
     * send_analytics_event bails immediately when publishable_key is empty —
     * wp_remote_post must never be called.
     */
    public function test_send_analytics_event_skips_when_no_publishable_key() {
        \WP_Mock::userFunction('wp_remote_post')->never();

        // Override the option so publishable_key comes back empty
        \WP_Mock::userFunction('get_option', array('return' => ''));

        $gw = new WC_Gateway_PayStand();
        // Force the property to empty to simulate a mis-configured gateway
        $ref = new \ReflectionProperty(WC_Gateway_PayStand::class, 'publishable_key');
        $ref->setAccessible(true);
        $ref->setValue($gw, '');

        // Call via reflection (private method)
        $method = new \ReflectionMethod(WC_Gateway_PayStand::class, 'send_analytics_event');
        $method->setAccessible(true);
        $method->invoke($gw, 'checkout_rendered', array());

        // WP_Mock will fail the test if wp_remote_post was called
        $this->assertTrue(true);
    }

    /**
     * send_analytics_event posts to the shared worker URL.
     * (Dedicated WooCommerce worker URL will replace this once provisioned.)
     */
    public function test_send_analytics_event_uses_worker_url() {
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

        \WP_Mock::userFunction('is_wp_error', array('return' => false));

        $method = new \ReflectionMethod(WC_Gateway_PayStand::class, 'send_analytics_event');
        $method->setAccessible(true);
        $method->invoke($this->wc_paystand, 'checkout_rendered', array('order_id' => 42));

        $this->assertStringContainsString(
            'paystand-core-services.workers.dev',
            $captured_url,
            'send_analytics_event must post to the plugin log worker'
        );
    }

    /**
     * send_analytics_event MUST send the worker's actual field names (quote_id /
     * magento_version), not the WooCommerce-native names (order_id / wc_version).
     * The worker (magento-log-worker/src/worker.js) destructures quote_id/magento_version —
     * sending order_id/wc_version silently drops those values via destructuring defaults.
     */
    public function test_send_analytics_event_payload_uses_worker_field_names() {
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

        \WP_Mock::userFunction('is_wp_error', array('return' => false));

        $method = new \ReflectionMethod(WC_Gateway_PayStand::class, 'send_analytics_event');
        $method->setAccessible(true);
        $method->invoke($this->wc_paystand, 'payment_complete', array(
            'order_id'      => '99',
            'payment_id'    => 'pay_abc',
            'error_message' => '',
        ));

        $this->assertArrayHasKey('quote_id',        $captured_body, 'payload must use quote_id (worker schema field)');
        $this->assertArrayHasKey('magento_version', $captured_body, 'payload must use magento_version (worker schema field)');
        $this->assertArrayNotHasKey('order_id',  $captured_body, 'payload must not use order_id — worker does not read it');
        $this->assertArrayNotHasKey('wc_version', $captured_body, 'payload must not use wc_version — worker does not read it');
        $this->assertEquals('99', $captured_body['quote_id']);
        $this->assertEquals('woocommerce', $captured_body['platform'], 'payload should self-identify platform');
    }

    /**
     * send_analytics_event uses blocking=false (fire-and-forget).
     */
    public function test_send_analytics_event_is_non_blocking() {
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

        \WP_Mock::userFunction('is_wp_error', array('return' => false));

        $method = new \ReflectionMethod(WC_Gateway_PayStand::class, 'send_analytics_event');
        $method->setAccessible(true);
        $method->invoke($this->wc_paystand, 'webhook_received', array());

        $this->assertFalse(
            $captured_args['blocking'],
            'send_analytics_event must be fire-and-forget (blocking => false)'
        );
    }

    /**
     * send_analytics_event maps env=live to ps_env=com, and sandbox to co.
     */
    public function test_send_analytics_event_env_mapping() {
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

        \WP_Mock::userFunction('is_wp_error', array('return' => false));

        $method = new \ReflectionMethod(WC_Gateway_PayStand::class, 'send_analytics_event');
        $method->setAccessible(true);

        // live env → com
        putenv('PAYSTAND_ENV=live');
        $method->invoke($this->wc_paystand, 'checkout_rendered', array());

        // sandbox env → co
        putenv('PAYSTAND_ENV=sandbox');
        $method->invoke($this->wc_paystand, 'checkout_rendered', array());

        // any other/malformed value → co (exhaustive fold, not just development/local)
        putenv('PAYSTAND_ENV=totally-bogus-value');
        $method->invoke($this->wc_paystand, 'checkout_rendered', array());

        putenv('PAYSTAND_ENV='); // reset

        $this->assertEquals('com', $bodies[0]['env'], 'live env should map to com');
        $this->assertEquals('co',  $bodies[1]['env'], 'sandbox env should map to co');
        $this->assertEquals('co',  $bodies[2]['env'], 'any non-live env should map to co');
    }

    /**
     * wp_json_encode() returning false (e.g. invalid UTF-8) must not call
     * wp_remote_post() with a malformed body — the send must be skipped and logged instead.
     */
    public function test_send_analytics_event_skips_send_when_json_encode_fails() {
        \WP_Mock::userFunction('wp_json_encode', array('return' => false));
        \WP_Mock::userFunction('wp_remote_post')->never();

        $method = new \ReflectionMethod(WC_Gateway_PayStand::class, 'send_analytics_event');
        $method->setAccessible(true);
        $method->invoke($this->wc_paystand, 'checkout_rendered', array());

        // WP_Mock fails the test if wp_remote_post was called; nothing else to assert.
        $this->assertTrue(true);
    }

    /**
     * send_analytics_event must never throw. A Throwable raised anywhere inside
     * (e.g. from wp_remote_post itself) must be swallowed and logged, not propagated —
     * this method is frequently invoked from inside a payment-processing catch block, and
     * an uncaught error here would fatal the entire request.
     */
    public function test_send_analytics_event_never_throws() {
        \WP_Mock::userFunction('wp_json_encode', array(
            'return' => function($data) { return json_encode($data); },
        ));

        \WP_Mock::userFunction('wp_remote_post', array(
            'return' => function() {
                throw new \RuntimeException('simulated transport failure');
            },
        ));

        $method = new \ReflectionMethod(WC_Gateway_PayStand::class, 'send_analytics_event');
        $method->setAccessible(true);

        $thrown = null;
        try {
            $method->invoke($this->wc_paystand, 'checkout_rendered', array());
        } catch (\Throwable $e) {
            $thrown = $e;
        }

        $this->assertNull($thrown, 'send_analytics_event must never let an internal Throwable propagate');
    }

    /**
     * process_wc_paystand_event fires processing_skipped when order_id is missing.
     */
    public function test_process_wc_paystand_event_logs_skipped_when_no_order_id() {
        $logged_events = array();

        \WP_Mock::userFunction('wp_json_encode', array(
            'return' => function($data) use (&$logged_events) {
                $logged_events[] = $data;
                return json_encode($data);
            },
        ));

        \WP_Mock::userFunction('wp_remote_post', array('return' => array()));
        \WP_Mock::userFunction('is_wp_error', array('return' => false));

        $event = array(
            'object'              => 'WC_Paystand_Event',
            'type'                => 'payment_complete',
            'save_payment_method' => false,
            // order_id intentionally absent
        );

        $this->wc_paystand->process_wc_paystand_event($event);

        $event_types = array_column($logged_events, 'event_type');
        $this->assertContains(
            'processing_skipped',
            $event_types,
            'A processing_skipped analytics event must fire when order_id is missing'
        );

        // Find the specific entry and check error_message
        $skipped = array_values(array_filter($logged_events, function($e) {
            return isset($e['event_type']) && $e['event_type'] === 'processing_skipped';
        }));
        $this->assertNotEmpty($skipped);
        $this->assertStringContainsString('no_order_id', $skipped[0]['error_message']);
    }

    /**
     * process_wc_paystand_event fires processing_skipped when order is not found.
     */
    public function test_process_wc_paystand_event_logs_skipped_when_order_not_found() {
        $logged_events = array();

        \WP_Mock::userFunction('wc_get_order', array('return' => false));

        \WP_Mock::userFunction('wp_json_encode', array(
            'return' => function($data) use (&$logged_events) {
                $logged_events[] = $data;
                return json_encode($data);
            },
        ));

        \WP_Mock::userFunction('wp_remote_post', array('return' => array()));
        \WP_Mock::userFunction('is_wp_error', array('return' => false));

        $event = array(
            'object'              => 'WC_Paystand_Event',
            'type'                => 'payment_complete',
            'save_payment_method' => false,
            'order_id'            => '42',
        );

        $this->wc_paystand->process_wc_paystand_event($event);

        $event_types = array_column($logged_events, 'event_type');
        $this->assertContains(
            'processing_skipped',
            $event_types,
            'A processing_skipped analytics event must fire when wc_get_order returns false'
        );

        $skipped = array_values(array_filter($logged_events, function($e) {
            return isset($e['event_type']) && $e['event_type'] === 'processing_skipped';
        }));
        $this->assertNotEmpty($skipped);
        $this->assertEquals('42', $skipped[0]['quote_id']);
        $this->assertStringContainsString('order_not_found', $skipped[0]['error_message']);
    }

    /**
     * process_wc_paystand_event fires processing_skipped (not a fatal
     * "Array to string conversion" error) when order_id arrives as a non-scalar value.
     */
    public function test_process_wc_paystand_event_logs_skipped_when_order_id_not_scalar() {
        $logged_events = array();

        \WP_Mock::userFunction('wp_json_encode', array(
            'return' => function($data) use (&$logged_events) {
                $logged_events[] = $data;
                return json_encode($data);
            },
        ));

        \WP_Mock::userFunction('wp_remote_post', array('return' => array()));
        \WP_Mock::userFunction('is_wp_error', array('return' => false));

        $event = array(
            'object'              => 'WC_Paystand_Event',
            'type'                => 'payment_complete',
            'save_payment_method' => false,
            'order_id'            => array('unexpected' => 'array'),
        );

        $this->wc_paystand->process_wc_paystand_event($event);

        $skipped = array_values(array_filter($logged_events, function($e) {
            return isset($e['event_type']) && $e['event_type'] === 'processing_skipped';
        }));
        $this->assertNotEmpty($skipped, 'A processing_skipped event must fire for a non-scalar order_id');
        $this->assertStringContainsString('order_id_not_scalar', $skipped[0]['error_message']);
    }

    /**
     * Regression guard: the pre-validation webhook_received log fired from the
     * WC_Paystand_Event branch must NEVER include order_id/payment_id — those come straight
     * from unauthenticated client POST data on this path (no server-side authenticity check
     * exists for it, per process_wc_paystand_event()'s own docblock).
     */
    public function test_webhook_received_excludes_unauthenticated_fields_on_js_callback_path() {
        $logged_events = array();

        \WP_Mock::userFunction('wc_get_order', array('return' => false));

        \WP_Mock::userFunction('wp_json_encode', array(
            'return' => function($data) use (&$logged_events) {
                $logged_events[] = $data;
                return json_encode($data);
            },
        ));

        \WP_Mock::userFunction('wp_remote_post', array('return' => array()));
        \WP_Mock::userFunction('is_wp_error', array('return' => false));

        // Attacker-controlled sourceId/order_id in the raw webhook payload.
        $_POST = array(
            'object'              => 'WC_Paystand_Event',
            'type'                => 'payment_complete',
            'save_payment_method' => false,
            'order_id'            => 'attacker-supplied-order-id',
            'sourceId'            => 'attacker-supplied-payment-id',
        );

        $this->wc_paystand->paystand_callback();

        $webhook_received_events = array_values(array_filter($logged_events, function($e) {
            return isset($e['event_type']) && $e['event_type'] === 'webhook_received';
        }));

        $this->assertNotEmpty($webhook_received_events, 'webhook_received must still fire on the JS-callback path');
        $this->assertEmpty(
            $webhook_received_events[0]['quote_id'],
            'webhook_received on the unauthenticated JS-callback path must not carry attacker-suppliable order_id'
        );
        $this->assertEmpty(
            $webhook_received_events[0]['payment_id'],
            'webhook_received on the unauthenticated JS-callback path must not carry attacker-suppliable payment_id'
        );

        $_POST = array();
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
