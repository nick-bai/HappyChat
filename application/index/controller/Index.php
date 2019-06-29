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
        try {

            $has = db('customers')->field('id')->where('uid', $this->token->getClaim('uid'))->find();
            if (empty($has)) {
                db('customers')->insert([
                    'uid' => $this->token->getClaim('uid'),
                    'name' => $this->token->getClaim('name'),
                    'avatar' => $this->token->getClaim('avatar'),
                    'location' => getLocation(request()->ip())
                ]);
            }
        } catch (\Exception $e) {

            $this->error($e->getMessage());
        }

        $this->assign([
            'uid' => $this->token->getClaim('uid'),
            'name' => $this->token->getClaim('name'),
            'avatar' => $this->token->getClaim('avatar'),
            'token' => $token,
            'online_user' => db('customers')->select()
        ]);

        return $this->fetch();
    }
}
