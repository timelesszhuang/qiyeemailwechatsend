<?php

namespace Mailapi\Controller;

use Mailapi\Controller;

/**
 * 客户单点登录相关操作
 */
class SinglesignonController extends \Mailapi\Controller\BaseController {

    /**
     * 获取单点登录的地址
     */
    public function get_entry_url() {
        $mail = session('EMAIL');
        //需要截取域名前边的值
        if ($mail) {
            //截取数据 xingzhuang@cio.club 截取出 xingzhuang 来
            $account_name = substr($mail, 0, strpos($mail, '@'));
        } else {
            //显示错误 邮箱未设置
            exit(json_encode(array('msg' => '请求参数异常', 'information' => '您还没有设置企业邮箱，请设置企业邮箱。', 'url' => '', 'status' => self::error)));
        }
        //私钥
        $prikey = C('MAILPRIKEY');
        //域名
        $domain = C('MAILDOMAIN');
        //语言，0-中文，1-英文，可以不传此参数，默认为0
        $lang = "0";
        $time = date(time()) . '000';
        $res = openssl_pkey_get_private($prikey);
        $src = $account_name . $domain . $time;
        if (openssl_sign($src, $out, $res)) {
            $enc = bin2hex($out);
            //提交登录的url,后台加上必须的参数,为了安全，可使用https提交
            $url = "https://entry.qiye.163.com/domain/oa/Entry?domain=" . $domain . "&account_name=" . $account_name . "&time=" . $time . "&enc=" . $enc . "&lang=" . $lang;
//            echo $url;
            exit(json_encode(array('msg' => '', 'information' => '', 'url' => $url, 'status' => 'success')));
        }
        exit(json_encode(array('msg' => '请求参数异常', 'information' => '获取单点登录地址异常，请联系管理员。', 'url' => '', 'status' => self::error)));
    }

}
