<?php

namespace core\admin\controller;

use core\admin\model\Model;
use core\base\controller\BaseController;
use core\base\controller\RouteController;
use core\base\model\BaseModel;


class IndexController extends BaseController
{
   protected function inputData(){



     //  $db = BaseModel::instance();
    //   $db = Model::instance();

    //   $query = "SELECT * FROM articles";

     //  $res = $db->query($query);

       exit('I am admin panel');
   }
}