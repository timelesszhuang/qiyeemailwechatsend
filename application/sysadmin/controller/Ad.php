<?php
/**
 * Created by PhpStorm.
 * User: timeless
 * Date: 16-11-3
 * Time: 上午10:10
 */

namespace app\sysadmin\controller;


use app\sysadmin\model\common;
use think\Config;
use think\Db;
use think\Request;
use think\Session;

class Ad extends Base
{

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 首页数据
     * @access public
     */
    public function index()
    {
        return $this->fetch('index');
    }

    /**
     * add_ads
     * 添加广告数据
     * @access public
     */
    public function add_ads()
    {
        $this->get_assign();
        return $this->fetch('add_ads');
    }


    /**
     * 执行添加广告
     * @access public
     */
    public function exec_add_ads()
    {
        $title = Request::instance()->param('title');
        $pic_url = Request::instance()->param('pic_url');
        $contents = Request::instance()->param('contents');
        if (!$title || !$contents) {
            return json(common::form_ajaxreturn_arr('添加失败', '标题跟内容不能为空', self::error));
        }
        if (Db::name('ads')->insertGetId(['title' => $title, 'pic_url' => $pic_url, 'contents' => $contents, 'addtime' => time()])) {
            return json(common::form_ajaxreturn_arr('添加成功', '添加成功', self::success));
        }
        return json(common::form_ajaxreturn_arr('添加失败', '原因未知请联系管理员', self::error));
    }


    /**
     * edit_ads
     * 编辑广告信息
     * @access public
     */
    public function edit_ads()
    {
        $this->get_assign();
        return $this->fetch('edit_ads');
    }


    /**
     * 发送广告数据
     * @access public
     */
    public function send_ads()
    {
        $this->get_assign();
        return $this->fetch('send_ads');
    }

    /**
     * 获取发送广告信息 list  发送广告时候使用
     * @access public
     */
    public function get_ads_list()
    {
        $r_data = [];
        foreach (Db::name('ads')->field('id,title')->order('id desc')->select() as $v) {
            $r_data[] = array('id' => $v['id'], 'text' => $v['title']);
        }
        exit(json_encode($r_data));
    }

    /**
     * 发送文件信息
     * @access public
     */
    public function exec_send_ads()
    {
        $ads = Request::instance()->param('ads/a');
        $corp_ids = Request::instance()->param('corp_ids/a');
        if (!$ads || !$corp_ids) {
            return json(common::form_ajaxreturn_arr('发送失败', '广告和组织/公司不能为空', self::error));
        } else {
            //感觉需要写个 程序来保存下发送的历史
//            echo json_encode(common::form_ajaxreturn_arr('正在发送', '后台正在发送。', self::success));
        }
        /*      //省略php接口中处理数据的代码
                // get the size of the output
                $size = ob_get_length();
                // send headers to tell the browser to close the connection
                header("Content-Length: $size");
                header('Connection: close');
                ob_end_flush();
                ob_flush();
                flush();*/
        set_time_limit(0);
        ignore_user_abort(true);
        //获取广告数据
        $info = Db::name('ads')->where(['id' => ['in', $ads]])->field('id,title,pic_url')->select();
        \app\sysadmin\model\ad::send_ads($info, $corp_ids);
    }


}