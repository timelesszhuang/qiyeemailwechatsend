<?php
namespace app\addresslist\controller;


use app\admin\model\cachetool;
use think\Db;
use think\Request;
use think\Session;

class Index
{

    public function index()
    {

        return $this->fetch('index');
        echo $_SERVER['HTTP_REFERER'];
        exit;
        //首先判断是不是请求来自微信
        $corpid = Request::instance()->param('corpid');
        if (!$corpid) {
            exit('请求异常');
        }
        // 首先获取下　网易邮箱接口绑定信息
        $bind_info = cachetool::get_bindinfo_bycorpid($corpid, 'get');

        if ($bind_info) {
            Session::set('api_status', $bind_info['api_status']);
            Session::set('flag', $bind_info['flag']);
            Session::set('corp_id', $bind_info['corp_id']);
            Session::set('corp_name', $bind_info['corp_name']);
            Session::set('privatesecret', $bind_info['privatesecret']);
            Session::set('product', $bind_info['product']);
            Session::set('domain', $bind_info['domain']);
        } else {
            return $this->fetch('index', ['msg' => '贵公司网易企业邮箱接口暂时不可用']);
        }
        //然后获取邮箱的通讯录
        //获取我的相关信息
    }

}
