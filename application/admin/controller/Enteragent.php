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
use think\Config;
use think\Request;

/**
 * 点击菜单进入邮箱应用相关操作
 */
class Enteragent
{

    /**
     * index 添加菜单
     * @access public
     */
    public function index()
    {
        $corp_id = Request::instance()->param('corpid');
        $redirect_url = urlencode(Config::get('common.DOMAIN').'/index.php/admin/enteragent/entry_menu_mail?corpid=' . $corp_id);
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$corp_id}&redirect_uri={$redirect_url}&response_type=code&scope=SCOPE&state={$corp_id}#wechat_redirect";
        ob_start();
        ob_end_flush();
        header("Location:$url");
        exit;
    }


    /**
     * entry_menu_mail
     * 进入菜单应用
     */
    public function entry_menu_mail()
    {
        $code = Request::instance()->param('code');
        $corpid = Request::instance()->param('corpid');
        $access_token = wechattool::get_corp_access_token($corpid, cachetool::get_permanent_code_by_corpid($corpid));
        $get_userid_url = "https://qyapi.weixin.qq.com/cgi-bin/user/getuserinfo?access_token={$access_token}&code={$code}";
        $user_info = common::send_curl_request($get_userid_url, [], 'get');
        $user_info = json_decode($user_info, true);
        //知道UserId  corpid之后可以获取 网易邮箱账号 也可以获取网易接口数据
        print_r($user_info);
    }


    /**
     * 点击菜单进入的地方
     * @access public
     */
    /*    public function entry_mail()
        {
            //构造该链接然后请求之后 会调转动  指定的路径
            $corpid = C('CORPID');
            $domain = C('DOMAIN');
            $redirect_url = urlencode($domain . '/index.php/Wechat/Wechatmailsend/entry_menu_mail');
            $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$corpid}&redirect_uri={$redirect_url}&response_type=code&scope=SCOPE&state=STATE#wechat_redirect";
            header("Location: {$url}");
        }*/

    /**
     * 跳转到邮箱单点登录  菜单点击之后  微信重定向到的地址
     * @access public
     */
    /*    public function entry_menu_mail()
        {
            $code = I('get.code');
            $get_userid_url = "https://qyapi.weixin.qq.com/cgi-bin/user/getuserinfo?access_token={$this->accesstoken}&code={$code}";
            $user_info = $this->send_curl_request($get_userid_url, [], 'get');
            $user_info = json_decode($user_info, true);
            $wechatuser_id = $user_info['UserId'];
            //然后根据userid 获取 user_id
            $wechatuserid_email = D('WechatUser')->get_weichatuserid_emailaccount();
            if (array_key_exists($wechatuser_id, $wechatuserid_email)) {
                //存在的情况下
                $accounts = $wechatuserid_email[$wechatuser_id];
                header("Location:" . $this->get_entry_url($accounts));
            } else {
                $this->display('index/wait_oath');
            }
        }*/


}