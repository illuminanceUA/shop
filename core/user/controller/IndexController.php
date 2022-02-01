<?php

namespace core\user\controller;

use core\admin\model\Model;
use core\base\controller\BaseController;

class IndexController extends BaseController
 {
    protected $name;

    protected function inputData()
    {
       // $str = '1234567890absdifg';

      //  $enStr = \core\base\model\Crypt::instance()->encrypt($str);

      //  $decStr = \core\base\model\Crypt::instance()->decrypt($enStr);

        $model = Model::instance();

        $res = $model->get('teachers', [
            'where' => ['id' => '16,17'],
            'operand' => ['IN'],
            'join' => [
                'stud_teach' => ['on' => ['id', 'teachers']],
                'students' => [
                    'fields' => ['name as student_name'],
                    'on' => ['students', 'id']
                ]
            ],
          //  'join_structure' => true
        ]);

        exit('Это главная страница!');
    }

 }