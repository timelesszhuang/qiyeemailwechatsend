<?php
namespace app\sysadmin\controller;


use think\Db;

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

    /**
     * 邮件推送数量排序
     * @access public
     */
    public function emailsend_count_order()
    {
        $time = strtotime(date('Y-m-01 00:00:00', time()));
        $where = [
            'accesstime' => ['gt', $time],
        ];
        $info = Db::name('crontab_log')->where($where)->field('corp_id,corp_name,mailsendcount')->select();
        $info_arr = [];
        foreach ($info as $k => $v) {
            if (array_key_exists($v['corp_id'], $info_arr)) {
                $info_arr[$v['corp_id']]['count'] = $info_arr[$v['corp_id']]['count'] + $v['mailsendcount'];
            } else {
                $info_arr[$v['corp_id']] = [
                    'count' => $v['mailsendcount'],
                    'name' => $v['corp_name'],
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
        $time = strtotime(date('Y-m-01 00:00:00', time()));
        $where = [
            'accesstime' => ['gt', $time],
        ];
        $sum = Db::name('crontab_log')->where($where)->sum('mailsendcount') ?: 0;
        //获取下　总的用户数量
        $user_count = Db::name('wechat_user')->count('id') ?: 0;
        //授权的企业数量
        $corp_count = Db::name('auth_corp_info')->count('id') ?: 0;
        $html = <<<html
  <h4>本月系统共推送邮件{$sum}封；授权企业数量{$corp_count}个；用户总数：{$user_count}户</h4>
html;
        echo $html;
    }


    /**
     * 获取每天的 总的活跃度
     */
    public function get_distinctactive_count()
    {
        $start_time = strtotime(date('Y-m-d 00:00:00', strtotime('-30 days')));
        //过去30 天的 活跃度
        for ($i = 1; $i <= 31; $i++) {
            $starttime = ($i - 1) * 86400 + $start_time;
            $stoptime = $i * 86400 + $start_time;
            //然后开始统计下 三十天之前的效果
            $time_arr[] = date('Y-m-d', $starttime);
            $count[] = Db::name('entermail_log')->query('select count(distinct wechat_userid) as count from sm_entermail_log where entry_time between ' . $starttime . ' and ' . $stoptime)[0]['count'];
        }
        $count = array('name' => "过去30天活跃度", 'data' => $count);
        exit(json_encode(array("tooltip_title" => "次（去重之后）", 'title' => '总活跃度曲线', 'subtitle' => '', 'y' => '次', 'time' => $time_arr, 'statistic' => array($count))));
    }


    /**
     * 邮箱推送 统计
     * @access public
     * SELECT count(corp_id), corp_name FROM `sm_entermail_log` group by corp_id
     */
    public function get_entryemail_count()
    {
        $time = strtotime(date('Y-m-01 00:00:00', time()));
        $where = [
            'entry_time' => ['gt', $time],
        ];
        $info = Db::name('entermail_log')->where($where)->field('count(corp_id) as count,corp_name')->group('corp_id')->select();
        $arr = [];
        foreach ($info as $k => $v) {
            $arr[] = array("id" => 1, "name" => $v["corp_name"], "data" => [intval($v['count'])]);
        }
        echo json_encode(['title' => " ", 'data' => $arr]);
    }


    /**
     * 加载用户数量
     * @access public
     */
    public function load_user_count()
    {
        $info = Db::name('wechat_user')->field('count(corp_id) as count,corp_name')->group('corp_id')->select();
        $arr = [];
        foreach ($info as $k => $v) {
            $arr[] = array("id" => 1, "name" => $v["corp_name"], "data" => [intval($v['count'])]);
        }
        echo json_encode(['title' => " ", 'data' => $arr]);
    }


}
