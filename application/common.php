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
    'DOMAIN' => $domain,
    'ENTRYMAILURL' => $domain . '/index.php/dailysendmail/Wechatmailsend/check_redirect_entry',
    'EMAILAGENT_ID' => 1,
    'ADS_READ_URL' => $domain . '/index.php/wap/Ad/read',
    'SHUAIDAN_URL' => 'http://salesman.cc/index.php/api/wechatshuaidan/index',
    'NOTBIND_INFO' => '我公司已经收到您的授权请求，正在给您开通微信邮件推送服务,我们会在24小时内联系您；您可以拨打 4006360163 （网易企业服务） 联系我们，或通过 七鱼在线咨询 联系我们。',
];