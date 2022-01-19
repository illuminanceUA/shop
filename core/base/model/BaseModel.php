<?php

namespace core\base\model;

use core\base\controller\Singleton;
use core\base\exceptions\DbException;

class BaseModel
{
    use Singleton;

    protected $db;

    private function __construct() // осуществляет подключение к базе данных
    {
        $this->db = new \mysqli(HOST, USER, PASS, DB_NAME);

        if($this->db->connect_error) {
            throw new DbException('Ошибка подключения к базе данных: '
                . $this->db->connect_errno . ' ' . $this->db->connect_error);
        }

        $this->db->query("SET NAMES UTF8");
    }

  final public function query($query, $crud = 'r', $return_id = false){

     $result = $this->db->query($query);

     if($this->db->affected_rows === -1){
         throw new DbException('Ошибка в SQL запросе: '
         . $query . ' - ' . $this->db->errno . ' ' . $this->db->error);
     }

      switch ($crud){

          case 'r':

              if($result->num_rows) {

                $res = [];

                for($i = 0; $i < $result->num_rows; $i++){
                    $res[] = $result->fetch_assoc();
                }

                return $res;

              }

             return false;

              break;

          case 'c':

              if($return_id) return  $this->db->insert_id;

              return true;

              break;

          default:

              return true;

              break;
      }

    }

    /**
     * @param $table
     * @param array $set
     * 'fields' => ['id', 'name'],
     * 'where' => ['name' => 'masha, olya, sveta', 'surname' => 'Sergeevna', 'fio' => 'Andrey', 'car' => 'Porshe', 'color' => $color ],
     * 'operand' => ['IN', 'LIKE%', '<>', '=', 'NOT IN'],
     * 'condition' => ['AND', 'OR'],
     * 'order' => [1, 'name'],
     * 'order_direction' => ['DESC'],
     * 'limit' => '1',
     *    'join' => [
     *    [
     *           'table' => 'join_table1',
     *           'fields' => ['id as j_id', 'name as j_name'],
     *           'type' => 'left',
     *           'where' => ['name' => 'sasha'],
     *            'operand' => ['='],
     *            'condition' => ['OR'],
     *            'on' => ['id', 'parent_id'],
     *            'group_condition' => 'AND'
     *         ],
     *            'join_table2' => [
     *                 'fields' => ['id as j2_id', 'name as j2_name'],
     *                 'type' => 'left',
     *                 'where' => ['name' => 'sasha'],
     *                 'operand' => ['='],
     *                 'condition' => ['AND'],
     *                 'on' => [
     *                 'table' => 'teachers',
     *                 'fields' => ['id', 'parent_id']
     *                      ]
     *                ]
     *            ]
     */

        final public function get($table, $set = []){

            $fields = $this->createFields($table, $set);

            $order = $this->createOrder($table, $set);

            $where = $this->createWhere($table, $set);

            if(!$where) $newWhere = true;
               else $newWhere = false;

            $joinArr = $this->createJoin($table, $set, $newWhere);

            $fields .= $joinArr['fields'];
            $join = $joinArr['join'];
            $where .= $joinArr['where'];

            $fields = rtrim($fields, ',');

            $limit = $set['limit'] ? $set['limit'] : '';

            $query = "SELECT $fields FROM $table $join $where $order $limit";

            return $this->query($query);

        }

        protected function createFields($table = false, $set) {
           $set['fields'] = (is_array($set['fields']) && !empty($set['fields']))
                                ? $set['fields'] : ['*'];

           $table = $table ? $table . '.' : '';

           $fields = '';


           foreach ($set['fields'] as $field) {
               $fields .= $table . $field . ',';
           }

           return $fields;

        }

        protected function createOrder($table = false, $set) {

            $table = $table ? $table . '.' : '';

            $orderBy = '';

            if(is_array($set['order']) && !empty($set['order'])){

                $set['order_direction'] = (is_array($set['order_direction']) && !empty($set['order_direction']))
                    ? $set['order_direction'] : ['ASC'];

                $orderBy = 'ORDER BY ';

                $directCount = 0;

                foreach ($set['order'] as $order){
                    if($set['order_direction'][$directCount]) {
                        $orderDirection = strtoupper($set['order_direction'][$directCount]);
                        $directCount++;
                    }else{
                        $orderDirection = strtoupper($set['order_direction'][$directCount - 1]);
                    }
                   if(is_int($order)) $orderBy .= $order . ' ' . $orderDirection . ',';
                      else  $orderBy .= $table . $order . ' ' . $orderDirection . ',';
                }
                $orderBy = rtrim($orderBy, ',');
            }

            return $orderBy;
        }

