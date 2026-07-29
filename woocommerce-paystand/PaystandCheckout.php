<?php
/**
 * Created by PhpStorm.
 * User: aldo
 * Date: 6/25/18
 * Time: 1:25 PM
 *
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
 */

if (!function_exists('getISO3166_3_code')) {
    include_once plugin_dir_path(__FILE__) . 'includes/iso3166.php';
};

/**
 * Paystand Checkout
 */
abstract class PaystandCheckout
{
    private $return_url = null;
    private $data = null;
    private $type = null;
    private $environment = null;

    /**
     * Creates a new instance of PaystandCheckout
     */
    public function __construct($type, $environment, $data, $return_url)
    {
        $this->type = $type;
        $this->data = $data;
        $this->return_url = $return_url;
        $this->environment = $environment;
    }

    /**
     * Render container
     */
    public function renderContainer()
    {
        ?>
        <div id="ps_container_id"></div>
        <?php
    }

    /**
     * Render header
     */
    public function renderHeader()
    {
        $this->renderContainer();
    }

    /**
     * Render
     */
    public function render()
    {
        $data = $this->data;

        if ($data['show_payment_method']=='yes') {
            $this->renderHeader();
        } else {
            $this->renderContainer();
        }

        $this->renderBody();
    }

    /**
     * render body
     */
    public function renderBody()
    {

        $data = $this->data;
        $order = $data['order'];
        $return_url = $this->return_url;
        $environment = $this->environment;

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

        if (isset($_GET['processing']) && ($_GET['processing'] == 'true')) {
            ?>
            <div class="order-status" id="order_status">
              Your order is processing, please be patient.
            </div>

            <script>
              function fetchStatus()
              {
                jQuery.ajax({
                  url : '<?php echo site_url(); ?>/?wc-api=wc_gateway_paystand&action=fetch_payment_status&order_id=<?php echo esc_attr($order->get_order_number()); ?>',
                  type : 'get',
                  error : function(response){
                    console.log(response);
                  },
                  success : function( response ){
                    let success = (response == "posted" || response == "paid");
                    if (success) {
                      clearInterval(window.refreshIntervalId);
                      window.location = '<?php echo esc_attr($_GET['redirectUrl']) ?>';
                    }
                  }
                });
              }

              window.refreshIntervalId = setInterval(fetchStatus, 1000);
            </script>

            <?php
        } else {
            ?>

    <script
      type="text/javascript"
      id="ps_checkout"
      src="<?php echo esc_url($data['paystand_url'])?>js/paystand.checkout.js"
      ps-env="<?php echo esc_attr($environment)?>"
      ps-viewLogo="hide"
      ps-publishableKey="<?php echo esc_attr($data['publishable_key']) ?>"
      ps-containerId="ps_container_id"
      ps-mode="<?php echo esc_attr($data['render_mode'])?>"
      ps-show="true"
      ps-checkoutType="<?php echo esc_attr($data['checkout_type'])?>"
      ps-viewCheckout="<?php echo esc_attr($data['view_checkout'])?>"
      ps-paymentAmount="<?php echo esc_attr($order->get_total()) ?>"
      ps-viewClose="hide"
      ps-fixedAmount="true"
      ps-payerName="<?php echo esc_attr($billing_full_name)?>"
      ps-payerEmail="<?php echo esc_attr($billing_email_address)?>"
      ps-payerAddressStreet = "<?php echo esc_attr($billing_street)?>"
      ps-payerAddressCity = "<?php echo esc_attr($billing_city)?>"
      ps-payerAddressCountry = "<?php echo esc_attr($billing_country)?>"
      ps-payerAddressState = "<?php echo esc_attr($billing_state)?>"
      ps-payerAddressPostal = "<?php echo esc_attr($billing_postalcode)?>"
      ps-paymentMeta = '{ "order_id" : "<?php echo esc_attr($order_id)?>", "user_id":  "<?php echo esc_attr($data['user_id']) ?>", "source": "woocommerce" }'
      ps-paymentCurrency =  "<?php echo esc_attr($data['currency']) ?>"
      ps-width =  "<?php echo esc_attr($data['render_width']) ?>%"
      ps-customPreset = "<?php echo esc_attr($data['custom_preset']) ?>"
      ps-viewFunds = "<?php echo esc_attr($data['view_funds']) ?>"
      >

    </script>

        <?php
        // The status-poll fallback is only armed for an order that is still awaiting payment.
        // Checking here rather than client-side means an order already reported as posted/paid
        // (for example a paid order whose pay URL is re-opened) never arms the poll at all, so it
        // cannot redirect a payer who has not just paid, and costs no extra request to discover.
        $ps_poll_status = $order ? strtolower((string) $order->get_meta('paymentStatus')) : '';
        $ps_poll_enabled = !empty($return_url) && !empty($order)
            && !in_array($ps_poll_status, array('posted', 'paid'), true);
        ?>
        <script type="text/javascript">
            // Single redirect guard shared by the widget's completion callback and the
            // status-poll fallback further down, so whichever fires first wins and the
            // other becomes a no-op.
            var psRedirected = false;
            var psGo = function() {
                <?php if (!empty($return_url)) { ?>
                if (psRedirected) { return; }
                psRedirected = true;
                window.location.href = "<?php echo $return_url ?>";
                <?php } ?>
            };

            // Fallback for environments where the widget never invokes onceComplete — its realtime
            // channel can fail to deliver the completion event, which strands a payer who has
            // already paid on this page. Polls the same order-status endpoint and the same status
            // values the saved-token flow already relies on, and redirects once the webhook has
            // recorded the payment. Independent of the widget callback; the shared psRedirected
            // guard means whichever path fires first is the only one that navigates.
            //
            // Armed from onceLoaded rather than on page load, so merely opening the pay page (or
            // abandoning it) does not generate polling traffic. If the widget never loads the payer
            // cannot pay, so there is nothing for the poll to recover in that case.
            var psPollArmed = false;
            var psArmStatusPoll = function() {
                <?php if ($ps_poll_enabled) { ?>
                if (psPollArmed) { return; }
                psPollArmed = true;
                var STATUS_URL = <?php echo json_encode(site_url() . '/?wc-api=wc_gateway_paystand&action=fetch_payment_status&order_id=' . urlencode((string) $order->get_id())); ?>;
                var attempts = 0, maxAttempts = 100; // ~5 minutes at 3s
                var timer = setInterval(function() {
                    if (psRedirected || ++attempts > maxAttempts) {
                        clearInterval(timer);
                        return;
                    }
                    var pr = new XMLHttpRequest();
                    pr.open('GET', STATUS_URL, true);
                    pr.onload = function() {
                        var s = String(pr.responseText || '').trim().toLowerCase();
                        if (s === 'posted' || s === 'paid') {
                            clearInterval(timer);
                            psGo();
                        }
                    };
                    pr.send();
                }, 3000);
                <?php } ?>
            };

            if (typeof psCheckout === 'undefined') {
                // Without the widget script there is nothing to hook; log rather than throw so the
                // status-poll fallback below still runs and can return the payer to the receipt page.
                if (window.console && window.console.error) {
                    window.console.error('[paystand] psCheckout is undefined - the checkout widget script did not load');
                }
            } else {
            psCheckout.onceLoaded(function() {
                psArmStatusPoll();
                psCheckout.onceComplete( function(result) {
                    //TODO:  It could be the case that the payment  is not successful... check response and do not send xhr
                    // TODO:  Check that payment was completed succesfully (not failed)

                    try {
                        // The savePaymentMethod checkbox is only rendered when the gateway's
                        // "Allow to Save Payment Method" setting is enabled (see render()), so this
                        // element is absent whenever that setting is off. Reading .checked straight
                        // off the lookup threw a TypeError on the first line of this handler, which
                        // aborted it before the XHR and the redirect ever ran — the payer was left
                        // on the order-pay page even though the async webhook still completed the
                        // order. No checkbox means saving is disabled, so false is the right value.
                        var savePaymentMethodEl = document.getElementById('savePaymentMethod');
                        var savePaymentMethod = savePaymentMethodEl ? savePaymentMethodEl.checked : false;

                        var response = result.response || {};
                        var payment = response.data || {};

                        // Extract fee split data from payment response
                        var feeSplit = payment.feeSplit || {};
                        var payerDiscount = feeSplit.payerDiscount || 0;
                        var payerTotalFees = feeSplit.payerTotalFees || 0;
                        var payerTotal = feeSplit.payerTotal || payment.amount || 0;

                        var wc_data = {
                            object: "WC_Paystand_Event",
                            type: "payment_complete",
                            user_id: "<?php echo esc_attr($data['user_id']) ?>",
                            order_id: "<?php echo esc_attr($order_id) ?>",
                            save_payment_method: savePaymentMethod,
                            data: payment,
                            payerDiscount: payerDiscount,
                            payerTotalFees: payerTotalFees,
                            payerTotal: payerTotal,
                            sourceType: "Payment",
                            sourceId: payment.id,
                        };

                        // Redirect as soon as the WC backend responds, with a 3-second hard timeout
                        // so a slow server response never leaves the user stuck on the order-pay page.
                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', '/?wc-api=wc_gateway_paystand', true);
                        xhr.setRequestHeader('Content-type', 'application/json');
                        xhr.onload = psGo;
                        xhr.onerror = psGo;
                        xhr.ontimeout = psGo;
                        xhr.timeout = 8000;
                        xhr.send(JSON.stringify(wc_data));
                        // Deliberate: if this POST takes longer than 3s the payer is redirected
                        // anyway and the in-flight request is abandoned. Order completion does not
                        // depend on it — Paystand's async webhook completes the order — so the only
                        // thing lost on a slow server is the save_payment_method/fee payload. Not
                        // stranding a payer who has already paid is the more important guarantee.
                        // Do not "fix" this timeout without accounting for that trade.
                        setTimeout(psGo, 3000);
                    } catch (e) {
                        // The payment already succeeded at this point, and the order is completed
                        // independently by Paystand's asynchronous webhook — so a failure here must
                        // never strand the payer on the order-pay page.
                        if (window.console && window.console.error) {
                            window.console.error('[paystand] checkout completion handler failed', e);
                        }
                        psGo();
                    }
                });
            });
            } // end: psCheckout defined
        </script>

        <div id="ps_checkout_load" style= " text-align: center" >
        </div>
            <?php
        }
        ?>
        <?php
    }
}
