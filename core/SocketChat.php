<?php
/**
 * author: NickBai
 * createTime: 2016/12/9 0009 下午 4:17
 */
namespace NickBai;

class SocketChat
{
    private $timeout = 60;  //超时时间
    private $handShake = False; //默认未牵手
    private $master = 1;  //主进程
    private $port = 2000;  //监听端口
    private static $connectPool = [];  //连接池
    private static $maxConnectNum = 1024; //最大连接数
    private static $chatUser = [];  //参与聊天的用户


    public function __construct( $port = 0 )
    {
        !empty( $port ) && $this->port = $port;
        $this->startServer();
    }

    //开始服务器
    public function startServer()
    {
        $this->master = socket_create_listen( $this->port );
        if( !$this->master ) throw new \ErrorException("listen {$this->port} fail !");

        $this->runLog("Server Started : ".date('Y-m-d H:i:s'));
        $this->runLog("Listening on   : 127.0.0.1 port " . $this->port);
        $this->runLog("Master socket  : " . $this->master . " \n");

        self::$connectPool[] = $this->master;

        while( true ){
            $readFds = self::$connectPool;
            //阻塞接收客户端链接
            @socket_select( $readFds, $writeFds, $e = null, $this->timeout );

            foreach( $readFds as $socket ){
                //当前链接 是主进程
                if( $this->master == $socket ){

                    $client = socket_accept( $this->master );  //接收新的链接
                    $this->handShake = False;

                    if ($client < 0){
                        $this->log('clinet connect false!');
                        continue;
                    } else{
                        //超过最大连接数
                        if( count( self::$connectPool ) > self::$maxConnectNum )
                            continue;

                        //加入连接池
                        $this->connect( $client );
                    }

                }else{
                    //不是主进程,开始接收数据
                    $bytes = @socket_recv($socket, $buffer, 2048, 0);
                    //未读取到数据
                    if( $bytes == 0 ){
                        $this->disConnect( $socket );
                    }else{
                        //未握手 先握手
                        if( !$this->handShake ){

                            $this->doHandShake( $socket, $buffer );
                        }else{

                            //如果是已经握完手的数据，广播其发送的消息
                            $buffer = $this->decode( $buffer );
                            $this->parseMessage( $buffer, $socket );
                        }
                    }

                }
            }

        }
    }

    //解析发送的数据
    public function parseMessage( $message, $socket )
    {
        //msg type  1 初始化  2 通知  3 一般聊天  4 断开链接  5 获取在线用户 6 通知下线
        $message = json_decode( $message, true );
        switch( $message['type'] ){

            case 1:
                $this->bind( $socket, $message );
                //通知其他客户端,当前用户上线
                $msg = [
                    'type' => "2",
                    'msg' => 'online',
                    'avar' => $message['avar']
                ];
                $this->sendToAll( $socket,  $msg );
                //更新在线用户
                $this->freshOnlineUser();

                break;
            case 3:
                $this->sendToAll( $socket, $message );
                break;
            case 4:
                //通知用户离线
                $msgOutline = [
                    'type' => '6',
                    'user' => self::$chatUser[(int)$socket]['user']
                ];
                $this->tellOnlineInfo( $msgOutline );
                //断开 要离线的用户
                $this->disConnect( $socket );
                //更新在线用户
                $this->freshOnlineUser();

                break;
            default:
                break;
        }
    }

    //用户--链接 绑定
    public function bind( $socket, $user )
    {
        self::$chatUser[(int) $socket] = [
            'user' => $user['user'],
            'avar' => $user['avar']
        ];
    }

    //用户--链接 解绑
    public function unBind( $socket )
    {
        unset( self::$chatUser[(int) $socket] );
    }

    //获取在线用户
    public function getOnlineUser()
    {
        return self::$chatUser;
    }

    //更新在线用户
    public function freshOnlineUser()
    {
        $msgOnlie = [
            'type' => "5",
            'msg' => 'online user',
            'info' => self::$chatUser
        ];
        $this->tellOnlineInfo( $msgOnlie );
    }

