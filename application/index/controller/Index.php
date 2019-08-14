<?php
/**
 * Created by PhpStorm.
 * User: timeless
 * Date: 16-11-29
 * Time: 上午11:19
 */

namespace app\index\controller;


use app\common\model\wechattool;
use Predis\Client;
use think\Config;
use think\Controller;

class Index extends Controller
{

    /**
     * 应用的首页
     */
    public function index()
    {
        $redisClient = new Client(Config::get('redis.redis_config'));
        $redisClient->set('a','1');
        return $this->fetch('index');
    }

    /**
     * 重定向到 其他授权的后台
     */
    public function redirect_auth()
    {
        $suite_id = Config::get('wechatsuite.EMAILSEND_SUITE_ID');
        $pre_auth_code = wechattool::get_pre_auth_code();
        $redirect_uri = urlencode(Config::get('common.DOMAIN') . '/index.php/index/Sysevent/trd_suite_callback');
        $url = "https://open.work.weixin.qq.com/3rdapp/install?suite_id=$suite_id&pre_auth_code=$pre_auth_code&redirect_uri=$redirect_uri&state=EMAILSEND";
        header('Location:' . $url);
        exit;
    }

}