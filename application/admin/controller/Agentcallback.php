<?php
namespace app\admin\controller;

use app\admin\model\agent;
use app\admin\model\cachetool;

//用户应用的回调信息
class Agentcallback
{
    public function index()
    {
        agent::event();
        //验证回调接口
    }
}