        protected function createWhere($table = false, $set, $instruction = 'WHERE') {

            $table = $table ? $table . '.' : '';

            $where = '';

            if(is_array($set['where']) && !empty($set['where'])){

                $set['operand'] = (is_array($set['operand']) && !empty($set['operand'])) ? $set['operand'] : ['='];
                $set['condition'] = (is_array($set['condition']) && !empty($set['condition'])) ? $set['condition'] : ['AND'];

                $where = $instruction;

                $operandCount = 0;
                $conditionCount = 0;

                foreach ($set['where'] as $key => $item){

                    $where .= ' ';

                    if($set['operand'][$operandCount]){
                        $operand = $set['operand'][$operandCount];
                        $operandCount++;
                    }else{
                        $operand = $set['operand'][$operandCount - 1];
                    }

                    if($set['condition'][$conditionCount]){
                        $condition = $set['condition'][$conditionCount];
                        $conditionCount++;
                    }else{
                        $condition = $set['condition'][$conditionCount - 1];
                    }

                   if($operand === 'IN' || $operand === 'NOT IN'){

                       if(is_string($item) && strpos($item, 'SELECT')){
                           $inStr = $item;
                       }else{
                            if(is_array($item)) $tempItem = $item;
                            else $tempItem = explode(',', $item);

                            $inStr = '';

                            foreach ($tempItem as $value){
                                $inStr .= "'" . trim($value) . "',";
                            }
                       }

                       $where .= $table . $key . ' ' . $operand . ' (' . trim($inStr, ',') . ') ' . $condition;

                   }elseif (strpos($operand, 'LIKE') !== false){

                       $likeTemplate = explode('%', $operand);

                       foreach ($likeTemplate as $ltKey => $lt){
                            if(!$lt){
                                if(!$ltKey){
                                    $item = '%' . $item;
                                }else{
                                    $item .= '%';
                                }
                            }
                       }

                       $where .= $table . $key . ' LIKE ' . "'" . $item . "' $condition";

                   }else{

                       if(strpos($item, 'SELECT') === 0){
                           $where .= $table . $key . $operand . '(' . $item . ") $condition";
                       }else{
                           $where .= $table . $key . $operand . "'" . $item . "' $condition";
                       }
                   }
                }

                $where = substr($where, 0, strrpos($where, $condition));

            }

            return $where;
        }

        protected function createJoin($table, $set, $newWhere = false){

          $fields = '';
          $join = '';
          $where = '';

          if($set['join']){
               $joinTable = $table;

              foreach ($set['join'] as $key => $item) {

                  if(is_int($key)){
                      if(!$item['table']) continue;
                        else $key = $item['table'];
                  }
                  if($join) $join .= ' ';

                  if($item['on']) {
                      $joinFields = [];

                      switch (2){

                          case count($item['on']['fields']):
                              $joinFields = $item['on']['fields'];
                              break;

                          case  count($item['on']):
                              $joinFields = $item['on'];
                              break;

                          default:
                              continue 2;
                              break;
                      }

                      if(!$item['type']) $join .= 'LEFT JOIN';
                          else $join .= trim(strtoupper($item['type'])) . ' JOIN ';

                      $join .= $key . ' ON ';

                      if($item['on']['table']) $join .= $item['on']['table'];
                          else $join .= $joinTable;

                      $join .= '.' . $joinFields[0] . '=' . $key . '.' . $joinFields[0];

                      $joinTable = $key;

                      if($newWhere){
                          if($item['where']){
                              $newWhere = false;
                          }

                          $groupCondition = 'WHERE';
                      }else{
                          $groupCondition = $item['groupCondition'] ? strtoupper($item['groupCondition']) : 'AND';
                      }

                      $fields .= $this->createFields($key, $item);
                      $where .= $this->createWhere($key, $item, $groupCondition);

                  }
               }
          }

          return compact('fields', 'join', 'where');

        }

}