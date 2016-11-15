<?php

//执行日常发送微信的脚本
namespace app\dailysendmail\controller;

use app\admin\model\cachetool;
use app\admin\model\wechatuser;
use app\common\model\common;
use app\common\model\wechattool;
use think\Config;
use think\Controller;
use think\Db;
use think\Request;

class Wechatmailsend extends Controller
{

    public $corp_name = '';
    public $corp_id = 0;
    public $corpid = '';
    public $bindinfo;
    public $prikey;
    public $product;
    public $domain;

    /**
     * 定期获取邮件列表
     * @access public
     * @todo 首先全部的 邮箱的账号全部都获取到  需要截取到 账号名
     * 然后循环脚本   循环的时间存储到数据库中
     */
    public function schedule_get_maillist()
    {
        //企业的corp_id 根据corpid  获取 该公司的相关数据
        $this->corp_id = Request::instance()->param('corp_id');
        //首先获取参数 corp_id  corpid  从缓存中获取 网易接口的 公钥 私钥等信息
        $this->corpid = Request::instance()->param('corpid');
        //获取 公司的邮箱的相关接口
        $this->bindinfo = cachetool::get_bindinfo_bycorpid($this->corpid);
        if (empty($this->bindinfo) || $this->bindinfo['api_status'] == '20') {
            //表示获取绑定信息失败 需要存储到数据库中 获取api 异常
        }
        $this->prikey = $this->bindinfo['privatesecret'];
        //域名
        $this->domain = $this->bindinfo['domain'];
        $this->product = $this->bindinfo['product'];
        $this->corp_name = $this->bindinfo['corp_name'];
        $wechatuserid_info = wechatuser::get_wechatuser_arr_bycorp_id($this->corp_id);
        //获取agent_id 根据 corp_id 还有邮件套件的 id
        //公司套件中的数据
        $email_agentid = Config::get('common.EMAILAGENT_ID');
        $agent_id = Db::name('agent_auth_info')->where(['appid' => $email_agentid, 'corp_id' => $this->corp_id])->find()['agentid'];
        foreach ($wechatuserid_info as $k => $v) {
            $this->get_recmail_log($v['account'], $v['wechat_userid'], $agent_id, $v['lastgetmailtime']);
            //更新一下 获取邮件的 上次获取时间
            //$this->updateMailInfo($end, $wechat_userid, $lastgettime);
        }
        //更新下公司的本次的请求信息 log数据库
    }

    //https://apibj.qiye.163.com/qiyeservice/api/mail/getReceivedMailLogs?
    //accounts=zhangsan&
    //domain=abc.com&
    //end=2014-12-31&
    //product=abc_com&
    //start=2014-12-01&
    //time=1418560371694&
    //sign=accounts=zhangsan&domain=abc.com&end=2014-12-31&product=abc_com&start=2014-12-01&time=1418560371694


    /**
     * 获取已经 收件列表
     * @access public
     * @param string $accounts 邮箱的账号
     * @param string $wechat_userid 邮箱的user_id
     * @param $agent_id 应用的 id
     * @param string $lastgettime 上次访问的时间
     */
    private function get_recmail_log($accounts, $wechat_userid, $agent_id, $lastgettime)
    {
        //$lastgettime 如果为空的话  开始的时间为当前五分钟信息
        $end = time();
        $pre_time = time() - 300;
        $start = $lastgettime ?: $pre_time;
        //更新每一个上次执行的数据
        $start = date('Y-m-d H:i:s', $start);
        $end = date('Y-m-d H:i:s', $end);
        //循环获取数据库中  上次发件的时间  默认从memcache 中取  如果没有的话 更新
        //私钥
        $time = date(time()) . '000';
        $res = openssl_pkey_get_private($this->prikey);
        //必须使用post方法
        $src = "accounts={$accounts}&domain=" . $this->domain . "&end={$end}&product=" . $this->product . "&start={$start}&time=" . $time;
        if (openssl_sign($src, $out, $res)) {
            $sign = bin2hex($out);
            $url = "https://apibj.qiye.163.com/qiyeservice/api/mail/getReceivedMailLogs";
            $json_log = common::send_curl_request($url . '?' . $src . '&sign=' . $sign);
            $response_json = json_decode($json_log, true);
            file_put_contents('error.log', $json_log, FILE_APPEND);
            file_put_contents('error.log', print_r($response_json, true), FILE_APPEND);
            if ($response_json['suc'] == '1') {
                $this->formatWechatSendeMail($response_json['con'], $accounts, $wechat_userid, $agent_id);
                //然后开始发送到微信中
            }
            //失败  返回详细信息
        }
    }

//返回代码实例
//Array
//(
//    [con] => Array
//        (
//            [list] => Array
//                (
//                    [0] => Array
//                        (
//                            [mailfrom] => general@whoisxmlapi.com
//                            [mailsize] => 299075
//                            [mailto] => xingzhuang@cio.club
//                            [result] => 1
//                            [sendip] => 209.85.217.177
//                            [sendtime] => 2016-09-13 14:11:34
//                            [subject] => Re: Re: Re: Re: Re: Re: Fw:Re:Re:Re: Re: Re: Re: Re: Re: Re: question about cn database
//                        )
//
//                )
//
//            [total] => 1
//        )
//    
//    [suc] => 1
//    [ver] => 0
//)
//
//微信发送请求  url 可以使用单点登录
//{
//   "touser": "UserID1|UserID2|UserID3",
//   "toparty": " PartyID1 | PartyID2 ",
//   "totag": " TagID1 | TagID2 ",
//   "msgtype": "news",
//   "agentid": 1,
//   "news": {
//       "articles":[
//           {
//               "title": "Title",
//               "description": "Description",
//               "url": "URL",
//               "picurl": "PIC_URL"
//           },
//           {
//               "title": "Title",
//               "description": "Description",
//               "url": "URL",
//               "picurl": "PIC_URL"
//           }    
//       ]
//   }
//}

