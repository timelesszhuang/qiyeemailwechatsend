<?php
/**
 * 甩单数据 定期 每隔一分钟 分配到乐销易  只要有授权的客户就执行该操作 发送数据到乐销易
 * User: timeless
 * Date: 17-02-06
 * Time: 上午11:13
 */

namespace app\dailysendmail\controller;

use app\common\model\common;
use app\common\model\wechattool;
use think\Config;
use think\Controller;
use think\Db;


//访问的 url 为 http://sm.youdao.so/index.php/dailysendmail/Shuaidandan/index


class Shuaidan extends Controller
{
    /**
     *  邮箱到期时间等信息获取
     *  这个操作会定期执行
     *  每一天执行一次就可以
     * @access public
     */
    public function index()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $info = Db::name('auth_corp_info')->where(['contact_status' => '10'])->field('id,corpid,permanent_code,corp_name,corp_full_name,userid,email,mobile,addtime')->select();
        file_put_contents('a.txt', print_r($info, true), FILE_APPEND);
        //把联系人的信息也获取到
        if (!$info) {
            return;
        }
        $all_customer = [];
        foreach ($info as $v) {
            $name = '';
            $email = $v['email'];
            $mobile = $v['mobile'];
            //获取职员信息
            if ($v['userid']) {
                list($name, $adminmobile, $adminemail) = wechattool::get_wechat_userid_info($v['userid'], wechattool::get_corp_access_token($v['corpid'],
                    $v['permanent_code']));
                $email = $adminemail ?: $email;
                $mobile = $adminmobile ?: $mobile;
            }
            $all_customer[] = ['corp_name' => $v['corp_name'], 'corp_full_name' => $v['corp_full_name'], 'user_name' => $name, 'email' => $email, 'mobile' => $mobile,
                'addtime' => $v['addtime']];
            $ids = array_column($info, 'id');
            Db::name('auth_corp_info')->where(['id' => ['in', $ids]])->update(['contact_status' => '20']);
        }
        common::send_curl_request(Config::get('common.SHUAIDAN_URL'), ['customer' => serialize($all_customer)]);
    }

}