<?php
/**
 * Created by PhpStorm.
 * User: nickbai
 * Date: 19-6-21
 * Time: 上午10:54
 */
namespace app\common\command;

use app\common\service\Server;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

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
            array_shift($argv);
            array_unshift($argv, 'think', $action);
        }

        Server::run();
    }
}