<?php
namespace paymentOld\alipay;

// use coreFunc;
use AlipaySubmit;
use AlipayNotify;

/**
* 支付宝支付接口
*/
class WorkPlugin
{
    var $plugin_id = 'alipaywap';// 插件唯一ID
    private $p_set = [];
    private $notify_url = '';
    private $return_url = '';
    private $dir = '';// getcwd()
    private $host = '';

    /**
     * +----------------------------------------------------------
     * 构造函数
     * $order_sn 订单编号
     * $order_amount 订单金额
     * $root_url 站点跟网址
     * +----------------------------------------------------------
     */
    function __construct($order_sn='', $order_amount='', $root_url='') {
        $this->order_sn = $order_sn;
        $this->order_amount = $order_amount;
        $this->root_url = $root_url;
        // TP写法
        import('paymentOld/'.$this->plugin_id.'/lib/coreFunc',EXTEND_PATH);
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
     * 
     * +----------------------------------------------------------
    */
    public function workForm() {
        // 建立请求
        $alipaySubmit = new AlipaySubmit($this->p_set());
        $html_text = $alipaySubmit->buildRequestForm($this->parameter(),"post","付款");
        return $html_text;
    }

    public function workUrl($data=[])
    {
        // 建立请求
        $alipaySubmit = new AlipaySubmit($this->p_set());
        $sResult = $alipaySubmit->buildRequestURL($this->parameter());

        // URL跳转
        $sResult = str_replace('&amp','&',$sResult);// 替换实体字符
        echo '<script src="'.THEME_S.'js/jquery-1.12.1.min.js"></script><script type="text/javascript">window.location.href="'.$sResult.'"</script>';exit;

        // return $sResult;
    }

    public function workCurl($data=[])
    {
        // 建立请求
        $alipaySubmit = new AlipaySubmit($this->p_set());
        $sResult = $alipaySubmit->buildRequestHttp($this->parameter());
        return $sResult;
    }

    /*
    * 功能：支付宝页面跳转同步通知页面
    */
    public function getReturn($data=[])
    {
        //计算得出通知验证结果
        $alipayNotify = new AlipayNotify($this->p_set());
        $verify_result = $alipayNotify->verifyReturn();

        if ($verify_result) { //验证成功
            //请在这里加上商户的业务逻辑程序代码
            //请根据您的业务逻辑来编写程序（以下代码仅作参考）
            //获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表
            // print_r($_GET);die;
            // $out_trade_no   = $_GET['out_trade_no'];//商户订单号
            // $trade_no       = $_GET['trade_no'];//支付宝交易号
            $trade_status   = $_GET['trade_status'];//交易状态
            // $total_fee      = $_GET['total_fee'];//交易金额
            // $notify_id      = $_GET['notify_id'];//通知校验ID
            // $notify_time    = $_GET['notify_time'];//通知的发送时间
            // $buyer_email    = $_GET['buyer_email'];//买家支付宝帐号

            if($trade_status=='TRADE_FINISHED' || $trade_status=='TRADE_SUCCESS') {
                return $_GET;//支付成功
            } else {
                return false;//支付失败
            }
        } else {
            //验证失败
            //调试
            $this->log($_GET);
            
            return false;
        }
    }

    /*
    * 功能：支付宝服务器异步通知页面
    */
    public function getNotify($data=[])
    {
        //计算得出通知验证结果
        $alipayNotify = new AlipayNotify($this->p_set());
        $verify_result = $alipayNotify->verifyNotify();

        if($verify_result) { //验证成功
            //请在这里加上商户的业务逻辑程序代码
            //请根据您的业务逻辑来编写程序（以下代码仅作参考）
            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
            // print_r($_POST);die;

            //商户订单号
            $out_trade_no = $_POST['out_trade_no'];
            //支付宝交易号
            $trade_no = $_POST['trade_no'];
            //交易状态
            $trade_status = $_POST['trade_status'];

            if($trade_status == 'TRADE_FINISHED') {
                // 完成
                return array_merge($_POST,['status'=>10]);
            } elseif ($trade_status == 'TRADE_SUCCESS') {
                // 支付成功
                return array_merge($_POST,['status'=>1]);
            } else {
                print_r($_POST);die;
            }
            // echo "success";//请不要修改或删除
        } else {
            //验证失败
            // echo "fail";

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
            // foreach ($data as $key => $value) {
            //     $content .= $key.'='.$value.",";
            // }
            $content = var_export($data); 
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





    /**
     * +----------------------------------------------------------
     * 配置信息
     * +----------------------------------------------------------
    */
    function p_set() {
        // 获取插件配置信息
        // {"account":"","key":"","partner":"","switch":"0"}
        $option = cmf_get_option('alipay_settings');

        //↓↓↓↓↓↓↓↓↓↓请在这里配置您的基本信息↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
        // 合作身份者id，以2088开头的16位纯数字
        $p_set['partner']  = $option['partner'];
        // 安全检验码，以数字和字母组成的32位字符
        $p_set['key']   = $option['key'];
        // 签约支付宝账号或卖家支付宝帐户
        $p_set['seller_email'] = $option['account'];

        //页面跳转同步通知页面路径，要用 http://格式的完整路径，不允许加?id=123这类自定义参数
        //return_url的域名不能写成http://localhost/*** ，否则会导致return_url执行无效
        // $p_set['return_url'] = 'http://127.0.0.1/create_direct_pay_by_user_php_utf8/return_url.php';

        //服务器异步通知页面路径，要用 http://格式的完整路径，不允许加?id=123这类自定义参数
        // $p_set['notify_url'] = 'http://www.xxx.com/create_direct_pay_by_user_php_utf8/notify_url.php';
        //↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑
        
        // 签名方式 不需修改
        $p_set['sign_type']    = 'MD5';
        // $p_set['sign_type']    = strtoupper('MD5');
        
        // 字符编码格式 目前支持 gbk 或 utf-8
        $p_set['input_charset']= 'utf-8';
        // $p_set['input_charset']= strtolower('utf-8');
        // $p_set['input_charset']= strtolower('gbk');
        
        // 访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
        $p_set['transport']    = 'http';
        
        // ca证书路径地址，用于curl中ssl校验
        // 请保证cacert.pem文件在当前文件夹目录中
        $p_set['cacert']    = ROOT_PATH . 'include/plugin/' . $this->plugin_id . '/cacert.pem';
        
        return $p_set;
    }

    /**
     * +----------------------------------------------------------
     * 请求参数
     * +----------------------------------------------------------
     */
    function parameter() {
        $set = $this->p_set();

        $param['service'] = "create_direct_pay_by_user";
        //支付类型，必填，不能修改
        $param['payment_type'] = "1";
        // 字符编码格式 目前支持 gbk 或 utf-8
        $param['_input_charset'] = $set['input_charset'];
        
        // 收款支付宝账号
        $param['seller_email'] = trim($set['seller_email']);
        // 合作身份者id，以2088开头的16位纯数字
        $param['partner'] = trim($set['partner']);
        
        //服务器异步通知页面路径，需http://格式的完整路径，不能加?id=123这类自定义参数
        $param['notify_url'] = ROOT_URL . 'include/plugin/' . $this->plugin_id . '/notify_url.php';
        //页面跳转同步通知页面路径，需http://格式的完整路径，不能加?id=123这类自定义参数，不能写成http://localhost/
        $param['return_url'] = ROOT_URL . 'include/plugin/' . $this->plugin_id . '/return_url.php';

        //商户订单号，商户网站订单系统中唯一订单号，必填
        $param['out_trade_no'] = $this->order_sn;
        //订单名称，必填
        $param['subject'] = 'Order Sn : ' . $this->order_sn . ' (' . $GLOBALS['_CFG']['site_name'] . ')';
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
        $param['anti_phishing_key'] = '';
        //客户端的IP地址，非局域网的外网IP地址，如：221.0.0.1
        $param['exter_invoke_ip'] = '';

        //扩展功能参数――其他//
        //商品展示地址，需以http://开头的完整路径，不允许加?id=123这类自定义参数。例如：http://www.商户网址.com/myorder.html
        $param['show_url'] = '';
        //自定义参数，可存放任何内容（除=、&等特殊字符外），不会显示在页面上
        // $param['extra_common_param'] = '';

        //扩展功能参数――分润(若要使用，请按照注释要求的格式赋值)//
        // $param['royalty_type'] = '';//提成类型，该值为固定值：10，不需要修改
        // $param['royalty_params'] = '';

        return $param;
    }







}