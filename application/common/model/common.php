<?php

/**
 * 公共的操作model 其他的model 集成自他  或者调用方法
 * User: timeless
 * Date: 16-10-25
 * Time: 上午10:27
 */
namespace app\common\model;

use think\Config;
use think\image\Exception;
use think\Loader;

class common
{
    /**
     * 发送curl 请求
     * @access public
     */
    public static function phpmemcache()
    {
        Loader::import('memcache.Memcachemanage', EXTEND_PATH, '.class.php');
        $mem = new \Memcachemanage(Config::get('memcache.HOST'), Config::get('memcache.PORT'), Config::get('memcache.EXPIRE'), Config::get('memcache.MEMCACHE_PREFIX'));
        return $mem;
    }

    /**
     * send_curl_request请求
     * 发送curl 请求
     * @param $url 发送到的请求链接
     * @param array $data 要post 发送的数据
     * @param string $flag 是 get 还是 post请求
     * @return
     */
    public static function send_curl_request($url, $data = array(), $flag = 'post')
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            if ($flag == 'get') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
            } else {
                curl_setopt($ch, CURLOPT_POST, 1);           // 发送一个常规的Post请求
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
            }
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);            // 显示返回的Header区域内容
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循
            $temp = curl_exec($ch);
            curl_close($ch);
            if (curl_errno($ch)) {
                file_put_contents('error.log', '微信推送消息错误：' . curl_error($ch) . "\r\n", FILE_APPEND);
            }
            file_put_contents('a.txt', $temp, FILE_APPEND);
            return $temp;
        } catch (Exception $ex) {
            file_put_contents('a.txt', $ex->getMessage(), FILE_APPEND);
        }

    }


}