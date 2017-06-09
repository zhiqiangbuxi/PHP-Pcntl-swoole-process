<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/7
 * Time: 15:18
 */

return [
    'DB' => [
        'database_type' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
        'username' => '602datauser',
        'password' => '2tuG37QK49rZFaf',
        'database_name' => 'linking',
        'charset' => 'utf8',
        'persistent' => true,
        /*'slave' => [
            'database_type' => 'mysql',
            'host' => '127.0.0.1',
            'port' => 3306,
            'username' => '602datauser',
            'password' => '2tuG37QK49rZFaf',
            'database_name' => 'linking',
            'charset' => 'utf8',
            'persistent' => true,
        ],*/
    ],
    'REDIS' => [
        'host' => '127.0.0.1',
        'port' => 6379,
        'timeout' => 3,
        'db' => 1,
        'persistent' => false,
    ],
];