<?php

namespace Mailapi\Controller;

use app\common\model\common;
use think\Db;
use think\image\Exception;

/**
 * 职员账号设置
 * @todo 职员的相关账号信息可以添加到数据库中
 */
class mailuser
{


    /**
     * 更新全部用户
     * @access public
     * @param $prikey
     * @param $domain
     * @param $product
     * @param $flag
     * @param $corp_id
     * @param $corpid
     * @param $corp_name
     * @return false
     */
    public static function exec_update_alluser($prikey, $domain, $product, $flag, $corp_id, $corpid, $corp_name)
    {
        try {
            $res = openssl_pkey_get_private($prikey);
            $page_num = 1;
            $dep_m = Db::name('mail_orgstructure');
            $user_m = Db::name('mail_user');
            $dep_idarr = $dep_m->getField('unit_id,unit_name', true);
            foreach ($dep_idarr as $k => $v) {
                //必须使用post方法   第一次请求该用户下的数据
                $response_json = self::get_depuser($k, $page_num, $domain, $product, $res, $flag);
                if ($response_json) {
                    self::update_user($response_json, $user_m, $k, $v, $corp_id, $corp_name, $corpid);
                    //该用户下用户数量   如果大于2000的话 需要分页 每次获取一页
                    $count = $response_json['con']['count'];
                    $page = ceil($count / 2000); //计算出总的页数
                    while ($page > 1) {
                        $response_json = self::get_depuser($k, $page, $domain, $product, $res, $flag);
                        self::update_user($response_json, $user_m, $k, $v, $corp_id, $corp_name, $corpid);
                        $page--;
                    }
                }
            }
            return true;
        } catch (Exception $ex) {
            return false;
        }
    }


    /**
     * 获取用户的用户的职员
     * 默认一页2000条
     * @param $unit_id
     * @param $page_num
     * @param $domain
     * @param $product
     * @param $res
     * @param $flag 标志
     * @return mixed
     */
    private static function get_depuser($unit_id, $page_num, $domain, $product, $res, $flag)
    {
        $time = date(time()) . '000';
        $src = "domain=" . $domain . "&page_num=" . $page_num . "&product=" . $product . "&recuion=true" . "&time=" . $time . "&unit_id=" . $unit_id;
        try {
            if (openssl_sign($src, $out, $res)) {
                $sign = bin2hex($out);
                if ($flag == '10') {
                    //华北
                    $url = "https://apibj.qiye.163.com/qiyeservice/api/unit/getAccountList";
                } else {
                    //华东
                    $url = "https://apibj.qiye.163.com/qiyeservice/api/unit/getAccountList";
                }
                return json_decode(common::send_curl_request($url, $src . '&sign=' . $sign), true);
            }
        } catch (Exception $ex) {
            return [];
        }
    }


    /**
     * 更新用户 更新用户
     * @access private
     * @param $response_json
     * @param $user_m
     * @param $unit_id
     * @param $unit_name
     * @param $corp_id
     * @param $corp_name
     * @param $corpid
     * @return boolean
     */
    private static function update_user($response_json, $user_m, $unit_id, $unit_name, $corp_id, $corp_name, $corpid)
    {
        //有种情况是 比如职员比较多的情况下
        if ($response_json['suc']) {
            Db::startTrans();
            $user_m->where(array('unit_id' => $unit_id, 'corp_id' => $corp_id))->delete();
            foreach ($response_json['con']['list'] as $k => $v) {
                $perdata = array(
                    'unit_id' => $unit_id,
                    'unit_name' => $unit_name,
                    'account_name' => $v['account_name'],
                    'account_openid' => $v['account_openid'],
                    'mobile' => isset($v['mobile']) ? $v['mobile'] : '',
                    'job_no' => $v['job_no'],
                    'nickname' => $v['nickname'],
                    'corp_id' => $corp_id,
                    'corp_name' => $corp_name,
                    'corpid' => $corpid,
                    'create_time' => strtotime($v['create_time']),
                );
                $data[] = $perdata;
            }
            if ($user_m->insertAll($data)) {
                Db::commit();
            } else {
                Db::rollback();
            }
        } else {
            return false;
        }
    }


