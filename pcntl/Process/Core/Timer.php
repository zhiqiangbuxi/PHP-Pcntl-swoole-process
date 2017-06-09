<?php

namespace Process\Core;
/**
 * libevent 定时器
 */
class Timer{

    private $func;
    private $timeout;

    public function __construct($func,$timeout)
    {
        $this->func = $func;
        $this->timeout = $timeout;
    }

    public function run()
    {
        $base = event_base_new();
        $event = event_new();

        event_set($event, 0, EV_TIMEOUT, $this->func);
        event_base_set($event, $base);
        event_add($event, $this->timeout);

        event_base_loop($base);
    }
}

