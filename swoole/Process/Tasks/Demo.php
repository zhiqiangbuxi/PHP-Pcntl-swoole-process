<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/9
 * Time: 10:28
 */
namespace Process\Tasks;

use DB\DB;

class Demo extends Base {

    private $instance;

    public function __construct()
    {
        parent::__construct();

        $this->instance = DB::instance();
    }

    public function index()
    {
        $result = $this->instance->get('user');
        var_dump($result);
    }
}