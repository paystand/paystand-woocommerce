<?php

/**
 * Contains form fields for Paystand Checkout Configuration
 */
class PaystandFormFields
{
    public static function get_init_form_fields($data) {
       return array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'woocommerce-paystand'),
                'type' => 'checkbox',
                'label' => __('Enable PayStand', 'woocommerce-paystand'),
                'default' => 'yes'
            ),
            'publishable_key' => array(
                'title' => __('PayStand Publishable Key', 'woocommerce-paystand'),
                'type' => 'text',
                'description' => __('Your PayStand publishable key from API configuration values in your Paystand Integrations dashboard.', 'woocommerce-paystand'),
                'default' => '',
                'desc_tip' => true,
            ),
            'customer_id' => array(
              'title' => __('PayStand Customer Id ', 'woocommerce-paystand'),
              'type' => 'text',
              'description' => __('Your PayStand customer_id from API configuration values in your Paystand Integrations dashboard.', 'woocommerce-paystand'),
              'default' => '',
              'desc_tip' => true,
            ),
            'client_id' => array(
              'title' => __('PayStand Client Id ', 'woocommerce-paystand'),
              'type' => 'text',
              'description' => __('Your PayStand client_id from API configuration values in your Paystand Integrations dashboard.', 'woocommerce-paystand'),
              'default' => '',
              'desc_tip' => true,
            ),
            'client_secret' => array(
              'title' => __('PayStand Client Secret', 'woocommerce-paystand'),
              'type' => 'text',
              'description' => __('Your PayStand client_secret from API configuration values in your Paystand Integrations dashboard.', 'woocommerce-paystand'),
              'default' => '',
              'desc_tip' => true,
            ),
            'webhook' => array(
                'title' => __('Webhook', 'woocommerce-paystand'),
                'type' => 'title',
                'description' => 'Set your webhook url to <code>' . $data['notify_url'] . '</code> in your <a href="https://www.paystand.com/login" target="_blank">PayStand dashboard</a> under Settings > Checkout Features',
            ),
            
            'behavior_title' => array('title' => __('Payment Processing Behavior', 'woocommerce-paystand'),'type' => 'title'),
            'show_payment_method' => array(
              'title' => __('Allow to Save Payment Method', 'woocommerce-paystand'),
              'type' => 'checkbox',
              'label' => __('Show Add Payment Method feature', 'woocommerce-paystand'),
              'default' => 'yes',
              'description' => __('Selecting this will result in WooCommerce Add Payment Method feature shows')
            ),
            'auto_processing' => array(
                'title' => __('Auto ACH/eCheck Clearance', 'woocommerce-paystand'),
                'type' => 'checkbox',
                'label' => __('Automatic Order Set to "Processing"', 'woocommerce-paystand'),
                'default' => 'no',
                'description' => __('Selecting this will result in WooCommerce orders being given the status "Processing" any time your customer succeeds ' . 
                    'in checkout, regardless of the time it takes funds to be processed and transferred. This feature is useful when '.
                    'using WooCommerce order status for non-financial business decisions (e.g., shipping).  ')
              ),
              'auto_complete' =>  array(
                'title' => __('Order auto-completion', 'woocommerce-paystand'),
                'type' => 'checkbox',
                'label' => __('Automatically complete paid orders', 'woocommerce-paystand'),
                'default' => 'no',
                'description' => 'Setting this will cause all orders to be automatically updated from processing to completed upon successful payment.  This is useful for situations where all of your orders do not require fulfillment, such as donations or virtual products.',
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
            'dev_title' => array('title' => __('Development Settings', 'woocommerce-paystand'),'type' => 'title'),
            'testmode' => array(
                'title' => __('PayStand Sandbox', 'woocommerce-paystand'),
                'type' => 'checkbox',
                'label' => __('Use PayStand Sandbox Server', 'woocommerce-paystand'),
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