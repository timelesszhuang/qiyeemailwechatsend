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
use Predis\Client;
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
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function get_permanent_code_by_corpid($corpid, $flag = 'get')
    {
        $redisClient = new Client(Config::get('redis.redis_config'));
        if ($flag != 'get') {
            //不是获取数据
            self::get_init_corp_info($redisClient);
        }
        $info = unserialize($redisClient->get(Config::get('redis.CORPID_PERMANENTCODE')));
        if (empty($info)) {
            self::get_init_corp_info($redisClient);
        }
        $info = unserialize($redisClient->get(Config::get('redis.CORPID_PERMANENTCODE')));
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
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function get_init_corp_info($redisClient)
    {
        //如果 memcache中不存在 则更新  memcache 为空 也更新
        $info = Db::name('auth_corp_info')->field('corpid,permanent_code')->select();
        foreach ($info as $k => $v) {
            $corpid_permanentcode_arr[$v['corpid']] = $v['permanent_code'];
        }
        $redisClient->setex(Config::get('redis.CORPID_PERMANENTCODE'), 300, serialize($corpid_permanentcode_arr));
    }


    /**
     * 根据微信的 corpid 获取 邮箱的相关绑定api信息  corpid =>[私钥，product，domain]
     * @access public
     * @param $corpid  绑定信息
     * @param string $flag 标志是获取还是更新
     * @return string ['privatesecret' => **,'product' => **,'domain' => **]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function get_bindinfo_bycorpid($corpid, $flag = 'get')
    {
        $key = Config::get('redis.CORPID_BINDINFO') . $corpid;
        $redisClient = new Client(Config::get('redis.redis_config'));
        $info = unserialize($redisClient->get($key));
        if (!$info) {
            $info = Db::name('corp_bind_api')->where('corpid', '=', $corpid)->field('corpid,corp_id,privatesecret,product,domain,corp_name,status,flag,api_status,addresslist_show')->find();
            $redisClient->setex($key, 300, serialize($info));
        }
        return $info;
    }

}