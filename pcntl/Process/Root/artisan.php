<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/7
 * Time: 11:44
 */
namespace Crontab\Root;

use Process\Core\Pcntl;
use Process\Core\Timer;
use Process\Tasks\Demo;

class artisan{

    public static $logFile = NULL;

    public static function register()
    {
        spl_autoload_register(function ($class_name) {
            $class_name = str_replace('\\','/',$class_name);
            require_once dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.$class_name.'.php';
        });

        self::$logFile = dirname(__DIR__).DIRECTORY_SEPARATOR.'Log'.DIRECTORY_SEPARATOR.'process.log';
    }
}
artisan::register();

Pcntl::addJobs(function (){
    $demo = new Demo();
    $demo->index();
});

Pcntl::addJobs(function (){
    while(true){
        $data = '[' . date('Y-m-d H:i:s') . ']' . PHP_EOL;
        file_put_contents(artisan::$logFile,$data,FILE_APPEND);
        sleep(2);
    }
});

Pcntl::main($argv);
