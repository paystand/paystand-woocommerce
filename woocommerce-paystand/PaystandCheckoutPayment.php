<?php
/**
 * Created by PhpStorm.
 * User: aldo
 * Date: 6/25/18
 * Time: 2:48 PM
 */

include_once( plugin_dir_path( __FILE__ ) . 'PaystandCheckout.php');

class PaystandCheckoutPayment extends PaystandCheckout
{

    public function __construct($type , $data, $return_url){
        parent::__construct($type , $data, $return_url);
    }

    public function render_header(){
        ?>
        <label for= "savePaymentMethod" style ="text-align: center;">
            <input type="checkbox" id="savePaymentMethod", name="savePaymentMethod" value="Save Pament Method"/>
            Save This Payment Method
        </label>
        <?php
        parent::render_header();

    }

}