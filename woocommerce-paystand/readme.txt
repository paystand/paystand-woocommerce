=== Paystand for WooCommerce ===
Contributors: paystand
Tags: woocommerce, paystand, payment, credit card, bank, ach, bitcoin
Requires at least: 3.5
Tested up to: 6.0.1
Stable tag: 2.4.9

The Paystand for WooCommerce plugin adds a WooCommerce payment gateway for Paystand.

== Description ==

Paystand is the only B2B payment processing service right for your business.  Accept payments from your customers' Cards (credit and debit) and Bank Accounts (ACH and Verified Bank Transfers), re-engage those payment methods with tokens securely stored with your users' authenticated profiles, automate recurring payments with subscriptions and schedules, handle multiple currency presentment, and much more, all wrapped in a predictable, accountable SaaS subscription.  Come join the leader in digital B2B payments.  Contact Paystand at [sales@paystand.com](mailto:sales@paystand.com) or visit our website at [Paystand.com](https://paystand.com/).
== Installation ==

Note:  To access our v1.x plugins, please contact [support@paystand.com](mailto:support@paystand.com)

1. Upload woocommerce-paystand to the `/wp-content/plugins` folder in your WooCommerce WordPress site.
2. Activate the plugin through the 'Plugins' menu on the WordPress admin page.
3. Under WooCommerce > Settings > Checkout > Paystand, click the Enable Paystand checkbox and add your Paystand Credentials.  Be sure to click the Save Settings button at the bottom of the page.  Your Credentails  can be found in your Paystand dashboard under Integrations > API Configuration Values.
4. Copy the Webhook Url from the WooCommerce settings page and go to your [Paystand dashboard](https://dashboard.paystand.com/v2/) and enter it under Integrations > Webhook Event URLs.
5. Go shopping on your WooCommerce site and pay with Paystand!

If you are interested in using the Paystand sandbox environment for testing, please contact us for additional details.

=== Multiple Currencies ===

Paystand Checkout Now Support Multiple currencies. Out of the box, this plugin supports USD, CAD, MXN, EUR, GBP and AUD. However if you need another currency which is
unsupported, please contact us (support@paystand.com) and we might be able to support your currency on request.

== Frequently Asked Questions ==

= What is Paystand? =

Paystand provides modern payment processing solutions.

Use Paystand (and this plugin) to process payments on your site. Check it out at [paystand.com](http://www.paystand.com/)!

= What does this plugin do? =

It adds payment processing to your WooCommerce site using Paystand.

= What version of WooCommerce does this plugin work with? =

This plugin works with WooCommerce version 3.5.0 and above.

== Upgrade Notice ==

To upgrade to v2.0.x from Version 1.0.x  please uninstall the old plugin and install the new version. Version 2.0.x has a lot of
changes and is not backwards compatible with previous versions.

== Troubleshooting ==

If you are having trouble with the checkout process or your orders are not marked as paid and updated from pending to processing, turn on logging.
To turn on logging, under WooCommerce > Settings > Checkout > Paystand, select the checkbox labeled "Enable logging" and save.
The location of the log file is displayed below the checkbox.
After checkout you should see something like the following in the log file:
...
07-21-2014 @ 21:20:55 - Generating payment form for order #94. Notify URL: https://www.example.com/wp/?wc-api=WC_Gateway_Paystand
07-21-2014 @ 21:21:37 - paystand_callback
07-21-2014 @ 21:21:37 - psn: Array
...
07-21-2014 @ 21:21:38 - Payment success: 1
...
If you see the "Generating" line but not the "paystand_callback" line then the PSN (Paystand Notification) webhook is not reaching the Paystand for WooCommerce plugin.
You should check that the webhook url is entered properly in your Paystand account dashboard under Settings > Checkout Features > Webhook Url.
Also, make sure the Enable Paystand checkbox is selected in your WordPress admin under WooCommerce > Settings > Checkout > Paystand.

== Feedback ==

If you have any questions or feedback, please email us at support@paystand.com.

== Changelog ==
= 2.4.9 =
* Security improvements
= 2.4.8 =
* Tested with woocommerce version 6.0.1
= 2.4.4 =
* Updated instructions for finding webhook urls section
* Updated works with to version 3.5.0
= 2.4.3 =
* Syncing config.json
= 2.4.2 =
* Tested with woocommerce version 5.8.2
= 2.4.1 =
* Send the splitFee object with fund on file payment.
= 2.4.0 =
* Bank payments verify account balance
* Support for adding custom checkout preset
* New Paystand logo
= 2.3.2 =
* Fix failed payments marking orders as successful
= 2.3.1 =
* Fix error while calculating the fee for Fund in File transactions
= 2.3.0 =
* Added processing fee when customer select pay with saved methods
* Disabling/ordering payment methods rails ach,bank,card
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

