<?php
/**
 * 企业号 授权相关操作
 * User: timeless
 * Date: 16-10-26
 * Time: 下午5:21
 */

namespace app\index\model;


use app\common\model\common;
use think\Db;

class auth
{

    /**
     * 分析 auth_corp_info 跟 auth_info 相关操作
     * @access public
     * @param $auth_info
     * @return bool
     */
    public static function analyse_corp_auth($auth_info)
    {
        //永久码
        $permanent_code = $auth_info['permanent_code'];
        $auth_user_info = $auth_info['auth_user_info'];

        $auth_corp_info_arr = $auth_info['auth_corp_info'];
        // 认证的 公司的信息 要 添加到数据库中的数据
        $add_auth_corp_info = [
            'corp_id' => $auth_corp_info_arr['corpid'],  //	授权方企业号id
            'corp_name' => $auth_corp_info_arr['corp_name'], //授权方企业号名称
            'permanent_code' => $permanent_code, //企业永久授权码
            'corp_type' => $auth_corp_info_arr['corp_type'],  //授权方企业号类型，认证号：verified, 注册号：unverified，体验号：test
            'corp_round_logo_url' => $auth_corp_info_arr['corp_round_logo_url'], //授权方企业号圆形头像
            'corp_square_logo_url' => $auth_corp_info_arr['corp_square_logo_url'], //授权方企业号方形头像
            'corp_user_max' => $auth_corp_info_arr['corp_user_max'], //	授权方企业号用户规模
            'corp_agent_max' => $auth_corp_info_arr['corp_agent_max'],
            'corp_wxqrcode' => $auth_corp_info_arr['corp_wxqrcode'], //	授权方企业号二维码
            'corp_full_name' => $auth_corp_info_arr['corp_full_name'], //所绑定的企业号主体名称
            'subject_type' => $auth_corp_info_arr['subject_type'], //企业类型，1. 企业; 2. 政府以及事业单位; 3. 其他组织, 4.团队号
            'verified_end_time' => $auth_corp_info_arr['verified_end_time'], //认证到期时间
            //管理员的信息
            'email' => array_key_exists('email', $auth_user_info) ? $auth_user_info['email'] : '',
            'mobile' => array_key_exists('mobile', $auth_user_info) ? $auth_user_info['mobile'] : '',
            'userid' => array_key_exists('userid', $auth_user_info) ? $auth_user_info['userid'] : '',
            'addtime' => time(),
            'edittime' => time(),
        ];
        file_put_contents('a.txt', '||||add_auth_corp_info:' . print_r($add_auth_corp_info, true), FILE_APPEND);
        //首先先添加 授权的公司信息 然后返回id
        $corp_id = self::add_auth_corp_info($add_auth_corp_info);
        if (!$corp_id) {
            common::add_log('添加预授权信息公司信息失败。', print_r($auth_info, true));
            common::add_log('添加预授权信息公司信息失败。添加数据库数组：', print_r($add_auth_corp_info, true));
            return false;
        }
        $agent_auth_info = $auth_info['auth_info'];
        file_put_contents('a.txt', '||||agent_auth_info:' . print_r($agent_auth_info, true), FILE_APPEND);
        $add_auth_agent_info = [];
        foreach ($agent_auth_info['agent'] as $k => $v) {
            file_put_contents('a.txt', '||||per_agent_info:' . print_r($v, true), FILE_APPEND);
            $privilege = array_key_exists('privilege', $v) ? $v['privilege'] : [];
//                "privilege":{
//                          "level":1,
//                          "allow_party":[1,2,3],           应用可见范围（部门）
//                          "allow_user":["zhansan","lisi"], 应用可见范围（成员）
//                          "allow_tag":[1,2,3],             应用可见范围（标签）
//                          "extra_party":[4,5,6],           额外通讯录（部门）
//                          "extra_user":["wangwu"],         额外通讯录（成员）
//                          "extra_tag":[4,5,6]              额外通讯录（标签）
//                     }
            $per_agent_info = [
                'corp_id' => $corp_id,
                'agentid' => $v['agentid'], //授权方应用id
                'appid' => $v['appid'], //服务商套件中的对应应用id
                'name' => $v['name'],   //授权方应用名字
                'square_logo_url' => $v['square_logo_url'],        //授权方应用方形头像
                'round_logo_url' => $v['round_logo_url'],          //授权方应用圆形头像
                'level' => $privilege['level'] ?: '',              //权限等级, 1: 标识信息只读, 2:信息只读, 3：信息读写
                'allow_party' => array_key_exists('allow_party', $privilege) ? ',' . implode(',', $privilege['allow_party']) . ',' : '',  //应用可见范围（部门）
                'allow_tag' => array_key_exists('allow_tag', $privilege) ? ',' . implode(',', $privilege['allow_tag']) . ',' : '',      //应用可见范围（标签）
                'allow_user' => array_key_exists('allow_user', $privilege) ? ',' . implode(',', $privilege['allow_user']) . ',' : '',    //应用可见范围（成员）
                'extra_party' => array_key_exists('extra_party', $privilege) ? ',' . implode(',', $privilege['extra_party']) . ',' : '',  //额外通讯录（部门）
                'extra_user' => array_key_exists('extra_user', $privilege) ? ',' . implode(',', $privilege['extra_user']) . ',' : '',    //额外通讯录（成员）
                'extra_tag' => array_key_exists('extra_tag', $privilege) ? ',' . implode(',', $privilege['extra_tag']) . ',' : '',      //额外通讯录（标签）
            ];
            $add_auth_agent_info[] = $per_agent_info;
        }
        file_put_contents('a.txt', '||||add_auth_agent_info:' . print_r($add_auth_agent_info, true), FILE_APPEND);
        if (!self::add_auth_agent_info($add_auth_agent_info)) {
            common::add_log('添加预授权信息公司信息失败。', print_r($auth_info, true));
            common::add_log('添加预授权信息公司信息失败。授权应用信息为：', print_r($add_auth_agent_info, true));
            return false;
        }
        return true;
    }

    /**
     * 添加 授权的 部门的信息
     * @access public
     * @param $d
     * @return int|string
     */
    public static function add_auth_corp_info($d)
    {
        return Db::name('auth_corp_info')->insertGetId($d);
    }


    /**
     * 添加 应用的相关操作
     * @access public
     * @param  $d
     * @return int|string
     */
    public static function add_auth_agent_info($d)
    {
        return Db::name('auth_corp_info')->insertAll($d);
    }


}