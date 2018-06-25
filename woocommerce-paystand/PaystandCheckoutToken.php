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

}