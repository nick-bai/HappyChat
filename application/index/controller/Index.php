<?php
/**
 * Created by PhpStorm.
 * User: NickBai
 * Email: 876337011@qq.com
 * Date: 2019/6/22
 * Time: 8:18 AM
 */
namespace app\index\controller;

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\ValidationData;

class Index extends Base
{
    public function index()
    {
        $token = cookie('token');
        $token = (new Parser())->parse($token);

        $validate = new ValidationData();

        $validate->setIssuer($token->getClaim('iss'));
        $validate->setAudience($token->getClaim('aud'));
        $validate->setId($token->getClaim('jti'));

        if(!$token->validate($validate)) {
            $this->redirect(url('login/index'));
        }

        $this->assign([
            'uid' => cookie('uid'),
            'name' => cookie('name'),
            'avatar' => cookie('avatar'),
            'token' => $token,
            'online_user' => json_decode(cache('user_list'), true)
        ]);

        return $this->fetch();
    }
}
