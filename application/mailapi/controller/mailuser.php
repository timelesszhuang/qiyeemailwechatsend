<?php

namespace app\mailapi\controller;


use app\common\model\common;
use think\Db;

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
            $dep_idarr = Db::name('mail_orgstructure')->where(['corpid' => $corpid])->field('unit_id,unit_name')->select();
            foreach ($dep_idarr as $k => $v) {
                //必须使用post方法   第一次请求该用户下的数据
                $response_json = self::get_depuser($v['unit_id'], $page_num, $domain, $product, $res, $flag);
                //print_r($response_json);
                if ($response_json) {
                    self::update_user($response_json, Db::name('mail_user'), $v['unit_id'], $v['unit_name'], $corp_id, $corp_name, $corpid);
                    //该用户下用户数量   如果大于2000的话 需要分页 每次获取一页
                    $count = $response_json['con']['count'];
                    $page = ceil($count / 2000); //计算出总的页数
                    while ($page > 1) {
                        $response_json = self::get_depuser($v['unit_id'], $page, $domain, $product, $res, $flag);
                        self::update_user($response_json, Db::name('mail_user'), $v['unit_id'], $v['unit_name'], $corp_id, $corp_name, $corpid);
                        $page--;
                    }
                }
            }
            return true;
        } catch (\Exception $ex) {
            file_put_contents('a.txt', '发送请求获取邮箱账号:' . $ex->getMessage(), FILE_APPEND);
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
            file_put_contents('a.txt', '发送请求获取邮箱账号:' . $ex->getMessage(), FILE_APPEND);
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
        file_put_contents('a.txt', print_r($response_json, true), FILE_APPEND);
        if ($response_json['suc']) {
            Db::startTrans();
            try {
                $user_m->where(array('unit_id' => $unit_id, 'corp_id' => $corp_id))->delete();
                $data = [];
                foreach ($response_json['con']['list'] as $k => $v) {
                    $perdata = array(
                        'unit_id' => $unit_id,
                        'unit_name' => $unit_name,
                        'account_name' => $v['account_name'],
                        'account_openid' => $v['account_openid'],
                        'mobile' => isset($v['mobile']) ? $v['mobile'] : '',
                        'job_no' => isset($v['job_no']) ? $v['job_no'] : '',
                        'nickname' => $v['nickname'],
                        'corp_id' => $corp_id,
                        'corp_name' => $corp_name,
                        'corpid' => $corpid,
                        'create_time' => substr($v['create_time'], 0, 10),
                    );
                    $data[] = $perdata;
                }
                file_put_contents('a.txt', print_r($data, true), FILE_APPEND);
                $user_m->insertAll($data);
                Db::commit();
            } catch (Exception $ex) {
                file_put_contents('a.txt', '提交数据：' . $ex->getMessage(), FILE_APPEND);
                Db::rollback();
            }
        } else {
            return false;
        }
    }


    /**
     * 添加手机号码查询
     * @access public
     * @param $prikey 公钥私钥
     * @param $domain 域名
     * @param $product 产品
     * @param $flag 标志
     * @param $mobile 手机号码
     * @param $account 邮箱账号
     * @param $id 更新数据
     */
    public static function add_mobile($prikey, $domain, $product, $flag, $mobile, $account, $id)
    {
//        https://apibj.qiye.163.com/qiyeservice/api/mobile/addMobile?
//        account_name=zhangsan&domain=abc.com&
//        mobile=13612312312&
//        product=abc_com&
//        time=1418559787507&
//        sign=account_name=zhangsan&domain=abc.com&mobile=13612312312&product=abc_com&time=1418559787507
        //私钥
        $time = date(time()) . '000';
        $res = openssl_pkey_get_private($prikey);
        //需要逐条获取部门信息
        //必须使用post方法
        $src = "account_name=" . $account . "&domain=" . $domain . "&mobile=" . $mobile . "&product=" . $product . "&time=" . $time;
        if (openssl_sign($src, $out, $res)) {
            $sign = bin2hex($out);
            if ($flag == '10') {
                //华北
                $url = "https://apibj.qiye.163.com/qiyeservice/api/mobile/addMobile";
            } else {
                //华东
                $url = "https://apihz.qiye.163.com/qiyeservice/api/mobile/addMobile";
            }
            $response_json = json_decode(common::send_curl_request($url, $src . '&sign=' . $sign), true);
            if ($response_json['suc']) {
                Db::name('mail_user')->where(['id' => $id])->update(['mobile' => $mobile]);
                exit(json_encode(['msg' => '绑定手机成功', 'status' => 'success']));
            }
            $msg = '绑定手机失败,错误参数' . $response_json['error_code'];
            exit(json_encode(['msg' => $msg, 'status' => 'failed']));
        }
        exit(json_encode(['msg' => '更新失败，请稍后重试。', 'status' => 'success']));
    }

    /**
     * 更新 用户相关信息
     * @access public
     * @param $prikey
     * @param $domain
     * @param $product
     * @param $flag
     * @param $mobile
     * @param $job_no
     * @param $account
     * @param $id
     */
    public static function update_user_info($prikey, $domain, $product, $flag, $mobile, $job_no, $account, $id)
    {
        //私钥
        $time = date(time()) . '000';
        $res = openssl_pkey_get_private($prikey);
        //需要逐条获取部门信息
        //必须使用post方法
        $job_no_string = $job_no ? "&job_no=$job_no" : "";
        $mobile_string = $mobile ? "&mobile=$mobile" : "";
        $src = "account_name=" . $account . "&domain=" . $domain . $job_no_string . $mobile_string . "&product = " . $product . "&time = " . $time;
        if (openssl_sign($src, $out, $res)) {
            $sign = bin2hex($out);
            if ($flag == '10') {
                //华北
                $url = "https://apibj.qiye.163.com/qiyeservice/api/account/updateAccount";
            } else {
                //华东
                $url = "https://apihz.qiye.163.com/qiyeservice/api/account/updateAccount";
            }
            $response_json = json_decode(common::send_curl_request($url, $src . '&sign=' . $sign), true);
            if ($response_json['suc']) {
                Db::name('mail_user')->where(['id' => $id])->update(['mobile' => $mobile]);
                exit(json_encode(['msg' => '绑定手机成功', 'status' => 'success']));
            }
            $msg = '绑定手机失败,错误参数' . $response_json['error_code'];
            exit(json_encode(['msg' => $msg, 'status' => 'failed']));
        }
        exit(json_encode(['msg' => '更新失败，请稍后重试。', 'status' => 'success']));
    }


}
