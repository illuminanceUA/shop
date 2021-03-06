<?php


namespace core\base\model;


abstract class BaseModelMethods
{

    protected $sqlFunction = ['NOW()'];

    protected $tableRows;

    protected function createFields($set, $table = false, $join = false)
    {
        if(array_key_exists('fields', $set) && $set['fields'] === null) return '';

        $concatTable = '';
        $aliasTable = $table;

        if(!$set['no_concat']){

            $arr = $this->createTableAlias($table);

            $concatTable = $arr['alias'] . '.';

            $aliasTable = $arr['alias'];

        }

        $fields = '';

        $join_structure = false;

        if(($join || isset($set['join_structure']) && $set['join_structure']) && $table){

            $join_structure = true;

            $this->showColumns($table);

            if(isset($this->tableRows[$table]['multi_id_row'])) $set['fields'] = [];
        }

        if(!isset($set['fields']) || !is_array($set['fields']) || !$set['fields']){

            if(!$join){

                $fields = $concatTable . '*,';

            }else{

                foreach ($this->tableRows[$aliasTable] as $key => $item){

                    if($key !== 'id_row' && $key !== 'multi_id_row'){

                        $fields .= $concatTable . $key . ' as TABLE' . $aliasTable . 'TABLE_' . $key . ',';

                    }

                }

            }

        }else{

            $id_field = false;

            foreach ($set['fields'] as $field){

                if($join_structure && !$id_field && $this->tableRows[$aliasTable] === $field){

                    $id_field = true;

                }

                if($field){

                    if($join && $join_structure){

                        if (preg_match('/^(.+)?\s+as\s+(.+)/i', $field, $matches)){

                            $fields .= $concatTable . $matches[1] . ' as TABLE' . $aliasTable . 'TABLE_' . $matches[2] . ',';

                        }else{

                            $fields .= $concatTable . $field . ' as TABLE' . $aliasTable . 'TABLE_' . $field . ',';

                        }

                    }else{

                        $fields .= $concatTable . $field . ',';

                    }

                }

            }

            if(!$id_field && $join_structure){

                if($join){

                    $fields .= $concatTable . $this->tableRows[$aliasTable]['id_row'] . ' as TABLE' . $aliasTable . 'TABLE_' . $this->tableRows[$aliasTable]['id_row'] . ',';

                }else{

                    $fields .= $concatTable . $this->tableRows[$aliasTable]['id_row'] . ',';

                }

            }

        }

        return $fields;

    }

    protected function createOrder($set, $table = false)
    {
        $table = ($table && (!isset($set['no_concat']) || !$set['no_concat']))
                  ? $this->createTableAlias($table)['alias'] . '.' : '';

        $orderBy = '';

        if(isset($set['order']) && $set['order']){

            $set['order'] = (array)$set['order'];

            $set['order_direction'] = (isset($set['order_direction']) && $set['order_direction'])
                ? (array)$set['order_direction'] : ['ASC'];

            $orderBy = 'ORDER BY ';

            $directCount = 0;

            foreach ($set['order'] as $order){

                if($set['order_direction'][$directCount]) {
                    $orderDirection = strtoupper($set['order_direction'][$directCount]);
                    $directCount++;
                }else{
                    $orderDirection = strtoupper($set['order_direction'][$directCount - 1]);
                }

                if(in_array($order, $this->sqlFunction)) $orderBy .= $order . ',';
                elseif(is_int($order)) $orderBy .= $order . ' ' . $orderDirection . ',';
                else $orderBy .= $table . $order . ' ' . $orderDirection . ',';

            }

            $orderBy = rtrim($orderBy, ',');
        }

        return $orderBy;
    }

    protected function createWhere($set, $table = false, $instruction = 'WHERE')
    {

        $table = ($table && (!isset($set['no_concat']) || !$set['no_concat']))
            ? $this->createTableAlias($table)['alias'] . '.' : '';

        $where = '';

        if(is_string($set['where'])){
            return $instruction . ' ' . trim($set['where']);
        }

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

                    if(is_string($item) && strpos($item, 'SELECT') === 0){
                        $inStr = $item;
                    }else{
                        if(is_array($item)) $tempItem = $item;
                        else $tempItem = explode(',', $item);

                        $inStr = '';

                        foreach ($tempItem as $value){
                            $inStr .= "'" . addslashes(trim($value)) . "',";
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

                    $where .= $table . $key . ' LIKE ' . "'" . addslashes($item) . "' $condition";

                }else{

                    if(strpos($item, 'SELECT') === 0){
                        $where .= $table . $key . $operand . '(' . $item . ") $condition";
                    }else{
                        $where .= $table . $key . $operand . "'" . addslashes($item) . "' $condition";
                    }
                }
            }

            $where = substr($where, 0, strrpos($where, $condition));

        }

