<?php
/**
 * Created by PhpStorm.
 * User: timeless
 * Date: 16-11-23
 * Time: 上午10:34
 */

namespace app\common\controller;


class Upfile
{
    /**
     * 上传文件程序   非使用thinkphp 上传文件
     * @access public
     * @todo 每一种文件类型都会新建一个目录 比如 media image flash
     */
    public function upload_file()
    {
        $url_path = DS . 'uploads' . DS . 'kd_upload' . DS;
        $path = ROOT_PATH . 'public' . $url_path;
        //文件保存目录路径
        //定义允许上传的文件扩展名
        $ext_arr = array(
            'image' => array('gif', 'jpg', 'jpeg', 'png', 'bmp'),
            'flash' => array('swf', 'flv'),
            'media' => array('swf', 'flv', 'mp3', 'wav', 'wma', 'wmv', 'mid', 'avi', 'mpg', 'asf', 'rm', 'rmvb'),
            'file' => array('doc', 'docx', 'xls', 'xlsx', 'ppt', 'htm', 'html', 'txt', 'zip', 'rar', 'gz', 'bz2'),
        );
        //最大文件大小
        $max_size = 100000000000;
        $save_path = realpath($path) . DS;
        //PHP上传失败
        if (!empty($_FILES['imgFile']['error'])) {
            switch ($_FILES['imgFile']['error']) {
                case '1':
                    $error = '超过php.ini允许的大小。';
                    break;
                case '2':
                    $error = '超过表单允许的大小。';
                    break;
                case '3':
                    $error = '图片只有部分被上传。';
                    break;
                case '4':
                    $error = '请选择图片。';
                    break;
                case '6':
                    $error = '找不到临时目录。';
                    break;
                case '7':
                    $error = '写文件到硬盘出错。';
                    break;
                case '8':
                    $error = 'File upload stopped by extension。';
                    break;
                case '999':
                default:
                    $error = '未知错误。';
            }
            exit(json_encode(['error' => 1, 'message' => $error]));
        }
        //有上传文件时
        if (empty($_FILES) === false) {
            //原文件名
            $file_name = $_FILES['imgFile']['name'];
            //服务器上临时文件名
            $tmp_name = $_FILES['imgFile']['tmp_name'];
            //文件大小
            $file_size = $_FILES['imgFile']['size'];
            //检查文件名
            if (!$file_name) {
                exit(json_encode(['error' => 1, 'message' => "请选择文件。"]));
            }
            //检查目录
            if (@is_dir($save_path) === false) {
                exit(json_encode(['error' => 1, 'message' => "上传目录不存在。"]));
            }
            //检查目录写权限
            if (@is_writable($save_path) === false) {
                exit(json_encode(['error' => 1, 'message' => "上传目录没有写权限。"]));
            }
            //检查是否已上传
            if (@is_uploaded_file($tmp_name) === false) {
                exit(json_encode(['error' => 1, 'message' => "上传失败。"]));
            }
            //检查文件大小
            if ($file_size > $max_size) {
                exit(json_encode(['error' => 1, 'message' => "上传文件大小超过限制。"]));
            }
            //检查目录名
            $dir_name = empty($_GET['dir']) ? 'image' : trim($_GET['dir']);
            if (empty($ext_arr[$dir_name])) {
                exit(json_encode(array('error' => 1, 'message' => "目录名不正确。")));
            }
            //获得文件扩展名
            $temp_arr = explode(".", $file_name);
            $file_ext = array_pop($temp_arr);
            $file_ext = trim($file_ext);
            $file_ext = strtolower($file_ext);
            //检查扩展名
            if (in_array($file_ext, $ext_arr[$dir_name]) === false) {
                exit(json_encode(array('error' => 1, 'message' => "上传文件扩展名是不允许的扩展名。\n只允许" . implode(",", $ext_arr[$dir_name]) . "格式。")));
            }
            $save_path .= $dir_name . DS;
            $url_path .= $dir_name . DS;
            if (!file_exists($save_path)) {
                mkdir($save_path);
            }
            //新文件名
            $new_file_name = date("YmdHis") . '_' . rand(10000, 99999) . '.' . $file_ext;
            //移动文件
            $file_path = $save_path . $new_file_name;
            if (move_uploaded_file($tmp_name, $file_path) === false) {
                exit(json_encode(array('error' => 1, 'message' => "上传文件失败。")));
            }
            @chmod($file_path, 0644);
            $file_url = $url_path . $new_file_name;
            header('Content-type: text/html; charset=UTF-8');
            exit(json_encode(array('error' => 0, 'url' => $file_url)));
        }
    }