    /**
     * 执行添加邮箱账号操作
     * @access public
     */
    public function exec_add_user()
    {
        $account_name = I('post.account_name');
        $nickname = I('post.nickname');
        $password = I("post.password");
        $password1 = I("post.password1");
        if ($password != $password1) {
            exit(json_encode(array('msg' => '用户信息添加失败，两次吗输入不一致。', 'title' => '添加用户信息失败', 'status' => self::error)));
        }
        if (!$account_name || !$nickname) {
            exit(json_encode(array('msg' => '用户信息添加失败, 用户名姓名必填。', 'title' => '添加用户信息失败', 'status' => self::error)));
        }
        $unit_id = I("post.unit_id");
        $unit_name = I('post.unit_name');
        $mobile = I("post.mobile");
        $job_no = I("post.job_no");
        $exp_time = I("post.exp_time");
        $addr_right = I("post.addr_right");
        $addr_visible = I("post.addr_visible");
        $fwd = I("post.fwd");
        $fwdauth = I("post.fwdauth");
        $passchange_req = I("post.passchange_req");
        $resetpass_mobile = I("post.resetpass_mobile");
        $resetpass_general = I("post.resetpass_general");
        //私钥
        $prikey = C('MAILPRIKEY');
        //域名
        $domain = C('MAILDOMAIN');
        $product = C('PRODUCT');
        $time = date(time()) . '000';
        $res = openssl_pkey_get_private($prikey);
        $exp_time_string = $exp_time ? "&exp_time=$exp_time" : "";
        $job_no_string = $job_no ? "&job_no=$job_no" : "";
        $mobile_string = $mobile ? "&mobile=$mobile" : "";
        $unit_id = $unit_id ? intval($unit_id) : "default";
        $src = "account_name=" . $account_name . "&addr_right=" . $addr_right . "&addr_visible=" . $addr_visible . "&domain=" . $domain . $exp_time_string . "&fwd=" . $fwd . "&fwdauth=" . $fwdauth . $job_no_string . $mobile_string . "&nickname=" . $nickname . "&pass_type=0" . "&passchange_req=" . $passchange_req . "&password=" . $password . "&product=" . $product . "&resetpass_general=" . $resetpass_general . "&resetpass_mobile=" . $resetpass_mobile . "&time=" . $time . "&unit_id=" . $unit_id;
        if (openssl_sign($src, $out, $res)) {
            $sign = bin2hex($out);
            $url = "https://apibj.qiye.163.com/qiyeservice/api/account/createAccount";
            $response_json = $this->exec_postresponse($url, $src . '&sign=' . $sign);
            if ($response_json['suc']) {
                if ($this->add_user_db($response_json['con'], $unit_name)) {
                    format_json_ajaxreturn('用户添加成功', '用户添加成功', 'success');
                }
                $msg = '用户信息添加失败';
            }
            $msg = !$msg ? '用户信息添加失败,错误参数' . $response_json['error_code'] : $msg;
        }
        $msg = !$msg ? '用户信息添加失败,参数异常，请联系管理员。' : $msg;
        format_json_ajaxreturn($msg, '添加用户信息失败', self::error);
    }

    /**
     * 数据库中添加该条记录
     * @access public
     * [con] => Array
     * (
     * [account_id] => 451287303
     * [account_name] => zhaoxingzhuang
     * [account_rank] => -17
     * [addr_right] => 1
     * [addr_visible] => 1
     * [address_right] => 1
     * [admin] =>
     * [authcode_status] => 0
     * [bigattach_status] => 0
     * [create_time] => 1464325734029
     * [delta_quota] => 512000
     * [domain] => cio.club
     * [domain_id] => 2952527
     * [exp_time] => 1464364800000
     * [extendc] => 0
     * [job_no] => 1
     * [mobile] => 13698612743
     * [nickname] => 赵兴壮
     * [org_id] => 0
     * [passchangeTime] => 1464325734029
     * [passchange_req] => 1
     * [passchange_time] => 1464325734029
     * [privacy_level] => 2
     * [quota] => 512000
     * [sex] => -1
     * [status] => 0
     * [true_name] => 赵兴壮
     * [type] => 2
     * [unit_id] => 750189
     * )
     */
    public function add_user_db($v, $unit_name)
    {
        return M('MailUser')->add(array(
            'account_name' => $v['account_name'],
            'account_openid' => $v['account_openid'],
            'addr_right' => $v['addr_right'],
            'addr_visible' => $v['addr_visible'],
            'create_time' => substr($v['create_time'], 0, -3),
            'domain' => $v['domain'],
            'domain_openid' => $v['domain_openid'],
            'fwd' => $v['fwd'],
            'fwdauth' => $v['fwdauth'] ? $v['fwdauth'] : '',
            'mobile' => $v['mobile'],
            'job_no' => $v['job_no'],
            'nickname' => $v['nickname'],
            'passchange_req' => $v['passchange_req'],
            'quota' => $v['quota'],
            'resetpass_general' => $v['resetpass_general'],
            'resetpass_mobile' => $v['resetpass_mobile'],
            'status' => $v['status'],
            'unit_id' => $v['unit_id'],
            'unit_name' => $unit_name,
        ));
    }

