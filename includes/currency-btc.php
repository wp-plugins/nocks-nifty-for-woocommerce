<?php
add_filter( 'woocommerce_currencies', 'nocks_nifty_add_currency_btc' );

function nocks_nifty_add_currency_btc( $currencies ) {
    $currencies['BTC'] = __( 'Bitcoin', 'woocommerce' );
    return $currencies;
}

add_filter('woocommerce_currency_symbol', 'nocks_nifty_add_currency_symbol_btc', 10, 2);

function nocks_nifty_add_currency_symbol_btc( $currency_symbol, $currency ) {
    switch( $currency ) {
        case 'BTC': $currency_symbol = '<i class="fa fa-btc"></i>'; break;
    }
    return $currency_symbol;
}

add_action( 'wp_enqueue_scripts', 'nocks_nifty_add_currency_css_btc' );
add_action( 'admin_enqueue_scripts', 'nocks_nifty_add_currency_css_btc' );

function nocks_nifty_add_currency_css_btc() {
    wp_enqueue_style( 'nocks_nifty_add_currency_css_btc', plugins_url('assets/font-awesome-4.3.0/css/font-awesome.min.css', dirname(__FILE__)));
}