    /**
     * 文件管理操作
     * @access public
     */
    public function file_manager_json()
    {
        //服务器图像文件操作
        //根目录路径，可以指定绝对路径，比如
//        $root_path = UPLOAD_PATH . 'Images';

        $url_path = DS . 'uploads' . DS . 'kd_upload' . DS;
        $root_path = ROOT_PATH . 'public' . $url_path;
        $host = 'http://' . $_SERVER['HTTP_HOST'];
        //根目录URL，可以指定绝对路径，比如 http://www.yoursite.com/attached/
        $url_path = $host . DS . $url_path;
        //图片扩展名
        $ext_arr = array('gif', 'jpg', 'jpeg', 'png', 'bmp');
        //目录名
        $dir_name = empty($_GET['dir']) ? '' : trim($_GET['dir']);
        if (!in_array($dir_name, array('', 'image', 'flash', 'media', 'file'))) {
            echo "Invalid Directory name.";
            exit;
        }
        $root_path .= $dir_name . DS;
        $url_path .= $dir_name . DS;
        if (!file_exists($root_path)) {
            mkdir($root_path);
        }
        //根据path参数，设置各路径和URL
        if (empty($_GET['path'])) {
            $current_path = realpath($root_path) . DS;
            $current_url = $url_path;
            $current_dir_path = '';
            $moveup_dir_path = '';
        } else {
            $current_path = realpath($root_path) . DS . $_GET ['path'];
            $current_url = $url_path . $_GET['path'];
            $current_dir_path = $_GET['path'];
            $moveup_dir_path = preg_replace('/(.*?)[^\/]+\/$/', '$1', $current_dir_path);
        }
        //排序形式，name or size or type
        $order = empty($_GET['order']) ? 'name' : strtolower($_GET['order']);
        //不允许使用..移动到上一级目录
        if (preg_match('/\.\./', $current_path)) {
            echo 'Access is not allowed.';
            exit;
        }
        //最后一个字符不是/
        if (!preg_match('/\/$/', $current_path)) {
            echo 'Parameter is not valid.';
            exit;
        }
//     目录不存在或不是目录
        if (!file_exists($current_path) || !is_dir($current_path)) {
            echo 'Directory does not exist.';
            exit;
        }
        //遍历目录取得文件信息
        $file_list = array();
        if ($handle = opendir($current_path)) {
            $i = 0;
            while (false !== ($filename = readdir($handle))) {
                if ($filename{0} == '.')
                    continue;
                $file = $current_path . $filename;
                if (is_dir($file)) {
                    $file_list[$i]['is_dir'] = true; //是否文件夹
                    $file_list[$i]['has_file'] = (count(scandir($file)) > 2); //文件夹是否包含文件

                    $file_list[$i]['filesize'] = 0; //文件大小
                    $file_list[$i]['is_photo'] = false; //是否图片
                    $file_list[$i]['filetype'] = ''; //文件类别，用扩展名判断
                } else {
                    $file_list[$i]['is_dir'] = false;
                    $file_list[$i]['has_file'] = false;
                    $file_list[$i]['filesize'] = filesize($file);
                    $file_list[$i]['dir_path'] = '';
                    $file_ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    $file_list[$i]['is_photo'] = in_array($file_ext, $ext_arr);
                    $file_list[$i]['filetype'] = $file_ext;
                }
                $file_list[$i]['filename'] = $filename; //文件名，包含扩展名
                $file_list[$i]['datetime'] = date('Y-m-d H:i:s', filemtime($file)); //文件最后修改时间
                $i++;
            }
            closedir($handle);
        }
        usort($file_list, array($this, 'cmp_func'));
        $result = array();
        //相对于根目录的上一级目录
        $result['moveup_dir_path'] = $moveup_dir_path;
        //相对于根目录的当前目录
        $result['current_dir_path'] = $current_dir_path;
        //当前目录的URL
        $result['current_url'] = $current_url;
        //文件数
        $result['total_count'] = count($file_list);
        //文件列表数组
        $result['file_list'] = $file_list;
        header('Content-type: text/html; charset=UTF-8');
        exit(json_encode($result));
    }

    //排序
    public function cmp_func($a, $b)
    {
        global $order;
        if ($a['is_dir'] && !$b['is_dir']) {
            return -1;
        } else if (!$a['is_dir'] && $b['is_dir']) {
            return 1;
        } else {
            if ($order == 'size') {
                if ($a['filesize'] > $b['filesize']) {
                    return 1;
                } else if ($a['filesize'] < $b['filesize']) {
                    return -1;
                } else {
                    return 0;
                }
            } else if ($order == 'type') {
                return strcmp($a['filetype'], $b['filetype']);
            } else {
                return strcmp($a['filename'], $b['filename']);
            }
        }
    }


}