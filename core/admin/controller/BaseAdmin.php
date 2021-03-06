<?php

namespace core\admin\controller;

use core\admin\model\Model;
use core\base\controller\BaseController;
use core\base\exceptions\RouteException;
use core\base\settings\Settings;
use libraries\FileEdit;

abstract class BaseAdmin extends BaseController
{

    protected $model;

    protected $table;
    protected $columns;
    protected $foreignData;

    protected $adminPath;

    protected $menu;
    protected $title;

    protected $alias;

    protected $fileArray;

    protected $messages;
    protected $settings;

    protected $translate;
    protected $blocks = [];

    protected $templateArr;
    protected $formTemplates;
    protected $noDelete;

    protected function inputData() {

        $this->init(true);

        $this->title = 'VG engine';

        if(!$this->model) $this->model = Model::instance();
        if(!$this->menu) $this->menu = Settings::get('projectTables');
        if(!$this->adminPath) $this->adminPath =  PATH . Settings::get('routes')['admin']['alias'] . '/';

        if(!$this->templateArr) $this->templateArr = Settings::get('templateArr');
        if(!$this->formTemplates) $this->formTemplates = Settings::get('formTemplates');

        if(!$this->messages) $this->messages = include $_SERVER['DOCUMENT_ROOT'] . PATH . Settings::get('messages') . 'informationMessages.php';

        $this->sendNoCacheHeaders();

    }

    protected function outputData(){

        if(!$this->content){

            $args = func_get_args(0);
            $vars = $args ? $args : [];

       //     if(!$this->template) $this->template = ADMIN_TEMPLATE . 'show';

            $this->content = $this->render($this->template, $vars);

        }
         $this->header = $this->render(ADMIN_TEMPLATE . 'include/header');
         $this->footer = $this->render(ADMIN_TEMPLATE . 'include/footer');

         return $this->render(ADMIN_TEMPLATE . 'layout/default');
    }

    protected function sendNoCacheHeaders(){

        header("Last-Modified: " . gmdate("D, d m Y H:i:s") . "GMT");
        header("Cache-Control: no-cache, must-revalidate");
        header("Cache-Control: max-age=0");
        header("Cache-Control: post-check=0, pre-check=0");

    }

    protected function execBase(){
        self::inputData();
    }

    protected function createTableData($settings = false){

        if(!$this->table){
             if($this->parameters) $this->table = array_keys($this->parameters)[0];
                else{
                    if(!$settings) $settings = Settings::instance();
                    $this->table = $settings::get('defaultTable');
                }
        }

        $this->columns = $this->model->showColumns($this->table);

        if(!$this->columns) new RouteException('???? ?????????????? ???????? ?? ?????????????? - ' . $this->table, 2);

    }

    protected function expansion($args = [], $settings = false){

        $filename = explode('_', $this->table);
        $className = '';

        foreach ($filename as $item) $className .= ucfirst($item);

        if(!$settings){
            $path = Settings::get('expansion');
        }elseif(is_object($settings)){
            $path = $settings::get('expansion');
        }else{
            $path = $settings;
        }

        $class = $path . $className . 'Expansion';

        if(is_readable($_SERVER['DOCUMENT_ROOT'] . PATH . $class . '.php')){

            $class = str_replace('/', '\\', $class);

            $exp = $class::instance();

            foreach ($this as $name => $value){
                $exp->$name = &$this->$name;
            }

            return $exp->expansion($args);

        }else{

            $file = $_SERVER['DOCUMENT_ROOT'] . PATH . $path . $this->table . '.php';

            extract($args);

            if(is_readable($file)) return include $file;

        }

        return false;
    }

    protected function createOutputData($settings = false){

       if(!$settings) $settings = Settings::instance();

       $blocks = $settings::get('blockNeedle');
       $this->translate = $settings::get('translate');

       if(!$blocks || !is_array($blocks)){

           foreach ($this->columns as $name => $item){
               if($name === 'id_row') continue;

               if(!$this->translate[$name]) $this->translate[$name][] = $name;
               $this->blocks[0][] = $name;
           }

           return;
       }

        $default = array_keys($blocks)[0];

        foreach ($this->columns as $name => $item){
            if($name === 'id_row') continue;

            $insert = false;

            foreach ($blocks as $block => $value){

                if(!array_key_exists($block, $this->blocks)) $this->blocks[$block] = [];

                if(in_array($name, $value)){
                    $this->blocks[$block] = $name;
                    $insert = true;

                    break;
                }
            }

            if(!$insert) $this->blocks[$default][] = $name;
            if(!$this->translate[$name]) $this->translate[$name][] = $name;
        }

        return;

    }