        return $where;
    }

    protected function createJoin($set, $table, $newWhere = false)
    {

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

                $concatTable = $this->createTableAlias($key)['alias'];

                if($join) $join .= ' ';

                if(isset($item['on']) && $item['on']) {

                    if(isset($item['on']['fields']) && is_array($item['on']['fields']) && count($item['on']['fields']) === 2){

                        $joinFields = $item['on']['fields'];

                    }elseif(count($item['on']) === 2){

                        $joinFields = $item['on'];

                    }else{

                        continue;

                    }

                    if(!$item['type']) $join .= 'LEFT JOIN ';
                    else $join .= trim(strtoupper($item['type'])) . ' JOIN ';

                    $join .= $key . ' ON ';

                    if($item['on']['table']) $joinTempTable = $item['on']['table'];
                    else $joinTempTable = $joinTable;

                    $join .= $this->createTableAlias($joinTempTable)['alias'];

                    $join .= '.' . $joinFields[0] . '=' . $concatTable . '.' . $joinFields[1];

                    $joinTable = $key;

                    if($newWhere){

                        if($item['where']){
                            $newWhere = false;
                        }

                        $groupCondition = 'WHERE';

                    }else{
                        $groupCondition = $item['groupCondition'] ? strtoupper($item['groupCondition']) : 'AND';
                    }

                    $fields .= $this->createFields($item, $key, $set['join_structure']);
                    $where .= $this->createWhere($item, $key, $groupCondition);

                }
            }
        }

        return compact('fields', 'join', 'where');

    }

    protected function createInsert($fields, $files, $except) // ?????????? ?????????????? ???????????? ?? ??????????????
    {

        $insertArr = [];

        $insertArr['fields'] = '(';

        $arrayType = array_keys($fields)[0];

        if(is_int($arrayType)){

            $checkFields = false;
            $countFields = 0;

              foreach ($fields as $i => $item){

                $insertArr['values'] .= '(';

                if(!$countFields) $countFields = count($fields[$i]);

                $j = 0;

                   foreach ($item as $row => $value){

                    if($except && in_array($row, $except)) continue;

                    if(!$checkFields) $insertArr['fields'] .= $row . ',';

                    if( in_array($value, $this->sqlFunction) ){
                        $insertArr['values'] .= $value . ',';
                    }elseif ($value == 'NULL' || $value === NULL){
                        $insertArr['values'] .= "NULL" . ',';
                    }else{
                        $insertArr['values'] .= "'" . addslashes($value) . "',";
                    }

                    $j++;

                    if($j === $countFields) break;

                }

                if($j < $countFields){
                    for (; $j < $countFields; $j++){
                        $insertArr['values'] .= "NULL" . ',';
                    }
                }

                $insertArr['values'] = rtrim($insertArr['values'], ',') . '),';

                if(!$checkFields) $checkFields = true;

            }

        }else{

            $insertArr['values'] = '(';

              if($fields){

                  foreach ($fields as $row => $value){

                      if($except && in_array($row, $except)) continue;

                      $insertArr['fields'] .= $row . ',';

                      if(in_array($value, $this->sqlFunction)){
                          $insertArr['values'] .= $value . ',';
                      }elseif ($value == 'NULL' || $value === NULL){
                          $insertArr['values'] .= "NULL" . ',';
                      }else{
                          $insertArr['values'] .= "'" . addslashes($value) . "',";
                      }

                  }

              }

              if($files){

                  foreach ($files as $row => $file){

                      $insertArr['fields'] .= $row . ',';

                      if(is_array($file)) $insertArr['values'] .= "'" . addslashes(json_encode($file)) . "',";
                               else $insertArr['values'] .= "'" . addslashes($file) . "',";
                  }

              }

              $insertArr['values'] = rtrim($insertArr['values'], ',') . ')';
        }

          $insertArr['fields'] = rtrim($insertArr['fields'], ',') . ')';
          $insertArr['values'] = rtrim($insertArr['values'], ',');

          return $insertArr;
    }

    protected function createUpdate($fields, $files, $except)
    {

        $update = '';

        if($fields){

            foreach ($fields as $row => $value){

                if($except && in_array($row, $except)) continue;

                $update .= $row . '=';

                if(in_array($value, $this->sqlFunction)){
                    $update .= $value . ',';
                }elseif ($value === NULL){
                    $update .= "NULL" . ',';
                } else{
                    $update .= "'" . addslashes($value) . "',";
                }

            }

        }

        if($files){

            foreach ($files as $row => $file){

                $update .= $row . '=';

                if(is_array($file)) $update .= "'" . addslashes(json_encode($file)) . "',";
                   else $update .= "'" . addslashes($file) . "',";
            }

        }

        return rtrim($update, ',');

    }

    protected function joinStructure($res, $table)
    {
        $joinArr = [];

        $idRow = $this->tableRows[$this->createTableAlias($table)['alias']]['id_row'];

        foreach ($res as $value){

           if($value){

               if(!isset($joinArr[$value[$idRow]])) $joinArr[$value[$idRow]] = [];

               foreach ($value as $key => $item){

                   if(preg_match('/TABLE(.+)?TABLE/u', $key, $matches)){

                       $tableNameNormal = $matches[1];

                       if(!isset($this->tableRows[$tableNameNormal]['multi_id_row'])){

                           $joinIdRow = $value[$matches[0] . '_' . $this->tableRows[$tableNameNormal]['id_row']];

                       }else{

                           $joinIdRow = '';

                           foreach ($this->tableRows[$tableNameNormal]['multi_id_row'] as $multi){

                               $joinIdRow .= $value[$matches[0] . '_' . $multi];

                           }

                       }

                       $row = preg_replace('/TABLE(.+)TABLE_/u', '', $key);

                       if($joinIdRow && !isset($joinArr[$value[$idRow]]['join'][$tableNameNormal][$joinIdRow][$row])){

                           $joinArr[$value[$idRow]]['join'][$tableNameNormal][$joinIdRow][$row] = $item;

                       }

                       continue;

                   }

                   $joinArr[$value[$idRow]][$key] = $item;

               }

           }

        }

        return $joinArr;

    }

    protected function createTableAlias($table)
    {

        $arr = [];

        if(preg_match('/\s+/i', $table)){

            $table = preg_replace('/\s{2,}/i', ' ', $table);

            $tableName = explode(' ', $table);

            $arr['table'] = trim($tableName[0]);
            $arr['alias'] = trim($tableName[1]);

        }else{
            $arr['alias'] = $arr['table'] = $table;
        }

        return $arr;

    }

}