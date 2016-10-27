<?php
namespace app\index\controller;

use app\index\model\auth;
use think\Controller;

use app\common\model\common;
use app\index\model\SyseventModel;

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
        SyseventModel::event();
    }


}
