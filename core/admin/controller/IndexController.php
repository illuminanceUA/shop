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


       $res = $db->delete($table, [
           'where' => ['id' => 20],
           'join' => [
               [
                   'table' => 'students',
                   'on' => ['student_id', 'id']
                ]
           ]
       ]);

       exit('id =' . $res['id'] . ' Name = ' . $res['name']);
   }
}