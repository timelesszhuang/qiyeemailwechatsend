<?php
namespace app\index\controller;

use think\Controller;

class Sysevent extends Controller
{
    public function index()
    {
        //企业号后台随机填写的encodingAesKey
        $encodingAesKey = "uYQlKaDCeljc1AEDzIDNrFHGQF6haPh9Lsdv1InA9qA";
        //企业号后台随机填写的token
        $token = "eCL75fcA";
        //引入放在Thinkphp下的Library/Vendor/wechat 下的微信加解密包
        import('vendor.wechat.WXBizMsgCrypt', '', '.php');
        //安装官方要求接收4个get参数 并urldecode处理
        $msg_signature = urldecode(I("get.msg_signature"));
        $timestamp = urldecode(I("get.timestamp"));
        $nonce = urldecode(I("get.nonce"));
        $echostr = urldecode(I("get.echostr"));
        //实例化加解密类
        $wxcpt = new \WXBizMsgCrypt($token, $encodingAesKey, 'wxe041af5a55ce7365');
        //解密数据最后 将解密后的数据返回给微信 成功会返回0 会将解密后的数据放入$echostr中
        $errCode = $wxcpt->VerifyURL($msg_signature, $timestamp, $nonce, $echostr, $reecho);
        if ($errCode == 0) {
            echo $reecho;
        }
    }
}
