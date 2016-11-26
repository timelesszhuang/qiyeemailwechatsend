<?php
/**
 * Created by PhpStorm.
 * User: timeless
 * Date: 16-11-4
 * Time: 上午9:24
 */

namespace app\sysadmin\model;

use app\common\model\wechattool;
use think\Config;
use think\Db;


/**
 * 授权的其他的信息
 */
class ad
{

    /**
     * 发送广告
     * $ads
     * @access public
     * @param $adds   广告信息
     * @param $corp_ids   公司的相关信息
     */
    public static function send_ads($adds, $corp_ids)
    {
        $loop = 1;
        $total = count($adds);
        if ($total >= 8) {
            $loop = ceil($total / 8);
        }
        //组织成 广告
        $all_ads = [];
        for ($i = 0; $i < $loop; $i++) {
            $articles = [];
            //每次循环取八条
            $start = $i * 8;
            $stop = (($start + 8) > $total) ? $total : ($start + 8);
            for ($start; $start < $stop; $start++) {
                //需要获取 url 信息
                $v = $adds[$start];
                if ($v['pic_url']) {
                    $perarticle = [
                        'title' => $v['title'],
                        'picurl' => Config::get('common.DOMAIN') . $v['pic_url'],
                        'url' => self::get_ads_url($v['id'])
                    ];
                } else {
                    $perarticle = [
                        'title' => $v['title'],
                        'url' => self::get_ads_url($v['id'])
                    ];
                }
                $articles[] = $perarticle;
            }
            $all_ads[] = $articles;
        }
        file_put_contents('a.txt', print_r($all_ads, true), FILE_APPEND);
        foreach ($corp_ids as $k => $v) {
            $corp_id = $v;
            //要把这个广告发送到  邮件推送的广告中
            $email_agentid = Config::get('common.EMAILAGENT_ID');
            list($agent_id, $corpid) = array_values(Db::name('agent_auth_info')->where(['appid' => $email_agentid, 'corp_id' => $corp_id])->field('agentid,corpid')->find());
            //然后获取 公司下的职员信息
            foreach ($all_ads as $per_ads) {
                wechattool::send_news($corpid, '@all', $agent_id, $per_ads);
            }
        }
    }

    /**
     * 获取 广告阅读的链接 会根据点击情况 存储阅读量
     * @access private
     * @param $add_id 广告的id
     * @return string
     */
    private static function get_ads_url($add_id)
    {
        return Config::get('common.ADS_READ_URL') . "?id={$add_id}";
    }

}