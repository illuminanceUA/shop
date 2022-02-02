<?php

namespace core\user\controller;

use core\admin\model\Model;
use core\base\controller\BaseController;

class IndexController extends BaseController
 {
    protected $name;

    protected function inputData()
    {

        $model = Model::instance();

        $res = $model->get('goods', [
            'where' => ['id' => '16,17'],
            'operand' => ['IN'],
            'join' => [
                'goods_filters' => ['on' => ['id', 'teachers']],
                'filters f' => [
                    'fields' => ['name as student_name', 'content'],
                    'on' => ['students', 'id']
                ],
                [
                    'table' => 'filters',
                    'on' => ['parent_id', 'id']
                ]
            ],
            'join_structure' => true
        ]);

        exit('Это главная страница!');
    }

 }