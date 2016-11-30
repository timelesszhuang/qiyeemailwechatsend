<?php
/**
 * Created by PhpStorm.
 * User: timeless
 * Date: 16-11-3
 * Time: 下午1:59
 */

namespace app\sysadmin\controller;


use app\admin\model\cachetool;
use app\common\model\common;
use think\Config;
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
        $corp_name = Request::instance()->param('corp_name', '');
        $domain = Request::instance()->param('domain', '');
        $api_status = Request::instance()->param('api_status', '');
        $status = Request::instance()->param('status', '');
        $map = '';
        if ($corp_name) {
            $map .= "corp_full_name like '%{$corp_name}%' ";
        }
        if ($domain) {
            $map .= $map ? ' and ' : '';
            $map .= " api.domain like '%{$domain}%' ";
        }
        if ($api_status) {
            $map .= $map ? ' and ' : '';
            $map .= " api.api_status='{$api_status}' ";
        }
        if ($status) {
            $map .= " and api.status='{$status}' ";
        }
        $db = Db::name('auth_corp_info');
        $count = $db->alias('auth')->join('sm_corp_bind_api as api', 'api.corp_id=auth.id', 'left')->where($map)->count('auth.id');
        $info = $db->alias('auth')->join('sm_corp_bind_api as api', 'api.corp_id=auth.id', 'left')->where($map)->limit($firstRow, $pageRows)
            ->field('auth.*,api.api_status,api.status')
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
        $info = Db::name('corp_bind_api')->where('corpid', $corpid)->find();
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
        $d = $this->get_auth_post();
        $d['addtime'] = time();
        $d['updatetime'] = time();
        //调用网易邮箱接口获取 邮箱信息
        list($d, $msg) = $this->get_email_info($d);
        if (!Db::name('corp_bind_api')->insertGetId($d)) {
            //修改绑定信息之后需要技术修改绑定信息
            cachetool::get_bindinfo_bycorpid('', 'init');
            return json(\app\sysadmin\model\common::form_ajaxreturn_arr('保存失败', '数据保存失败。', self::error));
        }
        return json(\app\sysadmin\model\common::form_ajaxreturn_arr('数据保存成功', '数据保存成功,' . $msg, self::success));
    }


    /**
     * 修改绑定信息
     * @access public
     */
    public function exec_edit_bind_info()
    {
        $mem = common::phpmemcache();
        cachetool::get_bindinfo_bycorpid('', 'init');
        $info = $mem->get(Config::get('memcache.CORPID_BINDINFO'));
        print_r($info);
        exit;
        $d = $this->get_auth_post();
        $d['updatetime'] = time();
        list($d, $msg) = $this->get_email_info($d);
        if (!Db::name('corp_bind_api')->update($d)) {
            //修改绑定信息之后需要技术修改绑定缓存
            cachetool::get_bindinfo_bycorpid('', 'init');
            return json(\app\sysadmin\model\common::form_ajaxreturn_arr('保存失败', '数据保存失败。', self::error));
        }
        return json(\app\sysadmin\model\common::form_ajaxreturn_arr('数据保存成功', '数据保存成功,' . $msg, self::success));
    }


    /**
     * 获取添加修改网易接口时候的post
     * @access private
     */
    private function get_auth_post()
    {
        if ($id = Request::instance()->param('id')) {
            $d['id'] = $id;
        }
        $d['privatesecret'] = Request::instance()->param('secret');
        $d['product'] = Request::instance()->param('product');
        $d['domain'] = Request::instance()->param('domain');
        $d['corp_name'] = Request::instance()->param('corp_name');
        $d['user_num'] = Request::instance()->param('user_num');
        $d['status'] = Request::instance()->param('status');
        $d['corpid'] = Request::instance()->param('corpid');
        $d['corp_id'] = Request::instance()->param('corp_id');
        if (!$d['product'] || !$d['domain'] || !$d['corp_name'] || !$d['user_num']) {
            return json(\app\sysadmin\model\common::form_ajaxreturn_arr('保存失败', '每一项内容都不能为空。', self::error));
        }
        return $d;
    }

    /**
     * edit_info 修改基本信息
     * @param $d
     * @return array
     */
    private function get_email_info($d)
    {
        list($corp_info, $get_api_status) = mailinfo::get_domain_info($d['privatesecret'], $d['domain'], $d['product']);
        if ($get_api_status) {
            $d['mail_logo'] = $corp_info['logo'];
            $d['mail_org_name'] = $corp_info['org_name'];
            $d['mail_exp_time'] = substr($corp_info['exp_time'], 0, -3);
            $d['api_status'] = '10';
        }
        $msg = $get_api_status ? '网易接口信息有效。' : '网易接口信息无效。';
        return [$d, $msg];
    }

    /**
     * 开启或者禁用邮件推送操作
     * @access public
     */
    public function cancelorok_sendmail()
    {
        $id = Request::instance()->param('id');
        $status = Request::instance()->param('status');
        if (Db::name('corp_bind_api')->where('corp_id', $id)->update(['status' => $status])) {
            //需要更新memcache 中相关键值对 然后取消邮件推送 更新相关的缓存
            cachetool::get_bindinfo_bycorpid('', 'init');
            return json(\app\sysadmin\model\common::form_ajaxreturn_arr('操作状态', '修改成功', self::success));
        }
        return json(\app\sysadmin\model\common::form_ajaxreturn_arr('操作失败', '修改失败', self::error));
    }


    /**
     * copy_crontab_set
     * copy备注信息
     */
    public function copy_crontab_set()
    {
        //这个是corp_id  这个是指的是
        $this->get_assign();
        $id = Request::instance()->param('id');
        $corpid = Db::name('auth_corp_info')->where(['id' => $id])->find()['corpid'];
        $url = '*/5 * * * * curl -s "http://sm.youdao.so/index.php/dailysendmail/wechatmailsend/schedule_get_maillist?corp_id=' . $id . '&corpid=' . $corpid . '"';
        return $this->fetch('copy_crontab_set', ['url' => $url]);
    }


    /**
     * 获取全部的信息
     * @access public
     */
    public function get_authcorp_list()
    {
        $r_data = [];
        foreach (Db::name('auth_corp_info')->field('id,corp_full_name')->order('id desc')->select() as $v) {
            $r_data[] = array('id' => $v['id'], 'text' => $v['corp_full_name']);
        }
        exit(json_encode($r_data));
    }


    /**
     *  取消授权的公司
     * @access public
     */
    public function cancel_corp_index()
    {
        return $this->fetch('cancel_corp_index');
    }


    /**
     * 取消授权的 企业信息
     * @access public
     */
    public function cancelcorp_index_json()
    {
        //分页信息获取
        list($firstRow, $pageRows) = common::get_page_info();
        $corp_name = Request::instance()->param('corp_name', '');
        $domain = Request::instance()->param('domain', '');
        $api_status = Request::instance()->param('api_status', '');
        $status = Request::instance()->param('status', '');
        $map = '';
        if ($corp_name) {
            $map .= "corp_full_name like '%{$corp_name}%' ";
        }
        if ($domain) {
            $map .= $map ? ' and ' : '';
            $map .= " api.domain like '%{$domain}%' ";
        }
        if ($api_status) {
            $map .= $map ? ' and ' : '';
            $map .= " api.api_status='{$api_status}' ";
        }
        if ($status) {
            $map .= " and api.status='{$status}' ";
        }
        $db = Db::name('auth_corp_info');
        $count = $db->alias('auth')->join('sm_corp_bind_api as api', 'api.corp_id=auth.id', 'left')->where($map)->count('auth.id');
        $info = $db->alias('auth')->join('sm_corp_bind_api as api', 'api.corp_id=auth.id', 'left')->where($map)->limit($firstRow, $pageRows)
            ->field('auth.*,api.api_status,api.status')
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


}