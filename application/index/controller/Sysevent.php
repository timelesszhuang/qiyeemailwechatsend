<?php

namespace app\index\controller;

use app\common\model\wechattool;
use app\index\model\auth;
use think\Config;
use think\Controller;

use app\common\model\common;
use app\index\model\SyseventModel;
use think\Exception;
use think\Loader;
use think\Request;
use think\Session;

/**
 * 接收系统事件
 */
class Sysevent extends Controller
{
    /**
     * 接收系统事件
     * 1、获取 suite_token  系统会向  这个请求发送 suite_token 每十分钟会更新一次 系统自动通知。
     * 2、获取临时授权码 授权流程完成后，企业号第三方应用官网的后台将临时授权码回调到套件的“系统事件接收URL”。
     * 3、变更授权通知  当授权方（即授权企业号）在企业号管理端的授权管理中，修改了对套件方的授权托管后，微信服务器会向应用提供商的套件事件接收 URL（创建套件时填写）推送变更授权通知。
     * 4、取消授权的通知  在企业号管理端的授权管理中，取消了对套件方的授权托管后，微信服务器会向应用提供商的套件事件接收 URL（创建套件时填写）推送取消授权通知。
     * 5、授权成功推送auth_code事件  使用方式为‘线上自助注册授权使用’的套件，从企业号第三方官网发起授权时，微信服务器会向应用提供商的套件事件接收 URL（创建套件时填写）推送授权成功通知；从应用提供商网站发起的应用套件授权流程，由于授权完成时会跳转应用提供商管理后台，微信服务器不会向应用提供商推送授权成功通知。
     * 6、当套件所管理的通讯录发生变更(包括管理范围的扩大缩小及成员、部门信息修改等)，微信服务器会向应用提供商的套件事件接收 URL（创建套件时填写）推送通讯录变更通知
     * @access public
     */
    public function index()
    {
//       SyseventModel::verify_url();
//       SyseventModel::test_xml();
        //企业号后台随机填写的encodingAesKey
//        $this->verifyUrl();
        SyseventModel::event();
    }


    /**
     * 验证 url
     */
    public function verifyUrl()
    {
        try {
            $encodingAesKey = Config::get('wechatsuite.EMAILSEND_ENCODINGAESKEY');
            //企业号后台随机填写的token
            $token = Config::get('wechatsuite.EMAILSEND_TOKEN');
            $suite_id = Config::get('wechatsuite.EMAILSEND_SUITE_ID');
            $corp_id = Config::get('wechatsuite.CORPID');
            //引入放在Thinkphp下的wechat 下的微信加解密包
            Loader::import('wechat.WXBizMsgCrypt', EXTEND_PATH, '.php');
            //安装官方要求接收4个get参数 并urldecode处理
            // 获取当前请求的name变量
            $msg_signature = urldecode(Request::instance()->get('msg_signature'));
            $timestamp = urldecode(Request::instance()->get('timestamp'));
            $nonce = urldecode(Request::instance()->get('nonce'));
            $echoStr = urldecode(Request::instance()->get('echostr'));
            //实例化加解密类
            //授权的地方不是 使用suite_id 使用 try catch  一部分使用的是
            file_put_contents('postdata.txt', 'get:' . print_r(Request::instance()->get(), true), FILE_APPEND);
            $wxcpt = new \WXBizMsgCrypt($token, $encodingAesKey, $corp_id);
            $errCode = $wxcpt->VerifyURL($msg_signature, $timestamp, $nonce, $echoStr, $sMsg);
            //身份校验
            file_put_contents('postdata.txt', 'errorCode:' . $errCode, FILE_APPEND);
            echo $sMsg;
            file_put_contents('postdata.txt', 'msg: ' . $sMsg, FILE_APPEND);
            exit;
        } catch (Exception $ex) {
            file_put_contents('error.txt', $ex->getMessage(), FILE_APPEND);
        }
    }


    /**
     * 从第三方接口授权过来的时候会 重定向到这 带着预授权码  然后再从 微信中获取
     * @access
     */
    public function trd_suite_callback()
    {
        $auth_code = Request::instance()->get('auth_code');
        $suite_flag = Request::instance()->get('state');
        if (!$auth_code) {
            exit('请求异常');
        }
        list($status, $corpid) = SyseventModel::analyse_permanent_codeinfo(Config::get("wechatsuite.{$suite_flag}_SUITE_ID"), $auth_code);
        if ($status) {
            //从数据库中 查询处corp_id
            Session::set('corpid', $corpid);
            header('Location:' . Config::get('common.DOMAIN') . '/index.php/admin/');
            exit;
        } else {
            exit('授权失败，请重试或联系我们：客服电话 4006360163 ；');
        }
    }


}
