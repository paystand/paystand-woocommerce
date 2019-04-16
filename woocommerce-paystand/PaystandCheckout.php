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

    public function render_container(){
        ?>
        <div id="ps_container_id"></div>
        <?php
    }

    public function render_header(){
        $this->render_container();
    }

    public function render( ){
        $data = $this->data;

        if($data['show_payment_method']=='yes'){
            $this->render_header();
        }else{
            $this->render_container();
        }

        $this->render_body();
    }

    public function render_body( ){

        $data = $this->data;
        $order = $data['order'];
        $return_url = $this->return_url;
        $environment = ($data['testmode'] == 'no') ? 'live' : 'sandbox';

        if ($order) {
            $billing_full_name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
            $billing_email_address = $order->get_billing_email();
            $billing_street = trim($order->get_billing_address_1() . ' ' . $order->get_billing_address_2());
            $billing_city = $order->get_billing_city();
            $billing_postalcode = $order->get_billing_postcode();
            $billing_state =  $order->get_billing_state();
            $billing_country = getISO3166_3_code($order->get_billing_country());
            $order_id = $data['order_id'];
        }

        if($_GET['processing'] == 'true'){
          ?>
            <div class="order-status" id="order_status">
              Your order is processing, please be patient.
            </div>

            <script>
              function fetchStatus()
              {
                jQuery.ajax({
                  url : '<?php echo site_url(); ?>/?wc-api=wc_gateway_paystand&action=fetch_payment_status&order_id=<?php echo $order->get_order_number(); ?>',
                  type : 'get',
                  error : function(response){
                    console.log(response);
                  },
                  success : function( response ){
                    let success = (response == "posted" || response == "paid");
                    if (success) {
                      clearInterval(window.refreshIntervalId);
                      window.location = '<?php echo $_GET['redirectUrl'] ?>';
                    }
                  }
                });
              }

              window.refreshIntervalId = setInterval(fetchStatus, 1000);
            </script>

            <?
        }
        else {
            ?>

    <script
      type="text/javascript"
      id="ps_checkout"
      src="<?=$data['paystand_url']?>js/paystand.checkout.js"
      ps-env="<?=$environment?>"
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
      ps-viewFunds = "<?= $data['view_funds'] ?>"
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
            <?
        }
        ?>
        <?php
    }
}
