( function( $ ) {

    function get_price_estimate(incomingCurrency)
    {
        $(".price-estimate").html("&nbsp;<i class=\'fa fa-refresh fa-spin\'></i>");
        $.ajax({
            url: nocks_nifty_ajaxurl,
            data: {
                "action":"ajax_nocks_nifty_price_estimate",
                "data": { incomingCurrency: incomingCurrency, amount: nocks_nifty_cart_total }
            },
            success:function(data) {
                var amount = data.amount;

                if(incomingCurrency == "BTC")
                {
                    $(".price-estimate").html("&nbsp;<i class=\'fa fa-btc\'></i> "+amount);
                }
                else
                {
                    $(".price-estimate").html("&nbsp;<i class=\'guldensign\'></i>"+amount);
                }
            }
        });
    }

    $(document).ready(function() {

        $(document).on("change", "[name=incoming_currency]", function() {
            var incomingCurrency = $(this).val();

            get_price_estimate(incomingCurrency);
        });

        setTimeout(function(){
            $("[name=incoming_currency][value=BTC]").prop('checked', true).change();
        }, 2000);
    });

} )( jQuery );