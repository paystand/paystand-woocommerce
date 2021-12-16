<?php
/**
 * Created by PhpStorm.
 * User: aldo
 * Date: 6/25/18
 * Time: 1:25 PM
 */

include_once( plugin_dir_path( __FILE__ ) . 'PaystandCheckoutPayment.php');
include_once( plugin_dir_path( __FILE__ ) . 'PaystandCheckoutToken.php');

class PaystandCheckoutFactory
{

    public static function build($type, $environment, $data, $return_url){

        switch($type){
            case 'checkout_payment':
                return new PaystandCheckoutPayment($type, $environment, $data, $return_url);
            break;
            case 'checkout_token':
                return new PaystandCheckoutToken($type, $environment, $data, $return_url);
            break;
            default:
                throw Exception('Unsupported checkout type');
        }

    }

}
