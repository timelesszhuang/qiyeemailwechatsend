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
use think\Session;

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

/*      Session::set('corpid', 'wxe041af5a55ce7365');
        Session::set('login_ticket', 'cfffd975fb170d7bd2e6a09a7eb6ed64');
        Session::set('permanent_code', 'uHOamacIoPDQfoqkM4qriPn9_avVVwypn6Bz203UEVpN6nbLbvUrmCSuB6faijzC');
        Session::set('api_status', '10');
        Session::set('flag', '10');
        Session::set('corp_id', '65');
        Session::set('corp_name', '山东强比信息技术有限公司正式');
        Session::set('privatesecret', '-----BEGIN RSA PRIVATE KEY----- MIICXgIBAAKBgQDImsqx5wgohQqq2p+AAxNR1s5QfkoO9LJ+nTRBa4lqg8oPkbuN YgLSD3Y0wVF1i0vffaLuVz6Jlmr4dQAGI0CuYSMmuDyaMId5cyoHeqCACRA/uQev iT9XYO2n6z7l8h61dDEOx028vc+vcwVkghmAqMKhNzmEJX/CfqclVQXahwIDAQAB AoGAB9OqRuips9MFCIeBI6B7F31XDWLwBsdbU39Us5y7ftFnh9X6yFhjnciGpyZH xFtL+YtQWRZEVV/uCoWeG58yfcmDo8NnDfNZZVpXDKdvVoW1JDGdIX6rhHmRUiso qA6m1N7GtU/lErz19dBb8Dj+GZh116fPw5u2HOMsu5bOKmkCQQD2W1nwOYmiz2k9 01h19ZXjIH5hiax7UurQW3wUXe4VQb6FH8qEBRhO+CyoWleZq+x1czoxGKrW+P08 sYLMkU6LAkEA0HT5oS9H+mlmotOhdDL6dnbsaq6od9ivvCR4FKsu/x1Jq4fL9iej 7dIgi2s3qjkO/QCy2O7yyf/An5tz7LJ/dQJBAIAJeFPmw4bPf2X3irk72xvBTo3I 7NDnhkylz3YSX2PC2I79t9Yng7u/Ng6FbZPbi7h7G5patKenno3FwDIrrwMCQQCX wpF6J1HfnJx8LlZ8oiB13l5/zGgZ2EcYUfSaF4Y/dLMNje+PZYySt0e6OHRuGNww lTGffVaEeQ1jJWlgCROBAkEA9gCeU1HUkQcRZCtMh6uC8J/dv0FFMaxWJnz3yLO/ dHz9XDwjTJxyC6AN3VhD+FU4hRMyaAKjUCreNDcZLza1KA== -----END RSA PRIVATE KEY----- ');
        Session::set('product', 'qiangbi_net');
        Session::set('domain', 'cio.club');*/

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