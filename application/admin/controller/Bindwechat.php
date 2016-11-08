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
            return $this->fetch('bind', ['status' => '50', 'corpid' => $corpid, 'wechat_userid' => $wechat_userid]);
        }
        $check_status = $info['status'];
        switch ($check_status) {
            case '10':
                $assign_data = ["status" => '10', "msg" => '您已经绑定,邮箱账号，可以正常收到邮件推送!!。'];
                break;
            case '20':
                $assign_data = ["status" => '20', "msg" => '您已经提交网易企业邮箱绑定信息，请等待管理员审核。'];
                break;
            case '30':
                $info['msg'] = '您提交的信息有误，管理员未通过，请重新填写之后等待管理员审核。';
                $assign_data = ["status" => '30', 'data' => $info];
                break;
        }
        return $this->fetch('bind', $assign_data);

    }

    /**
     * 执行绑定微信操作
     * @access public
     */
    public function exec_bind_wechat()
    {
        $name = Request::instance()->param("name");
        $email = Request::instance()->param('email');
        //微信 wechat_userid
        $wechat_userid = Request::instance()->param("wechat_userid");
        $corpid = Request::instance()->param('corpid');
        $id = Request::instance()->param('id', 0);
        $arr = array();
        //错误时跳回页面
        $display_url = "Bindwechat/bind";
        if (empty($wechat_userid) || empty($corpid)) {
            $arr = ['status' => '30', 'msg' => '请重新进入页面认证'];
            return $this->fetch($display_url, ['data' => $arr]);
        }
        if (empty($name)) {
            $arr = ['status' => 30, 'msg' => "请输入您的姓名。"];
            return $this->fetch($display_url, ['data' => $arr, 'wechat_userid' => $wechat_userid, 'corpid' => $corpid]);
        }
        if (empty($email)) {
            $arr = ['status' => 30, 'name' => $name, 'msg' => "请输入网易企业邮箱账号。"];
            return $this->fetch($display_url, ['data' => $arr, 'wechat_userid' => $wechat_userid, 'corpid' => $corpid]);
        }
        //判断邮箱格式正确与否
        if (!common::check_email($email)) {
            $arr = ['status' => '30', 'msg' => '邮箱账号格式不正确。', 'name' => $name];
            return $this->fetch($display_url, ['data' => $arr, 'wechat_userid' => $wechat_userid, 'corpid' => $corpid]);
        }
        //判断邮箱后缀是不是正确
        //需要根据 corpid 获取邮箱后缀
        $bind_info = cachetool::get_bindinfo_bycorpid($corpid);
        $corp_id = $bind_info['corp_id'];
        $corp_name = $bind_info['corp_name'];
        $domain = '@' . $bind_info['domain'];
        if (substr($email, strpos($email, '@')) != $domain) {
            $arr = ['status' => '30', 'name' => $name, 'msg' => "邮箱账号后缀不正确，应该为：" . $domain];
            return $this->fetch($display_url, ['data' => $arr, 'wechat_userid' => $wechat_userid, 'corpid' => $corpid]);
        }
        //获取信息 然后提示正在审核 请耐心等待
        //从后台获取  职员的微信账号等数据
        $corp_access_token = wechattool::get_corp_access_token($corpid, cachetool::get_pcode_bycorpid($corpid));
        list($wechat_name, $mobile, $wechat_email) = wechattool::get_wechat_userid_info($wechat_userid, $corp_access_token);
        $a_data = [
            'corp_id' => $corp_id,
            'corpid' => $corpid,
            'corp_name' => $corp_name,
            'name' => $wechat_name,
            'check_name' => $name,
            'wechat_userid' => $wechat_userid,
            'email' => $wechat_email ?: '',
            'check_email' => $email,
            'account' => substr($email, 0, strpos($email, '@')),
            'mobile' => $mobile ?: '',
            'status' => '20',
            'checktime' => 0,
            'addtime' => time(),
        ];
        $user = Db::name('wechat_user');
        if ($id) {
            //更新
            $a_data['id'] = $id;
            $status = $user->save($a_data);
        } else {
            //添加
            $status = $user->add($a_data);
        }
        if (!$status) {
            $arr["status"] = 30;
            $arr["msg"] = "当前用户绑定失败，请稍候重试。";
            $this->assign(array(
                "data" => $arr,
                "user_id" => $wechat_userid
            ));
            exit($this->display($display_url));
        }
//        $mem = get_mem_obj();
//        $mem->flush();
//        $this->redirect("Index/success_oath");
    }


}