    /**
     * 部门视图编辑相关操作
     * @access public.
     */
    public function edit_user()
    {
        $account_name = I('post.id');
        //私钥
        $prikey = C('MAILPRIKEY');
        //域名
        $domain = C('MAILDOMAIN');
        $product = C('PRODUCT');
        $time = date(time()) . '000';
        $res = openssl_pkey_get_private($prikey);
        //需要逐条获取部门信息
        //必须使用post方法
        $src = "account_name=" . $account_name . "&domain=" . $domain . "&product=" . $product . "&time=" . $time;
        if (openssl_sign($src, $out, $res)) {
            $sign = bin2hex($out);
            $url = "https://apibj.qiye.163.com/qiyeservice/api/account/getAccount";
            $response_json = $this->exec_postresponse($url, $src . '&sign=' . $sign);
            if ($response_json['suc']) {
                $this->update_user_info($response_json['con']);
                $this->assign('record', $response_json['con']);
            }
        }
        $this->display();
    }

    /**
     * 执行修改用户信息操作
     * @access public
     */
    public function exec_edit_user()
    {
        $account_name = I('post.account_name');
        $nickname = I('post.nickname');
        $password = I("post.password");
        $password1 = I("post.password1");
        if ($password != $password1) {
            exit(json_encode(array('msg' => '用户信息添加失败，两次吗输入不一致。', 'title' => '修改用户信息失败', 'status' => self::error)));
        }
        if (!$nickname) {
            exit(json_encode(array('msg' => '用户信息添加失败, 姓名必填。', 'title' => '修改用户信息失败', 'status' => self::error)));
        }
        $mobile = I("post.mobile");
        $job_no = I("post.job_no");
        $exp_time = I("post.exp_time");
        $addr_right = I("post.addr_right");
        $addr_visible = I("post.addr_visible");
        $fwd = I("post.fwd");
        $fwdauth = I("post.fwdauth");
        $passchange_req = I("post.passchange_req");
        $resetpass_mobile = I("post.resetpass_mobile");
        $resetpass_general = I("post.resetpass_general");
        //私钥
        $prikey = C('MAILPRIKEY');
        //域名
        $domain = C('MAILDOMAIN');
        $product = C('PRODUCT');
        $time = date(time()) . '000';
        $res = openssl_pkey_get_private($prikey);
        $exp_time_string = $exp_time ? "&exp_time=$exp_time" : "";
        $job_no_string = $job_no ? "&job_no=$job_no" : "";
        $mobile_string = $mobile ? "&mobile=$mobile" : "";
        $src = "account_name=" . $account_name . "&addr_right=" . $addr_right . "&addr_visible=" . $addr_visible . "&domain=" . $domain . $exp_time_string . "&fwd=" . $fwd . "&fwdauth=" . $fwdauth . $job_no_string . $mobile_string . "&nickname=" . $nickname . "&pass_type=0" . "&passchange_req=" . $passchange_req . "&password=" . $password . "&product=" . $product . "&resetpass_general=" . $resetpass_general . "&resetpass_mobile=" . $resetpass_mobile . "&time=" . $time;
        if (openssl_sign($src, $out, $res)) {
            $sign = bin2hex($out);
            $url = "https://apibj.qiye.163.com/qiyeservice/api/account/updateAccount";
            $response_json = $this->exec_postresponse($url, $src . '&sign=' . $sign);
            if ($response_json['suc']) {
                format_json_ajaxreturn('用户更新成功', '用户更新成功', 'success');
            }
            $msg = !$msg ? '用户更新失败,错误参数' . $response_json['error_code'] : $msg;
        }
        $msg = !$msg ? '用户更新失败,参数异常，请联系管理员。' : $msg;
        format_json_ajaxreturn($msg, '更新用户信息失败', self::error);
    }

