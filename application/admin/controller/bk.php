


   



    /**
     * 审核通过验证 微信 账号
     * @access public
     */
    public function check_wechatmail() {
        $id = I('post.id');
        if (M('WechatUser')->where(['id' => ['eq', $id]])->save(['status' => '10', 'checktime' => time()])) {
            get_mem_obj()->flush();
            format_json_ajaxreturn('用户审核成功', '用户审核成功', 'success');
        }
        format_json_ajaxreturn('用户审核失败', '用户审核失败', 'failed');
    }

    /**
     * 否决审核 通过验证 微信 账号
     * @access public
     */
    public function notcheck_wechatmail() {
        $id = I('post.id');
        if (M('WechatUser')->where(['id' => ['eq', $id]])->save(['status' => '30', 'checktime' => time()])) {
            get_mem_obj()->flush();
            format_json_ajaxreturn('用户否决成功', '用户否决成功', 'success');
        }
        format_json_ajaxreturn('用户否决失败', '用户否决失败', 'failed');
    }



    /**
     * 执行修改绑定用户操作
     * @access public
     */
    public function exec_add_wechatmail() {
        $data['wechat_user_id'] = $wechat_user_id = I('post.wechat_user_id');
        $data['check_name'] = I('post.check_name');
        $data['check_email'] = I('post.check_email');
        $data['status'] = '20';
        $data['addtime'] = time();
        if (!$data['wechat_user_id']) {
            format_json_ajaxreturn('请填写微信ID', '添加账号绑定失败', 'failed');
        }
        //还需要验证是不是该帐号已经存在
        if (!$data['check_name']) {
            format_json_ajaxreturn('请填写姓名', '添加账号绑定失败', 'failed');
        }
        if (!$this->check_email($data['check_email'])) {
            format_json_ajaxreturn('邮箱格式不正确，请修改。', '添加账号绑定', 'failed');
        } else {
            $domain = '@' . C('MAILDOMAIN');
            if (substr($data['check_email'], strpos($data['check_email'], '@')) != $domain) {
                format_json_ajaxreturn("邮箱后缀不正确，应该为$domain", '添加账号绑定', 'failed');
            }
        }
        if (M('WechatUser')->where(["wechat_user_id" => ['eq', $wechat_user_id]])->find()) {
            format_json_ajaxreturn("该微信绑定信息已经添加过。", "添加账号绑定", 'failed');
        }
        //还需要从微信的后台获取 name 邮箱地址 还有 手机号
        $data['account'] = substr($data['check_email'], 0, strpos($data['check_email'], '@'));
        list($name, $mobile, $email) = R('Wechat/Index/get_wechat_info', [$wechat_user_id]);
        if (!$name) {
            format_json_ajaxreturn("该微信ID不存在，请到微信后台添加该职员账号。", "添加账号绑定", 'failed');
        }
        $data['name'] = $name;
        $data['mobile'] = $mobile? : '';
        $data['email'] = $email? : '';
        if (M('WechatUser')->add($data)) {
            get_mem_obj()->flush();
            format_json_ajaxreturn("账号绑定添加成功，请审核。", '添加账号绑定', 'success');
        }
        format_json_ajaxreturn("账号绑定失败，请重试。", '添加账号绑定', 'failed');
    }

    /**
     * 更新微信 邮箱的绑定信息
     * @access public
     */
    public function edit_wechatmail() {
        $this->getassign_common_data();
        $this->assign('r', M('WechatUser')->where(['id' => ['eq', I('post.id')]])->find());
        $this->display();
    }

    /**
     * 执行修改绑定用户操作
     * @access public
     */
    public function exec_edit_wechatmail() {
        $data['id'] = I('post.id');
        $data['check_name'] = I('post.check_name');
        $data['check_email'] = I('post.check_email');
        $data['status'] = '20';
        $data['addtime'] = time();
        if (!$data['id']) {
            format_json_ajaxreturn('修改信息失败请重试。', '修改信息失败', 'failed');
        }

        //还需要验证是不是该帐号已经存在
        if (!$data['check_name']) {
            format_json_ajaxreturn('请填写姓名', '修改信息失败', 'failed');
        }
        if (!$this->check_email($data['check_email'])) {
            format_json_ajaxreturn('邮箱格式不正确，请修改。', '修改信息失败', 'failed');
        } else {
            $domain = '@' . C('MAILDOMAIN');
            if (substr($data['check_email'], strpos($data['check_email'], '@')) != $domain) {
                format_json_ajaxreturn("邮箱后缀不正确，应该为$domain", '修改信息失败', 'failed');
            }
        }
        $data['account'] = substr($data['check_email'], 0, strpos($data['check_email'], '@'));
        if (M('WechatUser')->save($data)) {
            get_mem_obj()->flush();
            format_json_ajaxreturn("修改成功，请重新审核。", '修改信息失败', 'success');
        }
        format_json_ajaxreturn("信息修改失败，请重试。", '修改信息失败', 'failed');
    }

    /**
     * 验证邮箱账号是不是正确
     * @access public
     */
    public function check_email($email) {
        if (ereg('^([a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-])+', $email)) {
            return true;
        } else {
            return false;
        }
    }