<?php
/**
 * Created by PhpStorm.
 * User: timeless
 * Date: 16-11-4
 * Time: 上午9:24
 */

namespace app\sysadmin\model;


/**
 * 授权的其他的信息
 */
class authcorp
{
    /**
     * 格式化公司的授权信息
     * @access public
     * @param $v 每一个数据
     * @param $k 键
     */
    public function formatter_corp_info(&$v, $k)
    {
        switch ($v['subject_type']) {
            case '1':
                $v['subject_type'] = '企业';
                break;
            case '2':
                $v['subject_type'] = '政府以及事业单位';
                break;
            case '3':
                $v['subject_type'] = '其他组织';
                break;
            case '4':
                $v['subject_type'] = '团队号';
                break;
        }
        switch ($v['corp_type']) {
            case 'verified':
                $v['corp_type'] = '认证号';
                break;
            case 'unverified':
                $v['corp_type'] = '注册号';
                break;
            case 'test':
                $v['corp_type'] = '体验号';
                break;
        }
        $info = unserialize($v['agent_serialize']);
        $v['agent'] = implode(',', $info);
        $v['addtime'] = date('Y-m-d H:i', $v['addtime']);
        unset($v['agent_serialize']);
    }


}