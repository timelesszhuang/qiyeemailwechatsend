<?php
/**
 * 微信相关 工具操作
 * User: timeless
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
        if ($suite_ticket) {
            return $suite_ticket;
        } else {
            $info = Db::table('SuiteTicket')->where('id', 1)->find();
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
        //令牌套件
        $post = [
            'suite_id' => Config::get('wechatsuite.EMAILSEND_SUITE_ID'),
            'suite_secret' => Config::get('wechatsuite.EMAILSEND_SECRET'),
            'suite_ticket' => wechattool::get_suite_ticket(),
        ];
        $info = json_decode(common::send_curl_request($url, $post));
        file_put_contents('a.txt', 'suite_access_token:' . print_r($info, true), FILE_APPEND);
        return $info['suite_access_token'];
    }

}