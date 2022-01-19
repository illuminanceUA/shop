<?php

namespace core\base\model;

use core\base\controller\Singleton;
use core\base\exceptions\DbException;

class BaseModel extends BaseModelMethods
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

    /**
     * @param $query
     * @param string $crud = r - SELECT / c - INSERT / u - UPDATE / d - DELETE
     * @param false $return_id
     * @return array|bool|int|string
     * @throws DbException
     */

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

            $fields = $this->createFields($set, $table);

            $order = $this->createOrder($set, $table);

            $where = $this->createWhere($set, $table);

            if(!$where) $newWhere = true;
               else $newWhere = false;

            $joinArr = $this->createJoin($set, $table, $newWhere);

            $fields .= $joinArr['fields'];
            $join = $joinArr['join'];
            $where .= $joinArr['where'];

            $fields = rtrim($fields, ',');

            $limit = $set['limit'] ? 'LIMIT ' . $set['limit'] : '';

            $query = "SELECT $fields FROM $table $join $where $order $limit";

            return $this->query($query);

        }

    /**
     * @param $table - таблица для вставки данных
     * @param array $set - массив параметров
     * fields => [поле => значение]; если не указан, то обрабатывается $_POST[поле => значение]
     * разрешена передача например NOW() в качестве Mysql функции обычно строкой
     * files => [поле => значение]; можно подать массив вида [поле => [массив значений]]
     * except => ['исключение 1', 'исключение 2'] - исключает данные элементы массива из добавления в запрос
     * return_id => true|false - возвращать или нет индентификатор вставленной запси
     * @return mixed
     */

        final public function add($table, array $set){

           $set['fields'] = (is_array($set['fields']) && !empty($set['fields'])) ? $set['fields'] : false;
           $set['files'] = (is_array($set['files']) && !empty($set['files'])) ? $set['files'] : false;
           $set['return_id'] = $set['return_id'] ? true : false;
           $set['except'] = (is_array($set['except']) && !empty($set['except'])) ? $set['except'] : false;

           $insertArr = $this->createInsert($set['fields'], $set['files'], $set['except']);

           if($insertArr){
               $query = "INSERT INTO $table ({$insertArr['fields']}) VALUES ({$insertArr['values']}))";

               return $this->query($query, 'c', $set['return_id']);
           }

           return false;


        }

}