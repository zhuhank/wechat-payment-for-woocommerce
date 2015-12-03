(function ($) {
    $(function () {
        if ($('input#woocommerce_wechatpay_WX_EnableProxy').is(':checked')) {
            $('#woocommerce_wechatpay_WX_ProxyPort').closest('tr').show();
            $('#woocommerce_wechatpay_WX_ProxyHost').closest('tr').show();

        } else {
            $('#woocommerce_wechatpay_WX_ProxyHost').closest('tr').hide();
            $('#woocommerce_wechatpay_WX_ProxyPort').closest('tr').hide();
        }

        $('input#woocommerce_wechatpay_WX_EnableProxy').change(function () {
            if ($(this).is(':checked')) {
                $('#woocommerce_wechatpay_WX_ProxyPort').closest('tr').show();
                $('#woocommerce_wechatpay_WX_ProxyHost').closest('tr').show();
            } else {
                $('#woocommerce_wechatpay_WX_ProxyHost').closest('tr').hide();
                $('#woocommerce_wechatpay_WX_ProxyPort').closest('tr').hide();
            }
        });
    });

})(jQuery);