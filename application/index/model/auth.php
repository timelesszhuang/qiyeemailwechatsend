<?php
/**
 * 企业号 授权相关操作
 * User: timeless
 * Date: 16-10-26
 * Time: 下午5:21
 */

namespace app\index\model;


use app\admin\model\cachetool;
use app\common\model\common;
use think\Db;

class auth
{

    /**
     * 分析 auth_corp_info 跟 auth_info 相关操作   第一次授权的应用执行的操作
     * @access public
     * @param $auth_info
     * @return bool
     * @todo 授权的时候需要把 应用的数量 以及 id 信息存储起来
     */
    public static function analyse_init_corp_auth($auth_info)
    {
        //永久码
        $permanent_code = $auth_info['permanent_code'];
        $auth_user_info = $auth_info['auth_user_info'];
        $auth_corp_info_arr = $auth_info['auth_corp_info'];
        // 认证的 公司的信息 要 添加到数据库中的数据  分析为 添加到数据库中的数据
        $add_auth_corp_info = self::analyse_init_corp_info($auth_corp_info_arr, $auth_user_info, $permanent_code);
        file_put_contents('a.txt', '||||add_auth_corp_info:' . print_r($add_auth_corp_info, true), FILE_APPEND);
        //首先先添加 授权的公司信息 然后返回id
        $corp_id = self::add_auth_corp_info($add_auth_corp_info);
        //微信的企业 corpid
        $corpid = $add_auth_corp_info['corpid'];
        //错误信息更新到 数据库中
        if (!$corp_id) {
            common::add_log('添加预授权信息公司信息失败。', print_r($auth_info, true));
            common::add_log('添加预授权信息公司信息失败。添加数据库数组：', print_r($add_auth_corp_info, true));
            return [false, $corpid];
        }
        $agent_auth_info = $auth_info['auth_info'];
        file_put_contents('a.txt', '||||agent_auth_info:' . print_r($agent_auth_info, true), FILE_APPEND);
        //分析agent的 相关授权信息  变为 添加到数据库中的 授权信息
        list($add_auth_agent_info, $agent_info) = self::analyse_agent_info($agent_auth_info['agent'], $corp_id, $corpid);
        //更新 公司授权表的 auth_corp_info 表的 agent 相关信息
        file_put_contents('a.txt', '||||agent_info:' . print_r($agent_info, true), FILE_APPEND);
        self::update_corp_agentinfo($agent_info, $corp_id);
        file_put_contents('a.txt', '||||add_auth_agent_info:' . print_r($add_auth_agent_info, true), FILE_APPEND);
        //保存错误 数据到数据库中
        if (!self::add_auth_agent_info($add_auth_agent_info)) {
            common::add_log('添加预授权信息公司信息失败。', print_r($auth_info, true));
            common::add_log('添加预授权信息公司信息失败。授权应用信息为：', print_r($add_auth_agent_info, true));
            return [false, $corpid];
        }
        //更新memcache 中 相关数据
        cachetool::get_permanent_code_by_corpid('', 'init');
        return [true, $corpid];
    }

    /**
     * 修改授权之后才会调用该操作
     * 分析 更改的公司的授权信息
     * @access public
     * @param $auth_info 要分析的 授权信息  包含公司的 信息 还有授权的 应用的信息都需要修改
     * @return boolean
     */
    public static function analyse_changeauth_corp_auth($auth_info)
    {
        //永久码
        $auth_corp_info_arr = $auth_info['auth_corp_info'];
        // 认证的 公司的信息 要 添加到数据库中的数据  分析为 添加到数据库中的数据
        $edit_auth_corp_info = self::analyse_changeauth_corp_info($auth_corp_info_arr);
//        file_put_contents('a.txt', '||||edit_auth_corp_info:' . print_r($edit_auth_corp_info, true), FILE_APPEND);
        //修改 授权的公司信息
        $corp_id = self::edit_auth_corp_info($edit_auth_corp_info);
        $corpid = $edit_auth_corp_info['corpid'];
        //错误信息更新到 数据库中
        if (!$corp_id) {
            common::add_log('添加预授权信息公司信息失败。', print_r($auth_info, true));
            common::add_log('添加预授权信息公司信息失败。添加数据库数组：', print_r($edit_auth_corp_info, true));
            return false;
        }
        $agent_auth_info = $auth_info['auth_info'];
//        file_put_contents('a.txt', '||||agent_auth_info:' . print_r($agent_auth_info, true), FILE_APPEND);
        //分析agent的 相关授权信息  变为 添加到数据库中的 授权信息
        list($edit_auth_agent_info, $agent_info) = self::analyse_agent_info($agent_auth_info['agent'], $corp_id, $corpid);
        self::update_corp_agentinfo($agent_info, $corp_id);
//        file_put_contents('a.txt', '||||edit_auth_agent_info:' . print_r($edit_auth_agent_info, true), FILE_APPEND);
        //修改授权的应用相关信息 数据库
        if (!self::edit_auth_agent_info($edit_auth_agent_info)) {
            common::add_log('添加预授权信息公司信息失败。', print_r($auth_info, true));
            common::add_log('添加预授权信息公司信息失败。授权应用信息为：', print_r($edit_auth_agent_info, true));
            return false;
        }
        return true;
    }


