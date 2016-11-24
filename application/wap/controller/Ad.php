<?php
/**
 * Created by PhpStorm.
 * User: timeless
 * Date: 16-11-3
 * Time: 上午10:10
 * 阅读广告数据
 */

namespace app\wap\controller;


use think\Controller;
use think\Db;
use think\Request;

class Ad extends Controller
{
    /**
     * 点击阅读广告信息
     * @access public
     */
    public function read()
    {
        $id = Request::instance()->param('id');
        $where = ['id' => $id];
        $m = Db::name('ads');
        $count = $m->where($where)->field('readcount')->find()['readcount'];
        $m->where($where)->update(['readcount' => ++$count]);
        if (!$id) {
            exit('您的请求异常。');
        }
        return $this->fetch('index', ['r' => $m->where($where)->find()]);
    }

}