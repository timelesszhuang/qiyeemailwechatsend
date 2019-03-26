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
        echo 'dasda';
        return $this->fetch('index');
    }

    /**
     * 首页信息修改
     */
    public function index_json()
    {
        //分页信息获取
        list($firstRow, $pageRows) = \app\common\model\common::get_page_info();
        $title = Request::instance()->param('title', '');
        $map = '';
        if ($title) {
            $map .= "title like '%{$title}%' ";
        }
        $db = Db::name('ads');
        $count = $db->where($map)->count('id');
        $info = $db->where($map)->limit($firstRow, $pageRows)
            ->field('id,title,addtime,readcount')
            ->select();
        array_walk($info, array($this, 'formatter_ads'));
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
     * 格式化 广告字段
     * @access public
     * @param $v
     */
    public function formatter_ads(&$v)
    {
        $v['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
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
        $id = Request::instance()->param('id');
        return $this->fetch('edit_ads', ['r' => Db::name('ads')->where(['id' => $id])->find()]);
    }

    /**
     *
     * @access public
     */
    public function exec_edit_ads()
    {
        $id = Request::instance()->param('id');
        $title = Request::instance()->param('title');
        $pic_url = Request::instance()->param('pic_url');
        $contents = Request::instance()->param('contents');
        if (!$title || !$contents) {
            return json(common::form_ajaxreturn_arr('修改失败', '标题跟内容不能为空', self::error));
        }
        if (Db::name('ads')->update(['id' => $id, 'title' => $title, 'pic_url' => $pic_url, 'contents' => $contents, 'updatetime' => time()])) {
            return json(common::form_ajaxreturn_arr('修改成功', '修改成功', self::success));
        }
        return json(common::form_ajaxreturn_arr('修改失败', '原因未知请联系管理员', self::error));
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
            echo json_encode(common::form_ajaxreturn_arr('正在发送', '后台正在发送。', self::success));
        }
        //省略php接口中处理数据的代码
        // get the size of the output
        $size = ob_get_length();
        // send headers to tell the browser to close the connection
        header("Content-Length: $size");
        header('Connection: close');
        ob_end_flush();
        set_time_limit(0);
        ignore_user_abort(true);
        //获取广告数据
        $info = Db::name('ads')->where(['id' => ['in', $ads]])->field('id,title,pic_url')->select();
        \app\sysadmin\model\ad::send_ads($info, $corp_ids);
    }


}