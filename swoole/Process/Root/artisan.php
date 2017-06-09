<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/9
 * Time: 10:17
 */
namespace Process\Root;

use Process\Core\process;
use Process\Tasks\Demo;

class artisan{

    public static function register()
    {
        spl_autoload_register(function ($class_name) {
            $class_name = str_replace('\\','/',$class_name);
            require_once dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.$class_name.'.php';
        });
    }
}

artisan::register();

process::addJobs(function(){
    //echo date('Y-m-d H:i:s') . PHP_EOL;
    $demo = new Demo();
    $demo->index();
});

process::addJobs(function (){
    $data = '[' . date('Y-m-d H:i:s') . ']' . PHP_EOL;
    file_put_contents(dirname(__DIR__) . '/Log/demo.log',$data,FILE_APPEND);
});

process::main($argv);