    /**
     * 初始化第一次 授权的应用的相关信息
     * @access public
     * @param $auth_corp_info_arr 授权的信息
     * @param $auth_user_info 授权的用户信息
     * @param $permanent_code 永久授权码
     * @return array
     */
    public static function analyse_init_corp_info($auth_corp_info_arr, $auth_user_info, $permanent_code)
    {
        return [
            'corpid' => $auth_corp_info_arr['corpid'],  //	授权方企业号id
            'corp_name' => $auth_corp_info_arr['corp_name'], //授权方企业号名称
            'permanent_code' => $permanent_code, //企业永久授权码
            'corp_type' => $auth_corp_info_arr['corp_type'],  //授权方企业号类型，认证号：verified, 注册号：unverified，体验号：test
            'corp_round_logo_url' => $auth_corp_info_arr['corp_round_logo_url'], //授权方企业号圆形头像
            'corp_square_logo_url' => $auth_corp_info_arr['corp_square_logo_url'], //授权方企业号方形头像
            'corp_user_max' => $auth_corp_info_arr['corp_user_max'], //	授权方企业号用户规模
            'corp_agent_max' => $auth_corp_info_arr['corp_agent_max'],
            'corp_wxqrcode' => $auth_corp_info_arr['corp_wxqrcode'], //	授权方企业号二维码
            'corp_full_name' => array_key_exists('corp_full_name', $auth_corp_info_arr) ? $auth_corp_info_arr['corp_full_name'] : '', //所绑定的企业号主体名称
            'subject_type' => array_key_exists('subject_type', $auth_corp_info_arr) ? $auth_corp_info_arr['subject_type'] : '10', //企业类型，1. 企业; 2. 政府以及事业单位; 3. 其他组织, 4.团队号
            'verified_end_time' => array_key_exists('verified_end_time', $auth_user_info) ? $auth_corp_info_arr['verified_end_time'] : 0, //认证到期时间
            //管理员的信息
            'email' => array_key_exists('email', $auth_user_info) ? $auth_user_info['email'] : '',
            'mobile' => array_key_exists('mobile', $auth_user_info) ? $auth_user_info['mobile'] : '',
            'userid' => array_key_exists('userid', $auth_user_info) ? $auth_user_info['userid'] : '',
            'addtime' => time(),
            'edittime' => time(),
        ];
    }


    /**
     * 分析修改之后的相关操作
     * @access public
     * @param $auth_corp_info_arr 要分析的公司的授权相关信息
     * @return array
     */
    public static function analyse_changeauth_corp_info($auth_corp_info_arr)
    {
        return [
            'corpid' => $auth_corp_info_arr['corpid'],  //	授权方企业号id
            'corp_name' => $auth_corp_info_arr['corp_name'], //授权方企业号名称
            'corp_type' => $auth_corp_info_arr['corp_type'],  //授权方企业号类型，认证号：verified, 注册号：unverified，体验号：test
            'corp_round_logo_url' => $auth_corp_info_arr['corp_round_logo_url'], //授权方企业号圆形头像
            'corp_square_logo_url' => $auth_corp_info_arr['corp_square_logo_url'], //授权方企业号方形头像
            'corp_user_max' => $auth_corp_info_arr['corp_user_max'], //	授权方企业号用户规模
            'corp_agent_max' => $auth_corp_info_arr['corp_agent_max'],
            'corp_wxqrcode' => $auth_corp_info_arr['corp_wxqrcode'], //	授权方企业号二维码
            'corp_full_name' => $auth_corp_info_arr['corp_full_name'], //所绑定的企业号主体名称
            'subject_type' => $auth_corp_info_arr['subject_type'], //企业类型，1. 企业; 2. 政府以及事业单位; 3. 其他组织, 4.团队号
            'verified_end_time' => $auth_corp_info_arr['verified_end_time'], //认证到期时间
            'edittime' => time()
        ];
    }


