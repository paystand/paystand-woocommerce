<?php
/**
 * Created by PhpStorm.
 * User: aldo
 * Date: 6/25/18
 * Time: 2:49 PM
 */
require_once plugin_dir_path(__FILE__) . 'PaystandCheckout.php';

class PaystandCheckoutToken extends PaystandCheckout
{

    public function __construct($type, $environment, $data, $return_url)
    {
        parent::__construct($type, $environment, $data, $return_url);
    }

    public function renderHeader()
    {
        ?>
        <label for= "savePaymentMethod" style ="text-align: center; visibility: hidden;">
            <input type="checkbox" checked id="savePaymentMethod" name="savePaymentMethod" value="Save Payment Method"/>
            Save This Payment Method
        </label>
        <?php
        parent::renderHeader();
    }
}
