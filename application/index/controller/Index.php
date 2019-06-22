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
            'uid' => $token->getClaim('uid'),
            'name' => $token->getClaim('name'),
            'avatar' => $token->getClaim('avatar')
        ]);

        return $this->fetch();
    }
}
