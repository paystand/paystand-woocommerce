=== PayStand for WooCommerce ===
Contributors: paystand
Tags: woocommerce, paystand, payment, credit card, echeck, ach, bitcoin
Requires at least: 3.5
Tested up to: 4.9.6
Stable tag: 2.2.2

The PayStand for WooCommerce plugin adds a WooCommerce payment gateway for PayStand.

== Description ==

PayStand is the only B2B payment processing service right for your business.  Accept payments from your customers' Cards (credit and debit) and Bank Accounts (ACH and Verified Bank Transfers), re-engage those payment methods with tokens securely stored with your users' authenticated profiles, automate recurring payments with subscriptions and schedules, handle multiple currency presentment, and much more, all wrapped in a predictable, accountable SaaS subscription.  Come join the leader in digital B2B payments.  Contact PayStand at [sales@paystand.com](mailto:sales@paystand.com) or visit our website at [PayStand.com](https://paystand.com/).
== Installation ==

Note:  To access our v1.x plugins, please contact [support@paystand.com](mailto:support@paystand.com)

1. Upload woocommerce-paystand to the `/wp-content/plugins` folder in your WooCommerce WordPress site.
2. Activate the plugin through the 'Plugins' menu on the WordPress admin page.
3. Under WooCommerce > Settings > Checkout > PayStand, click the Enable PayStand checkbox and add your Paystand Credentials.  Be sure to click the Save Settings button at the bottom of the page.  Your Credentails  can be found in your PayStand dashboard under Integrations > API Configuration Values.
4. Copy the Webhook Url from the WooCommerce settings page and go to your [PayStand dashboard](https://dashboard.paystand.com/v2/) and enter it under Settings > Checkout Features > Webhook Url.
5. Go shopping on your WooCommerce site and pay with PayStand!

If you are interested in using the PayStand sandbox environment for testing, please contact us for additional details.

=== Multiple Currencies ===

Paystand Checkout Now Support Multiple currencies. Out of the box, this plugin supports USD, CAD, MXN, EUR, GBP and AUD. However if you need another currency which is
unsupported, please contact us (support@paystand.com) and we might be able to support your currency on request.

== Frequently Asked Questions ==

= What is PayStand? =

PayStand provides modern payment processing solutions.

Use PayStand (and this plugin) to process payments on your site. Check it out at [paystand.com](http://www.paystand.com/)!

= What does this plugin do? =

It adds payment processing to your WooCommerce site using PayStand.

= What version of WooCommerce does this plugin work with? =

This plugin works with WooCommerce version 3.0.0 and above.

== Upgrade Notice ==

To upgrade to v2.0.x from Version 1.0.x  please uninstall the old plugin and install the new version. Version 2.0.x has a lot of 
changes and is not backwards compatible with previous versions.

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
If you see the "Generating" line but not the "paystand_callback" line then the PSN (PayStand Notification) webhook is not reaching the PayStand for WooCommerce plugin.
You should check that the webhook url is entered properly in your PayStand account dashboard under Settings > Checkout Features > Webhook Url.
Also, make sure the Enable PayStand checkbox is selected in your WordPress admin under WooCommerce > Settings > Checkout > PayStand.

== Feedback ==

If you have any questions or feedback, please email us at support@paystand.com.

== Changelog ==

= 2.2.2 =
* Added processing fee when customer select pay with saved methods
= 2.2.1 =
* Disabling/ordering payment methods rails ach,echeck,card	
= 2.2.0 =
* Add new Administration checkbox for control Save_Payment_Method's feature, by default flag is enable then will be required to uncheck to hide from users.
= 2.1.1 =
* Update version
= 2.1.0 =
* Add Functionality to automatically set orders as "processing" as soon as a Checkout payment is done by the client.
= 2.0.1 = 
* Bug fix for invalid tag in checkout options
= 2.0.0 = 
* Migrate to new Paystand APIs
= 1.0.4 =
* Minor update to payment page and PSN handling.
= 1.0.3 =
* Minor update to settings page.
= 1.0.2 =
* Minor update to PSN verification.
= 1.0.1 =
* Minor update to incorporate feedback.
= 1.0.0 =
* First version.  Please provide feedback.  You can email us at support@paystand.com.

