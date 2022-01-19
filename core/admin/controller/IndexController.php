<?php

namespace core\admin\controller;

use core\admin\model\Model;
use core\base\controller\BaseController;
use core\base\controller\RouteController;
use core\base\model\BaseModel;


class IndexController extends BaseController
{
   protected function inputData(){

       $db = Model::instance();
       $table = 'teachers';

       $color = ['red', 'blue', 'black'];

       $res = $db->get($table, [
           'fields' => ['id', 'name'],
           'where' => ['name' => 'masha, olya, sveta', 'surname' => 'Sergeevna', 'fio' => 'Andrey', 'car' => 'Porshe', 'color' => $color ],
           'operand' => ['IN', 'LIKE%', '<>', '=', 'NOT IN'],
           'condition' => ['AND', 'OR'],
           'order' => ['fio', 'name'],
           'order_direction' => ['DESC'],
           'limit' => '1'
       ]);

       exit('I am admin panel');
   }
}