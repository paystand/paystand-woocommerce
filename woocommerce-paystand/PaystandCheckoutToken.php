<?php
/**
 * Created by PhpStorm.
 * User: aldo
 * Date: 6/25/18
 * Time: 2:49 PM
 */
include_once( plugin_dir_path( __FILE__ ) . 'PaystandCheckout.php');

class PaystandCheckoutToken extends PaystandCheckout
{

    public function __construct($type , $data, $return_url){
        parent::__construct($type , $data, $return_url);
    }

    public function render_header(){
        ?>
        <label for= "savePaymentMethod" style ="text-align: center; visibility: hidden;">
            <input type="checkbox" checked id="savePaymentMethod" name="savePaymentMethod" value="Save Pament Method"/>
            Save This Payment Method
        </label>
        <?php
        parent::render_header();

    }
}