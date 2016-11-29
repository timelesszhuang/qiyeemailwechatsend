<?php
/**
 * Created by PhpStorm.
 * User: timeless
 * Date: 16-11-29
 * Time: 上午11:19
 */

namespace app\index\controller;


use think\Controller;

class Index extends Controller
{

    public function index()
    {
        return $this->fetch('index');
    }
}