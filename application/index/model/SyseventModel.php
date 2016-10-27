<?php
/**
 * 系统事件 model 相关
 * User: timeless
 * Date: 16-10-25
 * Time: 上午10:57
 */

namespace app\index\model;


use app\common\model\common;
use app\common\model\wechattool;
use think\Config;
use think\console\command\make\Model;
use think\Db;
use think\Loader;
use think\Request;

class SyseventModel
{

    /**
     * 验证回调的url   回调相关的里面参数可以修改为已经包含的参数
     * @access private
     */
    public static function verify_url()
    {
        $encodingAesKey = Config::get('EMAILSEND_ENCODINGAESKEY');
        //企业号后台随机填写的token
        $token = Config::get('EMAILSEND_TOKEN');
        $corp_id = Config::get('CORPID');
        //引入放在Thinkphp下的wechat 下的微信加解密包
        Loader::import('wechat.WXBizMsgCrypt', EXTEND_PATH, '.php');
        //安装官方要求接收4个get参数 并urldecode处理
        // 获取当前请求的name变量
        $msg_signature = urldecode(Request::instance()->param('msg_signature'));
        $timestamp = urldecode(Request::instance()->param('timestamp'));
        $nonce = urldecode(Request::instance()->param('nonce'));
        $echostr = urldecode(Request::instance()->param('echostr'));
        //实例化加解密类
        try {
            $wxcpt = new \WXBizMsgCrypt($token, $encodingAesKey, $corp_id);
            //解密数据最后 将解密后的数据返回给微信 成功会返回0 会将解密后的数据放入$echos
            $errCode = $wxcpt->VerifyURL($msg_signature, $timestamp, $nonce, $echostr, $reecho);
            //file_put_contents('a.txt', 'errorcode:' . $errCode, FILE_APPEND);
            if ($errCode == 0) {
                echo $reecho;
            } else {
                print $errCode;
            }
        } catch (Exception $e) {

        }

    }


