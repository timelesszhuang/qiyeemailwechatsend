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
        return $this->fetch('index');

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
            if (mailuser::exec_update_alluser($prikey, $domain, $product, $flag, $corp_id, $corpid, $corp_name)) {
                exit(json_encode(['msg' => '数据更新成功', 'status' => 'success']));
            }
        }
        // 更新部门信息失败的情况 返回
        exit(json_encode(['msg' => '更新数据失败', 'status' => 'failed']));
    }

    /**
     * ajax 获取信息
     * @access public
     */
    public function get_data()
    {
        //首先获取部门的数据
        $corpid = session('corpid');
        $corpid = 'wxe041af5a55ce7365';
        if (!$corpid) {
            exit('请求异常。');
        }
        $where = ['corpid' => $corpid];
        $dep_info = Db::name('mail_orgstructure')->where($where)->field('unit_id as id,parent_id as p_id,unit_name as text')->select();
        $user_info = Db::name('mail_user')->where($where)->field('account_openid as id,id as flag,unit_id as p_id,nickname as text')->select();
        $tree_arr = [];
        if (!empty($dep_info) && !empty($user_info)) {
            $tree_arr = array_merge($dep_info, $user_info);
        } else {
            if (!empty($user_info)) {
                $tree_arr = $user_info;
            }
            if (!empty($dep_info)) {
                $tree_arr = $dep_info;
            }
        }
        $all_info = [];
        foreach ($tree_arr as $k => $v) {
            $v['href'] = (isset($v['flag']) ? 'clerk' : '') . $v['id'];
            $v['icon'] = isset($v['flag']) ? 'glyphicon glyphicon-user' : '';
            unset($v['flag']);
            $all_info[] = $v;
        }
        if (!empty($all_info)) {
            //生成树状图
            exit(json_encode($this->list_to_tree($all_info)));
        }
    }

    /**
     * 把返回的数据集转换成Tree  本函数使用引用传递  修改  数组的索引架构
     *  可能比较难理解     函数中   $reffer    $list[]  $parent 等的信息实际上只是内存中地址的引用
     * @access public
     * @param array $list 要转换的数据集
     * @param string $pid parent标记字段
     * @return array
     */
    private function list_to_tree($list, $pk = 'id', $pid = 'p_id', $child = 'nodes', $root = 0)
    {
        // 创建Tree
        $tree = array();
        if (is_array($list)) {
            //创建基于主键的数组引用
            $refer = array();
            foreach ($list as $key => $data) {
                $refer[$data[$pk]] = &$list[$key];
            }
            foreach ($list as $key => $data) {
                // 判断是否存在parent
                $parentId = $data[$pid];
                if ($root == $parentId) {
                    //根节点元素
                    $tree[] = &$list[$key];
                } else {
                    if (isset($refer[$parentId])) {
                        //当前正在遍历的父亲节点的数据
                        $parent = &$refer[$parentId];
                        //把当前正在遍历的数据赋值给父亲类的  children
                        $parent[$child][] = &$list[$key];
                    }
                }
            }
        }
        return $tree;
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
