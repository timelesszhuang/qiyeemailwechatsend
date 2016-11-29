<?php
/**
 * Created by PhpStorm.
 * User: timeless
 * Date: 16-11-29
 * Time: 上午11:19
 */

namespace app\index\controller;


use app\common\model\wechattool;
use think\Config;
use think\Controller;

class Index extends Controller
{

    /**
     * 应用的首页
     */
    public function index()
    {
        return $this->fetch('index');
    }

    /**
     * 重定向到 其他授权的后台
     */
    public function redirect_auth()
    {
        $suite_id = Config::get('wechatsuite.EMAILSEND_SUITE_ID');
        $pre_auth_code = wechattool::get_pre_auth_code();
        $redirect_uri = Config::get('common.DOMAIN') . 'index.php/admin/login/index';
        $url = "https://qy.weixin.qq.com/cgi-bin/loginpage?suite_id=$suite_id&pre_auth_code=$pre_auth_code&redirect_uri=$redirect_uri&state=0";
//      $url = "http://qy.weixin.qq.com/cgi-bin/3rd_loginpage?action=jumptoauthpage&suiteid=$suite_id";
        header('Location:' . $url);
        exit;
    }

}