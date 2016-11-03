<?php
namespace app\sysadmin\controller;


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
        return $this->fetch('index', ['msg' => '登录成功。']);
    }


}
