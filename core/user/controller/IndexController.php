<?php

namespace core\user\controller;

use core\base\controller\BaseController;

class IndexController extends BaseController
 {
    protected $name;

    protected function inputData()
    {
        $str = '1234567890absdifg';

        $enStr = \core\base\model\Crypt::instance()->encrypt($str);

        $decStr = \core\base\model\Crypt::instance()->decrypt($enStr);
        exit('Это главная страница!');
    }

 }