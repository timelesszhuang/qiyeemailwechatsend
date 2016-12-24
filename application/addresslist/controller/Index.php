<?php
namespace app\addresslist\controller;


use app\admin\model\cachetool;
use app\common\model\common;
use app\common\model\wechattool;
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
        // 首先要做的第一步是获取　当前职员的数据　然后村粗在session 中
//        return $this->fetch('msg',['msg'=>'测试']);
        //首先判断是不是请求来自微信
        $corpid = Request::instance()->param('corpid');
        if (!$corpid) {
            exit('请求异常');
        }
        $bind_info = cachetool::get_bindinfo_bycorpid($corpid, 'get');
        if (empty($bind_info) || $bind_info['api_status'] != '10') {
            return $this->fetch('msg', ['msg' => '贵公司网易企业邮箱接口暂时不可用']);
        }
        $redirect_url = urlencode('http://sm.youdao.so/index.php/addresslist/index/getuserinfo_showaddresslist?corpid=' . $corpid);
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$corpid}&redirect_uri={$redirect_url}&response_type=code&scope=SCOPE&state={$corpid}#wechat_redirect";
        ob_start();
        ob_end_flush();
        header("Location:$url");
        exit;
    }


    /**
     * 获取用户信息 然后 展现地址列表
     * @access public
     */
    public function getuserinfo_showaddresslist()
    {
        //获取请求的参数
        $corpid = Request::instance()->param('corpid');
        $code = Request::instance()->param('code');
        $bind_info = cachetool::get_bindinfo_bycorpid($corpid, 'get');
        $access_token = wechattool::get_corp_access_token($corpid, cachetool::get_permanent_code_by_corpid($corpid));
        $get_userid_url = "https://qyapi.weixin.qq.com/cgi-bin/user/getuserinfo?access_token={$access_token}&code={$code}";
        $user_info = common::send_curl_request($get_userid_url, [], 'get');
        $user_info = json_decode($user_info, true);
        if (array_key_exists('errcode', $user_info)) {
            exit('请求code错误');
        }
        if (array_key_exists('OpenId', $user_info)) {
            exit('您不属于该公司，或者您没有权限访问');
        }
        $wechat_userid = $user_info['UserId'];
        //知道UserId  corpid之后可以获取 网易邮箱账号 也可以获取网易接口数据
        //查看下是不是已经绑定信息 如果没有绑定的话 或者还没有绑定的话 需要提示绑定
        $user_info = Db::name('wechat_user')->where(['corpid' => $corpid, 'wechat_userid' => $wechat_userid])->find();
        $msg = '';
        if (empty($user_info)) {
            $msg = '您还没有绑定邮箱账号。';
        } else {
            //已经完成
            if ($user_info['status'] == '20') {
                //审核信息 表示正在审核
                $msg = '您的账号绑定信息正在审核，请耐心等待贵公司管理员审核!';
            } else if ($user_info['status'] == '30') {
                //审核失败
                $msg = '您的绑定信息有误，贵公司管理员审核未通过!!';
            } else {
                Session::set('wechat_userid', $user_info['wechat_userid']);
                Session::set('account', $user_info['account']);
            }
        }
        Session::set('corpid', $corpid);
        Session::set('flag', $bind_info['flag']);
        Session::set('corp_id', $bind_info['corp_id']);
        Session::set('corp_name', $bind_info['corp_name']);
        Session::set('privatesecret', $bind_info['privatesecret']);
        Session::set('product', $bind_info['product']);
        Session::set('domain', $bind_info['domain']);
        return $this->fetch('index', ['msg' => $msg]);
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
        $corpid = Session::get('corpid');
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
     * @param string $pk
     * @param string $pid parent标记字段
     * @param string $child
     * @param int $root
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
        //根据session 获取手机号码等信息
        $corpid = Session::get('corpid');
        $account = Session::get('account');
        $modal_id = Request::instance()->param('modal_id');
        $user_info = Db::name('mail_user')->where(['corpid' => $corpid, 'account_name' => $account])->find();
        //然后还需要把　其他的信息也保存下来
        return $this->fetch('update_self_info', ['mobile' => $user_info['mobile'], 'account' => $account, 'id' => $user_info['id'], 'nickname' => $user_info['nickname'], 'job_no' => $user_info['job_no'], 'modal_id' => $modal_id]);
    }

    /**
     * 更新个人数据
     * @access update_self_info
     */
    public function exec_update_self_info()
    {
        $id = Request::instance()->param('id');
        $account = Request::instance()->param('account');
        $mobile = Request::instance()->param('mobile');
        $job_no = Request::instance()->param('job_no');
        $old_mobile = Request::instance()->param('old_mobile');
        $old_job_no = Request::instance()->param('old_job_no');
        Session::get('corp_name');
        mailuser::update_user_info(Session::get('privatesecret'), Session::get('domain'), Session::get('product'), Session::get('flag'), $mobile, $job_no, $account, $id);
    }


    /**
     * 查看个人信息
     * @access public
     */
    public function user_info()
    {
        $id = Request::instance()->param('href');
        if (!$id) {
            //错误的情况
        }
        $href = substr($id, 5);
        $info = Db::name('mail_user')->where(['account_openid' => $href])->find();
        return $this->fetch('user_addresslist', ['info' => $info, 'domain' => Session::get('domain')]);
    }


}
