<?php

namespace Home\Behavior;

/**
 * 验证是不是手机客户端
 */
class Mobilecheck
{

    public function run(&$params)
    {
        //is_mobile是二次开发自己添加的
        if (is_mobile()) {
            C('ismobile', 1);
        }
    }

}
