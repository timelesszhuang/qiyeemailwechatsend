<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
// 应用公共文件
$domain = 'http://sm.youdao.so';

return [
    //邮件列表中点击 跳转到的位置
    'redis_config' => [
        'host' => '59.111.92.124',
        'password' => 'LiuRui123$%^',
        'port' => 6379,
        'database' => 0
    ],
    'SUITE_TICKET' => 'suite_ticket',
    //corpid  获取 永久码
    'CORPID_PERMANENTCODE' => 'corpidpermanentcode',
    //corpid 获取邮箱的绑定信息
    'CORPID_BINDINFO' => 'corpid_bindinfo',
];