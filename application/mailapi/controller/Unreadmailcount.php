<?php

namespace Mailapi\Controller;

use Mailapi\Controller;

/**
 * 未读邮件数量统计
 */
class Unreadmailcount{

    /**
     * 获取没有读取的邮件数量
     * @access public
     */
    public function get_unreadmail_count() {
        $mail = session('EMAIL');
        //需要截取域名前边的值
        if ($mail) {
            //截取数据 xingzhuang@cio.club 截取出 xingzhuang 来
            $account_name = substr($mail, 0, strpos($mail, '@'));
        } else {
            //显示错误 邮箱未设置
            exit(json_encode(array('msg' => '请求参数异常', 'information' => '您还没有设置企业邮箱，请设置企业邮箱。', 'unread_count' => 0, 'status' => self::error)));
        }
        //私钥
        $prikey = C('MAILPRIKEY');
        //域名
        $domain = C('MAILDOMAIN');
        $time = date(time()) . '000';
        $res = openssl_pkey_get_private($prikey);
        $src = "account_name=" . $account_name . "&domain=" . $domain . "&time=" . $time . "&type=1";
        if (openssl_sign($src, $out, $res)) {
            $sign = bin2hex($out);
            $url = "https://cm.qiye.163.com/oaserver/user/getUnreadMsg?" . $src . "&sign=" . $sign;
            $response_json = $this->exec_getresponse($url);
            if ($response_json['status'] == 'ok') {
                exit(json_encode(array('msg' => '', 'information' => '', 'unread_count' => $response_json['count'], 'status' => 'success')));
            }
            exit(json_encode(array('msg' => $response_json['msg'], 'information' => $response_json['information'], 'unread_count' => $response_json['count'], 'status' => self::error)));
        }
        exit(json_encode(array('msg' => '请求参数异常', 'information' => '邮箱未读数量获取异常，请联系管理员。', 'unread_count' => 0, 'status' => self::error)));
    }

}