    protected function createRadio($settings = false){

        if(!$settings) $settings = Settings::instance();

        $radio = $settings::get('radio');

        if($radio){
            foreach ($this->columns as $name => $item){
                if($radio[$name]){
                    $this->foreignData[$name] = $radio[$name];
                }
            }
        }
    }

    protected function checkPost($settings = false){

        if($this->isPost()){
            $this->clearPostFields($settings);
            $this->table = $this->clearStr($_POST['table']);
            unset($_POST['table']);

            if($this->table){
                $this->createTableData($settings);
                $this->editData();
            }
        }

    }

    protected function addSessionData($arr = []){
        if(!$arr) $arr = $_POST;

        foreach ($arr as $key => $item){
            $_SESSION['res'][$key] = $item;
        }

        $this->redirect();

    }

    protected function countChar($str, $counter, $answer, $arr){

        if(mb_strlen($str) > $counter){

            $strRes = mb_str_replace('$1', $answer, $this->messages['count']);
            $strRes = mb_str_replace('$2', $counter, $strRes);

            $_SESSION['res']['answer'] = '<div class="error">' . $strRes . '</div>';
            $this->addSessionData($arr);
        }

    }

    protected function emptyFields($str, $answer, $arr = []){

        if(empty($str)){
            $_SESSION['res']['answer'] = '<div class="error">' . $this->messages['empty']. ' ' . $answer . '</div>';
            $this->addSessionData($arr);
        }
    }

    protected function clearPostFields($settings, &$arr = []){
        if(!$arr) $arr = &$_POST;
        if(!$settings) $settings = Settings::instance();

        $id = $_POST[$this->columns['id_row']] ?: false;

        $validate = $settings::get('validation');
        if(!$this->translate) $this->translate = $settings::get('translate');

        foreach ($arr as $key => $item){
            if(is_array($item)){
                $this->clearPostFields($settings, $item);
            }else{
                if(is_numeric($item)){
                    $arr[$key] = $this->clearNum($item);
                }

                if($validate){

                    if($validate[$key]){

                        if($this->translate[$key]){
                            $answer = $this->translate[$key][0];
                        }else{
                            $answer = $key;
                        }

                        if($validate[$key]['crypt']){
                            if($id){
                                if(empty($item)){
                                    unset($arr[$key]);
                                    continue;
                                }

                                $arr[$key] = md5($item);
                            }
                        }

                        if($validate[$key]['empty']) $this->emptyFields($item, $answer, $arr);

                        if($validate[$key]['trim']) $arr[$key] = trim($item);

                        if($validate[$key]['int']) $arr[$key] = $this->clearNum($item);

                        if($validate[$key]['count']) $this->countChar($item, $validate[$key]['count'], $answer, $arr);

                    }

                }
            }
        }

        return true;

    }

    protected function editData($returnId = false){

        $id = false;
        $method = 'add';


        if($_POST[$this->columns['id_row']]){
            $id = is_numeric($_POST[$this->columns['id_row']]) ?
                $this->clearNum($_POST[$this->columns['id_row']]):
                $this->clearStr($_POST[$this->columns['id_row']]);

            if($id){
                $where = [$this->columns['id_row'] => $id];
                $method = 'edit';
            }
        }

        foreach ($this->columns as $key => $item){

            if($key === 'id_row') continue;

            if($item['Type'] === 'date' || $item['Type'] === 'datetime'){
               !$_POST[$key] && $_POST[$key] = 'NOW()';
            }
        }

        $this->createFile();

        $this->createAlias($id);

        $this->updateMenuPosition();

        $except = $this->checkExceptFields();


        $resId = $this->model->$method($this->table, [
            'files' => $this->fileArray,
            'where' => $where,
            'returnId' => true,
            'except' => $except
        ]);

        if(!$id && $method === 'add'){
            $_POST[$this->columns['id_row']] = $resId;
            $answerSuccess = $this->messages['addSuccess'];
            $answerFail = $this->messages['addFail'];
        }else{
            $answerSuccess = $this->messages['editSuccess'];
            $answerFail = $this->messages['editFail'];
        }

        $this->expansion(get_defined_vars());

        $result = $this->checkAlias($_POST[$this->columns['id_row']]);

        if($resId){

            $_SESSION['res']['answer'] = '<div class="success">' . $answerSuccess . '</div>';

            if(!$returnId) $this->redirect();

            return $_POST[$this->columns['id_row']];

        }else{

            $_SESSION['res']['answer'] = '<div class="error">' . $answerFail . '</div>';

            if(!$returnId) $this->redirect();

        }

    }

