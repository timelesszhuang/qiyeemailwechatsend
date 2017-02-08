<?php
namespace app\admin\controller;


use app\mailapi\controller\maildep;
use app\mailapi\controller\mailuser;
use think\Config;
use think\Db;
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
        if (Session::has('api_status')) {
            return $this->fetch('index', ['msg' => '登录成功。']);
        } else {
            return $this->fetch('index/index', ['notbind_msg' => Config::get('common.NOTBIND_INFO')]);
        }
    }


    /**
     * 邮箱推送 统计
     * @access public
     */
    public function emailsend_count_order()
    {
        $corpid = Session::get('corpid');
        $time = strtotime(date('Y-m-01 00:00:00', time()));
        $where = [
            'accesstime' => ['gt', $time],
            'corpid' => $corpid,
        ];
        $info = Db::name('wechat_user_sendlog')->where($where)->field('account,name,mailsendcount')->select();
        $info_arr = [];
        foreach ($info as $k => $v) {
            if (array_key_exists($v['account'], $info_arr)) {
                $info_arr[$v['account']]['count'] = $info_arr[$v['account']]['count'] + $v['mailsendcount'];
            } else {
                $info_arr[$v['account']] = [
                    'count' => $v['mailsendcount'],
                    'name' => $v['name'],
                ];
            }
        }
        $arr = [];
        foreach ($info_arr as $k => $v) {
            $arr[] = array("id" => 1, "name" => $v["name"], "data" => [intval($v['count'])]);
        }
        echo json_encode(['title' => " ", 'data' => $arr]);
    }


    /**
     * 加载全部数据html
     * @access public
     */
    public function load_allcount_html()
    {
        $corpid = Session::get('corpid');
        $where = ['corpid' => $corpid];
        $auth_time = date('Y-m-d', Db::name('auth_corp_info')->where($where)->field('addtime')->find()['addtime']);
        //统计以下到现在有多少了
        $sum = Db::name('crontab_log')->where($where)->sum('mailsendcount') ?: 0;
        $html = <<<html
<div class="jumbotron">
  <h3>从{$auth_time}开始，共推送{$sum}封邮件</h3>
</div>
html;
        echo $html;
    }


    /**
     * 邮箱推送 统计
     * @access public
     */
    public function get_entryemail_count()
    {
        $corpid = Session::get('corpid');
        $time = strtotime(date('Y-m-01 00:00:00', time()));
        $where = [
            'entry_time' => ['gt', $time],
            'corpid' => $corpid,
        ];
        $info = Db::name('entermail_log')->where($where)->field('wechat_userid,user_name')->select();
        $info_arr = [];
        foreach ($info as $k => $v) {
            if (array_key_exists($v['wechat_userid'], $info_arr)) {
                $info_arr[$v['wechat_userid']]['count']++;
            } else {
                $info_arr[$v['wechat_userid']] = [
                    'count' => 1,
                    'name' => $v['user_name'],
                ];
            }
        }
        $arr = [];
        foreach ($info_arr as $k => $v) {
            $arr[] = array("id" => 1, "name" => $v["name"], "data" => [intval($v['count'])]);
        }
        echo json_encode(['title' => " ", 'data' => $arr]);
    }


    /**
     * 获取每天的 总的活跃度
     */
    public function get_distinctactive_count()
    {
        $corpid = Session::get('corpid');
        $start_time = strtotime(date('Y-m-d 00:00:00', strtotime('-30 days')));
        //过去30 天的 活跃度
        for ($i = 1; $i <= 31; $i++) {
            $starttime = ($i - 1) * 86400 + $start_time;
            $stoptime = $i * 86400 + $start_time;
            //然后开始统计下 三十天之前的效果
            $time_arr[] = date('Y-m-d', $starttime);
            $count[] = Db::name('entermail_log')->query('select count(distinct wechat_userid) as count from sm_entermail_log where corpid="' . $corpid . '" and entry_time between ' . $starttime . ' and ' . $stoptime)[0]['count'];
        }
        $count = array('name' => "过去30天活跃度", 'data' => $count);
        exit(json_encode(array("tooltip_title" => "次（去重之后）", 'title' => '总活跃度曲线', 'subtitle' => '', 'y' => '次', 'time' => $time_arr, 'statistic' => array($count))));
    }

    /**
     * 更新通讯录信息
     */
    public function update_addresslist()
    {
        if (!Session::has('corp_id')) {
            exit(json_encode(['msg' => '您的api 绑定信息暂时不可用，请稍后重试，或通过七鱼联系我们。', 'status' => 'failed']));
        }
        //成功的话在获取部门下的职员数据
        if ((new \app\addresslist\controller\Index())->exec_update_addresslist()) {
            exit(json_encode(['msg' => '通讯录更新成功', 'status' => 'success']));
        }
        // 更新部门信息失败的情况 返回
        exit(json_encode(['msg' => '通讯录更新成功', 'status' => 'failed']));
    }

}
