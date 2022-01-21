<?php

namespace core\admin\controller;

use core\admin\model\Model;
use core\base\controller\BaseController;
use core\base\controller\RouteController;
use core\base\model\BaseModel;
use core\base\settings\Settings;


class IndexController extends BaseController
{
   protected function inputData(){

      $redirect = PATH . Settings::get('routes')['admin']['alias'] . '/show';
      $this->redirect($redirect);
   }
}