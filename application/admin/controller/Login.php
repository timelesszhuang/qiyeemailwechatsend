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
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/service/get_login_info?access_token=' . wechattool::get_provider_token();
        $post = json_encode(['auth_code' => Request::instance()->param('auth_code')]);
        $json_login_info = common::send_curl_request($url, $post, 'post');
        print_r($json_login_info);
    }

}
