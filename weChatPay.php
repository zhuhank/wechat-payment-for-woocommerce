<?php
/*
 * Plugin Name: WeChatPay For WooCommerce
 * Plugin URI:
 * Description:Integrate the Chinese WeChat payment gateway with Woocommerce. WeChat is one of the most widely used payment method in China.
 * Version: 1.0
 * Author: Shudong Zhu
 * Author URI:
 *
 * Text Domain: wechatpay
 * Domain Path: /lang/
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


function wc_wechatpay_gateway_init() {

    if( !class_exists('WC_Payment_Gateway') )  return;
    include_once( plugin_dir_path(__FILE__) .'/class-wc-wechatpay.php');
    load_plugin_textdomain( 'wechatpay', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/'  );

    require_once( plugin_basename( 'class-wc-wechatpay.php' ) );

    add_filter('woocommerce_payment_gateways', 'woocommerce_wechatpay_add_gateway' );

    add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_wechatpay_plugin_edit_link' );

    $WX = new WC_WeChatPay();
    add_action( 'wp_ajax_WXLoopOrderStatus', array($WX, "WX_Loop_Order_Status" ) );
    add_action( 'wp_ajax_nopriv_WXLoopOrderStatus', array($WX, "WX_Loop_Order_Status") );
    add_action('woocommerce_receipt_wechatpay', array($WX, 'receipt_page'));
}
add_action( 'plugins_loaded', 'wc_wechatpay_gateway_init' );

/**
 * Add the gateway to WooCommerce
 *
 * @access  public
 * @param   array $methods
 * @package WooCommerce/Classes/Payment
 * @return  array
 */
function woocommerce_wechatpay_add_gateway( $methods ) {

    $methods[] = 'WC_WeChatPay';
    return $methods;
}

/**
 * Display WeChatPay Trade No. for customer
 * @param array $total_rows
 * @param mixed $order
 * @return array
 */
function wc_wechatpay_display_order_meta_for_customer( $total_rows, $order ){
    $trade_no = get_post_meta( $order->id, 'WeChatPay Trade No.', true );

    if( !empty( $trade_no ) ){
        $new_row['wechatpay_trade_no'] = array(
            'label' => __( 'WeChatPay Trade No.:', 'wechatpay' ),
            'value' => $trade_no
        );
        // Insert $new_row after shipping field
        $total_rows = array_merge( array_splice( $total_rows,0,2), $new_row, $total_rows );
    }
    return $total_rows;
}
add_filter( 'woocommerce_get_order_item_totals', 'wc_wechatpay_display_order_meta_for_customer', 10, 2 );

function wc_wechatpay_plugin_edit_link( $links ){
    return array_merge(
        array(
            'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_wechatpay') . '">'.__( 'Settings', 'wechatpay' ).'</a>'
        ),
        $links
    );
}
?>