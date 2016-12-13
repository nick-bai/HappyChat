<?php
/**
 * author: NickBai
 * createTime: 2016/12/9 0009 下午 4:19
 */
ob_implicit_flush();
require_once('./core/SocketChat.php');

//run server
$port = 8090;
new NickBai\SocketChat( $port );
