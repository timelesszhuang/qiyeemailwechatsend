<?php

namespace app\admin\controller;

use app\common\model\common;
use think\Session;

/**
 * 邮箱账号信息
 */
class Mailinfo extends Base
{

    /**
     * 获取邮箱信息
     * @access public
     */
    public function get_domain_info()
    {
        //私钥
        $prikey = Session::get('privatesecret');
        //域名
        $domain = Session::get('domain');
        $product = Session::get('product');
        list($corp_info, $get_api_status) = \app\mailapi\controller\mailinfo::get_domain_info($prikey, $domain, $product);
        if ($get_api_status) {
            return $this->fetch('mailinfo', ['info' => $corp_info]);
        }
        return $this->fetch('mailinfo', ['msg' => '获取信息异常']);
        

//        $time = date(time()) . '000';
//        $res = openssl_pkey_get_private($prikey);
//        //需要逐条获取部门信息
//        //必须使用post方法
//        $src = "domain=" . $domain . "&product=" . $product . "&time=" . $time;
//        if (openssl_sign($src, $out, $res)) {
//            $sign = bin2hex($out);
//            $url = "https://apibj.qiye.163.com/qiyeservice/api/domain/getDomain";
//            $response_json = json_decode(common::send_curl_request($url . '?' . $src . '&sign=' . $sign), true);
//            if ($response_json['suc']) {
//                return $this->fetch('mailinfo', ['info' => $corp_info]);
//            }
//        }
//        return $this->fetch('mailinfo', ['msg' => '获取信息异常']);


    }

}
