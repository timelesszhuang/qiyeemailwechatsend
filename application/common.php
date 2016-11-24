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
$domain = 'http://sm.youdao.so/';

return [
    //邮件列表中点击 跳转到的位置
    'ENTRYMAILURL' => $domain . 'index.php/dailysendmail/Wechatmailsend/check_redirect_entry',
    'EMAILAGENT_ID' => 1,
    'ADS_READ_URL' => $domain . 'index.php/sysadmin/Readad/index',
];