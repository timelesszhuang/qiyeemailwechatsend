<?php

namespace app\sysadmin\Behavior;

use think\Session;

/**
 * 验证登录状态 行为
 */
class Authenticate
{

    /**
     * 每个组织机构
     * @access public
     * @param mixed $params
     * @return bool|void
     * @internal param string $param 参数
     */
    public function run(&$params)
    {
        return Session::has('id') ? true : false;
    }

}
