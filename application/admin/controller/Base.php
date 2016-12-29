<?php
/**
 * Created by PhpStorm.
 * User: timeless
 * Date: 16-11-2
 * Time: 上午9:13
 */

namespace app\admin\controller;


use think\Controller;
use think\Hook;
use think\Request;

/**
 * 基本操作 公共类库 比如 判断是不是已经登陆了
 */
class Base extends Controller
{


    const error = 'failed';
    const success = 'success';

    /**
     * _initialize()
     * 判断用户登录状态
     * @access public
     * @todo 登录验证
     */
    public function _initialize()
    {
        parent::_initialize();
        //需要判断是不是已经登陆了
        $action = array();
        //调用钩子方法 判断是不是已经登录
        if (!Hook::exec('app\\admin\\behavior\\Authenticate', 'run', $action)) {
            //表示没有登陆的操作
            exit('登陆异常，请从微信后台企业邮箱套件设置点击登陆。');
            //提示下在哪登陆
            
        }
    }

    /**
     * 前台分配数据   modal_id  datagrid_id 数据
     * @access public
     */
    public function get_assign()
    {
        $this->assign('modal_id', Request::instance()->param('modal_id'));
        $this->assign('datagrid_id', Request::instance()->param('datagrid_id'));
    }

    /**
     * user_id 获取user_name的情况
     * @access public
     * @param array $user_info 用户信息
     * @param int $user_id 用户的id
     * @return array
     */
    public function format_user($user_info, $user_id)
    {
        if (empty($user_info)) {
            $user_info = D('User')->get_user_indep_info();
        }
        if (!$user_id) {
            return array($user_info, '');
        }
        $peruser_info = $user_info[$user_id];
        if ($peruser_info) {
            $name = $peruser_info['status'] == 'in' ? $peruser_info['name'] : $peruser_info['name'] . '(已离职)';
            return array($user_info, $name);
        }
        return array($user_info, '');
    }

    /**
     * 获取本月份的第一天 时间戳
     * @access public
     */
    public function get_curmonthfirstday($date)
    {
        return strtotime(date('Y-m-01', strtotime($date)));
    }

    /**
     * 获取本本月份最后一天  时间戳
     * @access public
     */
    public function get_curmonthlastday($date)
    {
        return strtotime(date('Y-m-d 23:59:59', strtotime(date('Y-m-01', strtotime($date)) . ' +1 month -1 day')));
    }

    /**
     * 获取ip 区域的详细信息 接口
     * @access public
     */
    public function get_ip_info($ip)
    {
        $curl = curl_init(); //这是curl的handle
        //下面是设置curl参数
        $url = "http://ip.taobao.com/service/getIpInfo.php?ip=$ip";
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($curl, CURLOPT_HEADER, 0); //don't show header
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); //相当关键，这句话是让curl_exec($ch)返回的结果可以进行赋值给其他的变量进行，json的数据操作，如果没有这句话，则curl返回的数据不可以进行人为的去操作（如json_decode等格式操作）
        curl_setopt($curl, CURLOPT_TIMEOUT, 2);
        //这个就是超时时间了
        $data = curl_exec($curl);
        return json_decode($data, true);
    }

    /**
     * 截取中文字符串  utf-8
     * @param String $str 要截取的中文字符串
     * @param $len
     * @return
     */
    public function utf8chstringsubstr($str, $len)
    {
        for ($i = 0; $i < $len; $i++) {
            $temp_str = substr($str, 0, 1);
            if (ord($temp_str) > 127) {
                $i++;
                if ($i < $len) {
                    $new_str[] = substr($str, 0, 3);
                    $str = substr($str, 3);
                }
            } else {
                $new_str[] = substr($str, 0, 1);
                $str = substr($str, 1);
            }
        }
        //把数组元素组合为string
        return join($new_str);
    }

}