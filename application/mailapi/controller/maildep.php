<?php

namespace app\mailapi\controller;

/**
 * 职员部门设置
 * @todo 部门相关信息可以添加到数据库中
 */
class maildep
{

    /**
     * 邮件相关操作 入口文件
     * @access public
     */
    public function mail_index()
    {
        $this->display();
    }

    /**
     * index 部门的list 展现  json数据返回
     * @access public
     * @return json 数据   公司组织架构 list
     */
    public function index_json()
    {
        $m = M('MailOrgstructure');
        $list = $m->field('unit_id as id,parent_id,unit_name as text,unit_desc as detail')->order('sort desc')->select();
        $navcatCount = $m->count();
        $a = array();
        foreach ($list as $k => $v) {
            $a[$k] = $v;
            $a[$k]['_parentId'] = intval($v['parent_id']); //_parentId为easyui中标识父id
        }
        $array = array();
        $array['total'] = $navcatCount;
        $array['rows'] = $a;
        echo json_encode($array);
    }

    /**
     * 刷新 部门信息
     * @access public
     */
    public function refresh_dep()
    {
        $this->getassign_common_data();
        $this->display();
    }

    /**
     * 部门操作首页
     * @access public
     */
    public function index()
    {
        $this->display();
    }

    /**
     * 获取所有部门信息然后更新部门信息 可能会有删除  会有添加  会有更新
     */
    public function exec_update_alldep()
    {
        //私钥
        $prikey = C('MAILPRIKEY');
        //域名
        $domain = C('MAILDOMAIN');
        $product = C('PRODUCT');
        $time = date(time()) . '000';
        $res = openssl_pkey_get_private($prikey);
        //必须使用post方法
        $src = "domain=" . $domain . "&product=" . $product . "&time=" . $time;
        if (openssl_sign($src, $out, $res)) {
            $sign = bin2hex($out);
            $url = "https://apibj.qiye.163.com/qiyeservice/api/unit/getUnitList";
            $response_json = $this->exec_postresponse($url, $src . '&sign=' . $sign);
            if ($response_json['suc'] == '1') {
                $this->formatupdate_emaildep($response_json['con']);
            }
            //失败  返回详细信息
            exit(json_encode(array(
                'msg' => '部门信息更新失败' . $response_json['error_code'],
                'title' => '更新部门信息失败',
                'status' => self::error
            )));
        }
        //失败返回状态
        exit(json_encode(array(
            'msg' => '部门信息更新失败,参数异常，请联系管理员。',
            'title' => '更新部门信息失败',
            'status' => self::error
        )));
    }

    /**
     * 更新公司邮箱信息
     * @access public
     * [con] => Array
     * (
     * [0] => Array
     * (
     * [unit_id] => 625726
     * [unit_name] => 经理办公室
     * )
     * [1] => Array
     * (
     * [parent_id] => 236019
     * [unit_desc] => 负责公司产品销售
     * [unit_id] => 236012
     * [unit_name] => 济南顾问一部
     * )
     * [2] => Array
     * (
     * [parent_id] => 236019
     * [unit_id] => 625755
     * [unit_name] => 济南顾问二部
     * )
     * [3] => Array
     * (
     * [parent_id] => 236019
     * [unit_id] => 625757
     * [unit_name] => 客服部
     * )
     * [4] => Array
     * (
     * [parent_id] => 236019
     * [unit_id] => 659033
     * [unit_name] => 郑州顾问一部
     * )
     * [5] => Array
     * (
     * [parent_id] => 625731
     * [unit_id] => 659037
     * [unit_name] => 财务部
     * )
     * [6] => Array
     * (
     * [unit_id] => 236019
     * [unit_name] => 企业沟通事业部
     * )
     * [7] => Array
     * (
     * [parent_id] => 625731
     * [unit_id] => 659038
     * [unit_name] => 人力资源部
     * )
     * [8] => Array
     * (
     * [unit_id] => 625754
     * [unit_name] => 市场部
     * )
     * [9] => Array
     * (
     * [unit_id] => 625736
     * [unit_name] => 新品事业部
     * )
     * [10] => Array
     * (
     * [unit_id] => 625730
     * [unit_name] => 技术服务部
     * )
     * [11] => Array
     * (
     * [unit_id] => 625731
     * [unit_name] => 行政部
     * )
     * )
     */
    public function formatupdate_emaildep($dep_arr)
    {
        //没有父亲部门的 默认为 0
        //首先把之前的数据清空
        $m = M('MailOrgstructure');
        $m->starttrans();
        $m->where(array('unit_id>=0'))->delete();
        $dep_data = array();
        foreach ($dep_arr as $k => $v) {
            $perdata = array(
                'unit_id' => $v['unit_id'],
                'unit_name' => $v['unit_name'],
                'parent_id' => $v['parent_id'] ? $v['parent_id'] : 0,
                'unit_desc' => $v['unit_desc'],
                'updatetime' => time()
            );
            $dep_data[] = $perdata;
        }
        $add_status = $m->addAll($dep_data);
        if ($add_status) {
            $m->commit();
            exit(json_encode(array(
                'msg' => '部门信息更新成功,请返回刷新。',
                'title' => '更新部门信息成功',
                'status' => 'success'
            )));
        } else {
            $m->rollback();
            exit(json_encode(array(
                'msg' => '部门信息更新失败',
                'title' => '更新部门信息失败',
                'status' => self::error
            )));
        }
    }

