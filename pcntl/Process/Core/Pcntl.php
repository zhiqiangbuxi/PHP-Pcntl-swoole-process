<?php

namespace Process\Core;

/**
 * Class pcntl fork master and workers
 */
final class Pcntl{

    private static $pidFile = NULL;
    private static $maxWorkers = 5;
    private static $currentWorkers = 0;
    private static $workerPids = [];
    private static $masterPid = 0;
    private static $workerJobs = [];

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
            echo 'This script is only allowed to run in cli ' . PHP_EOL;
            exit;
        }

        self::$pidFile = dirname(__DIR__).DIRECTORY_SEPARATOR.'PidFile'.DIRECTORY_SEPARATOR.'process.pid';
    }

    private static function start()
    {
        echo 'start...' . PHP_EOL;

        self::calProcess();
        self::daemon();
        self::fork();
        self::savePids();
        self::monitor();
    }

    private static function stop()
    {
        if (!file_exists(self::$pidFile)) {
            echo 'master is not running';exit;
        }

        $pidArr = json_decode(file_get_contents(self::$pidFile),true);
        foreach ($pidArr['workers'] as $pid) {
            echo "kill worker {$pid}..." . PHP_EOL;
            posix_kill($pid, SIGTERM);
        }

        sleep(1);

        echo "kill master {$pidArr['master']}..." . PHP_EOL;
        posix_kill($pidArr['master'], SIGTERM);

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

        $pidsArr = json_decode(file_get_contents(self::$pidFile),true);
        foreach ($pidsArr['workers'] as $pid)
        {
            system(sprintf("ps ax | grep %d | grep -v grep", $pid));
        }
        system(sprintf("ps ax | grep %d | grep -v grep", $pidsArr['master']));
    }

    /*
        计算要fork的子任务数/进程数 默认为 self::$maxWorkers
    */
    private static function calProcess()
    {
        self::$maxWorkers = self::$workerJobs ? count(self::$workerJobs) : self::$maxWorkers;
    }

    private static function daemon()
    {
        //告诉父进程 不关心子进程的状态 防止僵尸进程的产生
        pcntl_signal(SIGCHLD, SIG_IGN);

        clearstatcache();

        if (file_exists(self::$pidFile)) {
            echo 'pid file ' . self::$pidFile . ' already exists' . PHP_EOL;exit;
        }

        umask(0);
        $pid = pcntl_fork();
        if ($pid == -1) {
            echo "master fork failed";exit;
        }elseif ($pid) {
            exit;
        }else{
            //make the current process as a session leader 摆脱登录会话、终端控制的影响
            if (posix_setsid() == -1) {
                echo 'master setsid failed';exit;
            }

            self::$masterPid = posix_getpid();
        }
    }

    /**
     * fork children
     */
    private static function fork()
    {
        for ($i=0; $i < self::$maxWorkers; $i++) {

            if(self::$currentWorkers>=self::$maxWorkers){
                $childPid = pcntl_wait($status);
                if ($childPid <= 0) {
                    exit();
                }

                if ($status == SIGTERM) {
                    self::$currentWorkers--;
                }
            }

            $pid = pcntl_fork();
            if ($pid == -1) {
                echo 'forking children process failed';exit;
            }elseif ($pid > 0) {
                self::$workerPids[] = $pid;
                cli_set_process_title('task:master..');
                self::$currentWorkers++;
            }else {
                cli_set_process_title('task:worker..');
                //执行工作任务
                $func = self::$workerJobs[$i];
                $func();
                //posix_kill(posix_getpid(), SIGTERM);
            }
        }
    }

    private static function savePids()
    {
        $pidsJson = json_encode(array(
            'master' => self::$masterPid,
            'workers' => self::$workerPids,
        ));

        file_put_contents(self::$pidFile, $pidsJson);
    }

    public static function addJobs($job)
    {
        self::$workerJobs[] = $job;
    }

    private static function monitor()
    {
        while (true) {
            pcntl_signal_dispatch();
            sleep(1);
        }
    }
}