    /**
     * 系统事件接收
     * @access public
     */
    public static function event()
    {
        $encodingAesKey = Config::get('wechatsuite.EMAILSEND_ENCODINGAESKEY');
        //企业号后台随机填写的token
        $token = Config::get('wechatsuite.EMAILSEND_TOKEN');
        $suite_id = Config::get('wechatsuite.EMAILSEND_SUITE_ID');
//      $corp_id = Config::get('wechatsuite.CORPID');
        //引入放在Thinkphp下的wechat 下的微信加解密包
        Loader::import('wechat.WXBizMsgCrypt', EXTEND_PATH, '.php');
        //安装官方要求接收4个get参数 并urldecode处理
        // 获取当前请求的name变量
        $msg_signature = urldecode(Request::instance()->param('msg_signature'));
        $timestamp = urldecode(Request::instance()->param('timestamp'));
        $nonce = urldecode(Request::instance()->param('nonce'));
//        file_put_contents('auth.txt', '授权', FILE_APPEND);
        //实例化加解密类
        //授权的地方不是 使用suite_id 使用 try catch  一部分使用的是
        $sPostData = file_get_contents("php://input");
        $wxcpt = new \WXBizMsgCrypt($token, $encodingAesKey, $suite_id);
        $errCode = $wxcpt->DecryptMsg($msg_signature, $timestamp, $nonce, $sPostData, $sMsg);
        //验证通过
        if ($errCode == 0) {
            $xml = new \DOMDocument();
            $xml->loadXML($sMsg);
            //获取 infoType
            $info_type = $xml->getElementsByTagName('InfoType')->item(0)->nodeValue;
            switch ($info_type) {
                case "suite_ticket":
                    //获取　suite_ticket
                    $suiteticket = $xml->getElementsByTagName('SuiteTicket')->item(0)->nodeValue;
//                    file_put_contents('a.txt', 'suiteticket:' . $suiteticket, FILE_APPEND);
                    $mem_obj = common::phpmemcache();
                    $mem_obj->set(Config::get('memcache.SUITE_TICKET'), $suiteticket);
//                    file_put_contents('a.txt', '||||||newsuiteticket:' . wechattool::get_suite_ticket(), FILE_APPEND);
                    //还需要 添加到数据库中  防止没有该字段
                    Db::name('suite_ticket')->update(['suite_ticket' => $suiteticket, 'id' => 1]);
                    break;
                case "create_auth":
                    //获取 临时授权码 临时授权码使用一次后即失效　
                    $authcode = $xml->getElementsByTagName('AuthCode')->item(0)->nodeValue;
//                    file_put_contents('a.txt', $info_type, FILE_APPEND);
//                    file_put_contents('a.txt', 'xml:' . print_r($sMsg, true), FILE_APPEND);
//                    file_put_contents('a.txt', 'authcode:' . $authcode, FILE_APPEND);
                    //这个是临时授权码  根据临时授权码 获取 永久授权码 以及授权的信息
                    $get_permanent_code_url = 'https://qyapi.weixin.qq.com/cgi-bin/service/get_permanent_code?suite_access_token=' . wechattool::get_suite_access_token();
//                    file_put_contents('a.txt', '$get_permanent_code_url:' . $get_permanent_code_url, FILE_APPEND);
                    $post = json_encode([
                        'suite_id' => $suite_id,
                        'auth_code' => $authcode,
                    ]);
                    //永久授权码，并换取授权信息、企业access_token
                    $json_auth_info = common::send_curl_request($get_permanent_code_url, $post, 'post');
                    $auth_info = json_decode($json_auth_info, true);
                    if (!auth::analyse_init_corp_auth($auth_info)) {
                        return;
                    }
//                    file_put_contents('a.txt', 'auth_info:' . print_r($auth_info, true), FILE_APPEND);
                    break;
                case 'change_auth':
                    $corp_id = $xml->getElementsByTagName('AuthCorpId')->item(0)->nodeValue;
                    //根据corp_id 查询永久授权码
                    $permanent_code = DB::name('auth_corp_info')->where('corp_id', $corp_id)->find()['$permanent_code'];
                    $get_changed_auth_url = 'https://qyapi.weixin.qq.com/cgi-bin/service/get_auth_info?suite_access_token=' . wechattool::get_suite_access_token();
                    $post = json_encode([
                        'suite_id' => $suite_id,
                        'auth_corpid' => $corp_id,
                        'permanent_code' => $permanent_code,
                    ]);
                    $json_auth_info = common::send_curl_request($get_changed_auth_url, $post, 'post');
                    $auth_info = json_decode($json_auth_info, true);
                    if (!auth::analyse_changeauth_corp_auth($auth_info)) {
                        return;
                    }
                    break;
                //还有好多的事件需要处理
            }
        }
        echo 'success';
    }


    /**
     * test_xml
     * 测验xml 数据
     */
    public static function test_xml()
    {
        //测试获取 suite ticket
        /*
        $suite_xml = <<<suite
        <xml><SuiteId ><![CDATA[tjc6c18d9276c5e886]]></SuiteId >
        <SuiteTicket ><![CDATA[HSG23ryV7cqDzWXP0FsruvXFOgWEkqcgjD - XMdDlvcierAKeKe4TPrj7KOF4k9yB]]></SuiteTicket >
        <InfoType ><![CDATA[suite_ticket]]></InfoType >
        <TimeStamp >1477320050</TimeStamp>
        </xml>
        suite;
                $xml = new \DOMDocument();
                $xml->loadXML($suite_xml);
                echo '<pre>';
                print_r($xml);
                //获取InfoType
                $xml->getElementsByTagName('InfoType')->item(0)->nodeValue;
                var_dump($xml->getElementsByTagName('InfoType')->item(0)->nodeValue);*/

    }


}