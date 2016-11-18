<?php

/**
 * Created by PhpStorm.
 * 微信推送
 * User: Administrator
 * Date: 2016/11/16
 * Time: 9:49
 */
namespace app\admin\controller;


use app\admin\model\cachetool;
use app\common\model\common;
use app\common\model\wechattool;
use think\Db;
use think\Request;
use think\Session;

class Wechatmailcheck extends Base
{

    /**
     * 邮箱注册
     * @access public
     */
    public function index()
    {
        return $this->fetch('index');
    }

    /**
     * 微信邮箱绑定测试
     * @access public
     */
    public function index_mailcheck_json()
    {
        #查询条件
        list($firstRow, $pageRows) = common::get_page_info();
        $m = Db::name('wechat_user');
        $name = Request::instance()->param('name');
        $corpid = Session::get('corpid');
        $where['corpid'] = $corpid;
        if ($name) {
            $where['name'] = ['like', $name];
        }
        $count = $m->where($where)->count('id');
        $list = $m->where($where)
            ->limit($firstRow, $pageRows)
            ->order('id desc')
            ->field('id,check_name,name,check_email,email,status,addtime,checktime')
            ->select();
        array_walk($list, array($this, 'format_json_list'));
        $array = array();
        if (!empty($list)) {
            //需要格式化一些数据
            $array['total'] = $count;
            $array['rows'] = $list;
        } else {
            $array['total'] = 0;
            $array['rows'] = array();
        }
        echo json_encode($array);
    }


    /**
     * 格式化每一个审核的用户的数据
     * @access private
     * @param $val
     */
    private function format_json_list(&$val)
    {
        $val['addtime'] = date('Y-m-d H:i:s', $val['addtime']);
        $val['checktime'] = $val['checktime'] ? date('Y-m-d H:i:s', $val['checktime']) : '';
    }


    /**
     * 添加微信 邮件推送
     * @access public
     */
    public function add_wechatmail()
    {
        $this->get_assign();
        return $this->fetch('add_wechatmail');
    }

    /**
     * 执行修改绑定用户操作
     * @access public
     */
    public function exec_add_wechatmail()
    {
        $data['corp_id'] = Session::get('corp_id');
        $data['corp_name'] = $corpid = Session::get('corp_name');
        $data['corpid'] = $corpid = Session::get('corpid');
        $permanent_code = Session::get('permanent_code');
        $data['wechat_userid'] = $wechat_userid = Request::instance()->param('wechat_user_id');
        $data['check_name'] = Request::instance()->param('check_name');
        $data['check_email'] = Request::instance()->param('check_email');
        $data['status'] = '20';
        $data['addtime'] = time();
        $sys_title = '添加账号绑定成功';
        $err_title = '添加账号绑定失败';
        if (!$data['wechat_userid']) {
            return json(\app\sysadmin\model\common::form_ajaxreturn_arr($err_title, '请填写微信ID', self::error));
        }
        //还需要验证是不是该帐号已经存在
        if (!$data['check_name']) {
            return json(\app\sysadmin\model\common::form_ajaxreturn_arr($err_title, '请填写姓名', self::error));
        }
        if (!common::check_email($data['check_email'])) {
            return json(\app\sysadmin\model\common::form_ajaxreturn_arr($err_title, '邮箱格式不正确，请修改。', self::error));
        } else {
            //获取下当前的账号的 域名后缀
            $domain = '@' . Session::get('domain');
            if (substr($data['check_email'], strpos($data['check_email'], '@')) != $domain) {
                return json(\app\sysadmin\model\common::form_ajaxreturn_arr('', "邮箱后缀不正确，应该为{$domain}", self::error));
            }
        }
        $m = Db::name('wechat_user');
        if ($m->where(["wechat_userid" => $wechat_userid, 'corpid' => $corpid])->find()) {
            return json(\app\sysadmin\model\common::form_ajaxreturn_arr($err_title, "该微信绑定信息已经添加过。", self::error));
        }
        //还需要从微信的后台获取 name 邮箱地址 还有 手机号
        $data['account'] = substr($data['check_email'], 0, strpos($data['check_email'], '@'));
        //获取制定的微信id 的微信相关信息
        list($name, $mobile, $email) = wechattool::get_wechat_userid_info($wechat_userid, wechattool::get_corp_access_token($corpid, $permanent_code));
        if (!$name) {
            return json(\app\sysadmin\model\common::form_ajaxreturn_arr($err_title, "该微信ID不存在，请到微信后台添加该职员账号。", self::error));
        }
        $data['name'] = $name;
        $data['mobile'] = $mobile ?: '';
        $data['email'] = $email ?: '';
        $data['lastgetmailtime'] = time();
        $data['check_time'] = 0;
        if ($m->insert($data)) {
            return json(\app\sysadmin\model\common::form_ajaxreturn_arr($sys_title, "账号绑定添加成功，请审核。", self::success));
        }
        return json(\app\sysadmin\model\common::form_ajaxreturn_arr($err_title, "账号绑定失败，请重试。", self::error));
    }


