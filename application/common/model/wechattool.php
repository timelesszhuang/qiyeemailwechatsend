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
        file_put_contents('a.txt', 'json suite_access_token' . $json_info, FILE_APPEND);
        $info = json_decode($json_info, true);
        file_put_contents('a.txt', 'suite_access_token:' . print_r($info, true), FILE_APPEND);
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
            'corp_id' => Config::get('wechatsuite.CORPID'),
            'provider_secret' => Config::get('wechatsuite.PROVIDERSECRET'),
        ]);
        print_r($post);
        $json_info = common::send_curl_request($url, $post, 'post');
        file_put_contents('a.txt', 'json provider_access_token' . $json_info, FILE_APPEND);
        $info = json_decode($json_info, true);

        file_put_contents('a.txt', 'suite_access_token:' . print_r($info, true), FILE_APPEND);
        return $info['provider_access_token'];
    }


}