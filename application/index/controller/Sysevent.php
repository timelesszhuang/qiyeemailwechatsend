<?php
namespace app\index\controller;

use think\Controller;
use think\Loader;
use think\Request;

class Sysevent extends Controller
{
    public function index()
    {
        //企业号后台随机填写的encodingAesKey
        $encodingAesKey = "uYQlKaDCeljc1AEDzIDNrFHGQF6haPh9Lsdv1InA9qA";
        //企业号后台随机填写的token
        $token = "eCL75fcA";
        //引入放在Thinkphp下的wechat 下的微信加解密包
        Loader::import('wechat.WXBizMsgCrypt', EXTEND_PATH, '.php');
        //安装官方要求接收4个get参数 并urldecode处理
        // 获取当前请求的name变量
        $msg_signature = urldecode(Request::instance()->param('msg_signature'));
        $timestamp = urldecode(Request::instance()->param('timestamp'));
        $nonce = urldecode(Request::instance()->param('nonce'));
        $echostr = urldecode(Request::instance()->param('echostr'));
        //实例化加解密类
        file_put_contents('a.txt', '$msg_signature:' . $msg_signature . '$timestamp:' . $timestamp . '$nonce:' . $nonce . '$echostr:' . $echostr, FILE_APPEND);
        try {
            $wxcpt = new \WXBizMsgCrypt($token, $encodingAesKey, 'wxe041af5a55ce7365');
            //解密数据最后 将解密后的数据返回给微信 成功会返回0 会将解密后的数据放入$echostr中
            $errCode = $wxcpt->VerifyURL($msg_signature, $timestamp, $nonce, $echostr, $reecho);
            if ($errCode == 0) {
                echo $reecho;
            } else {
                print $errCode;
            }
        } catch (Exception $e) {
            file_put_contents('a.txt', $e->getMessage(), FILE_APPEND);
        }
    }
}
