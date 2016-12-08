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
use think\image\Exception;
use think\Request;

//这是测试
//sm.youdao.so/index.php/dailysendmail/wechatmailsend/schedule_get_maillist?corp_id=2&corpid=wxe041af5a55ce7365

class Wechatmailsend extends Controller
{

    public $corp_name = '';
    public $corp_id = 0;
    public $corpid = '';
    public $bindinfo;
    public $prikey;
    public $product;
    public $domain;
    public $flag;

    /**
     * 定期获取邮件列表
     * @access public
     * @todo 首先全部的 邮箱的账号全部都获取到  需要截取到 账号名
     * 然后循环脚本   循环的时间存储到数据库中
     */
    public function schedule_get_maillist()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        //企业的corp_id 根据corpid  获取 该公司的相关数据
        $this->corp_id = Request::instance()->param('corp_id');
        //首先获取参数 corp_id  corpid  从缓存中获取 网易接口的 公钥 私钥等信息
        $this->corpid = Request::instance()->param('corpid');
        //获取 公司的邮箱的相关接口
        $this->bindinfo = cachetool::get_bindinfo_bycorpid($this->corpid);
        //禁用推送的 或者接口不正常 或者邮箱信息过期 进行的相关操作
        if (empty($this->bindinfo) || $this->bindinfo['status'] == 'off' || $this->bindinfo['api_status'] == '20' || $this->bindinfo['api_status'] == '30') {
            //表示获取绑定信息失败 需要存储到数据库中 获取api 异常 跳出系统
            return;
        }
        $this->prikey = $this->bindinfo['privatesecret'];
        $this->domain = $this->bindinfo['domain'];
        $this->product = $this->bindinfo['product'];
        $this->corp_name = $this->bindinfo['corp_name'];
        $this->flag = $this->bindinfo['flag'];
        sleep(rand(1, 120));
        $wechatuserid_info = wechatuser::get_wechatuser_arr_bycorp_id($this->corp_id);
        try {
            //获取agent_id 根据 corp_id 还有邮件套件的 id
            //公司套件中的数据
            $email_agentid = Config::get('common.EMAILAGENT_ID');
            $agent_id = Db::name('agent_auth_info')->where(['appid' => $email_agentid, 'corp_id' => $this->corp_id])->find()['agentid'];
            //全部这次请求的公司 推送的数量
            $all_sendcount = 0;
            $access_time = time();
            foreach ($wechatuserid_info as $k => $v) {
                list($endtime, $total) = $this->get_recmail_log($v['account'], $v['wechat_userid'], $agent_id, $v['lastgetmailtime']);
                file_put_contents('a.txt', $endtime, FILE_APPEND);
                $all_sendcount += $total;
                //更新一下 获取邮件的 上次获取时间
                Db::name('wechat_user')->where(['wechat_userid' => $v['wechat_userid'], 'corpid' => $this->corpid])->update(['lastgetmailtime' => $endtime]);
                //更新log 数据 精确到详细的每个人
                if ($total) {
                    Db::name('wechat_user_sendlog')->insert(['corpid' => $this->corpid, 'corp_id' => $this->corp_id, 'corp_name' => $this->corp_name, 'account' => $v['account'], 'name' => $v['name'], 'mailsendcount' => $total, 'accesstime' => $endtime]);
                }
            }
        } catch (Exception $ex) {
            file_put_contents('a.txt', $ex->getMessage(), FILE_APPEND);
        }
        //更新下公司的本次的请求信息 所有log数据库
        if ($all_sendcount) {
            Db::name('crontab_log')->insert(['corpid' => $this->corpid, 'corp_id' => $this->corp_id, 'corp_name' => $this->corp_name, 'mailsendcount' => $all_sendcount, 'accesstime' => $access_time]);
        }
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
     * @return array
     */
    private function get_recmail_log($accounts, $wechat_userid, $agent_id, $lastgettime)
    {
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
        $res = openssl_pkey_get_private($this->prikey);
        //必须使用post方法
        $src = "accounts={$accounts}&domain={$this->domain}&end={$end}&product={$this->product}&start={$start}&time={$time}";
        $total = 0;
        try {
            if (openssl_sign($src, $out, $res)) {
                $sign = bin2hex($out);
                if ($this->flag == '10') {
                    //华北
                    $url = "https://apibj.qiye.163.com/qiyeservice/api/mail/getReceivedMailLogs";
                } else {
                    //华东
                    $url = "https://apihz.qiye.163.com/qiyeservice/api/mail/getReceivedMailLogs";
                }
                $response_json = json_decode(common::send_curl_request($url, $src . '&sign=' . $sign, 'post'), true);
                if ($response_json['suc'] == '1') {
                    $total = $this->formatWechatSendeMail($response_json['con'], $accounts, $wechat_userid, $agent_id);
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
     * 发送post请求 获取返回信息
     * @access public
     * @param $url url账号
     * @param $data data数据
     * @return
     */
    public function exec_postresponse($url, $data)
    {
        $curl = curl_init(); //这是curl的handle
        //下面是设置curl参数
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($curl, CURLOPT_HEADER, 0); //don't show header
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); //相当关键，这句话是让curl_exec($ch)返回的结果可以进行赋值给其他的变量进行，json的数据操作，如果没有这句话，则curl返回的数据不可以进行人为的去操作（如json_decode等格式操作）
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); //这个是重点。
        //这个就是超时时间了
        curl_setopt($curl, CURLOPT_TIMEOUT, 2);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        $info = curl_exec($curl);
        curl_close($curl);
        return json_decode($info, true);
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
        /*      $loop = 1;
                if ($total > 8) {
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
                            $result = $v['result'];
                            if ($result == 1) {
                                $perarticle = [
                                    'title' => "新邮件 {$v['subject']} 发件人： {$v['mailfrom']} {$v['sendtime']}",
                                    'url' => $url
                                ];
                                $articles[] = $perarticle;
                            }
                        }
                        if (!empty($articles)) {
                            wechattool::send_news($this->corpid, $wechat_userid, $agent_id, $articles);
                        }
                    }
                }*/

        foreach ($list as $k => $v) {
            $articles = [];
            $result = $v['result'];
            if ($result == 1) {
                $perarticle = [
                    'title' => "新邮件 {$v['subject']} 发件人： {$v['mailfrom']} {$v['sendtime']}",
                    'url' => $url
                ];
                $articles[] = $perarticle;
                wechattool::send_news($this->corpid, $wechat_userid, $agent_id, $articles);
            }
        }
        return $total;
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
        $url = $this->get_entry_url($accounts, $corpid);
        header("Location:{$url}");
        exit;
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
        $flag = $bindinfo['flag'];
//        $corp_name = $bindinfo['corp_name'];
        //语言，0-中文，1-英文，可以不传此参数，默认为0
        $lang = "0";
        $time = date(time()) . '000';
        $res = openssl_pkey_get_private($prikey);
        $src = $accounts . $domain . $time;
        if (openssl_sign($src, $out, $res)) {
            $enc = bin2hex($out);
            //提交登录的url,后台加上必须的参数,为了安全，可使用https提交
            if ($flag == '10') {
                //华北
                $url = "https://entry.qiye.163.com/domain/oa/Entry?domain=" . $domain . "&account_name=" . $accounts . "&time=" . $time . "&enc=" . $enc . "&lang=" . $lang;
            } else {
                //华东
                $url = "https://entryhz.qiye.163.com/domain/oa/Entry?domain=" . $domain . "&account_name=" . $accounts . "&time=" . $time . "&enc=" . $enc . "&lang=" . $lang;
            }
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
     * index 点击底部菜单之后跳转到的地方
     * @access public
     */
    public function click_entrymailmenu()
    {
        $corpid = Request::instance()->param('corpid');
        $redirect_url = urlencode('http://sm.youdao.so/index.php/dailysendmail/wechatmailsend/entry_menu_mail?corpid=' . $corpid);
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$corpid}&redirect_uri={$redirect_url}&response_type=code&scope=SCOPE&state={$corpid}#wechat_redirect";
        ob_start();
        ob_end_flush();
        header("Location:$url");
        exit;
    }


    /**
     * entry_menu_mail
     * 进入菜单应用 微信客户端跳转之后的链接
     */
    public function entry_menu_mail()
    {
        $code = Request::instance()->param('code');
        $corpid = Request::instance()->param('corpid');
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
        if (empty($user_info)) {
            return $this->fetch('oath_msg', ['msg' => '您还没有绑定邮箱账号或贵公司网易企业邮箱接口暂时不可用。']);
        }
        if ($user_info['status'] == '10') {
            //存在的情况下
            //需要更新相关 log 日志
            ob_start();
            ob_end_flush();
            header("Location:" . $this->get_entry_url($user_info['account'], $corpid));
            exit;
        }
        if ($user_info['status'] == '20') {
            //审核信息 表示正在审核
            return $this->fetch('oath_msg', ['msg' => '您的账号绑定信息正在审核，请耐心等待贵公司管理员审核!']);
        } else {
            //审核失败
            return $this->fetch('oath_msg', ['msg' => '您的绑定信息有误，贵公司管理员审核未通过，请重新填写绑定信息!!']);
        }
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
