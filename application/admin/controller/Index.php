<?php
namespace app\admin\controller;


use think\Session;

class Index extends Base
{

    public function _initialize()
    {
        //测试数据
/*      Session::set('api_status', '10');
        Session::set('corp_id', '2');
        Session::set('corpid', 'wxe041af5a55ce7365');
        Session::set('product', 'qiangbi_net');
        Session::set('privatesecret', '-----BEGIN RSA PRIVATE KEY----- MIICXgIBAAKBgQDImsqx5wgohQqq2p+AAxNR1s5QfkoO9LJ+nTRBa4lqg8oPkbuN YgLSD3Y0wVF1i0vffaLuVz6Jlmr4dQAGI0CuYSMmuDyaMId5cyoHeqCACRA/uQev iT9XYO2n6z7l8h61dDEOx028vc+vcwVkghmAqMKhNzmEJX/CfqclVQXahwIDAQAB AoGAB9OqRuips9MFCIeBI6B7F31XDWLwBsdbU39Us5y7ftFnh9X6yFhjnciGpyZH xFtL+YtQWRZEVV/uCoWeG58yfcmDo8NnDfNZZVpXDKdvVoW1JDGdIX6rhHmRUiso qA6m1N7GtU/lErz19dBb8Dj+GZh116fPw5u2HOMsu5bOKmkCQQD2W1nwOYmiz2k9 01h19ZXjIH5hiax7UurQW3wUXe4VQb6FH8qEBRhO+CyoWleZq+x1czoxGKrW+P08 sYLMkU6LAkEA0HT5oS9H+mlmotOhdDL6dnbsaq6od9ivvCR4FKsu/x1Jq4fL9iej 7dIgi2s3qjkO/QCy2O7yyf/An5tz7LJ/dQJBAIAJeFPmw4bPf2X3irk72xvBTo3I 7NDnhkylz3YSX2PC2I79t9Yng7u/Ng6FbZPbi7h7G5patKenno3FwDIrrwMCQQCX wpF6J1HfnJx8LlZ8oiB13l5/zGgZ2EcYUfSaF4Y/dLMNje+PZYySt0e6OHRuGNww lTGffVaEeQ1jJWlgCROBAkEA9gCeU1HUkQcRZCtMh6uC8J/dv0FFMaxWJnz3yLO/ dHz9XDwjTJxyC6AN3VhD+FU4hRMyaAKjUCreNDcZLza1KA== -----END RSA PRIVATE KEY-----');
        Session::set('domain', 'cio.club');
        Session::set('corp_name', '山东强比信息技术');*/
        parent::_initialize();
    }


    /**
     * 条竹跳转到首页
     * @access public
     */
    public function index()
    {
        return $this->fetch('index', ['msg' => '登录成功。']);
    }


}
