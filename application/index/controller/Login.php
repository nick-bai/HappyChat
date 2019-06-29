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
            $param['account'] = trim($param['account']);

            if (empty($param['account'])) {
                return json(['code' => -1, 'data' => '', 'msg' => '请输入昵称']);
            }

            try {

                $has = db('customers')->field('id')->where('name', $param['account'])->find();
                if (!empty($has)) {
                    return json(['code' => -2, 'data' => '', 'msg' => '该昵称已经存在']);
                }
            } catch (\Exception $e) {

                return json(['code' => -3, 'data' => $e->getMessage(), 'msg' => '系统错误']);
            }

            $uid = uniqid();
            $avatar = '/static/images/avatar/' . mt_rand(1, 14) . '.png';
            $time = time();
            $token = (new Builder())->setIssuer('http://baiyf.com')
                ->setAudience('http://chat.baiyf.com')
                ->setId(uniqid(), true)
                ->setIssuedAt($time)
                ->setNotBefore($time)
                ->setExpiration($time + 86400) // 1天有效期
                ->set('uid', $uid)
                ->set('name', $param['account'])
                ->set('avatar', $avatar)
                ->getToken();

            return json(['code' => 0, 'data' => base64_encode((string)$token), 'msg' => '登录成功']);
        }
    }
}