    /**
     * 循环执行邮件推送 到 微信 最多八条
     * formatSendemaildep
     * @access private
     * @param $con  获取到的邮件记录
     * @param $accounts  邮箱账号
     * @param $wechat_userid 微信的账号
     * @param $agent_id 应用的id
     */
    private function formatWechatSendeMail($con, $accounts, $wechat_userid, $agent_id)
    {
        $url = Config::get('common.ENTRYMAILURL') . "?account={$accounts}&corpid={$this->corpid}&entrykey={$this->get_entrykey($accounts,$this->corpid)}";
        $total = $con['total'];
        $list = $con['list'];
        $loop = 1;
        if ($total >= 8) {
            $loop = ceil($total / 8);
        }
        if ($total) {
            for ($i = 0; $i < $loop; $i++) {
                $articles = [];
                //每次循环取八条
                $start = $i * 8;
                $stop = (($start + 8) > $total) ? $total : ($start + 8);
                for ($start; $start < $stop; $start++) {
                    $v = $list[$start];
                    $perarticle = [
                        'title' => "新邮件 {$v['subject']} 发件人： {$v['mailfrom']} {$v['sendtime']}",
                        'url' => $url
                    ];
                    $articles[] = $perarticle;
                }
                $this->sendNews($wechat_userid, $articles, $agent_id);
            }
        }
    }

    /**
     * 验证 单点登录 的加密密钥
     * @access public
     */
    public function check_redirect_entry()
    {
        //首先验证是不是微信请求过来的
        if (!$this->is_weixin()) {
            exit('请求客户端不允许');
        }
        //加密的key
        $accounts_md5 = Request::instance()->param('entrykey');
        $accounts = Request::instance()->param('account');
        $corpid = Request::instance()->param('corpid');
        if ($accounts_md5 != $this->get_entrykey($accounts, $corpid)) {
            exit("请求异常，加密字段匹配异常");
        }
        ob_start();
        ob_end_flush();
        header("Location:" . $this->get_entry_url($accounts, $corpid));
    }

    /**
     * 获取单点登录的 url
     * @param $accounts 邮箱账号
     * @param $corpid 公司的corpid
     * @return string
     */
    private function get_entry_url($accounts, $corpid)
    {
        $bindinfo = cachetool::get_bindinfo_bycorpid($corpid);

        $prikey = $bindinfo['privatesecret'];
        //域名
        $domain = $bindinfo['domain'];
        $product = $bindinfo['product'];
//        $corp_name = $bindinfo['corp_name'];
        //语言，0-中文，1-英文，可以不传此参数，默认为0
        $lang = "0";
        $time = date(time()) . '000';
        $res = openssl_pkey_get_private($prikey);
        $src = $accounts . $domain . $time;
        if (openssl_sign($src, $out, $res)) {
            $enc = bin2hex($out);
            //提交登录的url,后台加上必须的参数,为了安全，可使用https提交
            $url = "https://entry.qiye.163.com/domain/oa/Entry?domain=" . $domain . "&account_name=" . $accounts . "&time=" . $time . "&enc=" . $enc . "&lang=" . $lang;
            return $url;
        }
        exit("请求异常。");
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

    /**
     * 判断是不是微信客户端 登陆过来的  需要想一下 防止被盗的策略
     * @access private
     */
    private function is_weixin()
    {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            return true;
        }
        return false;
    }

}
