<?php

namespace app\dailysendmail\controller;

use app\admin\model\wechatuser;
use app\common\model\common;
use app\common\model\wechattool;
use Predis\Client;
use think\Config;
use think\Controller;
use think\Db;
use think\image\Exception;

/**
 * beta1 新版本 使用redis 相关站点
 * 执行发送 邮件操作 可以同时起来 很多请求
 * http://sm.yizhixin.net/dailysendmail/Sendneteasemail/index
 */
class Sendneteasemail extends Controller
{

    public $redisClient;
    // 邮件待传递队列
    public $mailQueue = 'mailQueueList';


    public function _initialize()
    {
        // 初始化的
        set_time_limit(0);
        ignore_user_abort(true);
        $this->redisClient = new Client(Config::get('redis.redis_config'));
    }


    /**
     * 入门
     */
    public function index()
    {
        while (1) {
            // 每次单独出队列
            $mail = $this->redisClient->rpop($this->mailQueue);
            if ($mail) {
                try {
                    $mail = json_decode($mail, true);
                    wechattool::sendMail($mail['corpid'], $mail['arr']);
                } catch (\think\Exception $ex) {
                    file_put_contents('dailysendmailerror.txt', $ex->getMessage() . $ex->getLine(), FILE_APPEND);
                }
            } else {
                return;
            }
        }
    }
}