<?php
namespace app\admin\controller;


use think\Session;

class Index extends Base
{

    public function _initialize()
    {
        parent::_initialize();
    }


    /**
     * 条竹跳转到首页
     * @access public
     */
    public function index()
    {
        print_r($_SESSION);
        exit;
        return $this->fetch('index', ['msg' => '登录成功。']);
    }


}
