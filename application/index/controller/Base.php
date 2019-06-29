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
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\ValidationData;

class Base extends Controller
{
    protected $token;

    public function initialize()
    {
        $token = base64_decode(input('param.token'));
        if (empty($token)) {
            $this->redirect(url('login/index'));
        }

        $this->token = (new Parser())->parse($token);

        $validate = new ValidationData();

        $validate->setIssuer($this->token->getClaim('iss'));
        $validate->setAudience($this->token->getClaim('aud'));
        $validate->setId($this->token->getClaim('jti'));

        if(!$this->token->validate($validate)) {
            $this->redirect(url('login/index'));
        }
    }
}