<?php
/**
 * Created by PhpStorm.
 * User: timeless
 * Date: 16-11-3
 * Time: 下午1:59
 */

namespace app\sysadmin\controller;


use app\common\model\common;
use think\Db;
use think\Request;
use app\mailapi\controller\mailinfo;

class Authcorp extends Base
{

    /**
     * 首页信息
     * @access public
     */
    public function index()
    {
        return $this->fetch();
    }


    /**
     * 首页基本信息更新
     * @access public
     */
    public function index_json()
    {
        //分页信息获取
        list($firstRow, $pageRows) = common::get_page_info();
        $db = Db::name('auth_corp_info');
        $where = [];
        $count = $db->where($where)->count('id');
        $info = $db->where($where)->limit($firstRow, $pageRows)
            ->field('id,corpid,corp_type,corp_agent_max,corp_full_name,subject_type,agent_count,agent_serialize,addtime')
            ->select();
        $auth_model = new \app\sysadmin\model\authcorp();
        array_walk($info, array($auth_model, 'formatter_corp_info'));
        if ($count != 0) {
            $array['total'] = $count;
            $array['rows'] = $info;
            echo json_encode($array);
        } else {
            $array['total'] = 0;
            $array['rows'] = array();
            echo json_encode($array);
        }
    }

    /**
     * 添加或者修改网易邮箱接口API
     * @access public
     */
    public function editadd_netease_apiinfo()
    {
        //authcorp表的id
        $id = Request::instance()->param('id');
        //获取corp_id
        $corpid = Db::name('auth_corp_info')->where('id', $id)->find()['corpid'];
        // 从数据库中获取 corp_id
        $info = Db::name('corp_bind_api')->where('corp_id', $corpid)->find();
        // 获取包含域名的完整URL地址
        // 判断下是不是已经绑定  如果没有绑定的话 添加绑定 绑定的话修改绑定
        $this->assign('corpid', $corpid);
        $this->assign('corp_id', $id);
        //文件相关
        $this->get_assign();
        if ($info) {
            $this->assign('info', $info);
            return $this->fetch('edit_bind_info');
        } else {
            return $this->fetch('add_bind_info');
        }
    }

    /**
     * 执行绑定信息
     * @access public
     */
    public function exec_add_bind_info()
    {
        $d['secret'] = Request::instance()->param('secret');
        $d['product'] = Request::instance()->param('product');
        $d['domain'] = Request::instance()->param('domain');
        $d['corp_name'] = Request::instance()->param('corp_name');
        $d['user_num'] = Request::instance()->param('user_num');
        $d['status'] = Request::instance()->param('status');
        $d['corpid'] = Request::instance()->param('corpid');
        $d['corp_id'] = Request::instance()->param('corp_id');
        if (!$d['product'] || !$d['domain'] || !$d['corp_name'] || !$d['user_num']) {
            return json(\app\sysadmin\model\common::form_ajaxreturn_json('保存失败', '每一项内容都不能为空。', self::error));
        }
        $d['addtime'] = time();
        $d['updatetime'] = time();
        //调用网易邮箱接口获取 邮箱信息
        list($corp_info, $get_api_status) = mailinfo::get_domain_info($d['secret'], $d['domain'], $d['product']);
        if ($get_api_status) {
            $d['mail_logo'] = $corp_info['logo'];
            $d['mail_org_name'] = $corp_info['org_name'];
            $d['mail_exp_time'] = substr($corp_info['exp_time'], 0, -3);
        }
        $msg = $get_api_status ? '网易接口信息有效。' : '网易接口信息无效。';
        if (!Db::name('corp_bind_api')->add($d)) {
            return json(\app\sysadmin\model\common::form_ajaxreturn_json('保存失败', '数据保存失败。', self::error));
        }
        return json(\app\sysadmin\model\common::form_ajaxreturn_json('数据保存成功', '数据保存成功,' . $msg, self::success));

    }

}