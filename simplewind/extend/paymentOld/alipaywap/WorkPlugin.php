<?php
// namespace paymentOld\alipaywap;

use payment\common\alipay\custom\AlipaySubmit;
use payment\common\alipay\custom\AlipayNotify;
use traits\controller\Jump;

// import('payment/common/alipay/custom/coreFunc',EXTEND_PATH);

/**
* 支付宝支付接口
* @@@ sign_type 签名方式需改
*/
class WorkPlugin
{
    use Jump;
    var $plugin_id = 'alipaywap';// 插件唯一ID
    private $p_set = [];
    private $notify_url = '';
    private $return_url = '';
    private $dir = '';// getcwd()
    private $host = '';

    function __construct($order_sn='', $order_amount='', $order_id='123', $action='')
    {
        $this->order_sn = $order_sn;
        $this->order_amount = $order_amount;
        // $this->action = $action;
        $this->host = cmf_get_domain();
        $this->notify_url = url('funds/Pay/callBack','',false,$this->host);
        $this->return_url = url('funds/Pay/callBack','',false,$this->host);

        // TP写法 
        import('payment/common/alipay/custom/coreFunc',EXTEND_PATH);
    }

    /*
    * 构造即时到帐接口
    * version3.2 2011-03-25
    */
    /*public function work()
    {
        //
        $alipayService = new AlipayService($this->p_set());
        $html_text = $alipayService->create_direct_pay_by_user($this->parameter());
        echo $html_text;
    }*/

    /**
     * +----------------------------------------------------------
     * 建立支付请求
     * +----------------------------------------------------------
    */
    // 默认
    public function work($auto=true,$jumpurl='')
    {
        $result = $this->workForm($auto);
        return $result;
    }

    // 表单
    public function workForm($auto=true) {
        // 建立请求
        $alipaySubmit = new AlipaySubmit($this->p_set());
        $html_text = $alipaySubmit->buildRequestForm($this->parameter(),"get","付款",$auto);
        return $html_text;
    }

    // URL
    public function workUrl($auto=true)
    {
        // 建立请求
        $alipaySubmit = new AlipaySubmit($this->p_set());
        $alipaySubmit->buildRequestURL($this->parameter(),$auto);
    }

    // CURL模式
    public function workCurl()
    {
        // 建立请求
        $alipaySubmit = new AlipaySubmit($this->p_set());
        $sResult = $alipaySubmit->buildRequestHttp($this->parameter());
        return $sResult;
    }

    // 二维码
    public function QRcode()
    {
        # code...
    }

    /*
    * 功能：支付宝页面跳转同步通知页面
    */
    public function getReturn()
    {
        //计算得出通知验证结果
        $alipayNotify = new AlipayNotify($this->p_set());
        $verify_result = $alipayNotify->verifyReturn();

        if ($verify_result) { //验证成功
            $this->log($_GET);
            // 在这里只管返回数据
            return $_GET;
        } else { //验证失败
            //调试
            $this->log($_GET);
            return false;
        }
    }

    /*
    * 功能：支付宝服务器异步通知页面
    */
    public function getNotify()
    {
        //计算得出通知验证结果
        $alipayNotify = new AlipayNotify($this->p_set());
        $verify_result = $alipayNotify->verifyNotify();

        if($verify_result) { //验证成功
            $this->log($_POST);
            // 在这里只管返回数据
            return $_POST;
        } else { //验证失败
            //调试用，写文本函数记录程序运行情况是否正常
            $this->log($_POST);
            return false;
        }
    }

    /*
    * 功能：支付宝日志
    */
    public function log($data='string')
    {
        if (is_string($data)) {
            $content = $data;
        } elseif (is_array($data)) {
            $content = json_encode($data);
        } else {
            $content = '非法数据！';
        }

        return logResult($content);
    }




    /*
    * 功能：订单查询
    */
    public function orderStatus($value='')
    {
        // 获取插件配置信息
        $set = $this->p_set();

        $param['service']           = 'single_trade_query';
        $param['partner']           = $set['partner'];// 合作者ID
        $param['_input_charset']    = $set['input_charset'];
        $param['out_trade_no']      = $this->order_sn;//商户订单号,唯一
        ksort($param);
        reset($param);
         
        $param = '';
        $sign  = '';
         
        foreach ($param AS $key => $val)
        {
            $param .= "$key=" .urlencode($val). "&";
            $sign  .= "$key=$val&";
        }
             
        $param = substr($param, 0, -1);
        $sign  = substr($sign, 0, -1) . $plugin['config']['key'];
        $url = 'https://mapi.alipay.com/gateway.do?'.$param. '&sign='.md5($sign).'&sign_type=MD5';
        echo file_get_contents($url);
    }

