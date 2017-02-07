<?php

namespace app\mailapi\controller;

use app\common\model\common;
use think\image\Exception;

/**
 * 邮箱账号信息
 */
class mailinfo
{

    /**
     * 获取邮箱信息
     * @access public
     * @param $prikey 私钥
     * @param $domain 域名
     * @param $product 产品product 网易提供
     * @param 标志是华北还是华东|string $flag 标志是华北还是华东  默认是华北
     * @return array
     */
    public static function get_domain_info($prikey, $domain, $product, $flag = '10')
    {
        $time = date(time()) . '000';
        try {
            $res = openssl_pkey_get_private($prikey);
            //需要逐条获取部门信息
            //必须使用post方法
            $src = "domain=" . $domain . "&product=" . $product . "&time=" . $time;
            if (openssl_sign($src, $out, $res)) {
                $sign = bin2hex($out);
                if ($flag == '10') {
                    //华北
                    $url = "https://apibj.qiye.163.com/qiyeservice/api/domain/getDomain";
                } else {
                    //华东
                    $url = "https://apihz.qiye.163.com/qiyeservice/api/domain/getDomain";
                }
                $response_json = json_decode(common::send_curl_request($url, $src . '&sign=' . $sign), true);
                if ($response_json['suc']) {
                    return [$response_json['con'], true];
                }
                file_put_contents('a.txt', print_r($response_json, true), FILE_APPEND);
            }
        } catch (Exception $ex) {
            file_put_contents('a.txt', '获取邮件信息错误：' . print_r($ex->getMessage(), true), FILE_APPEND);
        }
        return [[], false];
    }

}
