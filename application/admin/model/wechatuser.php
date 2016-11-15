<?php

namespace app\admin\model;

use think\Db;

class wechatuser
{

    /**
     * 验证微信的绑定的账号的状态
     * @access public
     * @param $corpid
     * @param $wechat_userid
     * @return array
     */
    public static function check_wechat_userid_status($corpid, $wechat_userid)
    {
        $info = Db::name('wechat_user')->where(['corpid' => $corpid, 'wechat_userid' => $wechat_userid])->find();
        if ($info) {
            return [true, $info];
        } else {
            return [false, []];
        }
    }


    /**
     * 更具corp_id 获取 所有的用户的相关信息
     * @access public
     * @param $corp_id 系统对公司的唯一标识
     * @return array
     */
    public static function get_wechatuser_arr_bycorp_id($corp_id)
    {
        return Db::name('wechat_user')->where(['corp_id' => $corp_id, 'status' => '10'])->field('wechat_userid,account,lastgetmailtime')->select();
    }


}