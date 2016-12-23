<?php
namespace app\admin\controller;


use think\Db;
use think\Session;

class Index extends Base
{

    public function _initialize()
    {
        /*        //测试数据
                Session::set('api_status', '10');
                Session::set('corp_id', '5');
                Session::set('corpid', 'wxe041af5a55ce7365');
                Session::set('product', 'qiangbi_net');
                Session::set('privatesecret', '-----BEGIN RSA PRIVATE KEY----- MIICXgIBAAKBgQDImsqx5wgohQqq2p+AAxNR1s5QfkoO9LJ+nTRBa4lqg8oPkbuN YgLSD3Y0wVF1i0vffaLuVz6Jlmr4dQAGI0CuYSMmuDyaMId5cyoHeqCACRA/uQev iT9XYO2n6z7l8h61dDEOx028vc+vcwVkghmAqMKhNzmEJX/CfqclVQXahwIDAQAB AoGAB9OqRuips9MFCIeBI6B7F31XDWLwBsdbU39Us5y7ftFnh9X6yFhjnciGpyZH xFtL+YtQWRZEVV/uCoWeG58yfcmDo8NnDfNZZVpXDKdvVoW1JDGdIX6rhHmRUiso qA6m1N7GtU/lErz19dBb8Dj+GZh116fPw5u2HOMsu5bOKmkCQQD2W1nwOYmiz2k9 01h19ZXjIH5hiax7UurQW3wUXe4VQb6FH8qEBRhO+CyoWleZq+x1czoxGKrW+P08 sYLMkU6LAkEA0HT5oS9H+mlmotOhdDL6dnbsaq6od9ivvCR4FKsu/x1Jq4fL9iej 7dIgi2s3qjkO/QCy2O7yyf/An5tz7LJ/dQJBAIAJeFPmw4bPf2X3irk72xvBTo3I 7NDnhkylz3YSX2PC2I79t9Yng7u/Ng6FbZPbi7h7G5patKenno3FwDIrrwMCQQCX wpF6J1HfnJx8LlZ8oiB13l5/zGgZ2EcYUfSaF4Y/dLMNje+PZYySt0e6OHRuGNww lTGffVaEeQ1jJWlgCROBAkEA9gCeU1HUkQcRZCtMh6uC8J/dv0FFMaxWJnz3yLO/ dHz9XDwjTJxyC6AN3VhD+FU4hRMyaAKjUCreNDcZLza1KA== -----END RSA PRIVATE KEY-----');
                Session::set('domain', 'cio.club');
                Session::set('corp_name', '山东强比信息技术');*/
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
            $count[] = Db::name('entermail_log')->query('select count(distinct wechat_userid) as count from sm_entermail_log where corpid= ' . $corpid . ' and entry_time between ' . $starttime . ' and ' . $stoptime)[0]['count'];
        }
        $count = array('name' => "过去30天活跃度", 'data' => $count);
        exit(json_encode(array("tooltip_title" => "次（去重之后）", 'title' => '总活跃度曲线', 'subtitle' => '', 'y' => '次', 'time' => $time_arr, 'statistic' => array($count))));
    }


}