    /**
     * 分析 要添加到数据库中的 应用的相关信息
     * @param $agent_info
     * @param $corp_id 组织的id
     * @param $corpid 部门的id
     * @return array
     */
    public static function analyse_agent_info($agent_info, $corp_id, $corpid)
    {
//        file_put_contents('a.txt', '||||agent_info:' . print_r($agent_info, true), FILE_APPEND);
        $add_auth_agent_info = [];
        $all_agent_info = [];
        foreach ($agent_info as $k => $v) {
//            file_put_contents('a.txt', '||||per_agent_info:' . print_r($v, true), FILE_APPEND);
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
                'corpid' => $corpid,
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
            $all_agent_info[$v['appid']] = $v['name'];
        }
        return [$add_auth_agent_info, $all_agent_info];
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
        return Db::name('agent_auth_info')->insertAll($d);
    }

    /**
     * 更新 每一个 微信企业号中的相关信息
     * @access public
     * @param $agent_info 应用的信息
     * @param $id
     * @return int|string
     * @throws \think\Exception
     */
    public static function update_corp_agentinfo($agent_info, $id)
    {
        return Db::name('auth_corp_info')->where(['id' => $id])->update(['agent_count' => count($agent_info), 'agent_serialize' => serialize($agent_info)]);
    }

    /**
     * 修改 公司的 授权信息
     * @param $e 一维数组
     * @return int|string
     */
    private static function edit_auth_corp_info($e)
    {
        $corpid = $e['corpid'];
        $id = Db::name('auth_corp_info')->where('corpid', $corpid)->find()['id'];
        $e['id'] = $id;
        Db::name('auth_corp_info')->update($e);
        return $id;
    }

    /**
     * 修改每一个应用的相关 授权信息
     * @param $e 二维数组
     * @return boolean
     */
    private static function edit_auth_agent_info($e)
    {
        foreach ($e as $k => $v) {
            $map['corp_id'] = $v['corp_id'];
            $map['agentid'] = $v['agentid'];
            // 把查询条件传入查询方法
            $v['id'] = Db::name('agent_auth_info')->where($map)->find()['id'];
            Db::name('agent_auth_info')->update($v);
        }
        return true;
    }

    /**
     * 取消授权信息
     * @access public
     * @param $corpid  微信的corp_id
     * @return bool
     */
    public static function cancel_auth($corpid)
    {
        file_put_contents('a.txt', 'CORPID:' . $corpid, FILE_APPEND);
        $map['corpid'] = $corpid;
        // 把查询条件传入查询方法
        $pre_corpinfo = Db::name('auth_corp_info')->where($map)->find();
        $id = $pre_corpinfo['id'];
        $a = [
            'pre_corp_id' => $id,
            'pre_corpid' => $corpid,
            'corp_name' => $pre_corpinfo['corp_full_name'],
            'email' => $pre_corpinfo['email'],
            'mobile' => $pre_corpinfo['mobile'],
            'canceltime' => time()
        ];
        file_put_contents('a.txt', 'PRE_CORPINFO:' . print_r($a, true), FILE_APPEND);
        Db::startTrans();
        try {
            //把已经取消授权的人的信息删除        file_put_contents('a.txt', '||||agent_auth_info:' . print_r($agent_auth_info, true), FILE_APPEND);
            Db::name('cancel_corp_info')->insert($a);
            //删除 组织的信息
            Db::name('auth_corp_info')->where($map)->delete();
            $where['corp_id'] = $id;
            //删除组织下的应用相关信息
            Db::name('agent_auth_info')->where($where)->delete();
            //需要同步把 该公司的信息删除掉 的信息取消掉
            //同步删除 该账号下面的 职员信息
            Db::name('wechat_user')->where($where)->delete();
            //网易api 接口删除掉
            Db::name('corp_bind_api')->where($where)->delete();
            // 提交事务
            Db::commit();
            cachetool::get_bindinfo_bycorpid('', 'init');
            cachetool::get_permanent_code_by_corpid('', 'init');
        } catch (\Exception $e) {
            // 回滚事务
            file_put_contents('a.txt', $e->getMessage(), FILE_APPEND);
            Db::rollback();
        }
        return true;
    }


}