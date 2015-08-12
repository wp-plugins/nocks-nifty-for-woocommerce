<?php
add_filter( 'woocommerce_currencies', 'nocks_nifty_add_currency_nlg' );

function nocks_nifty_add_currency_nlg( $currencies ) {
    $currencies['NLG'] = __( 'Guldencoin', 'woocommerce' );
    return $currencies;
}

add_filter('woocommerce_currency_symbol', 'nocks_nifty_add_currency_symbol_nlg', 10, 2);

function nocks_nifty_add_currency_symbol_nlg( $currency_symbol, $currency ) {
    switch( $currency ) {
        case 'NLG': $currency_symbol = '<i class="guldensign"></i>'; break;
    }
    return $currency_symbol;
}

add_action( 'wp_enqueue_scripts', 'nocks_nifty_add_currency_css_nlg' );
add_action( 'admin_enqueue_scripts', 'nocks_nifty_add_currency_css_nlg' );

function nocks_nifty_add_currency_css_nlg() {
    wp_enqueue_style( 'nocks_nifty_add_currency_css_nlg', plugins_url('assets/guldensign/guldensign.css', dirname(__FILE__)));
}