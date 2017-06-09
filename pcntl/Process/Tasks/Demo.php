<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/7
 * Time: 15:25
 */

namespace Process\Tasks;

use DB\Model;

class Demo extends Base {

    private $instance;

    public function __construct()
    {
        parent::__construct();
        $this->instance = Model::instance();
    }

    public function index()
    {
        while(true) {
            $result = $this->instance->get('user');
            var_dump($result);
            sleep(2);
        }
    }
}