    /**
     * 更新微信 邮箱的绑定信息
     * @access public
     */
    public function edit_wechatmail()
    {
        $this->get_assign();
        $this->assign('r', Db::name('WechatUser')->where(['id' => ['eq', Request::instance()->param('id')]])->find());
        return $this->fetch('edit_wechatmail');
    }

    /**
     * 执行修改绑定用户操作
     * @access public
     */
    public function exec_edit_wechatmail()
    {
        $data['id'] = Request::instance()->param('id');
        $data['check_name'] = Request::instance()->param('check_name');
        $data['check_email'] = Request::instance()->param('check_email');
        $data['status'] = '20';
        $data['addtime'] = time();
        $err_title = '修改信息失败请重试。';
        $suc_title = '修改信息成功。';
        if (!$data['id']) {
            return json(\app\sysadmin\model\common::form_ajaxreturn_arr($err_title, "修改信息失败", self::error));
        }
        //还需要验证是不是该帐号已经存在
        if (!$data['check_name']) {
            return json(\app\sysadmin\model\common::form_ajaxreturn_arr($err_title, "请填写姓名", self::error));
        }
        if (!common::check_email($data['check_email'])) {
            return json(\app\sysadmin\model\common::form_ajaxreturn_arr($err_title, "邮箱格式不正确，请修改。", self::error));
        } else {
            $domain = '@' . Session::get('domain');
            if (substr($data['check_email'], strpos($data['check_email'], '@')) != $domain) {
                return json(\app\sysadmin\model\common::form_ajaxreturn_arr($err_title, "邮箱后缀不正确，应该为{$domain}", self::error));
            }
        }
        $data['account'] = substr($data['check_email'], 0, strpos($data['check_email'], '@'));
        if (Db::name('wechat_user')->update($data)) {
            return json(\app\sysadmin\model\common::form_ajaxreturn_arr($err_title, "修改成功，请重新审核。", self::success));
        }
        return json(\app\sysadmin\model\common::form_ajaxreturn_arr($err_title, "信息修改失败，请重试。", self::error));
    }


    /**
     * 审核通过验证 微信 账号
     * @access public
     */
    public function check_wechatmail()
    {
        $id = Request::instance()->param('id');
        if (Db::name('Wechat_user')->where(['id' => ['eq', $id]])->update(['status' => '10', 'checktime' => time()])) {
            return json(\app\sysadmin\model\common::form_ajaxreturn_arr('用户审核成功', "用户审核成功。", self::success));
        }
        return json(\app\sysadmin\model\common::form_ajaxreturn_arr('用户审核失败', "用户审核成功。", self::failed));
    }

    /**
     * 否决审核 通过验证 微信 账号
     * @access public
     */
    public function notcheck_wechatmail()
    {
        $id = Request::instance()->param('id');
        if (Db::name('Wechat_user')->where(['id' => ['eq', $id]])->update(['status' => '30', 'checktime' => time()])) {
            return json(\app\sysadmin\model\common::form_ajaxreturn_arr('用户否决成功', "用户否决成功。", self::success));
        }
        return json(\app\sysadmin\model\common::form_ajaxreturn_arr('用户否决失败', "用户否决失败。", self::failed));
    }


}
