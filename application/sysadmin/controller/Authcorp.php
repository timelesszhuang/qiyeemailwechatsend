<?php
/**
 * Created by PhpStorm.
 * User: timeless
 * Date: 16-11-3
 * Time: 下午1:59
 */

namespace app\sysadmin\controller;


use app\common\model\common;
use think\Db;

class Authcorp extends Base
{

    /**
     * 首页信息
     * @access public
     */
    public function index()
    {
        return $this->fetch();
    }


    /**
     * 首页基本信息更新
     * @access public
     */
    public function index_json()
    {

        //分页信息获取
        list($firstRow, $pageRows) = common::get_page_info();
        $db = Db::name('auth_corp_info');
        $where = [];
        $count = $db->where($where)->count('id');
        $info = $db->where($where)->limit($firstRow, $pageRows)->field('id,corp_id,corp_name,corp_type,corp_agent_max,corp_full_name,subject_type,addtime')->select();
        array_walk($data, array($this, 'formatter_corp_info'), $info);
        if ($count != 0) {
            $array['total'] = $count;
            $array['rows'] = $data;
            echo json_encode($array);
        } else {
            $array['total'] = 0;
            $array['rows'] = array();
            echo json_encode($array);
        }

    }

}