<?php
/**
 * Created by PhpStorm.
 * User: nickbai
 * Date: 19-6-21
 * Time: 上午10:54
 */
namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use Workerman\Worker;
use PHPSocketIO\SocketIO;

class Chat extends Command
{
    protected function configure()
    {
        $this->setName('chat')
            ->addArgument('action', Argument::OPTIONAL, "start|stop|restart|reload|status|connections", 'start')
            ->addOption('host', 'H', Option::VALUE_OPTIONAL, 'the host of workerman server.', null)
            ->addOption('port', 'p', Option::VALUE_OPTIONAL, 'the port of workerman server.', null)
            ->addOption('daemon', 'd', Option::VALUE_NONE, 'Run the workerman server in daemon mode.')
            ->setDescription('phpsocket.io Server for ThinkPHP');
    }

    protected function execute(Input $input, Output $output)
    {
        $action = $input->getArgument('action');

        if (DIRECTORY_SEPARATOR !== '\\') {
            if (!in_array($action, ['start', 'stop', 'reload', 'restart', 'status', 'connections'])) {
                $output->writeln("<error>Invalid argument action:{$action}, Expected start|stop|restart|reload|status|connections .</error>");
                return false;
            }

            global $argv;
            array_shift($argv);
            array_shift($argv);
            array_unshift($argv, 'think', $action);
        }

        $io = new SocketIO(2020);
        $io->on('connection', function($socket){
            $socket->addedUser = false;
            // when the client emits 'new message', this listens and executes
            $socket->on('new message', function ($data, $ack)use($socket){
                if ($ack && is_callable($ack)) {
                    $ack('Hello from ACK!!!');
                }
                // we tell the client to execute 'new message'
                $socket->broadcast->emit('new message', array(
                    'username'=> $socket->username,
                    'message'=> $data
                ));
            });

            // when the client emits 'add user', this listens and executes
            $socket->on('add user', function ($username) use($socket){
                global $usernames, $numUsers;
                // we store the username in the socket session for this client
                $socket->username = $username;
                // add the client's username to the global list
                $usernames[$username] = $username;
                ++$numUsers;
                $socket->addedUser = true;
                $socket->emit('login', array(
                    'numUsers' => $numUsers
                ));
                // echo globally (all clients) that a person has connected
                $socket->broadcast->emit('user joined', array(
                    'username' => $socket->username,
                    'numUsers' => $numUsers
                ));
            });

            // when the client emits 'typing', we broadcast it to others
            $socket->on('typing', function () use($socket) {
                $socket->broadcast->emit('typing', array(
                    'username' => $socket->username
                ));
            });

            // when the client emits 'stop typing', we broadcast it to others
            $socket->on('stop typing', function () use($socket) {
                $socket->broadcast->emit('stop typing', array(
                    'username' => $socket->username
                ));
            });

            // when the user disconnects.. perform this
            $socket->on('disconnect', function () use($socket) {
                global $usernames, $numUsers;
                // remove the username from global usernames list
                if($socket->addedUser) {
                    unset($usernames[$socket->username]);
                    --$numUsers;

                    // echo globally that this client has left
                    $socket->broadcast->emit('user left', array(
                        'username' => $socket->username,
                        'numUsers' => $numUsers
                    ));
                }
            });

        });

        $this->showLogo();

        Worker::runAll();
    }

    private function showLogo()
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