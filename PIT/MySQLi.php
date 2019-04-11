<?php
/*
 |  MySQLi      A MySQLi wrapper class with some neat / cool methods.
 |  @file       ./PIT/MySQLi.php
 |  @author     SamBrishes <sam@pytes.net>
 |  @version    0.1.0
 |
 |  @website    https://github.com/pytesNET/php-helpers
 |  @license    X11 / MIT License
 |  @copyright  Copyright © 2016 - 2019 SamBrishes, pytesNET <pytes@gmx.net>
 */
    
    namespace PIT;

    class MySQLi{
        const FETCH_ALL = "fetch_all";
        const FETCH_ARRAY = "fetch_array";
        const FETCH_ASSOC = "fetch_assoc";
        const FETCH_FIELD = "fetch_field";
        const FETCH_FIELDS = "fetch_fields";
        const FETCH_OBJECT = "fetch_object";
        const FETCH_ROW = "fetch_row";

        /*
         |  INSTANCE VARs
         */
        private $db;
        private $mode;
        private $prefix;
        
        private $ops = array(
            "<=", "<", "=", "!=", ">", ">=", 
            "LIKE", "NOT LIKE", "IN", "NOT IN", "BETWEEN", "NOT BETWEEN"
        );
        private $depth;
        private $transaction;
        
        /*
         |  DATA VARs
         */
        public $lastID;
        public $lastRows;
        public $lastQuery;
        public $lastResult;
        
        public $lastErrno;
        public $lastError;

        /*
         |  COSNTRUCTOR
         |  @since  0.1.0
         */
        public function __construct($host, $user, $pass, $name, $port = 3306, $prefix = "", $charset = "utf-8"){
            $database = @new MySQLi($host, $user, $pass, $name, $port);
            if($database->connect_errno){
                die("Database Connection failed ({$database->connect_errno}): "
                    . $database->connect_error);
            } else {
                $this->db = $database;
                $this->db->set_charset(preg_replace("#[^a-z0-9]#", "", strtolower($charset)));
                $this->mode = self::FETCH_ASSOC;
                $this->prefix = $prefix;
            }
        }

        /*
         |  DESTRUCTOR
         |  @since  0.1.0
         */
        public function __destruct(){
            if($this->db){
                $this->db->close();
            }
        }

        /*
         |  HELPER :: CHECK TABlE PREFIX
         |  @since  0.1.0
         |  
         |  @param  string  The table name as STRING.
         |
         |  @return string  The table name with the prefix as STRING.
         */
        public function prefix($table){
            if(!empty($this->prefix) && strpos($table, $this->prefix) === 0){
                return $table;
            }
            return $this->prefix . $table;
        }

        /*
         |  HELPER :: ESCAPE DATA
         |  @since  0.1.0
         |  
         |  @param  multi   The data to be escaped.
         |
         |  @return multi   The escaped data, or an empty '' string.
         */
        public function escape($data){
            if(is_bool($data) || is_null($data)){
                return ($data)? 1: 0;
            }
            
            if(is_numeric($data)){
                if(is_string($data)){
                    $test = doubleval($data);
                    $data = ($test && intval($test) != $test)? (double) $data: (int) $data;
                }
                return $data;
            }
            
            if(is_array($data) || is_object($data)){
                $data = serialize($data);
            }
            
            if(is_string($data)){
                if($data == "?"){
                    return "?";
                }
                if($data == "unix_timestamp()"){
                    return $data;
                }
                return "'".$this->db->real_escape_string($data)."'";
            }
            return "''";
        }
        
        /*
         |  HELPER :: VERSION
         |  @since  0.1.0
         |
         |  @param  string  Use "server" or "client" for the respective version.
         |  @param  bool    TRUE to convert the the version number, FALSE to do it not.
         |
         |  @return multi   The version number as INT or as STRING.
         */
        public function version($type = "server", $convert = false){
            $type = ($type == "client")? "client": "server";
            $version = $this->db->{$type."_version"};
            if($convert){
                $version = str_split(strrev((string) $version), 2);
                $version = strrev(implode(".", $version));
            }
            return $version;
        }
        
        /*
         |  BUILD :: SELECT 
         |  @since  0.1.0
         |
         |  @param  string  The table name as STRING.
         |  @param  string  The distinct to be selected as STRING.
         |  @param  string  The `as` alias for the table name as STRING.
         |
         |  @return string  The formatted SELECT clause.
         */
        protected function buildSelect($table, $distinct, $as){
            if(empty($distinct)){
                $distinct = "*";
            }
            
            $table = $this->prefix($table);
            if(!empty($as)){
                if(is_string($as)){
                    $as = array($as);
                }
                $table .= " AS `".implode("`. `", $as)."`";
            }
            return "SELECT {$distinct} FROM {$table}";
        }
        
        /*
         |  BUILD :: JOINS CLAUSEs
         |  @since  0.1.0
         |
         |  @param  multi   The 'JOIN' clause(s) as STRING or an formatted array:
         |                  :: array("JOIN table1"[, "LEFT JOIN table2"])
         |
         |  @return string  The formatted JOINS clause. 
         */
        protected function buildJoins($joins){
            if(empty($joins)){
                return "";
            }
            if(is_string($joins)){
                $joins = array($joins);
            }
            
            // Loop the joins
            foreach($joins AS &$join){
                $join = trim($join);
                if(empty($join)){
                    continue;
                }
                if(stripos($join, "JOIN") === false){
                    $join .= "INNER JOIN ";
                }
                $upper = array("join", "left", "right", "full", "inner", "outer");
                $join = str_replace($upper, array_map("strtoupper", $upper), $join);
                $join = implode(" AS ", explode(" as ", $join));
            }
            return implode(" ", array_filter($joins));
        }
        
        /*
         |  BUILD :: WHERE CLAUSEs
         |  @since  0.1.0
         |
         |  @param  multi   The 'WHERE' clause as STRING or an formatted array:
         |                  ## DEFAULT FORMAT
         |                  :: array(
         |                         "column1" => "value1",
         |                         "column2" => "value2"
         |                     )
         |                  -> WHERE column1 = 'value' AND column2 = 'value'
         |                  
         |                  ## CHANGE OPERATOR
         |                  :: array(
         |                         "column1" => array("LIKE", "value1"),
         |                         "column2" => array("!=", "value2")
         |                     )
         |                  -> WHERE column1 LIKE 'value1' AND column2 != 'value2'
         |                  
         |                  ## OR CLAUSE
         |                  :: array(
         |                         "column1" => array("<", 5),
         |                         "column2" => array(">", 10)
         |                     )
         |                  -> WHERE column1 < 10 OR column2 > 10
         |                  
         |                  ## STACK THEM
         |                  :: array(
         |                         array(
         |                             "column1" => array("LIKE", "test"),
         |                             "column2" => array("BETWEEN", 5, 10)
         |                         ),
         |                         "OR",
         |                         array(
         |                             array(
         |                                 "column1" => "test_value",
         |                             ),
         |                             array(
         |                                 "column3" => array(">", 10),
         |                                 "OR",
         |                                 "column4" => array("IN", "value1", "value2")
         |                             )
         |                     )
         |                  -> WHERE (column1 LIKE test AND column2 BETWEEN 5 AND 10) OR
         |                     ((column1 = 'test_value') AND (column3 > 10 OR column4 IN (value1, value2)))
         |
         |  @return string  The formatted WHERE clause.   
         */
        protected function buildWhere($where){
            if(empty($where)){
                return "";
            }
            if(is_string($where)){
                $string = trim($where);
                if(stripos($string, "WHERE") === 0){
                    $string = trim(substr($string, 5));
                }
                $upper = array(" and ", " or ", " like ", " between ", " not ", " in ");
                $string = str_replace($upper, array_map("strtoupper", $upper), $string);
                if(is_string($string) && !empty($string)){
                    return "WHERE {$string}";
                }
            } else {
                $this->depth = 0;
                $string = $this->buildWhereLoop(NULL, $where);
                if(is_string($string) && !empty($string)){
                    return "WHERE {$string}";
                }
            }
            return "";
        }
        
        
        /*
         |  BUILD :: WHERE LOOP
         |  @since  0.1.0
         */
        private function buildWhereLoop($column, $value){
            $_where = array();

            // Go Deeper
            if(!is_string($column)){
                if(is_array($value)){
                    $_add = array(); $_count = 0;

                    $this->depth++;
                    foreach($value AS $_column => $_value){
                        $_temp = $this->buildWhereLoop($_column, $_value);
                        if(is_string($_temp) && !empty($_temp)){
                            if($_count % 2 !== 0){
                                if($_temp !== "AND" && $_temp !== "OR"){
                                    $_add[] = "AND";
                                    $_count++;
                                }
                            }
                            $_add[] = $_temp;
                            $_count++;
                        }
                    }
                    $this->depth--;

                    if($this->depth === 0){
                        $_where[] = implode(" ", $_add);
                    } else {
                        $_where[] = "(".implode(" ", $_add).")";
                    }
                }

                if(is_string($value)){
                    if(in_array(strtoupper($value), array("AND", "OR"))){
                        $_where[] = strtoupper($value);
                    }
                }
            }

            // Do It
            if(is_string($column)){
                if(is_string($value) || is_numeric($value)){
                    $value = array("=", $value);
                }

                // Check Value
                if(is_array($value) && !empty($value)){
                    // Check Operator
                    if(count($value) === 1){
                        array_unshift($value, "=");
                    }
                    $_op = strtoupper(array_shift($value));
                    if(!in_array($_op, $this->ops)){
                        return false;
                    }

                    // Check Value
                    if($_op === "IN" || $_op === "NOT IN"){
                        $_value = array();
                        foreach($value AS $v){
                            $_value[] = $this->escape($v);
                        }
                        $_value = "(".implode(", ", $_value).")";
                    } else if($_op === "BETWEEN" || $_op == "NOT BETWEEN"){
                        if(count($value) < 2){
                            return false;
                        }
                        $_value = $this->escape($v)." AND ".$this->escape($v);
                    } else {
                        $_value = $this->escape(array_shift($value));
                    }

                    // Build
                    $_where[] = "`{$column}` {$_op} {$_value}";
                }
            }

            // Return
            if(is_array($_where) && !empty($_where)){
                return implode(" ", $_where);
            }
            return false;
        }
        
        /*
         |  BUILD :: GROUP CLAUSEs
         |  @since  0.1.0
         |
         |  @param  multi   The 'GROUP BY' clause as STRING or an formatted array:
         |                  ::  array(column1[, column2])
         |                  ->  GROUP BY column1, column2
         |
         |  @return string  The formatted GROUP BY clause. 
         */
        protected function buildGroup($group){
            if(is_string($group) && !empty($group)){
                $string = trim(str_ireplace("group by", "", $group));
                if(!empty($string)){
                    return "GROUP BY {$string}";
                }
            }
            if(is_array($group && !empty($group))){
                foreach($group AS &$column){
                    $column = trim($column);
                    if(!is_string($column) || empty($column)){
                        continue;
                    }
                }
                $group = array_filter($group);
                if(!empty($group)){
                    return "GROUP BY " . implode(" ", $group);
                }
            }
            return "";
        }
        
        /*
         |  BUILD :: ORDER (BY) CLAUSEs
         |  @since  0.1.0
         |
         |  @param  multi   The 'ORDER BY' clause as STRING or an formatted array:
         |                  ::  array(column1[, column2])
         |                  ->  ORDER BY column1 ASC[, column2 ASC]
         |                  ::  array(column1 => ASC, column2 => DESC)
         |                  ->  ORDER BY column1 ASC, column2 DESC
         |
         |  @return string  The formatted ORDER BY clause. 
         */
        protected function buildOrder($order){
            if(is_string($order) && !empty($order)){
                $string = trim(str_ireplace("order by", "", $order));
                if(!empty($string)){
                    return "ORDER BY {$string}";
                }
            }
            if(is_array($order && !empty($order))){
                $string = array();
                foreach($order AS $column => $dir){
                    if(!is_string($column) || empty($column)){
                        $column = $dir;
                        $dir    = "ASC";
                    }
                    if(!is_string($column) || empty($column)){
                        continue;
                    }
                    $string[] = "{$column} " . ($dir == "DESC")? "DESC": "ASC";
                }
                if(!empty($string)){
                    return "ORDER BY " . implode(" ", $string);
                }
            }
            return "";
        }
        
        /*
         |  BUILD :: LIMIT | OFFSET CLAUSEs
         |  @since  0.1.0
         |
         |  @param  multi   The 'LIMIT' clause as STRING, or the limit number as INT.
         |  @param  multi   The 'OFFSET' clause as STRING, or the offset number as INT.
         |
         |  @return string  The formatted LIMIT | OFFSET clause. 
         */
        protected function buildLimit($limit, $offset){
            if(empty($limit) && empty($offset)){
                return "";
            }
            
            // Sanitize Data
            if(is_string($limit) && stripos("limit", $limit)){
                $limit = trim(substr($limit, 5));
            } else if(!is_numeric($limit)){
                 $limit = "18446744073709551615";
            }
            if(is_string($offset) && stripos("offset", $offset)){
                $offset = trim(substr($offset, 6));
            } else if(!is_numeric($offset)){
                unset($offset);
            }
            
            // Return Data
            $return = array("LIMIT {$limit}", (isset($offset)? "OFFSET {$offset}": ""));
            return implode(" ", array_filter($return));
        }

        /*
         |  DB :: QUERY
         |  @since  0.1.0
         |
         |  @param  string  The complete SQL Query as STRING.
         |  @param  array   The prepared SQL Query statements as ARRAY.
         |  @param  bool    TRUE to send a buffered request (MYSQL_STORE_RESULT),
         |                  FALSE to send a unbuffered request (MYSQL_USE_RESULT).
         |
         |  @return multi   A BOOLEAN state or an mysqli_query object, depending on $query.
         */
        public function query($query, $prepare = array(), $buffer = true){
            if(empty($this->db)){
                return false;
            }
            $this->lastID = 0;
            $this->lastRows = 0;
            $this->lastQuery = $query;
            $this->lastResult = false;
            
            $this->lastErrno = 0;
            $this->lastError = NULL;
            
            if(empty($prepare)){
                $result = $this->db->query($query, ($buffer)? MYSQLI_STORE_RESULT: MYSQLI_USE_RESULT);
                if(!$result){
                    $this->lastErrno = $this->db->errno;
                    $this->lastError = $this->db->error;
                } else {
                    $this->lastID    = $this->db->insert_id;
                    $this->lastRows  = $this->db->affected_rows;
                }
            } else {
                $result = false;
                if($stmt = $this->db->prepare($query)){
                    $params = array("");
                    foreach($prepare AS $data){
                        if(is_array($data) || is_object($data)){
                            $data = serialize($data);
                        }
                        if(is_int($data)){
                            $params[0] .= "i";
                            $params[]   = &$data;
                        } else if(is_float($data)){
                            $params[0] .= "d";
                            $params[]   = &$data;
                        } else if(is_string($data)){
                            $params[0] .= "s";
                            $params[]   = &$data;
                        }
                    }
                    call_user_func_array(array($stmt, "bind_param"), $params);
                    
                    // Query
                    $stmt->execute();
                    
                    if($stmt->errno){
                        $this->lastErrno = $stmt->errno;
                        $this->lastError = $stmt->error;
                    } else {
                        if($stmt->affected_rows == -1){
                            $result = $stmt->get_result();
                            $this->lastID = 0;
                        } else {
                            $result = true;
                            $this->lastID = $stmt->insert_id;
                        }
                        $this->lastRows = $stmt->affected_rows;
                    }
                    
                    $stmt->close();
                }
            }
            
            // Return Result
            $this->lastResult = $result;
            return $result;
        }
        
        /*
         |  DB :: FETCH
         |  @since  0.1.0
         |
         |  @param  multi   The respective query to fetch or NULL for the current / last query.
         |  @param  bool    TRUE if the queried object is a single element, FALSE if not.
         |  @param  multi   The respective fetch mode or NULL for the default one.
         |  @param  multi   An additional argument for the fetch method, or NULL.
         |
         |  @return multi   An array or object with the respective result, FALSE on failure.
         */
        public function fetch($query = NULL, $single = true, $mode = NULL, $arg = NULL){
            $mode = defined("self::" . strtoupper($mode))? $mode: $this->mode;
            if(is_a($query, "mysqli_result")){
                if($single == true){
                    $result = (!$arg)? $query->{$mode}(): $query->{$mode}($rg);
                } else {
                    $result = array();
                    while($row = ((!$arg)? $query->{$mode}(): $query->{$mode}($arg))){
                        $result[] = $row;
                    }
                }
                return $result;
            }
            return false;
        }
        
        /*
         |  DB :: INSERT
         |  @since  0.1.0
         |
         |  @param  string  The database table name as STRING.
         |  @param  multi   The insert data query as STRING: "(column, ...) VALUES (value, ...)"
         |                  or as ARRAY: array("column" => "value").
         |  @param  array   The prepared query statements as ARRAY.
         |
         |  @return multi   The row ID as INT (or TRUE if no row ID could be fetched), on success.
         |                  0 if no row could be inserted, FALSE when everything went wrong.
         */
        public function insert($table, $data, $prepare = array()){
            if(empty($this->db)){
                return false;
            }
            $table = $this->prefix($table);
            
            // Create SQL
            $sql = "INSERT INTO {$table} ";
            if(is_array($data) && !empty($data)){
                foreach($data AS $key => &$value){
                    $value = $this->escape($value);
                }
                $sql .= "(`".implode("`, `", array_keys($data))."`) VALUES ";
                $sql .= "(".implode(", ", array_values($data)).");";
            } else if(is_string($data) && strpos($data, "VALUES")){
                $sql .= trim($data);
            } else {
                return false;
            }
            
            // Return
            $result = $this->query($sql, $prepare);
            if($result){
                return ($this->lastID)? $this->lastID: 1;
            }
            return 0;
        }
        
        /*
         |  DB :: UPDATE
         |  @since  0.1.0
         |
         |  @param  string  The database table name as STRING.
         |  @param  multi   The updated data query as STRING: "column = value"
         |                  or as ARRAY: array("column" => "value").
         |  @param  string  The where clause as STRING.
         |  @param  array   The prepared query statements as ARRAY.
         |
         |  @return multi   The affected rows on success, 0 if no row has been updated.
         |                  FALSE when everything went wrong.
         */
        public function update($table, $data, $where, $prepare = array()){
            if(empty($this->db)){
                return false;
            }
            $table = $this->prefix($table);
            
            // Create SQL
            $sql = "UPDATE {$table} SET ";
            if(is_array($data) && !empty($data)){
                foreach($data AS $key => &$value){
                    $value = "`{$key}`=".$this->escape($value);
                }
                $sql .= implode(", ", array_values($data))." ";
            } else if(is_string($data)){
                $data = trim($data);
                if(stripos($data, "SET ") === 0){
                    $data = ltrim(substr($data, 3));
                }
                $sql .= $data." ";
            } else {
                return false;
            }
            
            if(($where = $this->buildWhere($where)) !== ""){
                $sql .= $where . ";";
            } else {
                return false;
            }
            
            // Return
            $result = $this->query($sql, $prepare);
            if($result){
                return $this->lastRows;
            }
            return false;
        }
        
        /*
         |  DB :: DELETE
         |  @since  0.1.0
         |
         |  @param  string  The database table name as STRING.
         |  @param  string  The where clause as STRING.
         |  @param  array   The prepared query statements as ARRAY.
         |
         |  @return multi   The affected rows as INT of FALSE on failure.
         */
        public function delete($table, $where, $prepare = array()){
            if(empty($this->db)){
                return false;
            }
            $table = $this->prefix($table);
            
            // Create SQL
            $sql = "DELETE FROM {$table} ";
            if(($where = $this->buildWhere($where)) !== ""){
                $sql .= $where . ";";
            } else {
                return false;
            }
            
            // Return
            if($this->query($sql, $prepare)){
                return $this->lastRows;
            }
            return 0;
        }
        
        /*
         |  DB :: SELECTOR
         |  @since  0.1.0
         */
        public function selector($table, $clauses = array(), $prepare = array(), $mode = NULL){
            if(empty($this->db)){
                return false;
            }
            
            // Build and Return Query
            $sql = implode(" ", array(
                $this->buildSelect($table,
                    isset($clauses["distinct"])? $clauses["distinct"]: "*",
                    isset($clauses["as"])? $clauses["as"]: NULL
                ),
                $this->buildJoins(isset($clauses["joins"])? $clauses["joins"]: NULL),
                $this->buildWhere(isset($clauses["where"])? $clauses["where"]: NULL),
                $this->buildGroup(isset($clauses["group"])? $clauses["group"]: NULL),
                $this->buildOrder(isset($clauses["order"])? $clauses["order"]: NULL),
                $this->buildLimit(
                    isset($clauses["limit"])? $clauses["limit"]: NULL, 
                    isset($clauses["offset"])? $clauses["offset"]: NULL
                )
            )).";";
            
            // Query
            if(($result = $this->query($sql, $prepare)) !== false){
                return $this->fetch($result, false, $mode);
            }
            return false;
        }
        
        /*
         |  DB :: SELECT
         |  @since  0.1.0
         |
         |  @param  string  The database table name as STRING.
         |  @param  string  The distinct clause as STRING.
         |  @param  string  The where clause as STRING.
         |  @param  string  The order by clause as STRING.
         |  @param  int     The limit number as INTEGER, 0 to get all.
         |  @param  int     The offset number as INTEGER.
         |  @param  array   The prepared query statements as ARRAY.
         |
         |  @return multi   The result as ARRAY, or FALSE on failure.
         */
        public function select($table, $dist = "*", $where = "", $order = "", $limit = 0, $offset = 0, $prepare = array()){
            if(empty($this->db)){
                return false;
            }
            
            // Build SQL
            $sql = implode(" ", array(
                $this->buildSelect($table, $dist, NULL),
                $this->buildWhere(empty($where)? NULL: $where),
                $this->buildOrder(empty($order)? NULL: $order),
                $this->buildLimit($limit, $offset)
            )).";";
            
            // Query
            if(($result = $this->query($sql, $prepare)) !== false){
                return $this->fetch($result, false);
            }
            return false;
        }
        
        /*
         |  DB :: SINGLE
         |  @since  0.1.0
         |
         |  @param  string  The database table name as STRING.
         |  @param  string  The distinct clause as STRING.
         |  @param  string  The where clause as STRING.
         |  @param  int     The offset number as INTEGER.
         |  @param  array   The prepared query statements as ARRAY.
         |
         |  @return multi   The single result as ARRAY, FALSE on failure.
         */
        public function single($table, $dist = "*", $where = "", $offset = 0, $prepare = array()){
            if(empty($this->db)){
                return false;
            }
            
            // Build SQL
            $sql = implode(" ", array(
                $this->buildSelect($table, $dist, NULL),
                $this->buildWhere(empty($where)? NULL: $where),
                $this->buildLimit(1, $offset)
            )).";";
            
            // Query amd Return
            $result = $this->query($sql, $prepare);
            if($result){
                return $this->fetch($result);
            }
            return false;
        }
        
        /*
         |  DB :: COUNT
         |  @since  0.1.0
         |
         |  @param  string  The database table name as STRING.
         |  @param  string  The where clause as STRING.
         |  @param  array   The prepared query statements as ARRAY.
         |
         |  @return multi   The count number as INTEGER, FALSE on failure
         */
        public function count($table, $where, $prepare = array()){
            if(empty($this->db)){
                return false;
            }
            
            // Build SQL
            $sql = implode(" ", array(
                $this->buildSelect($table, "COUNT(*)", NULL),
                $this->buildWhere(empty($where)? NULL: $where)
            )).";";
            
            // Query amd Return
            $result = $this->query($sql, $prepare);
            if($result){
                $count = $this->fetch($result, true, self::FETCH_ALL, MYSQLI_NUM)[0];
                return (int) ((count($count) == 1)? $count[0]: 0);
            }
            return false;
            
        }
        
        /*
         |  DB :: EXISTS
         |  @since  0.1.0
         |
         |  @param  string  The table name as STRING.
         |
         |  @return multi   TRUE if the table exists, FALSE if not, NULL on failure.
         */
        public function exists($table){
            if(!is_string($table)){
                return false;
            }
            $table = $this->escape($this->prefix($table));
            
            // Query
            if(($result = $this->query("SHOW TABLES LIKE {$table};")) !== false){
                return $result->num_rows > 0;
            }
            return false;
        }
        
        /*
         |  HANDLE :: CHANGE FETCH MODE
         |  @since  0.1.0
         |
         |  @param  multi   The respective fetch mode, or NULL to reset.
         |
         |  @return multi   TRUE if the fetch mode exists, FALSE if not.
         */
        public function fetchMode($mode = NULL){
            if(defined("self::" . strtoupper($mode))){
                $this->mode = $mode;
                return true;
            } else if(empty($mode)){
                $this->mode = self::FETCH_ASSOC;
                return true;
            }
            return false;
        }
        
        /*
         |  HANDLE :: BEGIN TRANSACTION
         |  @since  0.1.0
         |
         |  @return bool    TRUE if the transaction begans, FALSE if not.
         */
        public function begin(){
            if(empty($this->db)){
                return false;
            }

            // Finish last Transaction
            if($this->transaction){
                $this->finish();
            }

            // Start a new Transaction
            if($this->db->begin_transaction()){
                $this->transaction = true;
                return true;
            }
            return false;
        }

        /*
         |  HANDLE :: FINISH (COMMIT) TRANSACTION
         |  @since  0.1.0
         |
         |  @return bool    TRUE if the transaction finished, FALSE if not.
         */
        public function finish(){
            if(empty($this->db) || !$this->transaction){
                return false;
            }

            if($this->db->commit()){
                $this->transaction = false;
                return true;
            }
            return false;
        }
        public function commit(){
            return $this->finish();
        }

        /*
        |   HANDLE :: RESET LAST TRANSACTION
        |   @since  0.1.0
        |
        |   @return bool    TRUE if the transaction could be reseted, FALSE if not.
        */
        public function reset(){
            if(empty($this->db) || !$this->transaction){
                return false;
            }
            return $this->db->rollback();
        }
        public function rollback(){
            return $this->reset();
        }
    }