    /**
     * 格式化用户数据
     * Array
     * (
     * [con] => Array
     * (
     * [account_name] => ceshi5
     * [account_openid] => bd80e59f035282dd630dc8003eaf949a
     * [addr_right] => 1
     * [addr_visible] => 1
     * [create_time] => 2016-05-27
     * [domain] => cio.club
     * [domain_openid] => 22690d3892e2560c
     * [exp_time] => 2016-05-28
     * [fwd] => 1
     * [fwdauth] => 1
     * [job_no] => 1
     * [mobile] => 13698612743
     * [nickname] => 赵兴壮
     * [org_openid] => 22690d3892e2560c
     * [passchange_req] => 1
     * [quota] => 512000
     * [resetpass_general] => 1
     * [resetpass_mobile] => 1
     * [status] => 0
     * [unit_id] => 2952527_default
     * )
     * [suc] => 1
     * [ver] => 0
     * )
     * @access private
     */
    private function update_user_info()
    {

    }

    /**
     * 禁用账号
     * @access public
     */
    public function forbidden_user()
    {
        $account_name = I('post.id');
//        $account_name = 'cehsi6';
        //私钥
        $prikey = C('MAILPRIKEY');
        //域名
        $domain = C('MAILDOMAIN');
        $product = C('PRODUCT');
        $time = date(time()) . '000';
        $res = openssl_pkey_get_private($prikey);
        //需要逐条获取部门信息
        //必须使用post方法
        $src = "account_name=" . $account_name . "&domain=" . $domain . "&product=" . $product . "&time=" . $time;
        if (openssl_sign($src, $out, $res)) {
            $sign = bin2hex($out);
            $url = "https://apibj.qiye.163.com/qiyeservice/api/account/suspendAccount";
            $response_json = $this->exec_postresponse($url, $src . '&sign=' . $sign);
            if ($response_json['suc']) {
                //0正常 1禁用 2已删除
                if ($this->change_user_status($account_name, 1)) {
                    format_json_ajaxreturn('用户禁用成功', '用户禁用成功', 'success');
                }
                $msg = '用户禁用成功，本地禁用失败，请更新全部账号。';
            }
            $msg = !$msg ? '用户禁用失败,错误参数' . $response_json['error_code'] : $msg;
        }
        $msg = !$msg ? '用户禁用失败,参数异常，请联系管理员。' : $msg;
        format_json_ajaxreturn($msg, '添加用户信息失败', self::error);
    }

    /**
     * 更新部门状态信息 改为禁用 更新本地数据库
     * @access private
     * @param string $account_name 账号名
     * @param int $status 账号的状态
     */
    private function change_user_status($account_name, $status)
    {
        return M('MailUser')->where(array('account_name' => array('eq', $account_name)))->save(array('status' => $status));
    }

    /**
     * 删除账号
     * @access public
     */
    public function delete_user()
    {
        $account_name = I('post.id');
//        $account_name = 'cehsi6';
        //私钥
        $prikey = C('MAILPRIKEY');
        //域名
        $domain = C('MAILDOMAIN');
        $product = C('PRODUCT');
        $time = date(time()) . '000';
        $res = openssl_pkey_get_private($prikey);
        //需要逐条获取部门信息
        //必须使用post方法
        $src = "account_name=" . $account_name . "&domain=" . $domain . "&product=" . $product . "&time=" . $time;
        if (openssl_sign($src, $out, $res)) {
            $sign = bin2hex($out);
            //该操作只是暂时删除  7天之内还是可以恢复的
            $url = "https://apibj.qiye.163.com/qiyeservice/api/account/deleteAccountSim";
            $response_json = $this->exec_postresponse($url, $src . '&sign=' . $sign);
            if ($response_json['suc']) {
//                0正常 1禁用 2已删除
                if ($this->change_user_status($account_name, 2)) {
                    format_json_ajaxreturn('用户删除成功', '用户删除成功', 'success');
                }
                $msg = '远程删除成功，本地删除失败，请更新全部账号。';
            }
            $msg = !$msg ? '用户信息添加失败,错误参数' . $response_json['error_code'] : $msg;
        }
        $msg = !$msg ? '用户信息添加失败,参数异常，请联系管理员。' : $msg;
        format_json_ajaxreturn($msg, '添加用户信息失败', self::error);
    }

