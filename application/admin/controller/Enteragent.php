<?php
/**
 * 客户点击菜单进入应用页面  获取应用 职员
 * User: timeless
 * Date: 16-10-31
 * Time: 下午6:20
 */

namespace app\admin\controller;


use app\admin\model\cachetool;
use app\common\model\common;
use app\common\model\wechattool;
use think\Request;

class Enteragent
{

    /**
     * index 添加菜单
     * @access public
     */
    public function index()
    {
        $corp_id = Request::instance()->param('corpid');
        $redirect_url = urlencode('http://sm.youdao.so/index.php/index/enteragent/entry_menu_mail');
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$corp_id}&redirect_uri={$redirect_url}&response_type=code&scope=SCOPE&state={$corp_id}#wechat_redirect";
        header("Location: {$url}");
    }


    /**
     * entry_menu_mail
     * 进入菜单应用
     */
    public function entry_menu_mail()
    {
        $code = Request::instance()->param('code');
        $corp_id = Request::instance()->param('state');
        echo $corp_id;
        exit;
        $access_token = wechattool::get_corp_access_token($corp_id, cachetool::get_permanent_code_by_corpid($corp_id));
        echo $access_token;
        $get_userid_url = "https://qyapi.weixin.qq.com/cgi-bin/user/getuserinfo?access_token={$access_token}&code={$code}";
        $user_info = common::send_curl_request($get_userid_url, [], 'get');
        $user_info = json_decode($user_info, true);
        print_r($user_info);

        /*        $wechatuser_id = $user_info['UserId'];
                //然后根据userid 获取 user_id
                $wechatuserid_email = D('Home/User')->get_wechatuserid_email();
                if (array_key_exists($wechatuser_id, $wechatuserid_email)) {
                    //存在的情况下
                    $email = $wechatuserid_email[$wechatuser_id]['emailaccount'];
                    $accounts = substr($email, 0, strpos($email, '@'));
                    header("Location:" . $this->get_entry_url($accounts));
                } else {
                    exit('请先在salesman中填写您的企业邮箱。');
                }*/
    }

}