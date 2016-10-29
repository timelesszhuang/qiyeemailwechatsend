<?php
/**
 * Created by PhpStorm.
 * User: timeless
 * Date: 16-10-29
 * Time: 下午1:02
 */

namespace app\admin\model;


use think\Config;
use think\Loader;
use think\Request;

class agent
{
    public static function verify_url()
    {

        $encodingAesKey = Config::get('wechatsuite.EMAILSEND_ENCODINGAESKEY');
        //企业号后台随机填写的token
        $token = Config::get('wechatsuite.EMAILSEND_TOKEN');
        //引入放在Thinkphp下的wechat 下的微信加解密包
        Loader::import('wechat.WXBizMsgCrypt', EXTEND_PATH, '.php');
        //安装官方要求接收4个get参数 并urldecode处理
        // 获取当前请求的name变量
        $msg_signature = urldecode(Request::instance()->param('msg_signature'));
        $timestamp = urldecode(Request::instance()->param('timestamp'));
        $nonce = urldecode(Request::instance()->param('nonce'));
        $echostr = urldecode(Request::instance()->param('echostr'));
        //实例化加解密类
        $sPostData = file_get_contents("php://input");
        file_put_contents('a.txt', 'init_data' . $sPostData, FILE_APPEND);
        $p_xml = new \DOMDocument();
        $p_xml->loadXML($sPostData);
        $corp_id = $p_xml->getElementsByTagName('ToUserName')->item(0)->nodeValue;
        $agent_id = $p_xml->getElementsByTagName('AgentID')->item(0)->nodeValue;
        file_put_contents('a.txt', '$msg_signature:' . $msg_signature . '$timestamp:' . $timestamp . '$nonce:' . $nonce . '$echostr:' . $echostr, FILE_APPEND);
        try {
            $wxcpt = new \WXBizMsgCrypt($token, $encodingAesKey, $corp_id);
            //解密数据最后 将解密后的数据返回给微信 成功会返回0 会将解密后的数据放入$echos
            $errCode = $wxcpt->VerifyURL($msg_signature, $timestamp, $nonce, $echostr, $reecho);
            file_put_contents('a.txt', 'errorcode:' . $errCode, FILE_APPEND);
            if ($errCode == 0) {
                echo $reecho;
            } else {
                print $errCode;
            }
        } catch (Exception $e) {
            //file_put_contents('a.txt', '$exception:' . $e->getMessage(), FILE_APPEND);
        }
    }


    /**
     * 事件相关操作
     * @access public
     */
    public static function event()
    {

        $encodingAesKey = Config::get('wechatsuite.EMAILSEND_ENCODINGAESKEY');
        //企业号后台随机填写的token
        $token = Config::get('wechatsuite.EMAILSEND_TOKEN');
        //引入放在Thinkphp下的wechat 下的微信加解密包
        Loader::import('wechat.WXBizMsgCrypt', EXTEND_PATH, '.php');
        //安装官方要求接收4个get参数 并urldecode处理
        // 获取当前请求的name变量
        $msg_signature = urldecode(Request::instance()->param('msg_signature'));
        $timestamp = urldecode(Request::instance()->param('timestamp'));
        $nonce = urldecode(Request::instance()->param('nonce'));
        //实例化加解密类
        //授权的地方不是 使用suite_id 使用 try catch  一部分使用的是
        $sPostData = file_get_contents("php://input");
        file_put_contents('a.txt', 'init_data' . $sPostData, FILE_APPEND);
        $p_xml = new \DOMDocument();
        $p_xml->loadXML($sPostData);
        $corp_id = $p_xml->getElementsByTagName('ToUserName')->item(0)->nodeValue;
        $agent_id = $p_xml->getElementsByTagName('AgentID')->item(0)->nodeValue;
        $wxcpt = new \WXBizMsgCrypt($token, $encodingAesKey, $corp_id);
        $errCode = $wxcpt->DecryptMsg($msg_signature, $timestamp, $nonce, $sPostData, $sMsg);
        //验证通过
        file_put_contents('a.txt', 'errorcode' . $errCode, FILE_APPEND);
        if ($errCode == 0) {
            $xml = new \DOMDocument();
            $xml->loadXML($sMsg);
            $reqFromUserName = $xml->getElementsByTagName('FromUserName')->item(0)->nodeValue;
            $reqCreateTime = $xml->getElementsByTagName('CreateTime')->item(0)->nodeValue;
            $reqMsgType = $xml->getElementsByTagName('MsgType')->item(0)->nodeValue;
            file_put_contents('a.txt', $reqFromUserName . $reqCreateTime, FILE_APPEND);
            //匹配类型
            switch ($reqMsgType) {
                case "event":
                    $reqEvent = $xml->getElementsByTagName('Event')->item(0)->nodeValue;
                    switch ($reqEvent) {
                        //进入事件
                        case "enter_agent":
//                            $this->enter_agent();
                            break;
                    }
                    break;
                //发送文本
                /*                case "text":
                                    $reqMsgContent = $xml->getElementsByTagName('Content')->item(0)->nodeValue;
                                    $user_str = $this->toUser();
                                    //获取发送者的salesman名称
                                    $send_salesman_name = $this->get_salesman_name();
                                    if (empty($send_salesman_name)) {
                                        return true;
                                    }
                                    $reqMsgContent = $send_salesman_name . ":" . $reqMsgContent;
                                    //如果要发送的用户不存在
                                    if (empty($user_str)) {
                                        return true;
                                    }
                                    $this->sendText($user_str, $reqMsgContent, C('AGENTID'));
                                    break;
                                case "image":
                                    $media_id = $xml->getElementsByTagName('MediaId')->item(0)->nodeValue;
                                    $user_str = $this->toUser();

                                    //获取发送者的salesman名称
                                    $send_salesman_name = $this->get_salesman_name();
                                    if (empty($send_salesman_name)) {
                                        return true;
                                    }
                                    //如果要发送的用户不存在
                                    if (empty($user_str)) {
                                        return true;
                                    }
                                    $send_text = $send_salesman_name . " 上传图片";
                                    $this->sendText($user_str, $send_text, C('AGENTID'));
                                    $this->sendImage($user_str, $media_id, C('AGENTID'));
                                    break;*/
            }


        }

    }


}