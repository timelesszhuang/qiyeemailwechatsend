<?php
/**
 * 系统事件 model 相关
 * User: timeless
 * Date: 16-10-25
 * Time: 上午10:57
 */

namespace app\index\model;


use app\common\model\common;
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
        exit();
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
        //引入放在Thinkphp下的wechat 下的微信加解密包
        Loader::import('wechat.WXBizMsgCrypt', EXTEND_PATH, '.php');
        //安装官方要求接收4个get参数 并urldecode处理
        // 获取当前请求的name变量
        $msg_signature = urldecode(Request::instance()->param('msg_signature'));
        $timestamp = urldecode(Request::instance()->param('timestamp'));
        $nonce = urldecode(Request::instance()->param('nonce'));
        //实例化加解密类
        $wxcpt = new \WXBizMsgCrypt($token, $encodingAesKey, $suite_id);
        $sPostData = file_get_contents("php://input");
        $errCode = $wxcpt->DecryptMsg($msg_signature, $timestamp, $nonce, $sPostData, $sMsg);
        //验证通过
        if ($errCode == 0) {
            $xml = new \DOMDocument();
            $xml->loadXML($sMsg);
            file_put_contents('a.txt', print_r($sMsg, true), FILE_APPEND);
            //获取 infoType
            $info_type = $xml->getElementsByTagName('InfoType')->item(0)->nodeValue;
            switch ($info_type) {
                case "suite_ticket":
                    $suiteticket = $xml->getElementsByTagName('SuiteTicket')->item(0)->nodeValue;
                    $mem_obj = common::phpmemcache();
                    $mem_obj->set(Config::get('memcache.SUITE_TICKET'), $suiteticket);
                    //还需要 添加到数据库中  防止没有该字段
                    Db::table('sm_suite_ticket')->update(['suite_ticket' => $suiteticket, 'id' => 1]);
                    break;
                //还有好多的事件需要处理
            }
        }
        echo 'success';
    }


    /**
     * 获取 suite_ticket
     * 首先从memcache 中获取 如果没有 则调用接口再次获取一次
     * @access public
     */
    public static function get_suite_ticket()
    {
        $mem_obj = common::phpmemcache();
        $suite_ticket = $mem_obj->get(Config::get('memcache.SUITE_TICKET'));
        if ($suite_ticket) {
            return $suite_ticket;
        } else {
            $info = Db::table('SuiteTicket')->where('id', 1)->find();
            $mem_obj->set(Config::get('memcache.SUITE_TICKET'), $info['suite_ticket']);
            return $info['suite_ticket'];
        }
    }


    /**
     * test_xml
     * 测验xml 数据
     */
    public static function test_xml()
    {
        //测试获取 suite ticket
        /*        $suite_xml = <<<suite
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