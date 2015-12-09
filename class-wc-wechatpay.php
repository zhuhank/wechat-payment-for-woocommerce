<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * WeChatPay Payment Gateway
 *
 * Provides an WeChatPay Payment Gateway.
 *
 * @class       WC_WeChatPay
 * @extends     WC_Payment_Gateway
 * @version     1.0
 * @auther      Shudong Zhu
 * @mail        nkg_hank@126.com
 */
class WC_WeChatPay extends WC_Payment_Gateway
{
    var $current_currency;
    var $multi_currency_enabled;
    var $supported_currencies;
    var $lib_path;
    var $charset;

    public function  __construct()
    {

        $this->current_currency = get_option('woocommerce_currency');
        $this->multi_currency_enabled = in_array('woocommerce-multilingual/wpml-woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) && get_option('icl_enable_multi_currency') == 'yes';
        $this->supported_currencies = array('RMB', 'CNY');
        $this->lib_path = plugin_dir_path(__FILE__) . 'lib';
        $this->charset = strtolower(get_bloginfo('charset'));
        if (!in_array($this->charset, array('gbk', 'utf-8'))) {
            $this->charset = 'utf-8';
        }
        $this->include_files();

        $this->id = 'wechatpay';
        $this->icon = plugins_url('images/wechatpay.png', __FILE__);
        $this->has_fields = false;
        $this->method_title = __('WeChatPay', 'wechatpay');   //checkout option title
        $this->order_button_text = __('Proceed to WeChatPay', 'wechatpay');
        $this->notify_url = WC()->api_request_url('WC_WeChatPay');

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->wechatpay_appID = $this->get_option('wechatpay_appID');
        $this->wechatpay_mchId = $this->get_option('wechatpay_mchId');
        $this->wechatpay_key = $this->get_option('wechatpay_key');
        $this->debug = $this->get_option('debug');
        $this->form_submission_method = $this->get_option('form_submission_method') == 'yes' ? true : false;
        $this->order_title_format = $this->get_option('order_title_format');
        $this->exchange_rate = $this->get_option('exchange_rate');
        $this->order_prefix = $this->get_option('order_prefix');
        $this->notify_url = WC()->api_request_url('WC_WeChatPay');
        $this->ipn = null;

        $this->logger = Log::Init(new CLogFileHandler(plugin_dir_path(__FILE__) . "logs/" . date('Y-m-d') . '.log'), 15);;
        if ('yes' == $this->debug) {
            $this->log = new WC_Logger();
        }

        // Actions
        add_action('admin_notices', array($this, 'requirement_checks'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options')); // WC >= 2.0
        add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));

        add_action('woocommerce_api_wc_wechatpay', array($this, 'check_wechatpay_response'));
        //  add_action('woocommerce_thankyou_wechatpay', array($this, 'thankyou_page'));
        add_action('template_redirect', array($this, 'reset_cart_front'));
        add_action('admin_enqueue_scripts', array($this, 'WX_enqueue_script'));
        add_action( 'wp_enqueue_scripts',array($this,'WX_enqueue_script_onCheckout')  );
    }



    public function WX_Loop_Order_Status(){

        //ajax loop
        Log::DEBUG(' start loop !' );

        $order_id =  $_GET['orderId'];
        $order = new WC_Order($order_id);
        $isPaid =! $order->needs_payment();
        Log::DEBUG(" check_wechatpay_response orderid:".$order_id."is need pay:" .$isPaid);
        if($isPaid){
            $returnUrl = urldecode($this->get_return_url($order));
            echo json_encode(array(
                'status' =>'paid',
                'message'=>$returnUrl
            ));
        }else{
            echo json_encode(array(
                'status' =>'nPaid',
                'message'=>'nPaid'
            ));
        }
        die('');
    }

    function WX_enqueue_script_onCheckout()
    {
        $orderId =  get_query_var('order-pay');
        $order = new WC_Order($orderId);
        if("wechatpay" ==$order->payment_method ){
            if (is_checkout_pay_page()&&!isset($_GET['pay_for_order'])) {
                wp_enqueue_script('Woo_WX_Loop', plugins_url( '/js/WX_Loop.js',__FILE__) , array('jquery'),null);
            }
        }

    }

    function WX_enqueue_script()
    {
        wp_enqueue_script('Woo_WX_Setting', plugins_url('/js/WX_Setting.js',__FILE__) , array('jquery'));
    }

    function reset_cart_front()
    {
        global $woocommerce;

        if (is_checkout_pay_page()) {
            $woocommerce->cart->empty_cart();
        }

    }


    function check_wechatpay_response()
    {

        //generate QR Code
        if (isset($_GET['QRData'])) {
            $url = $_GET['QRData'];
            Log::DEBUG('Generate WeChat QR Code:' . print_r($url, true));
            QRcode::png($url);
            exit;
        } else { //handle ipn callback
            $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
            Log::DEBUG(' message callback.' . print_r($xml, true));
            Log::DEBUG('weChat Async IPN message callback.');
            if ($this->isWeChatIPNValid($xml)) {
                Log::DEBUG('weChat IPN is valid message.');
                Log::DEBUG('weChat Async IPN message:' . print_r($xml, true));
                $order_id = $this->ipn['attach'];
                $order = new WC_Order($order_id);
                $order->payment_complete();
                $trade_no = $this->ipn['transaction_id'];
                update_post_meta($order_id, 'WeChatPay Trade No.', wc_clean($trade_no));

                $reply = new WxPayNotifyReply();
                $reply->SetReturn_code("SUCCESS");
                $reply->SetReturn_msg("OK");
                WxpayApi::replyNotify($reply->ToXml());

            } else {
                $reply = new WxPayNotifyReply();
                $reply->SetReturn_code("FAIL");
                $reply->SetReturn_msg("OK");
                WxpayApi::replyNotify($reply->ToXml());
            }
        }

    }

    function  include_files()
    {
        $lib = $this->lib_path;
        include_once($lib . '/phpqrcode/phpqrcode.php');
        include_once($lib . '/WxPay.Data.php');
        include_once($lib . '/WxPay.Api.php');
        include_once($lib . '/WxPay.Exception.php');
        include_once($lib . '/WxPay.Notify.php');
        include_once($lib . '/WxPay.Config.php');
        include_once($lib . '/log.php');
    }

    /**
     * Check if requirements are met and display notices
     *
     * @access public
     * @return void
     */
    function requirement_checks()
    {
        if (!in_array($this->current_currency, array('RMB', 'CNY')) && !$this->exchange_rate) {
            echo '<div class="error"><p>' . sprintf(__('WeChatPay is enabled, but the store currency is not set to Chinese Yuan. Please <a href="%1s">set the %2s against the Chinese Yuan exchange rate</a>.', 'wechatpay'), admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_wechatpay#woocommerce_wechatpay_exchange_rate'), $this->current_currency) . '</p></div>';
        }
    }

    function is_available()
    {

        $is_available = ('yes' === $this->enabled) ? true : false;

        if ($this->multi_currency_enabled) {
            if (!in_array(get_woocommerce_currency(), array('RMB', 'CNY')) && !$this->exchange_rate) {
                $is_available = false;
            }
        } else if (!in_array($this->current_currency, array('RMB', 'CNY')) && !$this->exchange_rate) {
            $is_available = false;
        }

        return $is_available;
    }

    /**
     * Initialise Gateway Settings Form Fields
     *
     * @access public
     * @return void
     */
    function init_form_fields()
    {

        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'wechatpay'),
                'type' => 'checkbox',
                'label' => __('Enable WeChatPay Payment', 'wechatpay'),
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Title', 'wechatpay'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'wechatpay'),
                'default' => __('WeChatPay', 'wechatpay'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'wechatpay'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'wechatpay'),
                'default' => __("Pay via WeChatPay, if you don't have an WeChatPay account, you can also pay with your debit card or credit card", 'wechatpay'),
                'desc_tip' => true,
            ),
            'wechatpay_appID' => array(
                'title' => __('Application ID', 'wechatpay'),
                'type' => 'text',
                'description' => __('Please enter the Application ID,If you don\'t have one, <a href="https://pay.weixin.qq.com" target="_blank">click here</a> to get.', 'wechatpay'),
                'css' => 'width:400px'
            ),
            'wechatpay_mchId' => array(
                'title' => __('Merchant ID', 'wechatpay'),
                'type' => 'text',
                'description' => __('Please enter the Merchant ID,If you don\'t have one, <a href="https://pay.weixin.qq.com" target="_blank">click here</a> to get.', 'wechatpay'),
                'css' => 'width:400px'
            ),
            'wechatpay_key' => array(
                'title' => __('WeChatPay Key', 'wechatpay'),
                'type' => 'text',
                'description' => __('Please enter your WeChatPay Key; this is needed in order to take payment.', 'wechatpay'),
                'css' => 'width:200px',
                'desc_tip' => true,
            ),
            /*'order_prefix' => array(
                'title' => __('Order No. Prefix', 'wechatpay'),
                'type' => 'text',
                'description' => __('eg.WC-. If you <strong>use your WeChatPay account for multiple stores</strong>, Please enter this prefix and make sure it is unique as WeChatPay will not allow orders with the same merchant order number.', 'wechatpay'),
                'default' => 'WC-'
            ),*/
            'WX_EnableProxy' => array(
                'title' => __('Enable Proxy', 'wechatpay'),
                'type' => 'checkbox',
                'id' => 'Woo_WX_EnableProxy',
                'label' => __('Enable Proxy', 'wechatpay'),
                'default' => 'no',
                'description' => __('If you are behind firewall or behind company network, you can  enable proxy to make the plugin works.', 'wechatpay')
            ),
            'WX_ProxyHost' => array(
                'title' => __('Proxy Host', 'wechatpay'),
                'type' => 'text',
                'id' => 'Woo_WX_ProxyHost',
                'default' => '',
                'desc_tip' => __('Please set proxy host.', 'wechatpay')
            ),
            'WX_ProxyPort' => array(
                'title' => __('Proxy Port', 'wechatpay'),
                'type' => 'text',
                'default' => '',
                'id' => 'Woo_WX_ProxyPort',
                'desc_tip' => __('Please set proxy port.', 'wechatpay')
            ),
            'WX_debug' => array(
                'title' => __('Debug Log', 'wechatpay'),
                'type' => 'checkbox',
                'label' => __('Enable logging', 'wechatpay'),
                'default' => 'no',
                'description' => __('Log WeChatPay events, such as trade status, inside <code>/plugins/weChatPay-for-woocommerce/logs/</code>', 'wechatpay')
            )
        );
        /*        if (function_exists('wc_get_log_file_path')) {
                    $this->form_fields['WX_debug']['description'] = sprintf(__('Log WeChatPay events, such as trade status, inside <code>%s</code>', 'wechatpay'), plugin_dir_path(__FILE__) . 'logs/');
                }*/
        if (!in_array($this->current_currency, array('RMB', 'CNY'))) {

            $this->form_fields['exchange_rate'] = array(
                'title' => __('Exchange Rate', 'wechatpay'),
                'type' => 'text',
                'description' => sprintf(__("Please set the %s against Chinese Yuan exchange rate, eg if your currency is US Dollar, then you should enter 6.19", 'wechatpay'), $this->current_currency),
                'css' => 'width:80px;',
                'desc_tip' => true,
            );
        }

    }

    /**
     * Admin Panel Options
     * - Options for bits like 'title' and account etc.
     *
     * @access public
     * @return void
     */
    public function admin_options()
    {

        ?>
        <h3><?php _e('WeChatPay', 'wechatpay'); ?></h3>
        <p><?php _e('WeChatPay is a simple, secure and fast online payment method.', 'wechatpay'); ?></p>

        <table class="form-table">
            <?php
            // Generate the HTML For the settings form.
            $this->generate_settings_html();
            ?>
        </table><!--/.form-table-->
        <?php
    }


    public function process_payment($order_id)
    {
        $order = new WC_Order($order_id);
        return array(
            'result' => 'success',
            'redirect' => $order->get_checkout_payment_url(true)
        );

    }

    function getWXURI($order_id)
    {

        $WxCfg = $this->getWXCfg();
        $order = new WC_Order($order_id);
        $total = $order->get_total();
        $totalFee = (int)($total * 100);
        $input = new WxPayUnifiedOrder();
        $input->SetBody("Shop Name: " . get_option('blogname'));
        $input->SetDetail("");
        $input->SetAttach($order_id);
        $input->SetOut_trade_no(date("YmdHis"));

        if (!in_array($this->current_currency, array('RMB', 'CNY'))) {
            $totalFee = round($totalFee * $this->exchange_rate, 2);
        }
        $input->SetTotal_fee($totalFee);

        $date = new DateTime();
        $date->setTimezone(new DateTimeZone('Asia/Shanghai'));
        $startTime = $date->format('YmdHis');
        $expiredTime = $startTime + 600;

        $input->SetTime_start($startTime);
        $input->SetTime_expire($expiredTime);
        //$input->SetGoods_tag("tag");
        $input->SetNotify_url($this->notify_url);
        $input->SetTrade_type("NATIVE");
        $input->SetProduct_id($order_id);
        $result = WxPayApi::unifiedOrder($input, 6, $WxCfg);
        Log::DEBUG('Response of WxPayApi::unifiedOrder:' . print_r($result, true));
        return $result["code_url"];
    }

    /*
     * Validate ipn message is valid or not
     */
    function isWeChatIPNValid($ipnXml)
    {

        //如果返回成功则验证签名
        try {
            $result = WxPayResults::Init($ipnXml);
            $this->ipn = $result;
        } catch (WxPayException $e) {
            $msg = $e->errorMessage();
            return false;
        }


        Log::DEBUG("call back  ipn:" . json_encode($result));

        if (!array_key_exists("transaction_id", $result)) {
            return false;
        }

        if (!$this->Queryorder($result["transaction_id"])) {
            return false;
        }

        return true;
    }


    /*
     * Query transaction form weChat using transaction id in Ipn
     */
    function Queryorder($transaction_id)
    {
        $WxCfg = $this->getWXCfg();

        $input = new WxPayOrderQuery();
        $input->SetTransaction_id($transaction_id);
        $result = WxPayApi::orderQuery($input, $WxCfg);
        Log::DEBUG(" WxPayApi::orderQuery:" . json_encode($result));
        if (array_key_exists("return_code", $result)
            && array_key_exists("result_code", $result)
            && $result["return_code"] == "SUCCESS"
            && $result["result_code"] == "SUCCESS"
        ) {
            return true;
        }
        return false;
    }

    function  genetateQR($order_id)
    {
            $baseQR = $this->notify_url . '?QRData=';
            $url = urlencode(urldecode($this->getWXURI($order_id)));
            $qrUrl = $baseQR . $url;
            echo '<img id="WxQRCode" alt="QR Code" style="width:200px;height:200px" OId ='.$order_id. " loopUrl=".$this->notify_url." src=" .  $qrUrl . '>';

    }

    function receipt_page($order)
    {
        if(!$this->qrUrl){
            Log::DEBUG('Pay order with weChat payment');
            echo '<p>' . __('Please scan the QR code with WeChat to finish the payment.', 'wechatpay') . '</p>';
            $this->genetateQR($order);
        }


    }


    function getWXCfg()
    {
        $weChatOptions = get_option('woocommerce_wechatpay_settings');
        $WxCfg = new WxPayConfig($weChatOptions["wechatpay_appID"], $weChatOptions["wechatpay_mchId"], $weChatOptions["wechatpay_key"]);
        $WxCfg->setEnableProxy($weChatOptions["WX_EnableProxy"]);
        if ($weChatOptions["WX_EnableProxy"]) {
            $WxCfg->setCURLPROXYHOST($weChatOptions["WX_ProxyHost"]);
            $WxCfg->setCURLPROXYPORT($weChatOptions["WX_ProxyPort"]);
        }

        return $WxCfg;
    }

}

?>
