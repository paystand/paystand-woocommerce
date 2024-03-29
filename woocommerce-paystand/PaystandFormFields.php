<?php

/**
Copyright 2022 Paystand Inc.

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at


Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.

@category Paystand
@package  Paystand
@author   Paystand <noreply@paystand.com>
@license  http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
@link     https://github.com/paystand/paystand-woocommerce
 **/

/**
 * Contains form fields for Paystand Checkout Configuration
 * @category Paystand
 */
class PaystandFormFields
{
    public static function getInitFormFields($data)
    {
        return array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'woocommerce-paystand'),
                'type' => 'checkbox',
                'label' => __('Enable Paystand', 'woocommerce-paystand'),
                'default' => 'yes'
            ),
            'publishable_key' => array(
                'title' => __('Paystand Publishable Key', 'woocommerce-paystand'),
                'type' => 'text',
                'description' => __('Your Paystand publishable key from API configuration values in your Paystand Integrations dashboard.', 'woocommerce-paystand'),
                'default' => '',
                'desc_tip' => true,
            ),
            'customer_id' => array(
              'title' => __('Paystand Customer Id ', 'woocommerce-paystand'),
              'type' => 'text',
              'description' => __('Your Paystand customer_id from API configuration values in your Paystand Integrations dashboard.', 'woocommerce-paystand'),
              'default' => '',
              'desc_tip' => true,
            ),
            'client_id' => array(
              'title' => __('Paystand Client Id ', 'woocommerce-paystand'),
              'type' => 'text',
              'description' => __('Your Paystand client_id from API configuration values in your Paystand Integrations dashboard.', 'woocommerce-paystand'),
              'default' => '',
              'desc_tip' => true,
            ),
            'client_secret' => array(
              'title' => __('Paystand Client Secret', 'woocommerce-paystand'),
              'type' => 'text',
              'description' => __('Your Paystand client_secret from API configuration values in your Paystand Integrations dashboard.', 'woocommerce-paystand'),
              'default' => '',
              'desc_tip' => true,
            ),
            'webhook' => array(
                'title' => __('Webhook', 'woocommerce-paystand'),
                'type' => 'title',
                'description' => 'Set your webhook url to <code>' . $data['notify_url'] . '</code> in your <a href="https://www.paystand.com/login" target="_blank">Paystand dashboard</a> under Settings > Checkout Features',
            ),

            'behavior_title' => array('title' => __('Payment Processing Behavior', 'woocommerce-paystand'),'type' => 'title'),
            'show_payment_method' => array(
              'title' => __('Allow to Save Payment Method', 'woocommerce-paystand'),
              'type' => 'checkbox',
              'label' => __('Enable saving payment methods', 'woocommerce-paystand'),
              'default' => 'yes',
              'description' => __('Selecting this will allow your customers to save their Paystand Payment Methods for future use')
            ),
            'enable_saved_payment_checkout' => array(
              'title' => __('Enable Saved Payment Checkout', 'woocommerce-paystand'),
              'type' => 'checkbox',
              'label' => __('Enable saved payment checkout', 'woocommerce-paystand'),
              'default' => 'yes',
              'description' => __('Selecting this will allow your customers to use their saved Paystand Payment Methods in checkout')
            ),
            'auto_processing' => array(
                'title' => __('Auto ACH/Bank Clearance', 'woocommerce-paystand'),
                'type' => 'checkbox',
                'label' => __('Automatic Order Set to "Processing"', 'woocommerce-paystand'),
                'default' => 'no',
                'description' => __(
                    'Selecting this will result in WooCommerce orders being given the status "Processing" any time your customer succeeds ' .
                    'in checkout, regardless of the time it takes funds to be processed and transferred. This feature is useful when '.
                    'using WooCommerce order status for non-financial business decisions (e.g., shipping).  '
                )
              ),
              'auto_complete' =>  array(
                'title' => __('Order auto-completion', 'woocommerce-paystand'),
                'type' => 'checkbox',
                'label' => __('Automatically complete paid orders', 'woocommerce-paystand'),
                'default' => 'no',
                'description' => 'Setting this will cause all orders to be automatically updated from processing to completed upon successful payment.  This is useful for situations where all of your orders do not require fulfillment, such as donations or virtual products.',
              ),
              'on_complete_status' => array(
                'title' => __('Order status on successful payment', 'woocommerce-paystand'),
                'type' => 'select',
                'label' => __('Select status', 'woocommerce-paystand'),
                'default' => 'default',
                'description' => __('Choose order status after a successful payment', 'woocommerce-paystand'),
                'options' => array('default' =>'Default', 'processing' => 'Processing', 'pending' => 'Pending payment', 'on-hold' => 'On hold')
              ),
              'checkout_description' => array(
                'title' => __('Checkout Description', 'woocommerce-paystand'),
                'type' => 'text',
                'description' => __('The message that will appear in the checkout page', 'woocommerce-paystand'),
                'default' => 'Use Paystand\'s modern checkout to pay securely with any major credit card, bank, or ACH',
                'desc_tip' => true,
              ),
              'view_funds' => array(
                'title' => __('Type of funds to show ', 'woocommerce-paystand'),
                'type' => 'text',
                'description' => __('The type of funds to show during checkout. It can be ach, bank or card; each one separated by a comma. Depending on your Paystand plan some might not be available.'),
                'default' => 'ach,bank,card',
                'desc_tip' => true,
              ),

            'style_title' => array( 'title' => __('Styling Settings', 'woocommerce-paystand'),'type' => 'title'),
            'view_checkout' => array(
              'title' => __('View Checkout Mode', 'woocommerce-paystand'),
              'type' => 'select',
              'label' => __('Select Checkout View Mode', 'woocommerce-paystand'),
              'default' => 'mobile',
              'description' => __('Defines the way how Checkout will be shown to the client'),
              'options' => array('default' => 'default',
                'portal-xlarge' => 'portal-xlarge','portal-large' => 'portal-large','portal-medium' => 'portal-medium',
                'portal-small' => 'portal_small', 'portal' => 'portal', 'mobile' => 'mobile'
              )
            ),
            'render_mode' => array(
              'title' => __('Checkout Rendering Mode', 'woocommerce-paystand'),
              'type' => 'select',
              'label' => __('Select Checkout Rendering Mode', 'woocommerce-paystand'),
              'default' => 'embed',
              'description' => __('Defines Whether checkout should render as a modal popup or an embedded checkout.'),
              'options' => array('embed' => 'embed','modal' => 'modal')
            ),
            'width' => array(
              'title' => __('Checkout Width Relative to Page (%)', 'woocommerce-paystand'),
              'type' => 'number',
              'min' => 1,
              'max' => 100,
              'label' => __('The % of width that Checkout will take relative to the page where it is placed', 'woocommerce-paystand'),
              'default' => 70,
              'description' => __('The % of width that Checkout will take relative to the page where it is placed.', 'woocommerce-paystand'),
            ),
           'custom_preset' => array(
               'title' => __('Key for custom preset', 'woocommerce-paystand'),
               'type' => 'text',
               'description' => __('On the Paystand dashboard integrations > billing portal page you can create custom presets to customize checkout'),
               'desc_tip' => true,
           ),
            'dev_title' => array('title' => __('Development Settings', 'woocommerce-paystand'),'type' => 'title'),
            'testmode' => array(
                'title' => __('Paystand Sandbox', 'woocommerce-paystand'),
                'type' => 'checkbox',
                'label' => __('Use Paystand Sandbox Server', 'woocommerce-paystand'),
                'default' => 'no',
              ),
              'debug' => array(
                  'title' => __('Debug Log', 'woocommerce-paystand'),
                  'type' => 'checkbox',
                  'label' => __('Enable logging', 'woocommerce-paystand'),
                  'default' => 'no',
              )

            );
    }
}