    /**
     * 更新指定部门id下的数据
     * 没有什么实际作用   选择部门更新信息
     * @access public
     */
    public function exec_update_alldep_tree()
    {
        //私钥
        $prikey = C('MAILPRIKEY');
        //域名
        $domain = C('MAILDOMAIN');
        $product = C('PRODUCT');
        $time = date(time()) . '000';
        $res = openssl_pkey_get_private($prikey);
        //需要逐条获取部门信息
        //必须使用post方法
        $src = "domain=" . $domain . "&product=" . $product . "&time=" . $time . "&unit_id=236019";
        if (openssl_sign($src, $out, $res)) {
            $sign = bin2hex($out);
            $url = "https://apibj.qiye.163.com/qiyeservice/api/unit/getUnit";
            $response_json = $this->exec_postresponse($url, $src . '&sign=' . $sign);
            print_r($response_json);
            //失败  返回详细信息
        }
        //失败返回状态
    }

    /**
     * 添加部门数据
     * @access public
     */
    public function add_dep()
    {
        $this->getassign_common_data();
        $this->display();
    }

    /**
     * 执行添加部门操作
     */
    public function exec_add_dep()
    {
        $unit_name = I('post.unit_name');
        $parent_id = I('post.parent_id');
        $unit_desc = I('post.unit_name');
        if (!$unit_name) {
            format_json_ajaxreturn('请填写部门名。', '添加状态', self::error);
        }//私钥
        $prikey = C('MAILPRIKEY');
        //域名
        $domain = C('MAILDOMAIN');
        $product = C('PRODUCT');
        $time = date(time()) . '000';
        $res = openssl_pkey_get_private($prikey);
        //必须使用post方法
        //不包含上级部门
        //https://apibj.qiye.163.com/qiyeservice/api/unit/createUnit?
        //domain=abc.com&
        //product=abc_com&
        //unit_desc=
        //unit_name=
        //sign= domain=abc.com&product=abc_com&time=1418378176267&unit_desc=测试部门&unit_name=测试部门
        //包含上级部门 创建子部门
        //https://apibj.qiye.163.com/qiyeservice/api/unit/createUnit?
        //domain=abc.com&
        //parent_id=335017&
        //product=abc_com&
        //time=1418378704477&
        //unit_desc=
        //unit_name=
        //sign=domain=abc.com&parent_id=335017&product=abc_com&time=1418378704477&unit_desc=子部门&unit_name=子部门
        if ($parent_id) {
            $src = "domain=" . $domain . "&parent_id=" . $parent_id . "&product=" . $product . "&time=" . $time . "&unit_desc=" . $unit_desc . "&unit_name=" . $unit_name;
        } else {
            $src = "domain=" . $domain . "&product=" . $product . "&time=" . $time . "&unit_desc=" . $unit_desc . "&unit_name=" . $unit_name;
        }
        $m = M('MailOrgstructure');
        if (openssl_sign($src, $out, $res)) {
            $sign = bin2hex($out);
            $url = "https://apibj.qiye.163.com/qiyeservice/api/unit/createUnit";
            $response_json = $this->exec_postresponse($url, $src . '&sign=' . $sign);
            //失败  返回详细信息
            if (!$response_json['suc']) {
                //表示错误
                format_json_ajaxreturn('部门添加失败,错误代码：' . $response_json['error_code'], '添加状态', self::error);
            }
            //表示添加成功
            //然后添加数据到数据库
            $unit_info = $response_json['con'];
            if ($m->add(array('unit_id' => $unit_info['unit_id'],
                'unit_name' => $unit_info['unit_name'],
                'parent_id' => $unit_info['parent_id'] ? $unit_info['parent_id'] : 0,
                'unit_desc' => $unit_info['unit_desc'],
                'updatetime' => time()))
            ) {
                format_json_ajaxreturn('部门添加成功', '添加状态', 'success');
            }
            format_json_ajaxreturn('远程添加部门成功，本地添加失败，请更新全部部门同步数据。', '添加状态', 'success');
        }
    }

