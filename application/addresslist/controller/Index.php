<?php
namespace app\addresslist\controller;


use think\Db;
use think\Request;
use think\Session;

class Index
{

    public function index()
    {

        $corpid = Request::instance()->param('corpid');
        echo $corpid;
        echo 'dsadsa';
    }

}
