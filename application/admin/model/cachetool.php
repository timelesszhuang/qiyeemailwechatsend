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
     * @return string
     */
    public static function get_permanent_code_by_corpid($corpid)
    {
        $mem = common::phpmemcache();
        $info = $mem->get(Config::get('memcache.CORPID_PERMANENTCODE'));
        if ($info) {
            $p_code = array_key_exists($corpid, $info) ? $info[$corpid] : '';
            if ($p_code) {
                return $p_code;
            }
        }
        //如果 memcache中不存在 则更新  memcache 为空 也更新
        $info = Db::name('auth_corp_info')->field('corp_id,permanent_code')->select();
        foreach ($info as $k => $v) {
            $corpid_permanentcode_arr[$v['corp_id']] = $v['permanent_code'];
        }
        $mem->set(Config::get('memcache.CORPID_PERMANENTCODE'), $corpid_permanentcode_arr);
        return $corpid_permanentcode_arr[$corpid];
    }

}