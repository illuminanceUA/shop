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

       $query = "(SELECT t1.name, t2.fio FROM t1 LEFT JOIN t2 ON t1.parent_id = t2.id WHERE t1.parent_id = 1)
              UNION 
              (SELECT t1.name, t2.fio FROM t1 LEFT JOIN t2 ON t1.parent_id = t2.id WHERE t2.id = 1)
              ORDER BY 1 ASC
       ";

       $res = $db->get($table, [
           'fields' => ['id', 'name'],
           'where' => ['name' => 'masha, olya, sveta', 'surname' => 'Sergeevna', 'fio' => 'Andrey', 'car' => 'Porshe', 'color' => $color ],
           'operand' => ['IN', 'LIKE%', '<>', '=', 'NOT IN'],
           'condition' => ['AND', 'OR'],
           'order' => [1, 'name'],
           'order_direction' => ['DESC'],
           'limit' => '1',
           'join' => [
                [
                   'table' => 'join_table1',
                   'fields' => ['id as j_id', 'name as j_name'],
                   'type' => 'left',
                   'where' => ['name' => 'sasha'],
                   'operand' => ['='],
                   'condition' => ['OR'],
                   'on' => [
                       'table' => 'teachers',
                       'fields' => ['id', 'parent_id']
                   ]
               ],
               'join_table2' => [
                   'table' => 'join_table2',
                   'fields' => ['id as j2_id', 'name as j2_name'],
                   'type' => 'left',
                   'where' => ['name' => 'sasha'],
                   'operand' => ['='],
                   'condition' => ['AND'],
                   'on' => [
                       'table' => 'teachers',
                       'fields' => ['id', 'parent_id']
                   ]
               ]
           ]
       ]);

       exit('I am admin panel');
   }
}