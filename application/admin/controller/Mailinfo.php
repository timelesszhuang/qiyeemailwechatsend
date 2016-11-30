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
    }

}
