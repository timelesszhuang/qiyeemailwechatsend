<?php

namespace app\admin\controller;

use app\admin\model\agent;
use app\admin\model\cachetool;
use app\admin\model\wechatuser;
use app\common\model\common;
use app\common\model\wechattool;
use think\Controller;
use think\Db;
use think\Request;
use think\Session;


/**
 * 绑定微信操作
 */
class Bindwechat extends Controller
{
    /**
     * 接收系统事件
     * @access public
     */
    public function bind()
    {
        //获取相关信息
        $token = Request::instance()->param('token');
        $corpid = Request::instance()->param('corpid');
        $wechat_userid = Request::instance()->param('wechat_userid');
        if (agent::get_bind_url_token($corpid, $wechat_userid) != $token) {
            //请求信息有误请重新绑定
            return $this->fetch('condition_oath', ['data' => [], 'status' => '10', 'msg' => '请求信息有错误 请重新进入应用后重试。']);
        }
        //status 10 表示 请求有问题 20表示 已经提交等待审核  30 表示 信息有误 审核未通过 40 表示 审核通过 50 表示第一次进入
        //判断已经绑定的信息
        //获取该 corp_id 中已经绑定的信息
        //根据 corpid 跟 wechat_userid 获取绑定状态
        list($status, $info) = wechatuser::check_wechat_userid_status($corpid, $wechat_userid);
        if (!$status) {
            return $this->fetch('bind', ['status' => '50', 'data' => ['name' => '', 'corpid' => $corpid, 'check_email' => '', 'wechat_userid' => $wechat_userid, 'msg' => '请填写绑定信息', 'id' => 0]]);
        }
        $check_status = $info['status'];
        switch ($check_status) {
            case '10':
                $assign_data = ["status" => '10', "msg" => '您已经绑定,邮箱账号，可以正常收到邮件推送!!。'];
                break;
            case '20':
                $assign_data = ["status" => '20', "msg" => '您已经提交网易企业邮箱绑定信息，请等待贵公司管理员审核。'];
                break;
            case '30':
                $info['msg'] = '您提交的信息有误，贵公司管理员审核未通过，请重新填写之后等待管理员审核。';
                $assign_data = ["status" => '30', 'data' => $info];
                break;
        }
        return $this->fetch('bind', $assign_data);
    }


    /**
     *
     */
    /*  public function test_bind()
        {
            return $this->fetch('bind', ['status' => '50', 'data' => ['name' => '', 'check_email' => '', 'msg' => '请填写绑定信息', 'id' => 0, 'corpid' => 'wxe041af5a55ce7365', 'wechat_userid' => 'xingzhuang']]);
        }*/

