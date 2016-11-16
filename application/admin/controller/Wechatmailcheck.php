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
        $this->fetch('add_wechatmail');
    }

}
