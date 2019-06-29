<?php
/**
 * Created by PhpStorm.
 * User: NickBai
 * Email: 876337011@qq.com
 * Date: 2019/6/22
 * Time: 8:35 AM
 */
namespace app\index\controller;

use Lcobucci\JWT\Builder;
use think\Controller;

class Login extends Controller
{
    public function index()
    {
        return $this->fetch();
    }

    public function doLogin()
    {
        if (request()->isPost()) {

            $param = input('post.');

            if (empty($param['account'])) {
                return json(['code' => -1, 'data' => '', 'msg' => '请输入昵称']);
            }

            $uid = uniqid();
            $avatar = '/static/images/avatar/' . mt_rand(1, 14) . '.png';
            $time = time();
            $token = (new Builder())->setIssuer('http://baiyf.com')
                ->setAudience('http://chat.baiyf.com')
                ->setId(uniqid(), true)
                ->setIssuedAt($time)
                ->setNotBefore($time)
                ->setExpiration($time + 86400 * 7) // 7天有效期
                ->set('uid', $uid)
                ->set('name', $param['account'])
                ->set('avatar', $avatar)
                ->getToken();

            cookie('token', $token, 86400 * 7);
            cookie('uid', $uid, 86400 * 7);
            cookie('name', $param['account'], 86400 * 7);
            cookie('avatar', $avatar, 86400 * 7);

            return json(['code' => 0, 'data' => $token, 'msg' => 'success']);
        }
    }
}