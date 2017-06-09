<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/8
 * Time: 16:28
 */
namespace Process\Core;

final class process{

    private static $pidFile = null;
    private static $logFile = null;
    private static $maxWorkers = 2;
    private static $workerPids = [];
    private static $masterPid = 0;
    private static $workerJobs = [];
    private static $newIndex = 0;

    public static function main($argv)
    {

        self::init();

        switch ($argv[1]) {
            case 'start':
                self::start();
                break;
            case 'stop':
                self::stop();
                break;
            case 'reload':
                self::reload();
                break;
            case 'status':
                self::status();
                break;
            default:
                echo 'please input start|stop|reload|status option' . PHP_EOL;
                break;
        }
    }

    private static function init()
    {
        if (PHP_SAPI != 'cli') {
            echo 'This script is only allowed to run in cli ' . PHP_EOL;exit;
        }

        self::$pidFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'PidFile' . DIRECTORY_SEPARATOR . 'process.pid';
        self::$logFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Log' . DIRECTORY_SEPARATOR . 'process.log';
    }

    private static function start()
    {
        echo 'start...' . PHP_EOL;

        self::countProcess();
        self::daemon();
    }

    private static function stop()
    {
        if (!file_exists(self::$pidFile)) {
            echo 'master is not running';exit;
        }

        $pidArr = json_decode(file_get_contents(self::$pidFile),true);

        echo "kill master {$pidArr['master']}..." . PHP_EOL;
        \swoole_process::kill($pidArr['master']);

        @unlink(self::$pidFile);
        echo 'stopped successfully...' . PHP_EOL;
    }

    private static function reload()
    {
        self::stop();
        sleep(1);
        self::start();
    }

    private static function status()
    {
        if (!file_exists(self::$pidFile))
        {
            echo 'master is not running' . PHP_EOL;exit;
        }

        $pidArr = json_decode(file_get_contents(self::$pidFile),true);

        system(sprintf("ps ax | grep %d | grep -v grep", $pidArr['master']));
        foreach ($pidArr['workers'] as $pid)
        {
            system(sprintf("ps ax | grep %d | grep -v grep", $pid));
        }
    }

    private static function daemon()
    {
        try {
            swoole_set_process_name(sprintf('php-ps:%s', 'master'));
            self::$masterPid= posix_getpid();
            self::run();
            self::savePid();
            self::processWait();
        }catch (\Exception $e){
            die('ALL ERROR: '.$e->getMessage());
        }
    }

    private static function run()
    {
        for ($i=0; $i < self::$maxWorkers; $i++) {
            self::CreateProcess($i);
        }
    }

    private static function CreateProcess($index = null){
        $process = new \swoole_process(function(\swoole_process $worker) use($index){
            if(is_null($index)){
                $index = self::$newIndex;
                self::$newIndex++;
            }
            swoole_set_process_name(sprintf('php-ps:worker%s',$index));
            while(true){
                self::checkMpid($worker);
                $func = self::$workerJobs[$index];
                $func();
                sleep(1);
            }
        }, false, false);
        $pid=$process->start();
        self::$workerPids[$index] = $pid;
        return $pid;
    }
    private static function checkMpid(&$worker){
        if(!\swoole_process::kill(self::$masterPid,0)){
            // 这句提示,实际是看不到的.需要写到日志中
            $date = date('Y-m-d H:i:s');
            $msg = "[{$date}] Master process exited, worker pid [{$worker->pid}] also quit" . PHP_EOL;
            file_put_contents(self::$logFile, $msg,FILE_APPEND);
            $worker->exit();
        }
    }

    //子进程异常终止的话重启
    private static function rebootProcess($ret){
        $pid=$ret['pid'];
        $index = array_search($pid, self::$workerPids);
        if($index !== false){
            $index = intval($index);
            unset(self::$workerPids[$index]);
            $newPid = self::CreateProcess($index);
            self::savePid();

            $date = date('Y-m-d H:i:s');
            $msg = "[{$date}] RebootProcess: worker:{$index}-pid:{$newPid} Done" . PHP_EOL;
            file_put_contents(self::$logFile, $msg,FILE_APPEND);
            return;
        }
        throw new \Exception('rebootProcess Error: no pid');
    }

    private static function processWait()
    {
        while(1) {
            if(count(self::$workerPids)){
                $ret = \swoole_process::wait();
                if ($ret) {
                    self::rebootProcess($ret);
                }
            }else{
                break;
            }
        }
    }

    private static function countProcess()
    {
        self::$maxWorkers = self::$workerJobs ? count(self::$workerJobs) : self::$maxWorkers;
    }

    private static function savePid()
    {
        $pidJson = json_encode(array(
            'master' => self::$masterPid,
            'workers' => self::$workerPids,
        ));

        file_put_contents(self::$pidFile, $pidJson);
    }

    public static function addJobs($job)
    {
        self::$workerJobs[] = $job;
    }
}

/*process::addJobs(function(){
    echo date('Y-m-d H:i:s') . PHP_EOL;
});

process::main($argv);*/