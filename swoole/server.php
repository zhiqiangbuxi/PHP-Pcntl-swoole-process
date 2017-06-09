<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/8
 * Time: 15:37
 */

$serv = new swoole_server('127.0.0.1',9501);

$serv->on('connect',function ($serv,$fd){
    echo 'connected' . PHP_EOL;
});

$serv->on('receive',function ($serv,$fd,$from_id,$data){
    $serv->send($fd,'Server: ' . $data);
});

$serv->on('close',function (){
    echo 'Client close' . PHP_EOL;
});

$serv->start();