    /**
     * 账号别名获取 更新
     * @access public
     */
    public function alias_user()
    {
        $account_name = I('post.id');
//        $account_name = 'cehsi6';
        //私钥
        $prikey = C('MAILPRIKEY');
        //域名
        $domain = C('MAILDOMAIN');
        $product = C('PRODUCT');
        $time = date(time()) . '000';
        $res = openssl_pkey_get_private($prikey);
        //获取部门账号别名
        //必须使用post方法
        $src = "account_name=" . $account_name . "&domain=" . $domain . "&product=" . $product . "&time=" . $time;
        if (openssl_sign($src, $out, $res)) {
            $sign = bin2hex($out);
            $url = "https://apibj.qiye.163.com/qiyeservice/api/account/getAccountAliasList";
            $response_json = $this->exec_postresponse($url, $src . '&sign=' . $sign);
        }
        $this->assign('account_name', $account_name);
        $this->assign('record', $response_json);
        $this->getassign_common_data();
        $this->display();
    }

    /**
     * 添加账号别名
     * @access public
     */
    public function exec_add_user_alias()
    {
        $account_name = I('post.account_name');
        $alias_name = I('post.alias_name');
        //私钥
        $prikey = C('MAILPRIKEY');
        //域名
        $domain = C('MAILDOMAIN');
        $product = C('PRODUCT');
        $time = date(time()) . '000';
        $res = openssl_pkey_get_private($prikey);
        //获取部门账号别名
        //必须使用post方法
        $src = "account_name=" . $account_name . "&alias_name=" . $alias_name . "&domain=" . $domain . "&product=" . $product . "&time=" . $time;
        if (openssl_sign($src, $out, $res)) {
            $sign = bin2hex($out);
            $url = "https://apibj.qiye.163.com/qiyeservice/api/account/addAccountAlias";
            $response_json = $this->exec_postresponse($url, $src . '&sign=' . $sign);
            if ($response_json['suc']) {
                format_json_ajaxreturn('用户别名添加成功', '用户别名添加成功', 'success');
            }
            $msg = !$msg ? '用户别名添加失败,错误参数' . $response_json['error_code'] : $msg;
        }
        $msg = !$msg ? '用户别名添加失败,参数异常，请联系管理员。' : $msg;
        format_json_ajaxreturn($msg, '添加别名添加失败', self::error);
    }

    /**
     * 删除账号别名
     * @access public
     */
    public function exec_delete_alias()
    {
        $account_name = I('post.account_name');
        //然后需要截取用户名
        $alias = I('post.alias_name');
        $alias_name = substr($alias, 0, strpos($alias, '@'));
        //私钥
        $prikey = C('MAILPRIKEY');
        //域名
        $domain = C('MAILDOMAIN');
        $product = C('PRODUCT');
        $time = date(time()) . '000';
        $res = openssl_pkey_get_private($prikey);
        //获取部门账号别名
        //必须使用post方法
        $src = "account_name=" . $account_name . "&alias_name=" . $alias_name . "&domain=" . $domain . "&product=" . $product . "&time=" . $time;
        if (openssl_sign($src, $out, $res)) {
            $sign = bin2hex($out);
            $url = "https://apibj.qiye.163.com/qiyeservice/api/account/deleteAccountAlias";
            $response_json = $this->exec_postresponse($url, $src . '&sign=' . $sign);
            if ($response_json['suc']) {
                format_json_ajaxreturn('用户别名删除成功', '用户别名删除成功', 'success');
            }
            $msg = !$msg ? '用户别名删除失败,错误参数' . $response_json['error_code'] : $msg;
        }
        $msg = !$msg ? '用户别名删除失败,参数异常，请联系管理员。' : $msg;
        format_json_ajaxreturn($msg, '删除别名失败', self::error);
    }