    //广播所有的客户端(排除自己和master)
    public function sendToAll( $client, $mess )
    {
        //拼装发送者的名称
        $mess['user'] = self::$chatUser[(int) $client]['user'];
        $mess['stime'] = date('Y-m-d H:i:s');

        foreach( self::$connectPool as $socket ){
            if( $socket != $this->master && $socket != $client  ){
                $this->send( $socket, $mess );
            }
        }
    }

    //广播客户端在线用户信息
    public function tellOnlineInfo( $mess )
    {
        foreach( self::$connectPool as $socket ){
            if( $socket != $this->master ){
                $this->send( $socket, $mess );
            }
        }
    }

    //处理发送信息
   public function send( $client, $msg )
    {
        $msg = $this->frame( json_encode( $msg ) );
        socket_write( $client, $msg, strlen($msg) );
    }

    //握手协议
    function doHandShake($socket, $buffer)
    {
        list($resource, $host, $origin, $key) = $this->getHeaders($buffer);
        $upgrade  = "HTTP/1.1 101 Switching Protocol\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "Sec-WebSocket-Accept: " . $this->calcKey($key) . "\r\n\r\n";  //必须以两个回车结尾

        socket_write($socket, $upgrade, strlen($upgrade));
        $this->handShake = true;
        return true;
    }

    //获取请求头
    function getHeaders( $req )
    {
        $r = $h = $o = $key = null;
        if (preg_match("/GET (.*) HTTP/"              , $req, $match)) { $r = $match[1]; }
        if (preg_match("/Host: (.*)\r\n/"             , $req, $match)) { $h = $match[1]; }
        if (preg_match("/Origin: (.*)\r\n/"           , $req, $match)) { $o = $match[1]; }
        if (preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $req, $match)) { $key = $match[1]; }
        return [$r, $h, $o, $key];
    }

    //验证socket
    function calcKey( $key )
    {
        //基于websocket version 13
        $accept = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
        return $accept;
    }


    //打包函数 返回帧处理
    public function frame( $buffer )
    {
        $len = strlen($buffer);
        if ($len <= 125) {

            return "\x81" . chr($len) . $buffer;
        } else if ($len <= 65535) {

            return "\x81" . chr(126) . pack("n", $len) . $buffer;
        } else {

            return "\x81" . char(127) . pack("xxxxN", $len) . $buffer;
        }
    }

    //解码 解析数据帧
    function decode( $buffer )
    {
        $len = $masks = $data = $decoded = null;
        $len = ord($buffer[1]) & 127;

        if ($len === 126) {
            $masks = substr($buffer, 4, 4);
            $data = substr($buffer, 8);
        }
        else if ($len === 127) {
            $masks = substr($buffer, 10, 4);
            $data = substr($buffer, 14);
        }
        else {
            $masks = substr($buffer, 2, 4);
            $data = substr($buffer, 6);
        }
        for ($index = 0; $index < strlen($data); $index++) {
            $decoded .= $data[$index] ^ $masks[$index % 4];
        }
        return $decoded;
    }

    //客户端链接处理函数
    function connect( $socket )
    {
        array_push( self::$connectPool, $socket );
        $this->runLog("\n" . $socket . " CONNECTED!");
        $this->runLog(date("Y-n-d H:i:s"));
    }

    //客户端断开链接函数
    function disConnect( $socket )
    {
        $index = array_search( $socket, self::$connectPool );
        socket_close( $socket );

        $this->unBind( $socket );
        $this->runLog( $socket . " DISCONNECTED!" );
        if ($index >= 0){
            array_splice( self::$connectPool, $index, 1 );
        }
    }

    //打印运行信息
    public function runLog( $mess = '' )
    {
        echo $mess . PHP_EOL;
    }

    //系统日志
    public function log( $mess = '' )
    {
        @file_put_contents( './' . date("Y-m-d") . ".log", date('Y-m-d H:i:s') . "  " . $mess . PHP_EOL, FILE_APPEND );
    }
}
