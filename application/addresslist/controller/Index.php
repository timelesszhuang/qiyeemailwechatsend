<?php
namespace app\addresslist\controller;


use app\admin\model\cachetool;
use app\mailapi\controller\maildep;
use app\mailapi\controller\mailuser;
use think\Controller;
use think\Db;
use think\Request;
use think\Session;

class Index extends Controller
{

    public function index()
    {
        //首先判断是不是请求来自微信
        $corpid = Request::instance()->param('corpid');
        if (!$corpid) {
            exit('请求异常');
        }
        //首先判断数据库中是不是已经有了
        // 首先获取下　网易邮箱接口绑定信息
        $bind_info = cachetool::get_bindinfo_bycorpid($corpid, 'get');
        if ($bind_info) {
            if ($bind_info['api_status'] != '10') {
                return $this->fetch('msg', ['msg' => '贵公司网易企业邮箱接口暂时不可用']);
            }
            Session::set('corpid', $corpid);
            Session::set('flag', $bind_info['flag']);
            Session::set('corp_id', $bind_info['corp_id']);
            Session::set('corp_name', $bind_info['corp_name']);
            Session::set('privatesecret', $bind_info['privatesecret']);
            Session::set('product', $bind_info['product']);
            Session::set('domain', $bind_info['domain']);
            return $this->fetch('index');
        } else {
            return $this->fetch('msg', ['msg' => '贵公司网易企业邮箱接口暂时不可用']);
        }
    }


    /**
     * 更新部门数据　更新职员数据
     */
    public function update()
    {
        if (!Session::has('corpid')) {
            exit(json_encode(['msg' => '您的请求有误,请重新请求', 'status' => 'failed']));
        }
        //从数据库中获取所属部门信息
        $corpid = Session::get('corpid');
        $corp_id = Session::get('corp_id');
        $flag = Session::get('flag');
        $corp_name = Session::get('corp_name');
        $prikey = Session::get('privatesecret');
        $product = Session::get('product');
        $domain = Session::get('domain');
        //获取组织架构的数据 首先删除信息 然后更新数据
        if (maildep::exec_update_alldep($prikey, $domain, $product, $flag, $corp_id, $corpid, $corp_name)) {
            //成功的话在获取部门下的职员数据
           mailuser::exec_update_alluser($prikey, $domain, $product, $flag, $corp_id, $corpid, $corp_name);
        }
        // 更新部门信息失败的情况 返回

    }


    /**
     * 更新全部的信息
     * ＠access public
     */
    public function update_self_info()
    {
        //然后还需要把　其他的信息也保存下来
        return $this->fetch('update_self_info');
    }


}