    /**
     * 恢复账号相关设置
     * @access public
     */
    public function recover_user()
    {
        $account_name = I('post.id');
        //私钥
        $prikey = C('MAILPRIKEY');
        //域名
        $domain = C('MAILDOMAIN');
        $product = C('PRODUCT');
        $time = date(time()) . '000';
        $res = openssl_pkey_get_private($prikey);
        //需要逐条获取部门信息
        //必须使用post方法
        $src = "account_name=" . $account_name . "&domain=" . $domain . "&product=" . $product . "&time=" . $time;
        if (openssl_sign($src, $out, $res)) {
            $sign = bin2hex($out);
            $url = "https://apibj.qiye.163.com/qiyeservice/api/account/recoverAccount";
            $response_json = $this->exec_postresponse($url, $src . '&sign=' . $sign);
            if ($response_json['suc']) {
                //                0正常 1禁用 2已删除
                if ($this->change_user_status($account_name, 0)) {
                    format_json_ajaxreturn('用户恢复成功', '用户恢复成功', 'success');
                }
                $msg = '远程恢复成功，本地恢复失败，请更新全部账号信息。';
            }
            $msg = !$msg ? '用户信息恢复失败,错误参数' . $response_json['error_code'] : $msg;
        }
        $msg = !$msg ? '用户信息恢复失败,参数异常，请联系管理员。' : $msg;
        format_json_ajaxreturn($msg, '恢复用户信息失败', self::error);
    }

    /**
     * 密保手机号码修改
     */
    public function mobile_user()
    {
        //https://apibj.qiye.163.com/qiyeservice/api/mobile/getMobile?
        //account_name=zhangsan&
        //domain=abc.com&
        //product=abc_com&
        //time=1418559871893
        //&sign=account_name=zhangsan&domain=abc.com&product=abc_com
        $account_name = I('post.id');
        //私钥
        $prikey = C('MAILPRIKEY');
        //域名
        $domain = C('MAILDOMAIN');
        $product = C('PRODUCT');
        $time = date(time()) . '000';
        $res = openssl_pkey_get_private($prikey);
        //需要逐条获取部门信息
        //必须使用post方法
        $src = "account_name=" . $account_name . "&domain=" . $domain . "&product=" . $product . "&time=" . $time;
        if (openssl_sign($src, $out, $res)) {
            $sign = bin2hex($out);
            $url = "https://apibj.qiye.163.com/qiyeservice/api/mobile/getMobile";
            $response_json = $this->exec_postresponse($url, $src . '&sign=' . $sign);
//            print_r($response_json);
            if ($response_json['suc']) {
                $this->assign('record', $response_json['con']);
            }
        }
        $this->getassign_common_data();
        $this->assign('account_name', $account_name);
        //账号展现
        $this->display();
    }

    /**
     * 添加手机号
     * @access public
     */
    public function exec_add_mobile()
    {
        $mobile = I('post.mobile');
        $account_name = I('post.account_name');
//        https://apibj.qiye.163.com/qiyeservice/api/mobile/addMobile?
//        account_name=zhangsan&domain=abc.com&
//        mobile=13612312312&
//        product=abc_com&
//        time=1418559787507&
//        sign=account_name=zhangsan&domain=abc.com&mobile=13612312312&product=abc_com&time=1418559787507
        //私钥
        $prikey = C('MAILPRIKEY');
        //域名
        $domain = C('MAILDOMAIN');
        $product = C('PRODUCT');
        $time = date(time()) . '000';
        $res = openssl_pkey_get_private($prikey);
        //需要逐条获取部门信息
        //必须使用post方法
        $src = "account_name=" . $account_name . "&domain=" . $domain . "&mobile=" . $mobile . "&product=" . $product . "&time=" . $time;
        if (openssl_sign($src, $out, $res)) {
            $sign = bin2hex($out);
            $url = "https://apibj.qiye.163.com/qiyeservice/api/mobile/addMobile";
            $response_json = $this->exec_postresponse($url, $src . '&sign=' . $sign);
            if ($response_json['suc']) {
                format_json_ajaxreturn('绑定手机成功', '绑定手机成功', 'success');
            }
            $msg = !$msg ? '绑定手机失败,错误参数' . $response_json['error_code'] : $msg;
        }
        $msg = !$msg ? '绑定手机失败,参数异常，请联系管理员。' : $msg;
        format_json_ajaxreturn($msg, '绑定手机失败', self::error);
    }

