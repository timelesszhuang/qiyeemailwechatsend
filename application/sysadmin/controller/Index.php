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
        $html = <<<html
<div class="jumbotron">
  <h3>本月系统共推送{$sum}封邮件</h3>
</div>
html;
        echo $html;
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

}
