/**
 * 公共使用的文件
 */

/**
 * @returns
 */
function changeTheme(themeName) {
    var $theme = $('#theme');
    var url = $theme.attr('href');
    var href = url.substring(0, url.indexOf('css')) + 'css/bootstrap.min.' + themeName + '.css';
    $theme.attr('href', href);
    $.cookie('theme', themeName, {expires: 7});
}


/**
 * 分成两栏的 左边是 链接列表  右边是 显示位置的  ajax 获取数据
 */
function show_byleftlink(id, data) {
    var show_id = 'leftlink_' + id + '_show_div';
    var selecter = 'leftlink_' + id;
    $('#' + selecter + ' a').click(function () {
        var type = $(this).attr('type');
        var url = $(this).attr('r_href');
        $(this).siblings('a').removeClass('list-group-item-success');
        $(this).addClass('list-group-item-success');
        switch (type) {
            case 'dialog':
                //这个地方有问题   打开模态框 参数不足  有一些地方是
                var modal_id = $(this).attr('modal_id');
                open_modal(modal_id, url);
                break;
            case 'page':
                if (url) {
                    get_html_byajax(url, show_id, data);
                }
                break;
            case 'redirect_newwindow':
                //暂时没有使用到
                break;
        }
    });
    //默认选中的模拟点击打开页面
    $('#' + selecter + ' .list-group-item-success').trigger('click');
}


/**
 *   打开模态窗体  操作完成之后没有更新之类的操作
 *   @param {string} pre_modal_id 要打开的modal_id
 *   @param {string} link 链接
 *   @param {int} id 形参的形式
 *   @param {string} 获取到的数据展现到的div show_container_id
 */
function open_modal(pre_modal_id, link) {
    var modal_id = pre_modal_id + '_modal';
    var id = arguments[2] ? arguments[2] : '';
    var datagrid_id = arguments[3] ? arguments[3] : '';
    var show_container_id = arguments[4] ? arguments[4] : '';
    //ajax 获取的页面之后
    var modal_content_id = pre_modal_id + '_content';
    $('#' + modal_id).modal('show');
    //把modal_id存储处在页面的隐藏字段中 以后
    var data = new Array();
    data['modal_id'] = modal_id;
    if (id !== '') {
        data['id'] = id;
    }
    data['datagrid_id'] = datagrid_id;
    var datastring = form_ajax_data_byarray(data);
    var status = false;
    if (is_null_or_empty(show_container_id)) {
        status = get_html_byajax(link, modal_content_id, datastring);
    } else {
        status = get_html_byajax(link, show_container_id, datastring);
    }
    if (status) {
        //这个地方解决 kindeditor 弹出层输入框不能输入框体
        $('#' + modal_id).on('shown.bs.modal', function () {
            $(document).off('focusin.modal');
        });
//        $('#' + modal_id).modal('show');
    } else {
        alert('打开模态框失败。');
        $('#' + modal_id).modal('hide');
    }
}


/**
 *执行ajax 操作 获取 html
 *@param {string}  href  获取html 的链接
 *@param {string}  show_div_id获取道德html 展现到的地方
 *@param {array}   data 要传递的数据
 */
function get_html_byajax(href, show_div_id, data) {
    var status = false;
    var selecter = '#' + show_div_id;
    $.ajax({
        type: "POST",
        dataType: "html",
        //因为如果是异步执行的话  没有返回就执行return 了 但是同步的话还是会有问题的 比如长时间没有相应的话会阻塞
        async: false,
        url: href,
        data: data,
        beforeSend: function (xhr) {
            var img = "<div style='width:50%;margin:0px auto;'><img src='/static/sys/image/load.gif' /></div>"
            $(selecter).html(img);
        },
        success: function (data) {
            $(selecter).html(data);
            status = true;
        },
        error: function (jqXHR, textStatus, errorThrown) {
            parse_ajax_errortype(jqXHR, textStatus, errorThrown);
        }
    });
    return status;
}

/**
 * 解析ajax error  信息
 */
function parse_ajax_errortype(jqXHR, textStatus, errorThrown) {
    alert(jqXHR.status);
}

/**
 * 获取浏览器可见窗体的宽度信息
 */
function get_browser_widthheightinfo() {
    var height = $(window).height();
    var width = $(window).width();
    var info = {"width": width, "height": height};
    return info;
}

/**
 * 打开窗体 操作完成之后更新 datagrid
 * @param {string} datagrid_id 要刷新的datagrid
 * @param {string} link 获取modal 框体内的内容
 * @param {string} pre_modal_id  标志  获取 1 要弹出的 modal_id  2 要存放数据的
 */
function add_record_modal(datagrid_id, link, pre_modal_id) {
    var modal_id = pre_modal_id + '_modal';
    var modal_content_id = pre_modal_id + '_content';
    //要把datagrid 数据存储处在页面的隐藏字段中
    var data = new Array();
    data['datagrid_id'] = datagrid_id;
    data['modal_id'] = modal_id;
    var datastring = form_ajax_data_byarray(data);
    var status = get_html_byajax(link, modal_content_id, datastring);
    if (status) {
        //这个地方解决 kindeditor 弹出层输入框不能输入框体
        $('#' + modal_id).on('shown.bs.modal', function () {
            $(document).off('focusin.modal');
        });
        $('#' + modal_id).modal('show');
    } else {
        alert('打开模态框失败。');
    }
}


