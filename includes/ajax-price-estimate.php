<?php
function ajax_nocks_nifty_price_estimate()
{
    header('Content-Type: application/json');

    $incomingCurrency = $_REQUEST['data']['incomingCurrency'];
    $eurAmount = $_REQUEST['data']['amount'];

    $nocks_nifty = new WC_Gateway_Nocks_Nifty();
    $result = $nocks_nifty->calculate_estimate_price($incomingCurrency, $eurAmount);
    $amount = $result['amount'];
    $pair = explode('_', $result['pair']);
    //$amount = $nocks_nifty->nocks_nifty_check_price($pair, $amount);

    echo json_encode(array(
        'amount' => number_format($amount, 4, '.', '')
    ));

    die(); // Always die in functions echoing ajax content
}
add_action('wp_ajax_nopriv_ajax_nocks_nifty_price_estimate', 'ajax_nocks_nifty_price_estimate');
add_action('wp_ajax_ajax_nocks_nifty_price_estimate', 'ajax_nocks_nifty_price_estimate');