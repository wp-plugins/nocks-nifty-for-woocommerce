<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require 'vendor/autoload.php';
require 'includes/currency-nlg.php';
require 'includes/currency-btc.php';
require 'includes/ajax-price-estimate.php';

use GuzzleHttp\Client;

/**
 * Plugin Name: Nocks Nifty WooCommerce
 * Author: Nocks
 * Plugin URI: https://nocks.nl
 * Description: Payment gateway for Nocks
 * Version: 0.0.1
 */

add_action( 'plugins_loaded', 'init_nocks_nifty' );

wp_enqueue_script('jquery');

add_action( 'wp_enqueue_scripts', 'nocks_nifty_scripts');
function nocks_nifty_scripts() {
    wp_enqueue_style( 'nocks_nifty_css', plugins_url('assets/css/style.css', __FILE__));
    wp_enqueue_script( 'nocks_nifty_js', plugins_url('assets/js/script.js', __FILE__));
}

function init_nocks_nifty()
{
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
        return;
    }

    function add_your_gateway_class( $methods ) {
        $methods[] = 'WC_Gateway_Nocks_Nifty';
        return $methods;
    }

    add_filter( 'woocommerce_payment_gateways', 'add_your_gateway_class' );

    class WC_Gateway_Nocks_Nifty extends WC_Payment_Gateway
    {
        public function __construct()
        {
            $this->valid_currencies = array('EUR', 'USD', 'NLG', 'BTC');

            $this->id                   = 'nocks_nifty';
            $this->domain               = 'https://nocks.nl/';
            $this->has_fields           = true;
            $this->method_title         = __( 'Nocks Nifty', 'woocommerce' );

            $this->init_form_fields();
            $this->init_settings();

            $this->title 			    = $this->get_option( 'title' );
            $this->description 		    = $this->get_option( 'description' );
            $this->bitcoin_address      = $this->get_option( 'bitcoin_address' );
            $this->guldencoin_address   = $this->get_option( 'guldencoin_address');
            $this->test                 = $this->get_option( 'test');

            $this->client = new Client([
                'base_url' => $this->domain.'api/'
            ]);

            $this->log = new WC_Logger();

            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

            add_action( 'woocommerce_api_wc_gateway_' . $this->id, array( $this, 'nocks_nifty_notification' ) );

            if ( !$this->is_valid_for_use() ) $this->enabled = false;
        }

        function is_valid_for_use()
        {
            $currency = get_woocommerce_currency();
            if(!in_array($currency, $this->valid_currencies))
            {
                return false;
            }

            if(!$this->guldencoin_address && !$this->bitcoin_address)
            {
                return false;
            }

            $totalAmount = $this->get_cart_total();

            if($currency == 'BTC' && $totalAmount > 0.5)
            {
                return false;
            }

            if($currency == 'NLG' && $totalAmount > 100000)
            {
                return false;
            }

            if($currency == 'EUR' && $totalAmount > 100)
            {
                return false;
            }

            if($currency == 'USD' && $totalAmount > 120)
            {
                return false;
            }

            return true;
        }

        public function get_cart_total()
        {
            global $woocommerce;
            if(isset($woocommerce->cart->total))
            {
                return $woocommerce->cart->total;
            }
            return 0;
        }

        public function admin_options()
        {
            ?>
            <h3><?php _e( 'Nocks Nifty', 'woocommerce' ); ?></h3>

            <table class="form-table">
                <?php
                // Generate the HTML For the settings form.
                $this->generate_settings_html();
                ?>
            </table><!--/.form-table-->
            <?php
        }

        public function init_form_fields()
        {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __( 'Enable/Disable', 'woocommerce' ),
                    'type' => 'checkbox',
                    'label' => __( 'Enable Nocks Nifty', 'woocommerce' ),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => __( 'Title', 'woocommerce' ),
                    'type' => 'text',
                    'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
                    'default' => __( 'Nocks Nifty', 'woocommerce' ),
                    'desc_tip'	  => true,
                ),
                'description' => array(
                    'title' => __( 'Description', 'woocommerce' ),
                    'type' => 'textarea',
                    'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
                    'default' => __( 'Pay with Bitcoin or Guldencoin using Nocks', 'woocommerce' )
                ),
                'bitcoin_address' => array(
                    'title' => __( 'Bitcoin address', 'woocommerce' ),
                    'type' 			=> 'text',
                    'description' => __( 'Please enter your Bitcoin address to receive payout in Bitcoin', 'woocommerce' ),
                    'default' => '',
                ),
                'guldencoin_address' => array(
                    'title' => __( 'Guldencoin address', 'woocommerce' ),
                    'type' 			=> 'text',
                    'description' => __( 'Please enter your Guldencoin address to receive payout in Guldencoin', 'woocommerce' ),
                    'default' => '',
                ),
                'test' => array(
                    'title'       => __( 'Testmode', 'woocommerce' ),
                    'type'        => 'select',
                    'description' => __( 'When enabled the invoice will always return paid.', 'woocommerce' ),
                    'default'     => '0',
                    'desc_tip'    => true,
                    'options'     => array(
                        '0' => __( 'Disabled', 'woocommerce' ),
                        '1' => __( 'Enabled', 'woocommerce' )
                    )
                ),
            );
        }

        function nocks_nifty_notification()
        {
            if ( isset( $_GET['order_id'] ) ) {
                global $woocommerce;
                $order = new WC_Order($_GET['order_id']);

                $this->update_order_status($_GET['order_id']);

                // Remove cart
                $woocommerce->cart->empty_cart();

                wp_redirect( $this->get_return_url( $order ) );
            }
        }

        function payment_fields()
        {
            $description = $this->get_option('description');
            echo $description;

            echo '
                <div class="nocks-nifty-payment-fields">
                    <div class="incoming_currency">
                        <input id="btc" type="radio" name="incoming_currency" value="BTC" />
                        <label class="coin btc" for="btc">Bitcoin</label>

                        <input id="nlg" type="radio" name="incoming_currency" value="NLG" />
                        <label class="coin nlg" for="nlg">Guldencoin</label>
                    </div>
                    <span class="price-estimate"></span>
                </div>
                <script>
                    var nocks_nifty_ajaxurl = "'. admin_url('admin-ajax.php').'";
                    var nocks_nifty_cart_total = "'.$this->get_cart_total().'";
                </script>
            ';
        }

        function process_payment( $order_id )
        {
            $order = new WC_Order( $order_id );

            $incomingCurrency = $_POST['incoming_currency'];
            $order_total = $order->order_total;

            $payment_option = $this->calculate_price($incomingCurrency, $order_total);

            $nocks_nifty_transaction = $this->nocks_nifty_create_transaction(array(
                'amount' => $payment_option['amount'],
                'withdrawal' => $payment_option['withdrawal'],
                'pair' => $payment_option['pair'],
                'returnUrl' => get_site_url().'/wc-api/wc_gateway_nocks_nifty?order_id='.$order_id.'&return='.$this->get_return_url( $order )
            ));

            // is there a Nocks Nifty transaction ?
            if(isset($nocks_nifty_transaction['success']) && isset($nocks_nifty_transaction['success']['transactionId']))
            {
                $transaction_id = $nocks_nifty_transaction['success']['transactionId'];
                $order->update_status('pending', 'Nocks Nifty transaction ID created: '.$transaction_id);
                update_post_meta( $order_id, 'nocks_nifty_id', $transaction_id);

                $result = array(
                    'result'   => 'success',
                    'redirect' => $this->domain.'nifty/transaction/'.$transaction_id.'?openWallet=1'
                );

                return $result;
            }
            else
            {
                echo '<p>Nocks Nifty: Something went wrong, please contact the webmaster.</p>';
            }

            return true;
        }

        function calculate_price($incomingCurrency, $amount)
        {
            $currency = get_woocommerce_currency();
            $withdrawal_pair = $this->get_withdrawal_pair($incomingCurrency);
            $pair = $withdrawal_pair['pair'];
            $withdrawal = $withdrawal_pair['withdrawal'];

            // If fiat currency
            if(in_array($currency, array('EUR', 'USD')))
            {
                // Get fiat in BTC
                $bitcoinPrice = $this->get_bitcoin_price($currency);
                $amount = number_format(($amount/$bitcoinPrice), 8, '.', '');
            }

            // If outgoing price not is BTC
            $pairX = explode('_', $pair);
            if($pairX[1] != 'BTC')
            {
                // Calculate outgoing currency price based on bitcoin price
                $outgoingPrice = $this->get_outgoing_currency_price();
                $amount = number_format(($amount/$outgoingPrice), 8, '.', '');
            }

            return array(
                'pair' => $pair,
                'withdrawal' => $withdrawal,
                'amount' => $amount
            );
        }

        function update_order_status($order_id)
        {
            $order = new WC_Order($order_id);

            $nocks_nifty_id = get_post_meta( $order->id, 'nocks_nifty_id', true );

            // Nocks Nity callback to check payment
            if(!$nocks_nifty_id)
            {
                return;
            }

            $payment_status = $this->nocks_nifty_check_payment($nocks_nifty_id);

            // Update order status
            $this->log->add( 'nocks_nifty', 'Order #'.$order->id.' payment status: ' . $payment_status );

            // Payment success or test modus
            if($payment_status || $this->test == 1) {
                $order->payment_complete();
            } else {
                $order->update_status('cancelled', 'Nocks Nifty Payment cancelled/timed out: '.$payment_status);
            }

            return $payment_status;
        }


        function get_withdrawal_pair($incomingCurrency)
        {
            $pair = '';
            $withdrawal = '';

            if(in_array($incomingCurrency, array('EUR', 'USD')) && $this->bitcoin_address)
            {
                $pair = 'BTC_BTC';
                $withdrawal = $this->bitcoin_address;
            }
            elseif(in_array($incomingCurrency, array('EUR', 'USD')) && $this->guldencoin_address)
            {
                $pair = 'BTC_NLG';
                $withdrawal = $this->guldencoin_address;
            }

            // If currency is BTC
            if($incomingCurrency == 'BTC' && $this->bitcoin_address)
            {
                $pair = 'BTC_BTC';
                $withdrawal = $this->bitcoin_address;
            }
            elseif($incomingCurrency == 'BTC' && $this->guldencoin_address)
            {
                $pair = 'BTC_NLG';
                $withdrawal = $this->guldencoin_address;
            }

            // If currency is NLG
            if($incomingCurrency == 'NLG' && $this->guldencoin_address)
            {
                $pair = 'NLG_NLG';
                $withdrawal = $this->guldencoin_address;
            }
            elseif($incomingCurrency == 'NLG' && $this->bitcoin_address)
            {
                $pair = 'NLG_BTC';
                $withdrawal = $this->bitcoin_address;
            }

            return array(
                'pair' => $pair,
                'withdrawal' => $withdrawal
            );
        }

        function nocks_nifty_create_transaction($transaction)
        {
            $response = $this->client->post('transaction', array(
                'headers' => array('Accept' => '*/*'),
                'json' => $transaction
            ));

            $response = json_decode($response->getBody()->getContents(), true);

            return $response;
        }

        function nocks_nifty_check_payment($transactionId)
        {
            $response = $this->client->get('transaction/'.$transactionId, array(
                'headers' => array('Accept' => '*/*')
            ));
            $response = json_decode($response->getBody()->getContents(), true);

            if(isset($response['success']))
            {
                if($response['success']['status'] == 'success')
                {
                    return true;
                }
                else
                {
                    return false;
                }
            }

            return false;
        }

        function nocks_nifty_check_price(array $pair, $amount)
        {
            $response = $this->client->get($this->domain.'checkPrice/'.$pair[0].'/'.$pair[1].'/'.$amount, array(
                'headers' => array('Accept' => '*/*')
            ));
            $response = json_decode($response->getBody()->getContents(), true);

            if(isset($response['result']))
            {
                return $response['result'];
            }

            return false;
        }

        // Powered by CoinDesk
        function get_bitcoin_price($currencyCode)
        {
            $response = $this->client->get('https://api.coindesk.com/v1/bpi/currentprice.json');
            $response = json_decode($response->getBody()->getContents(), true);

            if(isset($response['bpi'][$currencyCode]['rate']))
            {
                return $response['bpi'][$currencyCode]['rate'];
            }

            return false;
        }

        // Powered by Bittrex
        function get_outgoing_currency_price()
        {
            $response = $this->client->get('https://bittrex.com/api/v1.1/public/getticker?market=BTC-NLG');
            $response = json_decode($response->getBody()->getContents(), true);

            if(isset($response['result']['Last']))
            {
                return $response['result']['Last'];
            }

            return false;
        }
    }
}
?>