/**
 * 删除单条记录
 */
function delete_single_record(id, link, datagrid_id) {
    $.messager.confirm("操作提示", "您确定要执行删除操作吗？", function (data) {
        if (data) {
            delete_record(id, link, datagrid_id);
        } else {
            return;
        }
    })
}
/**
 * 删除记录
 * @param {int} ids 要删除的数值  数组的形式或者是单个的id
 * @param {string} link 要执行删除操作的链接地址
 * @param {string} datagr_id 选择要删除的datagrid
 */
function delete_record(ids, link, datagrid_id) {
    if (ids == '') {
        $.messager.alert('信息提示', '请选择要操作的项', 'error');
        return false;
    }
    $.ajax({
        url: link,
        type: 'post',
        data: {
            id: ids,
        },
        dataType: 'json',
        success: function (data) {
            exec_complete(data, datagrid_id);
        }
    });
}

/**
 * 取消或者开启邮件推送
 * @param {int} id 要删除的数值  数组的形式或者是单个的id
 * @param {string} link 要执行删除操作的链接地址
 * @param {string} datagr_id 选择要删除的datagrid
 */
function cancelorok_sendmail(id, link, datagrid_id, status) {
    if (is_null_or_empty(id)) {
        $.messager.alert('信息提示', '请选择要操作的项', 'error');
        return false;
    }
    $.messager.confirm("操作提示", "您确定要执行该操作吗？", function (data) {
        if (data) {
            $.ajax({
                url: link,
                type: 'post',
                data: {
                    id: id,
                    status: status
                },
                dataType: 'json',
                success: function (data) {
                    exec_complete(data, datagrid_id);
                }
            });
        } else {
            return;
        }
    })

}

/**
 * 编辑记录信息根据id
 * @param {int} id 要编辑的记录的id
 * @param {string} link 要编辑的菜单的地址
 * @param {string} datagrid_id 编辑完成之后要刷新的datagrid
 * @param {string} pre_modal_id 编辑modal 要显示的位置
 */
function edit_record_modal(record_id, link, datagrid_id, pre_modal_id) {
    var modal_id = pre_modal_id + '_modal';
    var modal_content_id = pre_modal_id + '_content';
    $('#' + modal_id).modal('show');
    //要把datagrid 数据存储处在页面的隐藏字段中
    var data = new Array();
    data["datagrid_id"] = datagrid_id;
    data["modal_id"] = modal_id;
    data["id"] = record_id;
    var datastring = form_ajax_data_byarray(data);
    var status = get_html_byajax(link, modal_content_id, datastring);
    if (status) {
        //这个地方解决 kindeditor 弹出层输入框不能输入框体
        $('#' + modal_id).on('shown.bs.modal', function () {
            $(document).off('focusin.modal');
        });
//        $('#' + modal_id).modal('show');
    } else {
        alert('打开模态框失败。');
    }
}

/**
 * ajax 更新操作完成之后操作
 * @param {string} data 完成请求之后返回的信息
 * @param {string} datagrid_id  执行操作
 */
function exec_complete(data, datagrid_id) {
    if (data.status == 'success') {
        if (datagrid_id) {
            var flag = datagrid_id.indexOf('treegrid');
            if (flag !== -1) {
                $('#' + datagrid_id).treegrid('reload');
            } else {
                $('#' + datagrid_id).datagrid('reload');
            }
        }
        $.messager.show({
            title: data.title,
            msg: data.msg,
            timeout: 1000,
            showType: 'slide'
        });
    } else if (data.status == 'failed') {
        $.messager.alert(data.title, data.msg, 'error');
    }
}

/**
 * 打开新的客户的浏览器窗体
 */
function open_window(id, href) {
    //左右居中操作 宽度合适
    var iWidth = window.screen.availWidth - 100;
    var iHeight = window.screen.availHeight - 100;
    var iLeft = (window.screen.availWidth - 10 - iWidth) / 2;           //获得窗口的水平位置;
    var location = href + '?id=' + id;
    var win_name = 'cus_' + id;
    var win = window.open(location, win_name, 'height=' + iHeight + ',width=' + iWidth + ',top=0,left=' + iLeft + ', toolbar = no, menubar = no, scrollbars = yes, resizable = no, location = no, status = no');
    if (!win || (win.closed || !win.focus) || typeof (win.document) == 'unknown' || typeof (win.document) == 'undefined') {
        alert('您的请求被拦截，请永久允许弹出窗体');
    }
    win.focus();
}


/**
 * 打开地图的浏览器窗体
 */
