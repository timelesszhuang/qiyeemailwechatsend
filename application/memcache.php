<?php
/**
 * User: timeless
 * Date: 16-10-25
 * Time: 上午10:31
 */
return [
    'HOST' => '127.0.0.1',
    'PORT' => '11211',
    'EXPIRE' => '0',
    'MEMCACHE_PREFIX' => 'salesmenbeta2', //防止memcache 部署多个版本
    //套件的 suite_token  先存储在 suite_token 中  如果没有的话  可以到调用接口到 微信的服务器获取
    //比如 更新相关的公司信息之后 需要重新 更新缓存中的字段
    'SUITE_TICKET' => 'suite_ticket',
    //corpid  获取 永久码
    'CORPID_PERMANENTCODE' => 'corpidpermanentcode',
    //corpid 获取邮箱的绑定信息
    'CORPID_BINDINFO' => 'corpid_bindinfo',
];