    protected function checkExceptFields($arr = []){

        if(!$arr) $arr = $_POST;

        $except = [];

        if($arr){

            foreach ($arr as $key => $item){
                if(!$this->columns[$key]) $except[] = $key;
            }
        }

        return $except;

    }

    protected function createFile(){

        $fileEdit = new FileEdit();
        $this->fileArray = $fileEdit->addFile();

    }

    protected function updateMenuPosition(){

    }

    protected function createAlias($id = false){

        if($this->columns['alias']){

            if(!$_POST['alias']){

                if($_POST['name']){
                    $aliasStr = $this->clearStr($_POST['name']);
                }else{
                    foreach ($_POST as $key => $item){
                        if(strpos($key, 'name') !== false && $item){
                            $aliasStr = $this->clearStr($item);
                            break;
                        }
                    }
                }

            }else{

                $aliasStr = $_POST['alias'] = $this->clearStr($_POST['alias']);

            }

            $textModify = new \libraries\TextModify();
            $alias = $textModify->translit($aliasStr);

            $where['alias'] = $alias;
            $operand[] = '=';

            if($id){
                $where[$this->columns['id_row']] = $id;
                $operand[] = '<>';
            }

            $resAlias = $this->model->get($this->table, [
                'fields' => ['alias'],
                'where' => $where,
                'operand' => $operand,
                'limit' => '1'
            ])[0];

            if(!$resAlias){

               $_POST['alias'] = $alias;

            }else{

               $this->alias = $alias;
               $_POST['alias'] = '';

            }

            if($_POST['alias'] && $id){
                method_exists($this, 'checkOldAlias') && $this->checkOldAlias($id);
            }

        }

    }

    protected function checkAlias($id){

        if($id){
            if($this->alias){

                $this->alias .= '-' . $id;

                $this->model->edit($this->table, [
                    'fields' => ['alias' => $this->alias],
                    'where' => [$this->columns['id_row'] => $id]
                ]);

                return true;
            }
        }

        return false;

    }

    protected function createOrderData($table)
    {

        $columns = $this->model->showColumns($table);

        if(!$columns)
            throw new RouteException('?????????????????????? ???????? ?? ?????????????? ' . $table);



        $name = '';
        $orderName = '';

        if($columns['name']){
            $orderName = $name = 'name';
        }else{

            foreach ($columns as $key => $value){
                if(strpos($key, 'name') !== false){
                    $orderName = $key;
                    $name = $key . ' as name';
                }
            }

            if(!$name) $name = $columns['id_row'] . ' as name';

        }

        $parentId = '';
        $order = [];

        if($columns['parent_id'])
             $order[] = $parentId = 'parent_id';

        if($columns['menu_position']) $order[] = 'menu_position';
           else $order[] = $orderName;

        return compact('name', 'parentId', 'order', 'columns');

    }

