<?php
/**
 * 邮箱到期时间更新 状态 10 接口正常 20接口不正常 30 表示到期
 * User: timeless
 * Date: 16-11-17
 * Time: 上午11:13
 */

namespace app\dailysendmail\controller;

use app\admin\model\cachetool;
use app\common\model\wechattool;
use app\mailapi\controller\mailinfo;
use think\Config;
use think\Controller;
use think\Db;


//访问的 url 为 http://sm.youdao.so/index.php/dailysendmail/qiyeemailduetime/index  定期执行脚本 更新邮箱的到期时间信息


class Qiyeemailduetime extends Controller
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
        //定期更新邮箱的数据
        //获取邮箱接口正常的 数量
        $m = Db::name('corp_bind_api');
        $count = $m->where(['api_status' => '10'])->count();
        $step = 50;
        for ($page = 1; $page <= ceil($count / $step); $page++) {
            $perstep_bindinfo = $m->limit(($page - 1) * $step, $step)->field('id,corp_id,corpid,privatesecret,product,domain,corp_name,mail_exp_time,api_status')->select();
            $this->analyse_bindinfo($perstep_bindinfo);
        }
        cachetool::get_bindinfo_bycorpid('', 'init');
    }


    /**
     * 分析每一步的信息   要保证每天都会执行这个脚本
     * @access private
     * @param $info
     */
    private function analyse_bindinfo($info)
    {
        foreach ($info as $k => $v) {
            //邮箱到期时间
            $exp_time = $v['mail_exp_time'];
            //过期的情况 邮箱已经过期的情况
            if ($exp_time < time()) {
                Db::name('corp_bind_api')->where(['id' => $v['id']])->update(['api_status' => '30']);
                continue;
            }
            //距离过期事件只有一个月的情况下 从数据库中获取之后需要给出提醒
            $dueday = ($exp_time - time()) / 86400;
            if ($dueday > 50) {
                continue;
            }
            //表示快到期期限
            //检测一下是不是已经续费了  重新获取下 过期时间
            list($corp_info, $get_api_status) = mailinfo::get_domain_info($v['privatesecret'], $v['domain'], $v['product']);
            if ($get_api_status) {
                $mail_exp_time = substr($corp_info['exp_time'], 0, -3);
                if ($mail_exp_time != $v['mail_exp_time']) {
                    Db::name('corp_bind_api')->where(['id' => $v['id']])->update(['mail_exp_time' => $mail_exp_time]);
                    continue;
                }
                $duedate = date('Y-m-d', $mail_exp_time);
                //给管理员推送企业邮箱要过期
                //获取微信管理员的 微信账号
                $wechat_userid = Db::name('auth_corp_info')->where(['id' => $v['corp_id']])->find()['userid'];
                $agent_id = Db::name('agent_auth_info')->where(['corp_id' => $v['corp_id'], 'appid' => Config::get('common.EMAILAGENT_ID')])->field('agentid')->find()['agentid'];
                wechattool::send_text($v['corpid'], $wechat_userid, $agent_id, "您好，贵公司企业邮箱将于{$duedate}到期，请联系经销商及时续费，以免影响使用。");
                continue;
            }
            //获取异常 需要把接口类型置为 api_status 置为异常
            Db::name('corp_bind_api')->where(['id' => $v['id']])->update(['api_status' => '20']);
        }
    }

}