<?php

/**
 * Created by PhpStorm.
 * 微信推送
 * User: Administrator
 * Date: 2016/11/16
 * Time: 9:49
 */
namespace app\admin\controller;


use app\common\model\common;
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
    /*   public function exec_add_wechatmail()
       {
           $data['wechat_user_id'] = $wechat_user_id = I('post.wechat_user_id');
           $data['check_name'] = I('post.check_name');
           $data['check_email'] = I('post.check_email');
           $data['status'] = '20';
           $data['addtime'] = time();
           if (!$data['wechat_user_id']) {
               format_json_ajaxreturn('请填写微信ID', '添加账号绑定失败', 'failed');
           }
           //还需要验证是不是该帐号已经存在
           if (!$data['check_name']) {
               format_json_ajaxreturn('请填写姓名', '添加账号绑定失败', 'failed');
           }
           if (!$this->check_email($data['check_email'])) {
               format_json_ajaxreturn('邮箱格式不正确，请修改。', '添加账号绑定', 'failed');
           } else {
               $domain = '@' . C('MAILDOMAIN');
               if (substr($data['check_email'], strpos($data['check_email'], '@')) != $domain) {
                   format_json_ajaxreturn("邮箱后缀不正确，应该为$domain", '添加账号绑定', 'failed');
               }
           }
           if (M('WechatUser')->where(["wechat_user_id" => ['eq', $wechat_user_id]])->find()) {
               format_json_ajaxreturn("该微信绑定信息已经添加过。", "添加账号绑定", 'failed');
           }
           //还需要从微信的后台获取 name 邮箱地址 还有 手机号
           $data['account'] = substr($data['check_email'], 0, strpos($data['check_email'], '@'));
           list($name, $mobile, $email) = R('Wechat/Index/get_wechat_info', [$wechat_user_id]);
           if (!$name) {
               format_json_ajaxreturn("该微信ID不存在，请到微信后台添加该职员账号。", "添加账号绑定", 'failed');
           }
           $data['name'] = $name;
           $data['mobile'] = $mobile ?: '';
           $data['email'] = $email ?: '';
           if (M('WechatUser')->add($data)) {
               get_mem_obj()->flush();
               format_json_ajaxreturn("账号绑定添加成功，请审核。", '添加账号绑定', 'success');
           }
           format_json_ajaxreturn("账号绑定失败，请重试。", '添加账号绑定', 'failed');
       }*/


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
    /*    public function exec_edit_wechatmail()
        {
            $data['id'] = I('post.id');
            $data['check_name'] = I('post.check_name');
            $data['check_email'] = I('post.check_email');
            $data['status'] = '20';
            $data['addtime'] = time();
            if (!$data['id']) {
                format_json_ajaxreturn('修改信息失败请重试。', '修改信息失败', 'failed');
            }
            //还需要验证是不是该帐号已经存在
            if (!$data['check_name']) {
                format_json_ajaxreturn('请填写姓名', '修改信息失败', 'failed');
            }
            if (!$this->check_email($data['check_email'])) {
                format_json_ajaxreturn('邮箱格式不正确，请修改。', '修改信息失败', 'failed');
            } else {
                $domain = '@' . C('MAILDOMAIN');
                if (substr($data['check_email'], strpos($data['check_email'], '@')) != $domain) {
                    format_json_ajaxreturn("邮箱后缀不正确，应该为$domain", '修改信息失败', 'failed');
                }
            }
            $data['account'] = substr($data['check_email'], 0, strpos($data['check_email'], '@'));
            if (M('WechatUser')->save($data)) {
                get_mem_obj()->flush();
                format_json_ajaxreturn("修改成功，请重新审核。", '修改信息失败', 'success');
            }
            format_json_ajaxreturn("信息修改失败，请重试。", '修改信息失败', 'failed');
        }*/


}
