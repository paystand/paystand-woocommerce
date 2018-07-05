<?php
/**
 * Created by PhpStorm.
 * User: aldo
 * Date: 6/25/18
 * Time: 1:25 PM
 */

if (!function_exists('getISO3166_3_code')) {
    include_once( plugin_dir_path( __FILE__ ) . 'includes/iso3166.php');
};

abstract class PaystandCheckout
{
    private $return_url = null;
    private $data = null;
    private $type = null;

    public function __construct($type , $data, $return_url){
        $this->type = $type;
        $this->data = $data;
        $this->return_url = $return_url;
    }

    public function render_header(){
        ?>
        <div id="ps_container_id"></div>


        <?php
    }

    public function render( ){
        $this->render_header();
        $this->render_body();
    }

    public function render_body( ){

        $data = $this->data;
        $order = $data['order'];
        $return_url = $this->return_url;

        if ($order) {
            $billing_full_name = trim($order->billing_first_name . ' ' . $order->billing_last_name);
            $billing_email_address = $order->billing_email;
            $billing_street = trim($order->get_billing_address_1() . ' ' . $order->get_billing_address_2());
            $billing_city = $order->billing_city;
            $billing_postalcode = $order->billing_postcode;
            $billing_state =  $order->billing_state;
            $billing_country = getISO3166_3_code($order->billing_country);
            $order_id = $data['order_id'];
        }
        ?>

    <script
      type="text/javascript"
      id="ps_checkout"
      src="<?=$data['paystand_url']?>js/paystand.checkout.js"
      ps-viewLogo="hide"      
      ps-publishableKey="<?= $data['publishable_key'] ?>"
      ps-containerId="ps_container_id"
      ps-mode="<?=$data['render_mode']?>"
      ps-show="true"
      ps-checkoutType="<?=$data['checkout_type']?>"
      ps-viewCheckout="<?=$data['view_checkout']?>"
      ps-paymentAmount="<?= $order->order_total ?>"
      ps-viewClose="hide"
      ps-fixedAmount="true"
      ps-payerName="<?=$billing_full_name?>"
      ps-payerEmail="<?=$billing_email_address?>"
      ps-payerAddressStreet = "<?=$billing_street?>"
      ps-payerAddressCity = "<?=$billing_city?>"
      ps-payerAddressCountry = "<?=$billing_country?>"
      ps-payerAddressState = "<?=$billing_state?>"
      ps-payerAddressPostal = "<?=$billing_postalcode?>"
      ps-paymentMeta = '{ "order_id" : "<?=$order_id?>", "user_id":  "<?= $data['user_id'] ?>" }'
      ps-paymentCurrency =  "<?= $data['currency'] ?>"
      ps-width =  "<?= $data['render_width'] ?>%"      
      >
      
    </script>

        <script type="text/javascript">
            psCheckout.onceLoaded(function() {
                psCheckout.onceComplete( function(result) {
                    //TODO:  It could be the case that the payment  is not successful... check response and do not send xhr
                    // TODO:  Check that payment was completed succesfully (not failed)

                    if (document.getElementById('savePaymentMethod').checked == true) {
                        // If "remember me" option is selected, send request to WooCommerce to save card
                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', '/?wc-api=wc_gateway_paystand', true);
                        xhr.setRequestHeader('Content-type', 'application/json');
                        xhr.onload = function () {
                            // We move to the "complete" screen once we get the response
                            <?php
                                if(!empty($return_url)){
                            ?>
                                    window.location.href = "<?= $return_url ?>" ;
                            <?php
                                }
                            ?>
                        };
                        var data = {
                            object: "WC_Paystand_Event",
                            type:"save_payment",
                            user_id : "<?=$data['user_id'] ?>",
                            data: result.response.data
                        };
                        xhr.send(JSON.stringify(data));
                    } else {
                        <?php
                        if(!empty($return_url)){
                        ?>
                        window.location.href = "<?= $return_url ?>" ;
                        <?php
                        }
                        ?>
                    }
                });
            });
        </script>

        <div id="ps_checkout_load" style= " text-align: center" >
        </div>
        <?php
    }
}