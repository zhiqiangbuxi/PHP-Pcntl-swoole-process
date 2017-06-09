<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/9
 * Time: 10:28
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
        $result = $this->instance->get('user');
        var_dump($result);
    }
}