function open_map_window(id, href) {
    //左右居中操作 宽度合适
    var iWidth = window.screen.availWidth - 100;
    var iHeight = window.screen.availHeight - 100;
    var iLeft = (window.screen.availWidth - 10 - iWidth) / 2;           //获得窗口的水平位置;
    var location = href + '?id=' + id;
    var win_name = 'map_' + id;
    var win = window.open(location, win_name, 'height=' + iHeight + ',width=' + iWidth + ',top=0,left=' + iLeft + ', toolbar = no, menubar = no, scrollbars = yes, resizable = no, location = no, status = no');
    if (!win || (win.closed || !win.focus) || typeof (win.document) == 'unknown' || typeof (win.document) == 'undefined') {
        alert('您的请求被拦截，请永久允许弹出窗体');
    }
    win.focus();
}

/**
 * 打开新的邮箱浏览器窗体
 */
function open_mail_window(location) {
    //左右居中操作 宽度合适
    var iWidth = window.screen.availWidth - 100;
    var iHeight = window.screen.availHeight - 100;
    var iLeft = (window.screen.availWidth - 10 - iWidth) / 2;           //获得窗口的水平位置;
    var win_name = 'mail_entry';
    var win = window.open(location, win_name, 'height=' + iHeight + ',width=' + iWidth + ',top=0,left=' + iLeft + ', toolbar = no, menubar = no, scrollbars = yes, resizable = no, location = no, status = no');
    if (!win || (win.closed || !win.focus) || typeof (win.document) == 'unknown' || typeof (win.document) == 'undefined') {
        alert('您的请求被拦截，请永久允许弹出窗体');
    }
    win.focus();
}


/**
 *ajax 提交表单信息   数据是序列化的表单   或者是组装的数据   提交完成之后会 刷新datagrid treegrid等信息  失败之后会 重新展现页面
 *@param {string} action url提交的地方
 *@param {serialize} data 序列化之后的表单信息
 *@param {string} modal_id 正在操作的表单的modal_id
 *@param {string} datagrid  要刷新的datagrid  这个参数通过 arguments 参数获得
 */
function submit_form(action, data) {
    var modal_id = arguments[2] ? arguments[2] : '';
    var datagrid_id = arguments[3] ? arguments[3] : '';
    var open_cus_window_fag = arguments[4] ? arguments[4] : '';
    var open_window_href = arguments[5] ? arguments[5] : '';
    var cus_id = 0;
    $('#' + modal_id).modal('hide');
    //表单序列化的
    $.ajax({
        type: "POST",
        dataType: "json",
        //因为如果是异步执行的话  没有返回就执行return 了 但是同步的话还是会有问题的 比如长时间没有相应的话会阻塞
        async: false,
        url: action,
        data: data,
        success: function (data) {
            if (data.status == 'success') {
                //应当调用treegrid 还是 datagrid
                if (datagrid_id != '') {
                    var flag = datagrid_id.indexOf('treegrid');
                    if (flag !== -1) {
                        $('#' + datagrid_id).treegrid('reload');
                    } else {
                        $('#' + datagrid_id).datagrid('reload');
                    }
                }
                if (modal_id != '') {
                    $.messager.show({
                        title: data.title,
                        msg: data.msg,
                        timeout: 1000,
                        showType: 'slide'
                    });
                } else {
                    $.messager.alert(data.title, data.msg, 'info');
                }
                cus_id = data.id;
            } else if (data.status == 'failed') {
                if (modal_id != '') {
                    $.messager.alert(data.title, data.msg, 'error', function () {
                        $('#' + modal_id).modal('toggle');
                    });
                } else {
                    $.messager.alert(data.title, data.msg, 'error');
                }
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            parse_ajax_errortype(jqXHR, textStatus, errorThrown);
        }
    });
    //打开客户视图
    if (!is_null_or_empty(cus_id) && open_cus_window_fag) {
        //是不是要打开客户视图窗体
        open_window(cus_id, open_window_href);
    }
}


/**
 * 确认是不是要关闭modal
 */
function confirm_closemodal(data, modal_id) {
    $.messager.confirm(data.title, data.msg, function (status) {
        if (status) {
            $('#' + modal_id).modal('toggle');
        }
    });
}

/**
 *根据关联的数组生成ajax 提交的数据
 *@param {array} data 一维关联数组
 */
function form_ajax_data_byarray(data) {
    var datastring = '';
    for (key in data) {
        val = data[key];
        if (datastring == '') {
            datastring = key + '=' + val;
        } else {
            datastring = datastring + '&' + key + '=' + val;
        }
    }
    return datastring;
}

/**
 * 某一个框体在加载数据的时候显示load。。。。
 * @param {string} show_div_id 要展示在的div 的id
 */
function load_info_gif(show_div_id) {
    var img = "/static/sys/image/load.gif";
    $('#' + show_div_id).html('<img src="' + img + '" tag="加载中">');
}


/**
 *验证是否为空
 *@return boolean 空或者没有设置
 *@param {boolean}  是否为空
 */
function is_null_or_empty(val) {
    if (val === null || val === undefined || val === '' || val === 0 || val === '0' || val === '0.00') {
        return true;
    } else {
        return false;
    }
}


