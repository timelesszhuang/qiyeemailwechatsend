<?php
/**
 * Created by PhpStorm.
 * User: timeless
 * Date: 16-11-3
 * Time: 上午10:10
 */

namespace app\sysadmin\controller;


use app\sysadmin\model\common;
use think\Db;
use think\Request;
use think\Session;

class Sysadmin extends Base
{
    public function _initialize()
    {
        parent::_initialize();
    }


    /**
     * 重置密码操作
     * @access public
     */
    public function reset_pwd()
    {
        $this->get_assign();
        return $this->fetch();
    }


    /**
     * 执行重置密码
     * @access public
     */
    public function exec_reset_pwd()
    {
        $pre_pwd = Request::instance()->param('pre_pwd');
        $new_pwd1 = Request::instance()->param('new_pwd1');
        $new_pwd2 = Request::instance()->param('new_pwd1');
        if (!$pre_pwd) {
            return json(common::form_ajaxreturn_json('更改失败', '原密码不能为空', self::error));
        }
        // 验证下是不是有问题不能正常访问
        if (!$new_pwd1 || !$new_pwd2 || ($new_pwd1 != $new_pwd2)) {
            return json(common::form_ajaxreturn_json('更改失败', '两次密码不为空，且要保持一致。', self::error));
        }
        $login_name = Session::get('login_name');
        //首先验证下原来的密码是不是正确
        $input_encode_pwd = common::form_pwd_info($login_name, $pre_pwd);
        //从数据库获取下数据
        $map = ['login_name' => $login_name];
        $db = Db::name('sys_admin');
        if ($db->where($map)->find()['pwd'] != $input_encode_pwd) {
            return json(common::form_ajaxreturn_json('更改失败', '原密码输入不正确。', self::error));
        }
        if (!$db->where($map)->update(['pwd' => common::form_pwd_info($login_name, $new_pwd1),'updatetime'=>time()])) {
            return json(common::form_ajaxreturn_json('更改失败', '请联系管理员。', self::error));
        }
        return json(common::form_ajaxreturn_json('更改成功', '密码更改成功。', self::success));
    }

    /**
     * 账号登出设置
     */
    public function log_out()
    {
        Session::clear();
        $this->redirect('login/index', ['alert' => '登出成功']);
    }


}