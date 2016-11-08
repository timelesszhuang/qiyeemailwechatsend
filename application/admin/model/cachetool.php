<?php
/**
 * Created by PhpStorm.
 * User: timeless
 * Date: 16-10-29
 * Time: 下午4:41
 */

namespace app\admin\model;

//缓存相关操作  缓存一些微信常用的 信息
use app\common\model\common;
use think\Config;
use think\Db;

class cachetool
{

    /**
     * 根据 永久授权码 获取 corpid
     * @access public
     * @param $corpid  各个用户公司的corpid
     * @param string $flag
     * @return string
     */
    public static function get_permanent_code_by_corpid($corpid, $flag = 'get')
    {
        $mem = common::phpmemcache();
        if ($flag != 'get') {
            //不是获取数据
            self::get_init_corp_info($mem);
        }
        $info = $mem->get(Config::get('memcache.CORPID_PERMANENTCODE'));
        if (empty($info)) {
            self::get_init_corp_info($mem);
        }
        $info = $mem->get(Config::get('memcache.CORPID_PERMANENTCODE'));
        return self::get_pcode_bycorpid($corpid, $info);
    }

    /**
     * 实际上获取数据的操作
     * @param $corpid
     * @param $info
     * @return string
     */
    public static function get_pcode_bycorpid($corpid, $info)
    {
        if ($corpid) {
            $p_code = array_key_exists($corpid, $info) ? $info[$corpid] : '';
            if ($p_code) {
                return $p_code;
            }
        }
        //corpid 为 空值 表示不获取数据只是更新
    }

    /**
     * 更新memcache信息
     * @param $mem
     * @return mixed
     */
    public static function get_init_corp_info($mem)
    {
        //如果 memcache中不存在 则更新  memcache 为空 也更新
        $info = Db::name('auth_corp_info')->field('corpid,permanent_code')->select();
        foreach ($info as $k => $v) {
            $corpid_permanentcode_arr[$v['corpid']] = $v['permanent_code'];
        }
        $mem->set(Config::get('memcache.CORPID_PERMANENTCODE'), $corpid_permanentcode_arr);
    }


    /**
     * 根据微信的 corpid 获取 邮箱的相关绑定api信息  corpid =>[私钥，product，domain]
     * @access public
     * @param $corpid  绑定信息
     * @param string $flag 标志是获取还是更新
     * @return array ['privatesecret' => **,'product' => **,'domain' => **]
     */
    public function get_bindinfo_bycorpid($corpid, $flag = 'get')
    {
        $mem = common::phpmemcache();
        if ($flag != 'get') {
            //不是获取数据
            self::get_init_corpid_bindinfo_info($mem);
        }
        $info = $mem->get(Config::get('memcache.CORPID_BINDINFO'));
        if (empty($info)) {
            self::get_init_corpid_bindinfo_info($mem);
        }
        $info = $mem->get(Config::get('memcache.CORPID_BINDINFO'));
        return self::get_bindinfo_by_corpid($corpid, $info);
    }


    /**
     * 更新memcache信息
     * @param $mem
     * @return mixed
     */
    public static function get_init_corpid_bindinfo_info($mem)
    {
        //如果 memcache中不存在 则更新  memcache 为空 也更新
        $info = Db::name('corp_bind_api')->field('corpid,privatesecret,product,domain')->select();
        foreach ($info as $k => $v) {
            $corpid_bindinfo_arr[$v['corpid']] = [
                'privatesecret' => $v['privatesecret'],
                'product' => $v['product'],
                'domain' => $v['domain'],
            ];
        }
        $mem->set(Config::get('memcache.CORPID_BINDINFO'), $corpid_bindinfo_arr);
    }


    /**
     * 实际上获取数据的操作
     * @param $corpid
     * @param $info
     * @return string
     */
    public static function get_bindinfo_by_corpid($corpid, $info)
    {
        if ($corpid) {
            $arr = array_key_exists($corpid, $info) ? $info[$corpid] : '';
            if ($arr) {
                return $arr;
            }
        }
    }

}