=== WooCommerce-PayStand ===
Contributors: paystand
Tags: woocommerce, paystand, payment
Requires at least: 3.5
Tested up to: 3.9.1
Stable tag: 1.0.0

The WooCommerce-Paystand plugin adds a WooCommerce payment gateway for PayStand.

== Description ==

[PayStand](http://www.paystand.com) is a modern payment processing service for your website. [Sign up for PayStand](http://www.paystand.com/signup) and you can use this plugin to process payments for your WooCommerce site.

== Installation ==

1. Upload woocommerce-paystand to the `/wp-content/plugins` folder in your WooCommerce WordPress site.
2. Activate the plugin through the 'Plugins' menu on the WordPress admin page.
3. Under WooCommerce > Settings > Checkout > PayStand, add your Org ID and Public API Key and choose whether you want to use the sandbox server for testing or the live production server.
4. Copy the Webhook Url from the settings page and go to your [PayStand dashboard](https://www.paystand.com/login) or your [PayStand Sandbox Dashboard](https://www.paystand.co/login) and enter it under Settings > Checkout Features > Webhook Url.
5. Go shopping on your WooCommerce site and pay with PayStand!

== Frequently Asked Questions ==

= What is PayStand? =

PayStand provides modern payment processing solutions.

Use PayStand (and this plugin) to process payments on your site. Check it out at [paystand.com](http://www.paystand.com/)!

= What does this plugin do? =

It adds payment processing to your WooCommerce site using PayStand.

= What version of WooCommerce does this plugin work with? =

This plugin works with WooCommerce version 2.1.0 and above.

== Troubleshooting ==

If you are having trouble with the checkout process or your orders are not marked as paid and updated from pending to processing, turn on logging.
To turn on logging, under WooCommerce > Settings > Checkout > PayStand, select the checkbox labeled "Enable logging" and save.
The location of the log file is displayed below the checkbox.
After checkout you should see something like the following in the log file:
...
07-21-2014 @ 21:20:55 - Generating payment form for order #94. Notify URL: https://www.example.com/wp/?wc-api=WC_Gateway_PayStand
07-21-2014 @ 21:21:37 - paystand_callback
07-21-2014 @ 21:21:37 - psn: Array
...
07-21-2014 @ 21:21:38 - Payment success: 1
...
If you see the "Generating" line but not the "paystand_callback" line then the PSN (PayStand Notification) webhook is not reaching the WooCommerce PayStand plugin.
You should check that the webhook url is entered properly in your PayStand account dashboard under Settings > Checkout Features > Webhook Url.

== Changelog ==

= 1.0 =
* First version. Please provide feedback.

