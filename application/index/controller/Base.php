<?php
/**
 * Created by PhpStorm.
 * User: NickBai
 * Email: 876337011@qq.com
 * Date: 2019/6/22
 * Time: 8:18 AM
 */
namespace app\index\controller;

use think\Controller;

class Base extends Controller
{
    public function initialize()
    {
        if (empty(cookie('token'))) {
            $this->redirect(url('login/index'));
        }
    }
}