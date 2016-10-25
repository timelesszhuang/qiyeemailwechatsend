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
    'SUITE_TICKET' => 'suite_ticket',
];