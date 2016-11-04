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
use think\Request;

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
        $info = $db->where($where)->limit($firstRow, $pageRows)
            ->field('id,corpid,corp_type,corp_agent_max,corp_full_name,subject_type,agent_count,agent_serialize,addtime')
            ->select();
        $auth_model = new \app\sysadmin\model\authcorp();
        array_walk($info, array($auth_model, 'formatter_corp_info'));
        if ($count != 0) {
            $array['total'] = $count;
            $array['rows'] = $info;
            echo json_encode($array);
        } else {
            $array['total'] = 0;
            $array['rows'] = array();
            echo json_encode($array);
        }
    }

    /**
     * 添加或者修改网易邮箱接口API
     * @access public
     */
    public function editadd_netease_apiinfo()
    {
        //authcorp表的id
        $id = Request::instance()->param('id');
        //获取corp_id
        $corp_id = Db::name('auth_corp_info')->where('id', $id)->find()['corp_id'];
    }

}