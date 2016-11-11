<?php
/**
 * 微信相关 工具操作
 * User: timeless
 * Date: 16-10-25
 * Date: 16-10-25
 * Time: 下午5:40
 */

namespace app\common\model;


use think\Config;
use think\console\Command;
use think\Db;

class wechattool
{
    /**
     * 获取 suite_ticket
     * 首先从memcache 中获取 如果没有 则调用接口再次获取一次
     * @access public
     */
    public static function get_suite_ticket()
    {
        $mem_obj = common::phpmemcache();
        $suite_ticket = $mem_obj->get(Config::get('memcache.SUITE_TICKET'));
        file_put_contents('a.txt', 'suite_ticket' . $suite_ticket, FILE_APPEND);
        if ($suite_ticket) {
            return $suite_ticket;
        } else {
            $info = Db::name('suite_ticket')->where('id', 1)->find();
            $mem_obj->set(Config::get('memcache.SUITE_TICKET'), $info['suite_ticket']);
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
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/service/get_suite_token';
        file_put_contents('a.txt', 'suite_token_url' . $url, FILE_APPEND);
        //令牌套件
        $post = json_encode([
            'suite_id' => Config::get('wechatsuite.EMAILSEND_SUITE_ID'),
            'suite_secret' => Config::get('wechatsuite.EMAILSEND_SECRET'),
            'suite_ticket' => wechattool::get_suite_ticket(),
        ]);
        $json_info = common::send_curl_request($url, $post, 'post');
//        file_put_contents('a.txt', 'json suite_access_token' . $json_info, FILE_APPEND);
        $info = json_decode($json_info, true);
//        file_put_contents('a.txt', 'suite_access_token:' . print_r($info, true), FILE_APPEND);
        return $info['suite_access_token'];
    }


    /**
     * 获取服务提供商的 凭证
     * @access public
     */
    public static function get_provider_token()
    {
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/service/get_provider_token';
        $post = json_encode([
            'corpid' => Config::get('wechatsuite.CORPID'),
            'provider_secret' => Config::get('wechatsuite.PROVIDERSECRET'),
        ]);
        $json_info = common::send_curl_request($url, $post, 'post');
//        file_put_contents('a.txt', 'json provider_access_token' . $json_info, FILE_APPEND);
        $info = json_decode($json_info, true);
        file_put_contents('a.txt', 'suite_access_token:' . print_r($info, true), FILE_APPEND);
        return $info['provider_access_token'];
    }


    /**
     *获取企业号 每个公司的 access_token 相关套件
     * @access public
     * @param $auth_corpid
     * @param $permanent_code
     */
    public static function get_corp_access_token($auth_corpid, $permanent_code)
    {
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/service/get_corp_token?suite_access_token=' . self::get_suite_access_token();
        $post = json_encode([
            'suite_id' => Config::get('wechatsuite.EMAILSEND_SUITE_ID'),
            'auth_corpid' => $auth_corpid,
            'permanent_code' => $permanent_code,
        ]);
        $json_info = common::send_curl_request($url, $post, 'post');
        file_put_contents('a.txt', 'json corp_access_token' . $json_info, FILE_APPEND);
        $info = json_decode($json_info, true);
        file_put_contents('a.txt', 'corp_access_token:' . print_r($info, true), FILE_APPEND);
        return $info['access_token'];
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
        return [$info['name'], $info['mobile'], $info['email']];
    }


}