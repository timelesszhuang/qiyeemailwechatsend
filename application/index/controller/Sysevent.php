<?php
namespace app\index\controller;

use think\Config;
use think\Controller;
use think\Loader;
use think\Request;

/**
 * 接收系统事件
 */
class Sysevent extends Controller
{
    /**
     * 接收系统事件
     * 1、系统会向 这个请求发送 suite_token
     * @access public
     */
    public function index()
    {
        //企业号后台随机填写的encodingAesKey
        $encodingAesKey = Config::get('EMAILSEND_ENCODINGAESKEY');
        //企业号后台随机填写的token
        $token = Config::get('EMAILSEND_TOKEN');
        $suite_id = Config::get('EMAILSEND_SUITE_ID');
        //引入放在Thinkphp下的wechat 下的微信加解密包
        Loader::import('wechat.WXBizMsgCrypt', EXTEND_PATH, '.php');
        //安装官方要求接收4个get参数 并urldecode处理
        // 获取当前请求的name变量
        $msg_signature = urldecode(Request::instance()->param('msg_signature'));
        $timestamp = urldecode(Request::instance()->param('timestamp'));
        $nonce = urldecode(Request::instance()->param('nonce'));
        //实例化加解密类
        //file_put_contents('a.txt', '$msg_signature:' . $msg_signature . '$timestamp:' . $timestamp . '$nonce:' . $nonce . '$echostr:' . $echostr, FILE_APPEND);
        $wxcpt = new \WXBizMsgCrypt($token, $encodingAesKey, $suite_id);
        $sPostData = file_get_contents("php://input");
        $errCode = $wxcpt->DecryptMsg($msg_signature, $timestamp, $nonce, $sPostData, $sMsg);
        //验证通过
        if ($errCode == 0) {
            $xml = new \DOMDocument();
            $xml->loadXML($sMsg);
            file_put_contents('a.txt', var_dump($sMsg), FILE_APPEND);
//            $reqToUserName = $xml->getElementsByTagName('ToUserName')->item(0)->nodeValue;
//            $reqFromUserName = $xml->getElementsByTagName('FromUserName')->item(0)->nodeValue;
//            $reqCreateTime = $xml->getElementsByTagName('CreateTime')->item(0)->nodeValue;
//            $reqMsgType = $xml->getElementsByTagName('MsgType')->item(0)->nodeValue;
//            $this->touser = $reqFromUserName;
//            //匹配类型
//            switch ($reqMsgType) {
//                case "event":
//                    $reqEvent = $xml->getElementsByTagName('Event')->item(0)->nodeValue;
//                    switch ($reqEvent) {
//                        //进入事件
//                        case "enter_agent":
//                            $this->enter_agent();
//                            break;
//                    }
//                    break;
//            }
        }
        file_put_contents('a.txt', '错误：' . $errCode, FILE_APPEND);
        echo 'success';
    }

    /**
     * 验证回调的url   回调相关的里面参数可以修改为已经包含的参数
     * @access private
     */
    private function verify_url()
    {
        $encodingAesKey = Config::get('EMAILSEND_ENCODINGAESKEY');
        //企业号后台随机填写的token
        $token = Config::get('EMAILSEND_TOKEN');
        $corp_id = Config::get('CORPID');
        //引入放在Thinkphp下的wechat 下的微信加解密包
        Loader::import('wechat.WXBizMsgCrypt', EXTEND_PATH, '.php');
        //安装官方要求接收4个get参数 并urldecode处理
        // 获取当前请求的name变量
        $msg_signature = urldecode(Request::instance()->param('msg_signature'));
        $timestamp = urldecode(Request::instance()->param('timestamp'));
        $nonce = urldecode(Request::instance()->param('nonce'));
        $echostr = urldecode(Request::instance()->param('echostr'));
        //实例化加解密类
        //file_put_contents('a.txt', '$msg_signature:' . $msg_signature . '$timestamp:' . $timestamp . '$nonce:' . $nonce . '$echostr:' . $echostr, FILE_APPEND);
        try {
            $wxcpt = new \WXBizMsgCrypt($token, $encodingAesKey, $corp_id);
            //解密数据最后 将解密后的数据返回给微信 成功会返回0 会将解密后的数据放入$echos
            $errCode = $wxcpt->VerifyURL($msg_signature, $timestamp, $nonce, $echostr, $reecho);
            //file_put_contents('a.txt', 'errorcode:' . $errCode, FILE_APPEND);
            if ($errCode == 0) {
                echo $reecho;
            } else {
                print $errCode;
            }
        } catch (Exception $e) {
            //file_put_contents('a.txt', '$exception:' . $e->getMessage(), FILE_APPEND);
        }

    }
}
