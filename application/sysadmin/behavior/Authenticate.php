<?php

namespace app\sysadmin\Behavior;

use think\Session;

/**
 * 验证登录状态 行为
 */
class Authenticate
{

    /**
     * 每个组织机构
     * @access public
     * @param mixed $params
     * @return bool|void
     * @internal param string $param 参数
     */
    public function run(&$params)
    {
        return Session::has('id') ? true : false;


        /*      //表示没有登录    记住本次操作不能执行修改cookies 否则登录时候设置的信息失败
                if (!session('?USER_ID') && intval(cookie('user_id')) != 0 && trim(cookie('login_name')) != '' && trim(cookie('salt_code')) != '') {
                    //这个地方是支持直接登录的
                    $user_v_m = D("UserLoginView");
                    $user_m = D('User');
                    $condition['id'] = array('eq', cookie('user_id'));
                    $rs = $user_v_m->where($condition)->find();
                    if ($user_m->form_saltcode($rs['id'], $rs['login_name'], $rs['login_pwd'], $rs['salt']) == trim(cookie('salt_code'))) {
                        session('IS_LOGIN', 'TRUE');
                        session('USER_ID', $rs['id']);
                        session('USER_NAME', $rs['login_name']);
                        session('NAME', $rs['name']);
                        session('EMAIL', $rs['email']);
                        //所在部门的id
                        session('DEP_ID', $rs['department_id']);
                        //查看是不是主管  是的话
                        //保存manage_id
                        $dep_id = $user_m->check_is_manage();
                        if ($dep_id) {
                            session('IS_MANAGER', 'TRUE');
                            session('MANAGE_DEP_ID', $dep_id);
                        }
                        //这个地方要将session_id写入到memcache 保持客户端登陆单点
                        $user_m->save_session_id();
                        //不要这种重定向
                        //成功之后调用index模块
                        alert('success', '登陆成功');
                        redirect(__MODULE__ . "/Index");
                    } else {
                        alert('danger', '由于修改账号信息，自动登录已经取消。', $_SERVER['HTTP_REFERER']);
                        redirect(__MODULE__ . "/Login");
                    }
                } else {
                    if (session('?USER_ID')) {
                        //验证是不是包含其他的数据
                        $this->_check_login_status();
                        if (!isset($_SESSION['IS_LOGIN'])) {
                            //跳转到登登录页面
                            redirect(U('Home/Login/index'));
                        } else {
                            return true;
                        }
                    } else {
                        redirect(U('Home/Login/index'));
                    }
                }*/
    }


    /**
     * _check_login_status
     * 验证是不是有其他的客户端登录
     * 如果有的话该客户端自动掉线
     * @todo 跳转到指定的页面
     */
    /*    private function _check_login_status()
        {
            //判断一下session_id然后从memcache中取出
            $mem = get_mem_obj();
            $user_id = session('USER_ID');
            //获取session_id
            if ($user_id) {
                //本次的session 跟之前的session 比较
                $session_id = session_id();
                $key = "login_session_id{$user_id}";
                $pre_session_id = $mem->get($key);
                if ($session_id != $pre_session_id && $pre_session_id != '') {
                    //销毁session
                    session(null);
                    //这个地方以后要修改   现在不能正常用L()获取L();
                    alert('danger', '您的账号在其他客户端登录，若不是本人操作请修改密码。', $_SERVER['HTTP_REFERER']);
                    redirect(__MODULE__ . "/Login");
                }
            }
        }*/

}
