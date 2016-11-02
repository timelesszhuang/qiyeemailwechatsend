<?php
/**
 * Created by PhpStorm.
 * User: timeless
 * Date: 16-11-2
 * Time: 上午11:02
 */

namespace app\sysadmin\controller;


use app\sysadmin\model\common;
use think\Controller;
use think\Db;
use think\Request;
use think\Session;

class Login extends Controller
{

    /**
     * 跳转到登录页面
     * @access public
     */
    public function index()
    {
        return $this->fetch('index', ['msg' => Request::instance()->param('alert')]);
    }

    /**
     * 验证登录
     * @access public
     */
    public function do_login()
    {
        //cookies 存储的时候应该用
        $ver_code = Request::instance()->param('vd_code'); //验证码
        if (!captcha_check($ver_code)) {
            //验证失败
            $this->redirect_login('验证码填写错误或验证码失效。');
        };
        $login_name = Request::instance()->param('user_name');
        $pwd = Request::instance()->param('user_password');
        if (!empty($login_name) && !empty($pwd)) {
            //数据库中匹配密码正确与否
            if ($info = Db::name('sys_admin')->where(['pwd' => common::form_pwd_info($login_name, $pwd), 'login_name' => $login_name])->find()) {
                Session::set('id', $info['id']);
                Session::set('name', $info['name']);
                $this->redirect('index/index', ['msg' => '登录成功。']);
            }
            $this->redirect_login('账号或密码错误！');
        }
        $this->redirect_login('账号密码都不能为空！');
    }

    /**
     * 跳转到 登陆页面
     * @access private
     * @param $info 要传递的消息
     */
    private function redirect_login($info)
    {
        $this->redirect('login/index', ['alert' => $info]);
        exit;
    }
}