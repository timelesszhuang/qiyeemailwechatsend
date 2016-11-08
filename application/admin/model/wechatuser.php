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
        file_put_contents('err.log', print_r($info, true), FILE_APPEND);
        if ($info) {
            return [true, $info];
        } else {
            return [false, []];
        }
    }

}