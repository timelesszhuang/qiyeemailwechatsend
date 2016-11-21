<?php
/**
 * Created by PhpStorm.
 * User: timeless
 * Date: 16-11-3
 * Time: 上午10:10
 */

namespace app\sysadmin\controller;


use app\sysadmin\model\common;
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
     * edit_ads
     * 编辑广告信息
     * @access public
     */
    public function edit_ads()
    {
        $this->get_assign();
        return $this->fetch('edit_ads');
    }

}