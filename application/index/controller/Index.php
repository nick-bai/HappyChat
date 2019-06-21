<?php
namespace app\index\controller;

use think\Controller;

class Index extends Controller
{
    public function index()
    {
        // 加入信号量


        var_dump($signal);die;

        return $this->fetch();
    }
}
