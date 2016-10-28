<?php
namespace app\admin\controller;

use app\common\model\common;
use app\common\model\wechattool;
use think\Controller;
use think\Request;


/**
 *   授权成功之后会跳转到 该后台
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
        $info = json_decode($json_login_info, true);
        $user_type = $info['usertype'];
        if ($user_type == 5) {
            exit('您没有权限访问');
        }
        $email = $info['user_info']['email'];
        $corpid = $info['corp_info']['corpid'];
        //这个可以保存在 session 中
        $login_ticket = $info['redirect_login_info']['login_ticket'];

        //获取的登陆的 url
        $get_login_url = 'https://qyapi.weixin.qq.com/cgi-bin/service/get_login_url?access_token=' . wechattool::get_provider_token();
        $post = json_encode([
            'login_ticket' => $login_ticket,
            'target' => 'agent_setting',
            'agentid' => 49,
        ]);
        $json_login_url_info = common::send_curl_request($get_login_url, $post, 'post');
        $login_url_info = json_decode($json_login_url_info, true);
        //print_r($login_url_info);
        if ($login_url_info['errcode'] != 0) {
            exit('参数异常，请重试');
        }
        echo $login_url_info['login_url'];
        header('Location:' . $login_url_info['login_url']);
    }

}
