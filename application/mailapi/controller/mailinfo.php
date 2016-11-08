<?php

namespace app\mailapi\controller;

use app\common\model\common;

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
     * @return array
     */
    public static function get_domain_info($prikey, $domain, $product)
    {
        $time = date(time()) . '000';
        $res = openssl_pkey_get_private($prikey);
        //需要逐条获取部门信息
        //必须使用post方法
        $src = "domain=" . $domain . "&product=" . $product . "&time=" . $time;
        if (openssl_sign($src, $out, $res)) {
            $sign = bin2hex($out);
            $url = "https://apibj.qiye.163.com/qiyeservice/api/domain/getDomain";
            $response_json = json_decode(common::send_curl_request($url, $src . '&sign=' . $sign), true);
            if ($response_json['suc']) {
                return [$response_json['con'], true];
            }
        }
        return [[], false];
    }

}