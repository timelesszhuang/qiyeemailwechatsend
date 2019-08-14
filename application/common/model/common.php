<?php

/**
 * 公共的操作model 其他的model 集成自他  或者调用方法
 * User: timeless
 * Date: 16-10-25
 * Time: 上午10:27
 */

namespace app\common\model;

use think\Config;
use think\Db;
use think\Loader;
use think\Request;

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
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($flag == 'get') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        } else {
            curl_setopt($ch, CURLOPT_POST, 1);           // 发送一个常规的Post请求
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT,60);
        $temp = curl_exec($ch);
        if (curl_errno($ch)) {
            file_put_contents('error.log', '发送curl请求错误：' . $url . curl_error($ch) . "\r\n", FILE_APPEND);
        }
        curl_close($ch);
        return $temp;
    }

    /**
     * log 日志 触发相关操作
     * @access public
     * @param $type 错误的类型
     * @param $info 状态
     * @return int|string
     */
    public static function add_log($type, $info)
    {
        //系统日志表
        return Db::name('log')->insertGetId(['type' => $type, 'info' => $info, 'addtime' => time()]);
    }

    /**
     * 修改页面信息  #分页实现
     * @access public
     */
    public static function get_page_info()
    {
        $pageNumber = intval(Request::instance()->param('page'));
        $pageRows = intval(Request::instance()->param('rows'));
        #没有请求的话实现当前的页面是第一页
        $pageNumber = (($pageNumber == null || $pageNumber == 0) ? 1 : $pageNumber);
        #每一页显示的数量  默认是 10
        $pageRows = (($pageRows == FALSE) ? 10 : $pageRows);
        $firstRow = ($pageNumber - 1) * $pageRows;
        return array($firstRow, $pageRows);
    }


    /**
     * 验证邮箱账号
     * @access public
     * @param $email 邮箱账号信息
     * @return bool
     */
    public static function check_email($email)
    {
        if (preg_match('/^([a-zA-Z0-9_\-\.])+@([a-zA-Z0-9_\-])+(\.[a-zA-Z0-9_\-])+/', $email)) {
            return true;
        } else {
            return false;
        }
    }

}