<?php
/**
 * Created by PhpStorm.
 * User: NickBai
 * Email: 876337011@qq.com
 * Date: 2019/6/22
 * Time: 10:56 AM
 */
namespace app\common\service;

use Workerman\Worker;
use PHPSocketIO\SocketIO;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\ValidationData;

class Server
{
    public static function run()
    {
        $io = new SocketIO(2020);
        $io->on('connection', function($socket) {

            $socket->addedUser = false;

            $socket->on('NEW_MESSAGE', function($data, $callback) use($socket) {
                $data = json_decode($data, true);

                $socket->broadcast->emit('new message', [
                    'uid' => $socket->uid,
                    'name'=> $socket->username,
                    'avatar' => $socket->avatar,
                    'time' => date('Y-m-d H:i:s'),
                    'message'=> $data['content']
                ]);

                if (is_callable($callback)) {
                    $callback(['code' => 0, 'data' => '', 'msg' => '发送成功']);
                }
            });

            $socket->on('ADD_USER', function($data, $callback) use($socket) {
                $data = json_decode($data, true);

                $token = (new Parser())->parse(base64_decode($data['token']));

                if (empty($token->getClaim('uid'))) {
                    if (is_callable($callback)) {
                        $callback(['code' => 400, 'data' => '', 'msg' => '登录过期']);
                    }

                    return false;
                }

                $has = db('customers')->field('id')->where('uid', $token->getClaim('uid'))->find();
                if (empty($has)) {
                    db('customers')->insert([
                        'uid' => $token->getClaim('uid'),
                        'name' => $token->getClaim('name'),
                        'avatar' => $token->getClaim('avatar'),
                        'location' => getLocation(request()->ip())
                    ]);
                }

                $socket->uid = $token->getClaim('uid');
                $socket->username = $token->getClaim('name');
                $socket->avatar = $token->getClaim('avatar');

                $socket->addedUser = true;

                $socket->broadcast->emit('user joined', [
                    'uid' => $socket->uid,
                    'name'=> $socket->username,
                    'avatar' => $socket->avatar,
                    'location' => getLocation(request()->ip()),
                ]);

                if (is_callable($callback)) {
                    $callback(['code' => 0, 'data' => db('customers')->select(), 'msg' => '登录成功']);
                }
            });

            $socket->on('typing', function () use($socket) {
                $socket->broadcast->emit('typing', array(
                    'username' => $socket->username
                ));
            });

            $socket->on('stop typing', function () use($socket) {
                $socket->broadcast->emit('stop typing', array(
                    'username' => $socket->username
                ));
            });

            $socket->on('disconnect', function () use($socket) {

                if ($socket->addedUser) {
                    db('customers')->where('uid', $socket->uid)->delete();

                    $socket->broadcast->emit('user left', [
                        'uid' => $socket->uid
                    ]);
                }
            });

        });

        self::showLogo();

        Worker::runAll();
    }

    private static function showLogo()
    {
        // website https://www.bootschool.net/ascii
        $logo = <<<EOL
  _                                              _               _   
 | |__     __ _   _ __    _ __    _   _    ___  | |__     __ _  | |_ 
 | '_ \   / _` | | '_ \  | '_ \  | | | |  / __| | '_ \   / _` | | __|
 | | | | | (_| | | |_) | | |_) | | |_| | | (__  | | | | | (_| | | |_ 
 |_| |_|  \__,_| | .__/  | .__/   \__, |  \___| |_| |_|  \__,_|  \__|
                 |_|     |_|      |___/                              
EOL;

        echo $logo . PHP_EOL . PHP_EOL;
    }
}