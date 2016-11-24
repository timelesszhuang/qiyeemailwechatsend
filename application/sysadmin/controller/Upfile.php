<?php
/**
 * Created by PhpStorm.
 * User: timeless
 * Date: 16-11-3
 * Time: 上午10:10
 */

namespace app\sysadmin\controller;


use app\sysadmin\model\common;
use think\Db;
use think\Request;
use think\Session;

class Upfile extends Base
{
    /**
     * 上传文件
     * @access public
     */
    public function upload_img()
    {
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('pic');
        // 移动到框架应用根目录/public/uploads/ 目录下
        $relative_path = DS . 'uploads' . DS . 'images';
        $path = ROOT_PATH . 'public' . $relative_path;
        $info = $file->validate(['size' => 10485760, 'ext' => 'jpg,png'])->move($path);
        if ($info) {
            // 成功上传后 获取上传信息
            // 输出 jpg
//            echo $info->getExtension();
            // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
            $savepath = $info->getSaveName();
            // 输出 42a79759f284b767dfcb2a0197904287.jpg
//            echo $info->getFilename();
            $url = $relative_path . DS . $savepath;
            return json_encode(['title' => "上传成功", "msg" => "上传成功", 'url' => $url, 'status' => self::success]);
        } else {
            // 上传失败获取错误信息
            return json_encode(common::form_ajaxreturn_arr("上传失败", "失败原因：{$file->getError()}", self::error));
        }
    }

}