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
        $flag = Session::get('flag');
        if (!Session::has('api_status')) {
            return $this->fetch('mailinfo', ['msg' => '贵公司网易企业邮箱接口暂时不可用，请拨打 4006360163 （网易企业服务） 联系我们，或通过 在线咨询 联系我们。']);
        }
        list($corp_info, $get_api_status) = \app\mailapi\controller\mailinfo::get_domain_info($prikey, $domain, $product, $flag);
        if ($get_api_status) {
            return $this->fetch('mailinfo', ['info' => $corp_info]);
        }
        return $this->fetch('mailinfo', ['msg' => '获取信息异常']);
    }

}