    /**
     * exec_delete_mobile 绑定手机号码删除
     * @access public
     */
    public function exec_delete_mobile()
    {
//        https://apibj.qiye.163.com/qiyeservice/api/mobile/deleteMobile?
//        account_name=zhangsan&
//        domain=abc.com&
//        product=abc_com&
//        time=1418559994256&
//        sign=account_name=zhangsan&domain=abc.com&product=abc_com&time=1418559994256
        $mobile = I('post.mobile');
        $account_name = I('post.account_name');
        //私钥
        $prikey = C('MAILPRIKEY');
        //域名
        $domain = C('MAILDOMAIN');
        $product = C('PRODUCT');
        $time = date(time()) . '000';
        $res = openssl_pkey_get_private($prikey);
        //需要逐条获取部门信息
        //必须使用post方法
        $src = "account_name=" . $account_name . "&domain=" . $domain . "&product=" . $product . "&time=" . $time;
        if (openssl_sign($src, $out, $res)) {
            $sign = bin2hex($out);
            $url = "https://apibj.qiye.163.com/qiyeservice/api/mobile/deleteMobile";
            $response_json = $this->exec_postresponse($url, $src . '&sign=' . $sign);
            if ($response_json['suc']) {
                format_json_ajaxreturn('删除绑定手机成功', '删除绑定手机成功', 'success');
            }
            $msg = !$msg ? '删除绑定手机失败,错误参数' . $response_json['error_code'] : $msg;
        }
        $msg = !$msg ? '删除绑定手机失败,参数异常，请联系管理员。' : $msg;
        format_json_ajaxreturn($msg, '绑定手机失败', self::error);
    }

    /**
     * 修改密码
     * @access public
     */
    public function pwd_user()
    {
        $this->getassign_common_data();
        $this->assign('account_name', I('post.id'));
        $this->display();
    }

    /**
     * 更新密码
     * @access public
     */
    public function exec_update_pwd()
    {
//        https://apibj.qiye.163.com/qiyeservice/api/account/updatePassword?
//        account_name=zhangsan&
//        domain=abc.com&
//        passchange_req=0&
//        password=654321&
//        product=abc_com&
//        time=1418558629335&
//        sign=account_name=zhangsan&domain=abc.com&passchange_req=0&password=654321&product=abc_com&time=1418558629335
        $account_name = I('post.account_name');
        $password = I('post.password');
        $password1 = I('post.password1');
        if ($password != $password1) {
            format_json_ajaxreturn('两次密码输入不一致', '修改密码失败', self::error);
        }
        //首次登陆是不是要修改密码
        $passchange_req = I('post.passchange_req');
        //私钥
        $prikey = C('MAILPRIKEY');
        //域名
        $domain = C('MAILDOMAIN');
        $product = C('PRODUCT');
        $time = date(time()) . '000';
        $res = openssl_pkey_get_private($prikey);
        //需要逐条获取部门信息
        //必须使用post方法
        $src = "account_name=" . $account_name . "&domain=" . $domain . "&pass_type=0" . "&passchange_req=" . $passchange_req . "&password=" . $password . "&product=" . $product . "&time=" . $time;
//      echo $src;
//      format_json_ajaxreturn($src, '修改密码失败', self::error);
        if (openssl_sign($src, $out, $res)) {
            $sign = bin2hex($out);
            $url = "https://apibj.qiye.163.com/qiyeservice/api/account/updatePassword";
            $response_json = $this->exec_postresponse($url, $src . '&sign=' . $sign);
            if ($response_json['suc']) {
                format_json_ajaxreturn('修改密码成功', '修改密码成功', 'success');
            }
            $msg = !$msg ? '修改密码失败,错误参数' . $response_json['error_code'] : $msg;
        }
        $msg = !$msg ? '修改密码失败,参数异常，请联系管理员。' : $msg;
        format_json_ajaxreturn($msg, '修改密码失败', self::error);
    }

}
