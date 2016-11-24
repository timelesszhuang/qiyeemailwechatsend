<?php
/**
 * Created by PhpStorm.
 * User: timeless
 * Date: 16-11-3
 * Time: 上午10:10
 * 阅读广告数据
 */

namespace app\sysadmin\controller;


use app\sysadmin\model\common;
use think\Controller;
use think\Db;
use think\Request;

class Readad extends Controller
{
    /**
     * 点击阅读广告信息
     * @access public
     */
    public function index()
    {
        $id = Request::instance()->param('id');
        if (!$id) {
            exit('您的请求异常。');
        }
        return $this->fetch('index');
    }

}