    /**
     * 执行绑定微信操作
     * @access public
     */
    public function exec_bind_wechat()
    {
        $name = Request::instance()->param("name");
        $check_email = Request::instance()->param('check_email');
        //微信 wechat_userid
        $wechat_userid = Request::instance()->param("wechat_userid");
        $corpid = Request::instance()->param('corpid');
        $id = Request::instance()->param('id', 0);
        $arr = array();
        //错误时跳回页面
        $display_url = "bindwechat/bind";
        if (empty($wechat_userid) || empty($corpid)) {
            $arr = ['msg' => '请重新进入页面认证'];
            return $this->fetch($display_url, ['data' => $arr, 'status' => '30',]);
        }
        if (empty($name)) {
            $arr = [
                'msg' => "请输入您的姓名。",
                'wechat_userid' => $wechat_userid,
                'corpid' => $corpid
            ];
            return $this->fetch($display_url, ['data' => $arr, 'status' => '30',]);
        }
        if (empty($check_email)) {
            $arr = [
                'name' => $name,
                'msg' => "请输入网易企业邮箱账号。",
                'wechat_userid' => $wechat_userid,
                'corpid' => $corpid
            ];
            return $this->fetch($display_url, ['data' => $arr, 'status' => '30']);
        }
        //判断邮箱格式正确与否
        if (!common::check_email($check_email)) {
            $arr = [
                'msg' => '邮箱账号格式不正确。',
                'name' => $name,
                'wechat_userid' => $wechat_userid,
                'corpid' => $corpid
            ];
            return $this->fetch($display_url, ['data' => $arr, 'status' => '30']);
        }
        //判断邮箱后缀是不是正确
        //需要根据 corpid 获取邮箱后缀
        $bind_info = cachetool::get_bindinfo_bycorpid($corpid);
        $corp_id = $bind_info['corp_id'];
        $corp_name = $bind_info['corp_name'];
        $domain = '@' . $bind_info['domain'];
        if (substr($check_email, strpos($check_email, '@')) != $domain) {
            $arr = [
                'name' => $name,
                'msg' => "邮箱账号后缀不正确，应该为：" . $domain,
                'wechat_userid' => $wechat_userid,
                'corpid' => $corpid
            ];
            return $this->fetch($display_url, ['data' => $arr, 'status' => '30']);
        }
        //获取信息 然后提示正在审核 请耐心等待
        //从后台获取  职员的微信账号等数据
        $corp_access_token = wechattool::get_corp_access_token($corpid, cachetool::get_permanent_code_by_corpid($corpid));
        list($wechat_name, $mobile, $wechat_email) = wechattool::get_wechat_userid_info($wechat_userid, $corp_access_token);
        $status = '20';
        $account = substr($check_email, 0, strpos($check_email, '@'));
        if ($wechat_name == $name && $wechat_email == $check_email) {
            //如果两个名字是一致的话 直接审核通过
            $status = '10';
        } else {
            //获取用户的相关信息来
            if ($wechat_name != $name) {
                //微信里跟填写的姓名不一致 绑定失败
                return $this->fetch("bindwechat/failed_oath");
            }
            //判断下邮箱中的姓名跟填写的是不是一致 不一致的话绑定失败 等待管理员审核
            if ($this->checkAccountInfo($corpid, $account, $name)) {
                $status = '10';
            }
        }
        $a_data = [
            'corp_id' => $corp_id,
            'corpid' => $corpid,
            'corp_name' => $corp_name,
            'name' => $wechat_name,
            'check_name' => $name,
            'wechat_userid' => $wechat_userid,
            'email' => $wechat_email ?: '',
            'check_email' => $check_email,
            'account' => $account,
            'mobile' => $mobile ?: '',
            'status' => $status,
            'checktime' => 0,
            'addtime' => time(),
            'lastgetmailtime' => time(),
        ];
        $user = Db::name('wechat_user');
        if ($id) {
            //更新
            $a_data['id'] = $id;
            $op_status = $user->update($a_data);
        } else {
            //添加
            $op_status = $user->insertGetId($a_data);
        }
        if (!$op_status) {
            return $this->fetch("bindwechat/failed_oath");
        }
        if ($status == '10') {
            return $this->fetch("bindwechat/success_oath");
        } else {
            return $this->fetch("bindwechat/check_oath");
        }
    }


    /**
     * 获取账号的相关信息
     * @access public
     * @param $corpid
     * @param $account
     * @param $name
     * @return bool|mixed
     */
    private function checkAccountInfo($corpid, $account, $name)
    {
        $bind_info = cachetool::get_bindinfo_bycorpid($corpid);
        $api_status = $bind_info['api_status'];
        $flag = $bind_info['flag'];
        $prikey = $bind_info['privatesecret'];
        $product = $bind_info['product'];
        $domain = $bind_info['domain'];
        if ($api_status != '10') {
            return false;
        }
        list($account_info, $get_api_status) = \app\mailapi\controller\mailinfo::get_account_info($prikey, $domain, $product, $flag, $account);
        if ($get_api_status) {
            $mailnickname = $account_info['nickname'];
            if ($name == $mailnickname) {
                return true;
            }
        }
        return false;
    }


}
