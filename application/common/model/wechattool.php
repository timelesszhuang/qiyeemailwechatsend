<?php
/**
 * 微信相关 工具操作
 * User: timeless
 * Date: 16-10-25
 * Date: 16-10-25
 * Time: 下午5:40
 */

namespace app\common\model;


use app\admin\model\agent;
use app\admin\model\cachetool;
use Predis\Client;
use think\Config;
use think\console\Command;
use think\Db;

class wechattool
{

    /**
     * 继续执行操作
     */
    public static function backRunable($msg)
    {
        ob_end_clean();
        ob_start();
        echo $msg;
        $size = ob_get_length();
        header("Content-Length: " . $size);
        header("Connection: close");
        header("HTTP/1.1 200 OK");
        header('Content-Type:application/json; charset=utf-8');
        ob_end_flush();
        if (ob_get_length())
            ob_flush();
        flush();
    }


    /**
     * 获取 suite_ticket
     * 首先从memcache 中获取 如果没有 则调用接口再次获取一次
     * @access public
     */
    public static function get_suite_ticket()
    {
        $redisClient = new Client(Config::get('redis.redis_config'));
        $suite_ticket = $redisClient->get(Config::get('redis.SUITE_TICKET'));
        if ($suite_ticket) {
            return $suite_ticket;
        } else {
            $info = Db::name('suite_ticket')->where('id', 1)->find();
            $redisClient->set(Config::get('redis.SUITE_TICKET'), $info['suite_ticket']);
            return $info['suite_ticket'];
        }
    }


    /**
     * 获取应用套件令牌
     * ＠access public
     *  注意：通过本接口获取的accesstoken不会自动续期，每次获取都会自动更新。
     */
    public static function get_suite_access_token()
    {
        $key = 'suiteAccessToken';
        // 需要缓存下来
        $redisClient = new Client(Config::get('redis.redis_config'));
        $suite_access_token = $redisClient->get($key);
        if ($suite_access_token) {
            return $suite_access_token;
        }
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/service/get_suite_token';
        //令牌套件
        $post = json_encode([
            'suite_id' => Config::get('wechatsuite.EMAILSEND_SUITE_ID'),
            'suite_secret' => Config::get('wechatsuite.EMAILSEND_SECRET'),
            'suite_ticket' => wechattool::get_suite_ticket(),
        ]);
        $json_info = common::send_curl_request($url, $post, 'post');
        $info = json_decode($json_info, true);
        $suite_access_token = $info['suite_access_token'];
        $redisClient->set($key, $suite_access_token);
        $redisClient->expire($key, $info['expires_in']);
        return $suite_access_token;
    }


    /**
     * 获取服务提供商的 凭证
     * @access public
     */
    public static function get_provider_token()
    {
        $key = 'ProviderAccessToken';
        $redisClient = new Client(Config::get('redis.redis_config'));
        $provider_access_token = $redisClient->get($key);
        if ($provider_access_token) {
            return $provider_access_token;
        }
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/service/get_provider_token';
        $post = json_encode([
            'corpid' => Config::get('wechatsuite.CORPID'),
            'provider_secret' => Config::get('wechatsuite.PROVIDERSECRET'),
        ]);
        $json_info = common::send_curl_request($url, $post, 'post');
        $info = json_decode($json_info, true);
        $redisClient->set($key, $info['provider_access_token']);
        $redisClient->expire($key, $info['expires_in']);
        return $info['provider_access_token'];

    }


    /**
     *获取企业号 每个公司的 access_token 相关套件
     * @access public
     * @param $auth_corpid
     * @param $permanent_code
     * @return string
     */
    public static function get_corp_access_token($auth_corpid, $permanent_code)
    {
        $key = $auth_corpid . 'CorpAccessToken';
        $redisClient = new Client(Config::get('redis.redis_config'));
        $corp_access_token = $redisClient->get($key);
        if ($corp_access_token) {
            return $corp_access_token;
        }
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/service/get_corp_token?suite_access_token=' . self::get_suite_access_token();
        $post = json_encode([
            'auth_corpid' => $auth_corpid,
            'permanent_code' => $permanent_code,
        ]);
        $json_info = common::send_curl_request($url, $post, 'post');
        $info = json_decode($json_info, true);
        $redisClient->set($key, $info['access_token']);
        $redisClient->expire($key, $info['expires_in']);
        return isset($info['access_token']) ? $info['access_token'] : '';
    }


