<?php

namespace app\dailysendmail\controller;

use app\admin\model\wechatuser;
use app\common\model\common;
use app\common\model\wechattool;
use Predis\Client;
use think\Config;
use think\Controller;
use think\Db;
use think\image\Exception;

/**
 * 执行发送 邮件操作 可以同时起来 很多请求
 */
class Sendneteasemail extends Controller
{

    public $redisClient;
    // 邮件待传递队列
    public $mailQueue = 'mailQueueList';


    public function _initialize()
    {
        // 初始化的
        set_time_limit(0);
        ignore_user_abort(true);
        $this->redisClient = new Client(Config::get('redis.redis_config'));
    }


    /**
     * 入门
     */
    public function index()
    {
        while (1) {
            // 每次单独出队列
            $mail = $this->redisClient->rpop($this->mailQueue);
            if ($mail) {
                try {
                    $mail = json_decode($mail, true);
                    wechattool::sendMail($mail['corpid'], $mail['arr']);
                } catch (\think\Exception $ex) {
                    file_put_contents('dailysendmailerror.txt', $ex->getMessage() . $ex->getLine(), FILE_APPEND);
                }
            } else {
                return;
            }
        }
    }

    /**
     * 获取某个公司下面的所有的用户的 corp_id
     * @access private
     * @param $corp_bind
     * @param $email_agentid
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    private function getSetCorpMailInfo($corp_bind, $email_agentid)
    {
        $corp_id = $corp_bind['corp_id'];
        //首先获取参数 corp_id  corpid  从缓存中获取 网易接口的 公钥 私钥等信息
        $corpid = $corp_bind['corpid'];
        //获取 公司的邮箱的相关接口
        $corp_name = $corp_bind['corp_name'];
        // 获取 该公司下的 用户的上次购买信息
        $wechatuserid_info = wechatuser::get_wechatuser_arr_bycorp_id($corp_id);
        // 当前用户的 绑定的 agent_id
        $agent_id = Db::name('agent_auth_info')->where(['appid' => $email_agentid, 'corp_id' => $corp_id])->find()['agentid'];
        $all_sendcount = 0;
        $access_time = time();
        foreach ($wechatuserid_info as $k => $user) {
            // 獲取当前用的 RecmailArr
            list($endtime, $total) = $this->getRecmailArr($user, $corp_bind, $agent_id);
            $all_sendcount += $total;
            //更新一下 获取邮件的 上次获取时间
            Db::name('wechat_user')->where(['wechat_userid' => $user['wechat_userid'], 'corpid' => $corpid])->update(['lastgetmailtime' => $endtime]);
            //更新log 数据 精确到详细的每个人
            if ($total) {
                Db::name('wechat_user_sendlog')->insert(['corpid' => $corpid, 'corp_id' => $corp_id, 'corp_name' => $corp_name, 'account' => $user['account'], 'name' => $user['name'], 'mailsendcount' => $total, 'accesstime' => $endtime]);
            }
        }
        //更新下公司的本次的请求信息 所有log数据库
        if ($all_sendcount) {
            Db::name('crontab_log')->insert(['corpid' => $corpid, 'corp_id' => $corp_id, 'corp_name' => $corp_name, 'mailsendcount' => $all_sendcount, 'accesstime' => $access_time]);
        }
    }


    /**
     * 获取已经 收件列表
     * @access public
     * @param $user
     * @param $corp_bind
     * @param $agent_id 应用的 id
     * @return array
     */
    private function getRecmailArr($user, $corp_bind, $agent_id)
    {
        $accounts = $user['account'];
        $wechat_userid = $user['wechat_userid'];
        $lastgettime = $user['lastgetmailtime'];
        //$lastgettime 如果为空的话  开始的时间为当前五分钟信息
        $endtime = time();
        $pre_time = time() - 300;
        $starttime = $lastgettime ?: $pre_time;
        //更新每一个上次执行的数据
        $start = date('Y-m-d H:i:s', $starttime);
        $end = date('Y-m-d H:i:s', $endtime);
        //循环获取数据库中  上次发件的时间  默认从memcache 中取  如果没有的话 更新
        //私钥
        $time = date(time()) . '000';
        $res = openssl_pkey_get_private($corp_bind['privatesecret']);
        //必须使用post方法
        $src = "accounts={$accounts}&domain={$corp_bind['domain']}&end={$end}&product={$corp_bind['product']}&start={$start}&time={$time}";
        $total = 0;
        try {
            if (openssl_sign($src, $out, $res)) {
                $sign = bin2hex($out);
                if ($corp_bind['flag'] == '10') {
                    //华北
                    $url = "https://apibj.qiye.163.com/qiyeservice/api/mail/getReceivedMailLogs";
                } else {
                    //华东
                    $url = "https://apihz.qiye.163.com/qiyeservice/api/mail/getReceivedMailLogs";
                }
                $response_json = json_decode(common::send_curl_request($url, $src . '&sign=' . $sign, 'post'), true);
                if ($response_json['suc'] == '1') {
                    $total = $this->formatWechatSendeMail($response_json['con'], $accounts, $wechat_userid, $agent_id, $corp_bind['corpid']);
                    //更新数据到数据库中
                }
                //失败  返回详细信息
            }
        } catch (Exception $ex) {
            file_put_contents('a.txt', $ex->getMessage(), FILE_APPEND);
        }
        return [$endtime, $total];
    }

    /**
     * 循环执行邮件推送 到 微信 最多八条
     * formatSendemaildep
     * @access private
     * @param $con  获取到的邮件记录
     * @param $accounts  邮箱账号
     * @param $wechat_userid 微信的账号
     * @param $agent_id 应用的id
     * @param $corpid
     * @return
     */
    private function formatWechatSendeMail($con, $accounts, $wechat_userid, $agent_id, $corpid)
    {
        $url = Config::get('common.ENTRYMAILURL') . "?account={$accounts}&corpid={$corpid}&entrykey={$this->get_entrykey($accounts,$corpid)}";
        $total = $con['total'];
        $list = $con['list'];
        foreach ($list as $k => $v) {
            // 一封一封的发
            $result = $v['result'];
            if ($result == 1) {
                $subject = isset($v['subject']) ? $v['subject'] : '';
                $mailfrom = isset($v['mailfrom']) ? $v['mailfrom'] : '未知';
                $sendtime = isset($v['sendtime']) ? $v['sendtime'] : '未知';
                $data = [
                    'corpid' => $corpid,
                    'arr' => [
                        "touser" => $wechat_userid,
                        "msgtype" => "news",
                        "agentid" => $agent_id,
                        "news" => [
                            "articles" => [
                                [
                                    'title' => "新邮件 {$subject} 发件人： {$mailfrom} {$sendtime}",
                                    'url' => $url
                                ]
                            ]
                        ]
                    ]
                ];
                $this->redisClient->lpush($this->mailQueue, json_encode($data));
            }
        }
        return $total;
    }

    /**
     * 验证是不是登录的url正常还是被攻击的
     * @param $account 邮箱账号
     * @param $corpid 公司的corpid
     * @return
     */
    private function get_entrykey($account, $corpid)
    {
        return md5(sha1($account . 'qiangbi') . $corpid);
    }

}