    /*退款*/
    public function refund($value='')
    {
        # code...
    }
    public function refundStatus($value='')
    {
        # code...
    }





    /**
     * +----------------------------------------------------------
     * 配置信息
     * +----------------------------------------------------------
    */
    public function p_set() {
        // 获取插件配置信息
        // {"account":"","key":"","partner":"","switch":"0"}
        $option = cmf_get_option('alipay_settings');

        //↓↓↓↓↓↓↓↓↓↓请在这里配置您的基本信息↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
        // 合作身份者id，以2088开头的16位纯数字
        $set['partner'] = trim($option['partner']);

        //收款支付宝账号，一般情况下收款账号就是签约账号
        $set['seller_id'] = $set['partner'];
        //↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑

        //商户的私钥（后缀是.pen）文件相对路径
        $set['private_key_path'] = EXTEND_PATH .'paymentOld/'. $this->plugin_id .'/key/rsa_private_key.pem';
        
        //支付宝公钥（后缀是.pen）文件相对路径
        $set['ali_public_key_path']= EXTEND_PATH .'paymentOld/'. $this->plugin_id .'/key/alipay_public_key.pem';
        
        // 签名方式 不需修改
        $set['sign_type']    = 'RSA';//wap的AlipaySubmit里用的是这个
        // $set['sign_type'] = 'MD5';// 推荐
        // $set['sign_type']    = strtoupper('md5');
        
        // 字符编码格式 目前支持 gbk 或 utf-8
        $set['input_charset'] = trim(strtolower('utf-8'));
        
        // ca证书路径地址，用于curl中ssl校验
        // 请保证cacert.pem文件在当前文件夹目录中
        $set['cacert'] = EXTEND_PATH .'paymentOld/'. $this->plugin_id .'/cacert.pem';

        // 访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
        $set['transport'] = 'http';
        
        return $set;
    }

    /**
     * +----------------------------------------------------------
     * 请求参数
     * +----------------------------------------------------------
     */
    public function parameter() {
        $set = $this->p_set();
        $siteInfo = cmf_get_option('site_info');

        $param['service'] = "alipay.wap.create.direct.pay.by.user";
        //支付类型，必填，不能修改
        $param['payment_type'] = "1";
        // 字符编码格式 目前支持 gbk 或 utf-8
        $param['_input_charset'] = $set['input_charset'];
        
        // 合作身份者id，以2088开头的16位纯数字
        $param['partner'] = trim($set['partner']);
        // 收款支付宝账号
        $parameter['seller_id'] = $param['partner'];
        
        //服务器异步通知页面路径，需http://格式的完整路径，不能加?id=123这类自定义参数
        // $param['notify_url'] = cmf_url('funds/Pay/callBack','',false,$this->host);
        $param['notify_url'] = $this->notify_url;
        //页面跳转同步通知页面路径，需http://格式的完整路径，不能加?id=123这类自定义参数，不能写成http://localhost/
        $param['return_url'] = $this->return_url;

        //商户订单号，商户网站订单系统中唯一订单号，必填
        $param['out_trade_no'] = $this->order_sn;
        //订单名称，必填
        $param['subject'] = 'OrderSn：'. $this->order_sn .' ('. $siteInfo['site_name'] .')';
        //付款金额，必填
        $param['total_fee'] = $this->order_amount;
        //订单描述
        $param['body'] = "";


        /*--扩展功能参数--*/
        //扩展功能参数――默认支付方式//
        //默认支付方式，取值见“即时到帐接口”技术文档中的请求参数列表
        // $param['paymethod'] = "";
        //默认网银代号，代号列表见“即时到帐接口”技术文档“附录”→“银行列表”
        // $param['defaultbank'] = "";

        //扩展功能参数――防钓鱼//
        //防钓鱼时间戳，若要使用请调用类文件submit中的query_timestamp函数
        // $param['anti_phishing_key'] = '';
        //客户端的IP地址，非局域网的外网IP地址，如：221.0.0.1
        // $param['exter_invoke_ip'] = '';

        //扩展功能参数――其他//
        //商品展示地址，需以http://开头的完整路径，不允许加?id=123这类自定义参数。例如：http://www.商户网址.com/myorder.html
        $param['show_url'] = '';
        //自定义参数，可存放任何内容（除=、&等特殊字符外），不会显示在页面上
        // $param['extra_common_param'] = '';

        //扩展功能参数――分润(若要使用，请按照注释要求的格式赋值)//
        // $param['royalty_type'] = '';//提成类型，该值为固定值：10，不需要修改
        // $param['royalty_params'] = '';

        //超时时间
        $param['it_b_pay'] = '';
        //钱包token
        $param['extern_token'] = '';

        return $param;
    }


}