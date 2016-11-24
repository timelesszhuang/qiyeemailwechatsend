<?php
/**
 * 系统管理员的 公共操作
 * User: timeless 赵兴壮<834916321@qq.com>
 * Date: 16-11-2
 * Time: 下午4:35
 */

namespace app\wap\model;


class common
{

    const error = 'failed';
    const success = 'success';


    /**
     * 生成密码加密信息
     * @access public
     * @param $login_name 登陆名
     * @param $pwd 登陆密码
     * @return string 加密之后的密码
     */
    public static function form_pwd_info($login_name, $pwd)
    {
        return md5(md5($login_name) . sha1($pwd));
    }


    /**
     * 生成要返回到前台的数据
     * @access public
     * @param $msg  提示
     * @param $title 提示标题
     * @param $status 状态
     * @return array
     */
    public static function form_ajaxreturn_arr($title, $msg, $status)
    {
        return ['msg' => $msg, 'title' => $title, 'status' => $status];
    }

}