    /**
     * 账号编辑相关操作
     * @access public
     */
    public function edit_dep()
    {
        $m = M('MailOrgstructure');
        $this->assign('record', $m->where(array('unit_id' => I('post.id')))->find());
        $this->getassign_common_data();
        $this->display();
    }

    /**
     * 执行修改部门数据  不能更新部门的所属上级部门
     * @access public
     */
    public function exec_edit_dep()
    {
        $unit_id = I('post.unit_id');
        $unit_name = I('post.unit_name');
        $unit_desc = I('post.unit_desc');
        if (!$unit_id || !$unit_name) {
            format_json_ajaxreturn('请填写部门名', '添加状态', self::error);
        }
        //https://apibj.qiye.163.com/qiyeservice/api/unit/updateUnit?
        //domain=abc.com&
        //product=abc_com&
        //time=1418378451504&
        //unit_desc= &
        //unit_id=335017&
        //unit_name=  &
        //sign=domain=abc.com&product=abc_com&time=1418378451504&unit_desc=正式部门&unit_id=335017&unit_name=正式部门
        $prikey = C('MAILPRIKEY');
        //域名
        $domain = C('MAILDOMAIN');
        $product = C('PRODUCT');
        $time = date(time()) . '000';
        $res = openssl_pkey_get_private($prikey);
        $src = "domain=" . $domain . "&product=" . $product . "&time=" . $time . "&unit_desc=" . $unit_desc . "&unit_id=" . $unit_id . "&unit_name=" . $unit_name;
        $m = M('MailOrgstructure');
        if (openssl_sign($src, $out, $res)) {
            $sign = bin2hex($out);
            $url = "https://apibj.qiye.163.com/qiyeservice/api/unit/updateUnit";
            $response_json = $this->exec_postresponse($url, $src . '&sign=' . $sign);
            //失败  返回详细信息
            if (!$response_json['suc']) {
                //表示错误
                format_json_ajaxreturn('部门更新失败,错误代码：' . $response_json['error_code'], '更新状态', self::error);
            }
            //表示添加成功
            //然后添加数据到数据库
            if ($m->save(array('unit_id' => $unit_id,
                'unit_name' => $unit_name,
                'unit_desc' => $unit_desc,
                'updatetime' => time()))
            ) {
                format_json_ajaxreturn('部门更新成功', '更新状态', 'success');
            }
            format_json_ajaxreturn('远程更新部门成功，本地更新失败，请更新全部部门同步数据。', '更新状态', 'success');
        }
    }

    /**
     * 删除部门
     * @access public
     */
    public function delete_dep()
    {
        $id = I('post.id');
        $prikey = C('MAILPRIKEY');
        //域名
        $domain = C('MAILDOMAIN');
        $product = C('PRODUCT');
        $time = date(time()) . '000';
        $res = openssl_pkey_get_private($prikey);
        //https://apibj.qiye.163.com/qiyeservice/api/unit/deleteUnit?
        //domain=abc.com&
        //product=abc_com&
        //time=1418381515027&
        //unit_id=335018&
        //sign=domain=abc.com&product=abc_com&time=1418381515027&unit_id=335018
        $src = "domain=" . $domain . "&product=" . $product . "&time=" . $time . "&unit_id=" . $id;
        $m = M('MailOrgstructure');
        if (openssl_sign($src, $out, $res)) {
            $sign = bin2hex($out);
            $url = "https://apibj.qiye.163.com/qiyeservice/api/unit/deleteUnit";
            $response_json = $this->exec_postresponse($url, $src . '&sign=' . $sign);
            //失败  返回详细信息
            if (!$response_json['suc']) {
                //表示错误
                format_json_ajaxreturn('部门删除失败,错误代码：' . $response_json['error_code'], '删除状态', self::error);
            }
            //表示添加成功
            //然后添加数据到数据库
            if ($m->where(array('unit_id' => $id))->delete()) {
                format_json_ajaxreturn('部门删除成功', '删除状态', 'success');
            }
            format_json_ajaxreturn('远程删除部门成功，本地删除失败，请更新全部部门同步数据。', '删除状态', 'success');
        }
    }

    /**
     * 部门信息tree
     * @access public
     */
    public function json_maildep_tree()
    {
        $qiuyun = new \Org\Util\Qiuyun;
        //表示获取全部部门下子孙部门  还有全部职员数据
        $m = M('MailOrgstructure');
        $org_data = $m->field('unit_id as id,parent_id,unit_name as text')->order('sort desc')->select();
        $parent_id = 0;
        $tree = $qiuyun->list_to_tree($org_data, 'id', 'parent_id', 'children', $parent_id);
        exit(json_encode($tree));
    }

}
