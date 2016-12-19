<?php

namespace app\mailapi\controller;

use app\common\model\common;
use think\Db;
use think\image\Exception;

/**
 * 职员部门设置
 * @todo 部门相关信息可以添加到数据库中
 */
class maildep
{

    /**
     * 获取所有部门信息然后更新部门信息 可能会有删除  会有添加  会有更新
     * @param $prikey  私钥
     * @param $domain   域名
     * @param $product 产品
     * @param $flag 标志
     * @param $corp_id 组织架的 部门信息
     * @param $corpid 组织的微信corpid 信息
     * @param $corp_name 组织的name
     * @return bool
     */
    public static function exec_update_alldep($prikey, $domain, $product, $flag, $corp_id, $corpid, $corp_name)
    {
        $time = date(time()) . '000';
        $res = openssl_pkey_get_private($prikey);
        //必须使用post方法
        $src = "domain=" . $domain . "&product=" . $product . "&time=" . $time;
        try {
            if (openssl_sign($src, $out, $res)) {
                $sign = bin2hex($out);
                if ($flag == '10') {
                    //华北
                    $url = "https://apibj.qiye.163.com/qiyeservice/api/unit/getUnitList";
                } else {
                    //华东
                    $url = "https://apihz.qiye.163.com/qiyeservice/api/unit/getUnitList";
                }
                $response_json = json_decode(common::send_curl_request($url, $src . '&sign=' . $sign), true);
                file_put_contents('a.txt', '全部信息:' . print_r($response_json, true), FILE_APPEND);
                if ($response_json['suc'] == '1') {
                    return self::formatupdate_emaildep($response_json['con'], $corp_id, $corpid, $corp_name);
                }
            }
        } catch (Exception $ex) {
            return false;
        }
    }

    /**
     * 更新公司邮箱信息
     * @access public
     * [con] => Array
     * (
     * [0] => Array
     * (
     * [unit_id] => 625726
     * [unit_name] => 经理办公室
     * )
     * [1] => Array
     * (
     * [parent_id] => 236019
     * [unit_desc] => 负责公司产品销售
     * [unit_id] => 236012
     * [unit_name] => 济南顾问一部
     * )
     * [2] => Array
     * (
     * [parent_id] => 236019
     * [unit_id] => 625755
     * [unit_name] => 济南顾问二部
     * )
     * [3] => Array
     * (
     * [parent_id] => 236019
     * [unit_id] => 625757
     * [unit_name] => 客服部
     * )
     * [4] => Array
     * (
     * [parent_id] => 236019
     * [unit_id] => 659033
     * [unit_name] => 郑州顾问一部
     * )
     * [5] => Array
     * (
     * [parent_id] => 625731
     * [unit_id] => 659037
     * [unit_name] => 财务部
     * )
     * [6] => Array
     * (
     * [unit_id] => 236019
     * [unit_name] => 企业沟通事业部
     * )
     * [7] => Array
     * (
     * [parent_id] => 625731
     * [unit_id] => 659038
     * [unit_name] => 人力资源部
     * )
     * [8] => Array
     * (
     * [unit_id] => 625754
     * [unit_name] => 市场部
     * )
     * [9] => Array
     * (
     * [unit_id] => 625736
     * [unit_name] => 新品事业部
     * )
     * [10] => Array
     * (
     * [unit_id] => 625730
     * [unit_name] => 技术服务部
     * )
     * [11] => Array
     * (
     * [unit_id] => 625731
     * [unit_name] => 行政部
     * )
     * )
     * @param $dep_arr 部门数组信息
     * @param $corp_id
     * @param $corpid
     * @param $corp_name
     * @return false
     */
    public static function formatupdate_emaildep($dep_arr, $corp_id, $corpid, $corp_name)
    {
        try {

        } catch (Exception $ex) {
            file_put_contents('a.txt', '部门信息:' . print_r($dep_arr, true), FILE_APPEND);
        }
        //没有父亲部门的 默认为 0
        //首先把之前的数据清空
        $m = Db::name('mail_orgstructure');
        Db::startTrans();
        try {
            $m->where(array('corpid' => $corpid))->delete();
            $dep_data = array();
            foreach ($dep_arr as $k => $v) {
                $perdata = array(
                    'unit_id' => $v['unit_id'],
                    'unit_name' => $v['unit_name'],
                    'parent_id' => isset($v['parent_id']) ? $v['parent_id'] : 0,
                    'unit_desc' => $v['unit_desc'],
                    'corp_id' => $corp_id,
                    'corp_name' => $corp_name,
                    'corpid' => $corpid,
                    'updatetime' => time()
                );
                $dep_data[] = $perdata;
            }
            $m->insertAll($dep_data);
            // 提交事务
            Db::commit();
            return true;
        } catch (\Exception $e) {
            // 回滚事务
            file_put_contents('a.txt', $e->getMessage(), FILE_APPEND);
            Db::rollback();
        }
        return false;
    }

}
