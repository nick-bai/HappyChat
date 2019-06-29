<?php
/**
 * Created by PhpStorm.
 * User: NickBai
 * Email: 876337011@qq.com
 * Date: 2019/6/22
 * Time: 8:18 AM
 */
namespace app\index\controller;

class Index extends Base
{
    public function index($token)
    {
        $this->assign([
            'uid' => $this->token->getClaim('uid'),
            'name' => $this->token->getClaim('name'),
            'avatar' => $this->token->getClaim('avatar'),
            'token' => $token
        ]);

        return $this->fetch();
    }
}
