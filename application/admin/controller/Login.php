<?php
namespace app\admin\controller;

use app\admin\model\cachetool;
use app\common\model\common;
use app\common\model\wechattool;
use think\Controller;
use think\Db;
use think\Request;
use think\Session;


/**
 *授权成功之后会跳转到 该后台  这个是用户的操作 后台只能是系统管理员才能进行操作
 */
class Login extends Controller
{
    /**
     * 接收系统事件
     * @access public
     */
    public function index()
    {
        //获取登陆这的相关信息
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/service/get_login_info?access_token=' . wechattool::get_provider_token();
        $auth_code = Request::instance()->param('auth_code');
        if (!$auth_code) {
            exit('请求异常');
        }
        $post = json_encode(['auth_code' => $auth_code]);
        $json_login_info = common::send_curl_request($url, $post, 'post');
        //file_put_contents('a.txt', print_r($json_login_info, true), FILE_APPEND);
        $info = json_decode($json_login_info, true);
        if (array_key_exists('errcode', $info)) {
            exit('请求　auth_code　异常');
        }
        $user_type = $info['usertype'];
        if ($user_type == 5) {
            exit('您没有权限访问');
        }
        $corpid = $info['corp_info']['corpid'];
        //这个可以保存在 session 中
        $login_ticket = $info['redirect_login_info']['login_ticket'];
        Session::set('corpid', $corpid);
        Session::set('login_ticket', $login_ticket);
        //然后根据 login_ticket
        //然后根据 corp_id 获取邮箱登录信息
        //根据corpid 获取 私钥,product,domain 等数据
        $bind_info = cachetool::get_bindinfo_bycorpid($corpid);
        Session::set('permanent_code', Db::name('auth_corp_info')->where(['corpid' => $corpid])->find()['permanent_code']);
        if (!empty($bind_info)) {
            Session::set('api_status', $bind_info['api_status']);
            Session::set('flag', $bind_info['flag']);
            Session::set('corp_id', $bind_info['corp_id']);
            Session::set('corp_name', $bind_info['corp_name']);
            Session::set('privatesecret', $bind_info['privatesecret']);
            Session::set('product', $bind_info['product']);
            Session::set('domain', $bind_info['domain']);
            $this->redirect('Index/index');
        } else {
            //账号信息有问题  提示联系我们
            return $this->fetch('index/index', ['msg' =>'贵公司网易企业邮箱接口暂时不可用，请拨打 4006360163 （网易企业服务） 联系我们，或通过 在线咨询 联系我们。']);
        }

    }


    /**
     * 从 我们的 管理后台跳转到 微信管理后台的页面
     * @param $login_ticket 登陆的相关信息
     * @param $agentid 客户 授权方的 应用id 比如我的是 49
     */
    public function login_wechat($login_ticket, $agentid)
    {
        //获取的登陆的 url
        $get_login_url = 'https://qyapi.weixin.qq.com/cgi-bin/service/get_login_url?access_token=' . wechattool::get_provider_token();
        $post = json_encode([
            'login_ticket' => $login_ticket,
            'target' => 'agent_setting',
            'agentid' => $agentid,
        ]);
        $json_login_url_info = common::send_curl_request($get_login_url, $post, 'post');
        $login_url_info = json_decode($json_login_url_info, true);
        if ($login_url_info['errcode'] != 0) {
            exit('参数异常，请重试');
        }
        ob_start();
        ob_end_flush();
        header('Location:' . $login_url_info['login_url']);
    }


    /**
     * 退出登陆
     * @access public
     */
    public function log_out()
    {
        Session::clear();
        return $this->fetch('login/exit', ['msg' => '登出成功，再次进入该系统请从微信企业号中进入。']);
    }


}
