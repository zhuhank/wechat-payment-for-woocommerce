<?php
/**
* 	配置账号信息
*/

class WxPayConfig
{
	//=======【基本信息设置】=====================================
	//
	/**
	 * TODO: 修改这里配置为您自己申请的商户信息
	 * 微信公众号信息配置
	 *
	 * APPID：绑定支付的APPID（必须配置，开户邮件中可查看）
	 *
	 * MCHID：商户号（必须配置，开户邮件中可查看）
	 *
	 * KEY：商户支付密钥，参考开户邮件设置（必须配置，登录商户平台自行设置）
	 * 设置地址：https://pay.weixin.qq.com/index.php/account/api_cert
	 *
	 * APPSECRET：公众帐号secert（仅JSAPI支付的时候需要配置， 登录公众平台，进入开发者中心可设置），
	 * 获取地址：https://mp.weixin.qq.com/advanced/advanced?action=dev&t=advanced/dev&token=2005451881&lang=zh_CN
	 * @var string
	 */
	  private $APPID = '';
      private $MCHID = '';
	  private $KEY = '';
	  private $APPSECRET = '';

	//=======【证书路径设置】=====================================
	/**
	 * TODO：设置商户证书路径
	 * 证书路径,注意应该填写绝对路径（仅退款、撤销订单时需要，可登录商户平台下载，
	 * API证书下载地址：https://pay.weixin.qq.com/index.php/account/api_cert，下载之前需要安装商户操作证书）
	 * @var path
	 */
	private $SSLCERT_PATH = '';
	private $SSLKEY_PATH = '';

	//=======【curl代理设置】===================================
	/**
	 * TODO：这里设置代理机器，只有需要代理的时候才设置，不需要代理，请设置为0.0.0.0和0
	 * 本例程通过curl使用HTTP POST方法，此处可修改代理服务器，
	 * 默认CURL_PROXY_HOST=0.0.0.0和CURL_PROXY_PORT=0，此时不开启代理（如有需要才设置）
	 * @var unknown_type
	 */
	private $CURL_PROXY_HOST = "";//"10.152.18.220";
	private $CURL_PROXY_PORT = '';//8080;
    private $enableProxy = false;

	//=======【上报信息配置】===================================
	/**
	 * TODO：接口调用上报等级，默认紧错误上报（注意：上报超时间为【1s】，上报无论成败【永不抛出异常】，
	 * 不会影响接口调用流程），开启上报之后，方便微信监控请求调用的质量，建议至少
	 * 开启错误上报。
	 * 上报等级，0.关闭上报; 1.仅错误出错上报; 2.全量上报
	 * @var int
	 */
	private $REPORT_LEVENL = 1;

    /**
     * @return boolean
     */
    public function isEnableProxy()
    {
        return $this->enableProxy;
    }

    /**
     * @param boolean $enableProxy
     */
    public function setEnableProxy($enableProxy)
    {
        $this->enableProxy = $enableProxy;
    }

    /**
     * @return int
     */
    public function getREPORTLEVENL()
    {
        return $this->REPORT_LEVENL;
    }

    /**
     * @param int $REPORT_LEVENL
     */
    public function setREPORTLEVENL($REPORT_LEVENL)
    {
        $this->REPORT_LEVENL = $REPORT_LEVENL;
    }

   public function __construct($APPID, $MCHID, $KEY, $APPSECRET=null, $SSLCERT_PATH=null, $SSLKEY_PATH=null
        , $CURL_PROXY_HOST=null, $CURL_PROXY_PORT=null)
    {
        $this->APPID = $APPID;
        $this->MCHID = $MCHID;
        $this->KEY = $KEY;
        $this->APPSECRET = $APPSECRET;
        $this->SSLCERT_PATH = $SSLCERT_PATH;
        $this->SSLKEY_PATH = $SSLKEY_PATH;
        $this->CURL_PROXY_HOST = $CURL_PROXY_HOST;
        $this->CURL_PROXY_PORT = $CURL_PROXY_PORT;
    }

    /**
     * @return string
     */
    public function getAPPID()
    {
        return $this->APPID;
    }

    /**
     * @param string $APPID
     */
    public function setAPPID($APPID)
    {
        $this->APPID = $APPID;
    }

    /**
     * @return string
     */
    public function getMCHID()
    {
        return $this->MCHID;
    }

    /**
     * @param string $MCHID
     */
    public function setMCHID($MCHID)
    {
        $this->MCHID = $MCHID;
    }

    /**
     * @return string
     */
    public function getKEY()
    {
        return $this->KEY;
    }

    /**
     * @param string $KEY
     */
    public function setKEY($KEY)
    {
        $this->KEY = $KEY;
    }

    /**
     * @return string
     */
    public function getAPPSECRET()
    {
        return $this->APPSECRET;
    }

    /**
     * @param string $APPSECRET
     */
    public function setAPPSECRET($APPSECRET)
    {
        $this->APPSECRET = $APPSECRET;
    }

    /**
     * @return path
     */
    public function getSSLCERTPATH()
    {
        return $this->SSLCERT_PATH;
    }

    /**
     * @param path $SSLCERT_PATH
     */
    public function setSSLCERTPATH($SSLCERT_PATH)
    {
        $this->SSLCERT_PATH = $SSLCERT_PATH;
    }

    /**
     * @return string
     */
    public function getSSLKEYPATH()
    {
        return $this->SSLKEY_PATH;
    }

    /**
     * @param string $SSLKEY_PATH
     */
    public function setSSLKEYPATH($SSLKEY_PATH)
    {
        $this->SSLKEY_PATH = $SSLKEY_PATH;
    }

    /**
     * @return unknown_type
     */
    public function getCURLPROXYHOST()
    {
        return $this->CURL_PROXY_HOST;
    }

    /**
     * @param unknown_type $CURL_PROXY_HOST
     */
    public function setCURLPROXYHOST($CURL_PROXY_HOST)
    {
        $this->CURL_PROXY_HOST = $CURL_PROXY_HOST;
    }

    /**
     * @return string
     */
    public function getCURLPROXYPORT()
    {
        return $this->CURL_PROXY_PORT;
    }

    /**
     * @param string $CURL_PROXY_PORT
     */
    public function setCURLPROXYPORT($CURL_PROXY_PORT)
    {
        $this->CURL_PROXY_PORT = $CURL_PROXY_PORT;
    }




}