    protected function createManyToMany($settings = false)
    {

        if(!$settings) $settings = $this->settings ? : Settings::instance();

        $manyToMany = $settings::get('manyToMany');
        $blocks = $settings::get('blockNeedle');

        if($manyToMany){

         foreach ($manyToMany as $mTable => $tables){

             $targetKey = array_search($this->table, $tables);

             if($targetKey !== false){

                 $otherKey = $targetKey ? 0 : 1;

                 $checkBoxList = $settings::get('templateArr')['checkboxlist'];

                 if(!$checkBoxList || !in_array($tables[$otherKey], $checkBoxList)) continue;

                 if(!$this->translate[$tables[$otherKey]]){

                     if($settings::get('projectTables')[$tables[$otherKey]])
                         $this->translate[$tables[$otherKey]] = [$settings::get('projectTables')[$tables[$otherKey]]['name']];

                 }

                 $orderData = $this->createOrderData($tables[$otherKey]);


                 $insert = false;

                 if($blocks){

                     foreach ($blocks as $key => $value){

                         if(in_array($tables[$otherKey], $value)){

                             $this->blocks[$key][] = $tables[$otherKey];
                             $insert = true;
                             break;

                         }

                     }

                 }

                 if(!$insert) $this->blocks[array_keys($this->blocks)[0]][] = $tables[$otherKey];

                 $foreign = [];

                 if($this->data){

                     $res = $this->model->get($mTable, [
                         'fields' => [$tables[$otherKey] . '_' . $orderData['columns']['id_row']],
                         'where' => [$this->table . '_' . $this->columns['id_row'] = $this->data[$this->columns['id_row']]]
                     ]);

                     if($res){

                         foreach ($res as $value){

                             $foreign[] = $value[$tables[$otherKey] . '_' . $orderData['columns']['id_row']];

                         }

                     }

                 }

                 if(isset($tables['type'])){

                     $data = $this->model->get($tables[$otherKey], [
                         'fields' => [$orderData['columns']['id_row'] . ' as id', $orderData['name'], $orderData['parentId']],
                         'order' => $orderData['order']
                     ]);

                     if($data){

                         foreach ($data as $value){

                             if($tables['type'] === 'root' && $orderData['parentId']){

                                 if($value[$orderData['parentId']] === null)
                                     $this->foreignData[$tables[$otherKey]][$tables[$otherKey]]['sub'][] = $value;

                             }elseif ($tables['type'] === 'child' && $orderData['parentId']){

                                 if($value[$orderData['parentId']] !== null)
                                     $this->foreignData[$tables[$otherKey]][$tables[$otherKey]]['sub'][] = $value;

                             }else{

                                 $this->foreignData[$tables[$otherKey]][$tables[$otherKey]]['sub'][] = $value;

                             }

                             if(in_array($value['id'], $foreign))
                                 $this->data[$tables[$otherKey]][$tables[$otherKey]][] = $value['id'];

                         }

                     }

                 }elseif ($orderData['parentId']){

                     $parent = $tables[$otherKey];

                     $keys = $this->model->showForeignKeys($tables[$otherKey]);

                     if($keys){

                         foreach ($keys as $value){

                             if($value['COLUMN_NAME'] === 'parent_id'){

                                 $parent = $value['REFERENCED_TABLE_NAME'];

                                 break;

                             }

                         }

                     }

                     if($parent === $tables[$otherKey]){

                         $data = $this->model->get($tables[$otherKey], [
                             'fields' => [$orderData['columns']['id_row'] . ' as id', $orderData['name'], $orderData['parentId']],
                             'order' => $orderData['order']
                         ]);

                         if($data){

                             while (($key = key($data)) !== null){

                                  if (!$data[$key]['parent_id']){

                                      $this->foreignData[$tables[$otherKey]][$data[$key]['id']]['name'] = $data[$key]['name'];

                                      unset($data[$key]);
                                      reset($data);
                                      continue;

                                  }else{

                                      if($this->foreignData[$tables[$otherKey]][$data[$key][$orderData['parentId']]]){

                                          $this->foreignData[$tables[$otherKey]][$data[$key][$orderData['parentId']]]['sub'][$data[$key]['id']] = $data[$key];

                                          if(in_array($data[$key]['id'], $foreign))
                                              $this->data[$tables[$otherKey]][$data[$key][$orderData['parentId']]][] = $data[$key]['id'];


                                          unset($data[$key]);
                                          reset($data);
                                          continue;

                                      }else{

                                          foreach ($this->foreignData[$tables[$otherKey]] as $id => $value){

                                              $parentId = $data[$key][$orderData['parentId']];

                                              if(isset($value['sub']) && $value['sub'] && isset($value['sub'][$parentId])){

                                                  $this->foreignData[$tables[$otherKey]][$id]['sub'][$data[$key]['id']] = $data[$key];

                                                  if(in_array($data[$key]['id'], $foreign))
                                                      $this->data[$tables[$otherKey]][$id][] = $data[$key]['id'];

                                                  unset($data[$key]);
                                                  reset($data);
                                                  continue 2;

                                              }

                                          }

                                      }

                                      next($data);

                                  }

                             }

                         }

                     }else{



                     }

                     exit();

                 }

             }

         }

        }

    }
}