    /**
     * 获取微信的相关信息
     * @access public
     * @param string $wechat_userid 微信的user_id
     * @param $corp_access_token
     * @return array
     */
    public static function get_wechat_userid_info($wechat_userid, $corp_access_token)
    {
        $get_wechat_userid_url = "https://qyapi.weixin.qq.com/cgi-bin/user/get?access_token={$corp_access_token}&userid=" . $wechat_userid;
        $info = json_decode(common::send_curl_request($get_wechat_userid_url), true);
        return [isset($info['name']) ? $info['name'] : '', isset($info['mobile']) ? $info['mobile'] : '', isset($info['email']) ? $info['email'] : ''];
    }


    /**
     * 发送消息、imgage的链接地址
     * @param $corpid 微信的corpid
     * @return string
     */
    public static function get_sendwechat_url($corpid)
    {
        //根据 corp_id 获取永久授权码
        return 'https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token=' . wechattool::get_corp_access_token($corpid, cachetool::get_permanent_code_by_corpid($corpid));
    }


    /**
     *  获取预授权码  公司自己网站授权
     * @access public
     */
    public static function get_pre_auth_code()
    {
        $key = 'PreAuthCode';
        $redisClient = new Client(Config::get('redis.redis_config'));
        $pre_auth_code = $redisClient->get($key);
        if ($pre_auth_code) {
            return $pre_auth_code;
        }
        $url = "https://qyapi.weixin.qq.com/cgi-bin/service/get_pre_auth_code?suite_access_token=" . self::get_suite_access_token();
        $json_info = common::send_curl_request($url, [], 'GET');
        $info = json_decode($json_info, true);
        $redisClient->set($key, $info['pre_auth_code']);
        $redisClient->expire($key, $info['expires_in']);
        return $info['pre_auth_code'];
    }


    /**
     * 发送文本信息
     * @access public
     * @param $corpid  微信中组织的id
     * @param $touser  要发送给的人
     * @param $agent_id 授权放的微信id
     * @param $content 详细的内容
     * @return bool
     */
    public static function send_text($corpid, $touser, $agent_id, $content)
    {
        //表示没有
        $post = json_encode([
            "touser" => $touser,
            "msgtype" => "text",
            "agentid" => $agent_id,
            "text" => [
                "content" => $content,
            ],
        ], JSON_UNESCAPED_UNICODE);
        common::send_curl_request(self::get_sendwechat_url($corpid), $post, 'post');
        return true;
    }


    /**
     * 发送微信news   图文
     * @access public
     * @param $corpid 组织的corpid
     * @param $touser 发送给的人
     * @param $content 　内容详情
     * @param $agent_id 　应用的id
     * @return bool
     */
    public static function send_news($corpid, $touser, $agent_id, $content)
    {
        $post = json_encode([
            "touser" => $touser,
            "msgtype" => "news",
            "agentid" => $agent_id,
            "news" => [
                "articles" => $content
            ]
        ], JSON_UNESCAPED_UNICODE);
        $info = common::send_curl_request(self::get_sendwechat_url($corpid), $post, 'post');
        return true;
    }


    /**
     * 发送微信news   图文
     * @access public
     * @param $corpid 组织的corpid
     * @param $touser 发送给的人
     * @param $content 　内容详情
     * @param $agent_id 　应用的id
     * @return bool
     */
    public static function sendMail($corpid, $data)
    {
        $post = json_encode($data, JSON_UNESCAPED_UNICODE);
        $info = common::send_curl_request(self::get_sendwechat_url($corpid), $post, 'post');
        return true;
    }

}