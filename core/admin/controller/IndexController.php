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

      $files = [];

       $_POST['id'] = 8;
       $_POST['name'] = '';
       $_POST['content'] = "<p>New`' book</p>";

       $res = $db->edit($table);

       exit('id =' . $res['id'] . ' Name = ' . $res['name']);
   }
}