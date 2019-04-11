<?php
/*
 |  PDO         A PDO wrapper class with some neat / cool methods.
 |  @file       ./PIT/PDO/PDO.php
 |  @author     SamBrishes <sam@pytes.net>
 |  @version    0.1.0
 |
 |  @website    https://github.com/pytesNET/php-helpers
 |  @license    X11 / MIT License
 |  @copyright  Copyright © 2016 - 2019 SamBrishes, pytesNET <pytes@gmx.net>
 */

    namespace PIT\PDO;

    class PDO{
        /*
         |    INSTANCE VARs
         */
        private $db;
        private $ops = array(
            "<=", "<", "=", "!=", ">", ">=",
            "LIKE", "NOT LIKE", "IN", "NOT IN", "BETWEEN", "NOT BETWEEN"
        );
        private $depth;
        private $prefix;

        /*
         |    DATA VARs
         */
        public $lastID;
        public $lastRows;
        public $lastQuery;
        public $lastResult;

        public $lastErrno;
        public $lastError;

        /*
         |  CONSTRUCTOR
         |  @since  0.1.0
         |
         |  @param  string  The MySQL / MariaDB hostname.
         |  @param  string  The MySQL / MariaDB database.
         |  @param  string  The MySQL / MariaDB username.
         |  @param  string  The MySQL / MariaDB password.
         |  @param  string  The MySQL / MariaDB dbprefix.
         |  @param  string  The MySQL / MariaDB charset (utf8 recommended).
         */
        public function __construct($host, $name, $user, $pass, $prefix = "", $charset = "utf8"){
            $string  = "mysql:host={$host};dbname={$name};charset=";
            $string .= preg_replace("#[^a-z0-9]#", "", strtolower($charset)).";";
            $options = array(
                PDO::ATTR_ERRMODE               => PDO::ERRMODE_WARNING,
                PDO::ATTR_DEFAULT_FETCH_MODE    => PDO::FETCH_OBJ
            );

            try {
                $this->db = new PDO($string, $user, $pass, $options);
                $this->prefix = $prefix;
            } catch (PDOException $e) {
                die("Database Connection failed: " . $e->getMessage());
            }
        }

        /*
         |  DESTRUCTOR
         |  @since  0.1.0
         */
        public function __destruct(){
            $this->db = null;
            $this->lastID = null;
            $this->lastRows = null;
            $this->lastQuery = null;
            $this->lastResult = null;
            $this->lastErrno = null;
            $this->lastError = null;
        }

        /*
         |  HELPER :: PREFIX TABLE NAME
         |  @since  0.1.0
         |
         |  @param  string  The (may unprefixed) table name.
         |
         |  @return string  The prefixed table name.
         */
        public function prefix($table){
            if(!empty($this->prefix) && strpos($table, $this->prefix) === false){
                return $this->prefix . $table;
            }
            return $table;
        }

        /*
         |  HELPER :: QUOTE DATA
         |  @since  0.1.0
         |
         |  @param  string  The unquoted and undescaped string.
         |
         |  @return string  The quoted and escaped string.
         */
        public function quote($string){
            return !is_string($string)? $string: $this->db->quote($string);
        }

        /*
         |  PREPARE :: DATA VALUE
         |  @since  0.1.0
         |
         |  @param  multi   The respective column value.
         |                  :: Converts bool and null into integer (0 or 1)
         |                  :: Converts "numerics" into double (float) or integer.
         |                  :: Converts arrays and object into serialized strings.
         |                  :: Quotes each string with PDO::quote.
         |  @param  array   The prepared statements as ARRAY.
         |
         |  @return multi   The "sanitized" data or '' on failure.
         */
        public function data($value, $prepare = array()){
            if(is_bool($value) || is_null($value)){
                return ($value)? 1: 0;
            }
            if(is_numeric($value)){
                $test = doubleval($value);
                if($test && intval($test) != $test){
                    return (double) $value;
                }
                return (int) $value;
            }
            if(is_array($value) || is_object($value)){
                $value = serialize($value);
            }
            if(is_string($value)){
                if(strpos($value, ":") === 0 && array_key_exists($value, $prepare)){
                    return $value;
                }
                return $this->quote($value);
            }
            return "''";
        }

        /*
         |  PREPARE :: WHERE
         |  @since  0.1.0
         |
         |  @param  multi   A "WHERE <...>" formatted STRING or a special-formatted ARRAY.
         |  @param  array   The key => value paired prepare values.
         |
         |  @return string  A valid WHERE clause or an empty STRING on failure.
         */
        public function where($data, $prepare = array()){
            if(is_string($data) && stripos(trim($data), "WHERE") === 0){
                return "WHERE " . trim(substr(trim($data), 5));
            } else if(!is_array($data)){
                return "";
            }

            $where = "";
            $this->whereLoop(0, $data, $prepare, $where);
            if(!empty($where)){
                return "WHERE {$where}";
            }
            return "";
        }
        private function whereLoop($column, $value, $prepare, &$where){
            if(!is_string($column)){
                if(is_array($value)){
                    $glue = "OR";

                    $this->depth = true;
                    foreach($value AS $col => $val){
                        $temp = $this->whereLoop($col, $val, $prepare, $where);
                        if(is_numeric($col)){
                            continue;
                        }
                        if(is_array($temp)){
                            list($glue, $temp) = $temp;
                        }

                        // Glue It
                        if(!empty($where)){
                            $where .= " {$glue} ";
                        }
                        $glue = "AND";

                        // String It
                        if($this->depth){
                            $where .= "(";
                            $this->set_depth = false;
                        }
                        $where .= " {$temp} ";
                    }
                    $multi  = substr_count($where, "(")-substr_count($where, ")");
                    $where .= str_repeat(")", $multi);
                }
                return $where;
            }

            // Check Column
            if(strpos($column, "+") === 0){
                $glue = "AND";
                $column = substr($column, 1);
            } else if(strpos($column, "-") === 0){
                $glue = "OR";
                $column = substr($column, 1);
            } else if(strpos($column, "~") === 0){
                $glue = "XOR";
                $column = substr($column, 1);
            }

            // Split Value
            if(!is_array($value)){
                $value = array($value);
            }
            list($op, $val) = array_pad($value, -2, "=");

            // Check Operator
            $op = trim(strtoupper($op));
            if(!in_array($op, $this->ops)){
                $op = "=";
            }

            // Check Value
            switch($op){
                case "IN":          //@fallthrough
                case "NOT IN":
                    $val = explode(",", $val);
                    foreach($val AS &$v){
                        $v = trim($v);
                        if(strpos($val, ":") != "0" && !array_key_exists($val, $prepare)){
                            $v = $this->data($v);
                        }
                    }
                    $val = "(".implode(",", $v).")";
                    break;
                case "BETWEEN":     //@Fallthrough
                case "NOT BETWEEN":
                    $val = trim($val);
                    if(preg_match("#^[a-z0-9\-\:\.]+ and [a-z0-9\-\:\.]+$#i", $val)){
                        $val = str_replace("and", "AND", $val);
                        break;
                    }
                    $val = explode(",", $val);
                    if($val == 2){
                        $val = trim($val[0]) . " AND " . trim($val[1]);
                        break;
                    }
                    $val = false;
                    break;
                default:
                    if(!(strpos($val, ":") === 0 && array_key_exists($val, $prepare))){
                        $val = $this->data($val);
                    }
                    break;
            }
            if($val == false){
                return false;
            }

            // Return Data
            if(isset($glue)){
                return array((isset($glue)? $glue: "AND"), "`{$column}` {$op} {$val}");
            }
            return "`{$column}` {$op} {$val}";
        }

        /*
         |  PREPARE :: ORDER
         |  @since  0.1.0
         |
         |  @param  multi   A "ORDER BY <column> <dir>" formatted STRING or a "(<column> => <dir>)"
         |                  formatted ARRAY.
         |
         |  @return string  The sanitized and correct ORDER BY clause.
         */
        public function order($data){
            if(is_string($data) && stripos(trim($data), "ORDER BY") === 0){
                return "ORDER BY " . trim(substr(trim($data), 8));
            } else if(!is_array($data)){
                return "";
            }

            // Build
            $string = array();
            foreach($data AS $column => $dir){
                if(stripos($dir, "desc")){
                    $string[] = "`{$column}` DESC";
                } else {
                    $string[] = "`{$column}` ASC";
                }
            }
            return "ORDER BY " . implode(", ", $string);
        }

        /*
         |  PREPARE :: LIMIT / OFFSET
         |  @since  0.1.0
         |
         |  @param  multi   A single number as limit, a "LIMIT 0-9[ OFFSET 0-9]" formatted STRING
         |                  or a "(limit[, offset])" formatted ARRAY.
         |
         |  @return string  The sanitized and correct LIMIT / OFFSET clause.
         */
        public function limit($data){
            if(is_string($data) && preg_match("#^limit [0-9]+(?: offset [0-9]+)?$#i", trim($data))){
                return trim(strtoupper($data));
            } else if(is_numeric($data) && (int) $data > 0){
                return "LIMIT " . ((int) $data);
            } else if(!is_array($data)){
                return "";
            }
            list($limit, $offset) = array_pad($data, 2, NULL);

            // Build
            $string = "";
            if(is_numeric($limit) && $limit > 0){
                $string .= "LIMIT " . ((int) $limit);
            } else {
                $string .= "LIMIT 18446744073709551615"; // ref https://dev.mysql.com/doc/refman/5.5/en/select.html
            }
            if(is_numeric($offset) && $offset > 0){
                $string .= " OFFSET " . ((int) $offset);
            }
            return $string;
        }

        /*
         |  CORE :: DO SQL QUERY
         |  @since  0.1.0
         |
         |  @param  string  The SQL Query to execute.
         |  @param  array   The key => value paired prepare values.
         |
         |  @return multi   The PDOStatement object or FALSE on failure.
         */
        public function query($sql, $prepare = array()){
            if(empty($prepare)){
                @$stmt = $this->db->query($sql);
            } else {
                $stmt = $this->db->prepare($sql);
                @$stmt->execute($prepare);
            }

            if($stmt === false){
                $this->lastID = 0;
                $this->lastRows = 0;
                $this->lastQuery = $sql;
                $this->lastResult = false;

                $this->lastErrno = $this->db->errorCode();
                $this->lastError = $this->db->errorInfo();
                return false;
            }

            if($stmt->errorCode() != "00000"){
                $this->lastID = 0;
                $this->lastRows = 0;
                $this->lastQuery = $sql;
                $this->lastResult = false;

                $error = $stmt->errorInfo();
                $this->lastErrno = (string) array_shift($error) .".". (string) array_shift($error);
                $this->lastError = array_pop($error);
                return false;
            }

            $this->lastID = $this->db->lastInsertID();
            $this->lastRows = $stmt->rowCount();
            $this->lastQuery = $sql;
            $this->lastResult = $stmt;
            return $stmt;
        }

        /*
         |  CORE :: FETCH RESULT
         |  @since  0.1.0
         |
         |  @param  object  The PDOStatement object.
         |  @param  multi   The PDO fetch mode, a class name to use "PDO::FETCH_CLASS",
         |                  NULL to use the default ("PDO::FETCH_OBJ") or false to return
         |                  the PDOStatement object itself on success!
         |
         |  @param  multi   The PDOStatement object, an array with the results or FALSE.
         */
        public function fetch($query, $fetch = NULL){
            if($fetch === false){
                return $query;
            }

            // All Modes
            $modes = array(
                "assoc"     => PDO::FETCH_ASSOC,
                "numeric"   => PDO::FETCH_NUM,
                "both"      => PDO::FETCH_BOTH,
                "named"     => PDO::FETCH_NAMED,
                "object"    => PDO::FETCH_OBJ
            );

            // Fetch Fetch Mode
            if(in_array($fetch, array_keys($modes))){
                $fetch = $modes[$fetch];
            } else if(is_string($fetch) && class_exists($fetch)){
                $class = $fetch;
                $fetch = PDO::FETCH_CLASS;
            } else if(!in_array($fetch, array_values($modes))){
                $fetch = PDO::FETCH_OBJ;
            }

            // Fetch and Return
            if(!$query->setFetchMode($fetch, isset($class)? $class: NULL)){
                return false;
            }
            return $query->fetchAll();
        }

        /*
         |  CORE :: SELECT DATA (ARRAY-EDITION)
         |  @since  0.1.0
         |
         |  @param  string  The table name with, or without, prefix.
         |  @param  array   The key => value paired SQL Query clauses.
         |  @param  array   The key => value paired prepare values.
         |  @param  multi   The PDO fetch mode, a class name to use "PDO::FETCH_CLASS",
         |                  NULL to use the default ("PDO::FETCH_OBJ") or false to return
         |                  the PDOStatement object itself on success!
         |
         |  @param  multi   The PDOStatement object, an array with the results or FALSE.
         */
        public function selector($table, $query, $prepare = array(), $fetch = NULL){
            $table = $this->prefix($table);

            // Check Destination
            if(isset($query["dist"])){
                $dist = "SELECT {$query["dist"]} FROM {$table}";
            } else if(isset($query["destination"])){
                $dist = "SELECT {$query["destination"]} FROM {$table}";
            } else if(isset($query["select"])){
                if(strpos($query["select"], "SELECT") !== 0){
                    return false;
                }

                $dist = trim($query["select"]);
                if(strpos($query["select"], "FROM") === false){
                    $dist .= " FROM {$table}";
                }
            }

            // Check Joins
            $joins = "";
            if(isset($query["joins"]) && stripos($query["joins"], "JOIN") !== false){
                $joins = trim($query["joins"]);
            }

            // Check Where
            $where = "";
            if(isset($query["where"]) && ($where = $this->where($query["where"], $prepare)) === false){
                return false;
            }

            // Check Group
            $group = "";
            if(isset($query["group"]) && stripos($query["group"], "GROUP BY") !== false){
                $group = trim($query["group"]);
            }

            // Check Having
            $having = "";
            if(isset($query["having"]) && stripos($query["having"], "HAVING") !== false){
                $having = !empty($group)? trim($query["having"]): "";
            }

            // Check Order
            $order = "";
            if(isset($query["order"]) && ($order = $this->order($query["order"])) === false){
                return false;
            }

            // Check Limit
            $limit = "";
            if(isset($query["limit"]) && ($limit = $this->limit($query["limit"])) === false){
                return false;
            }

            // Create SQL and Return
            $sql = rtrim("{$dist} {$where} {$group} {$having} {$order} {$limit}").";";
            $query = $this->query($sql, $prepare);
            if($query === false || $query->rowCount() === 0){
                return false;
            }
            return $this->fetch($query, $fetch);
        }

        /*
         |  CORE :: SELECT DATA
         |  @since  0.1.0
         |
         |  @param  string  The table name with, or without, prefix.
         |  @param  string  The destination string.
         |  @param  multi   The where clause as STRING or ARRAY.
         |  @param  multi   The order clause as STRING or ARRAY.
         |  @param  multi   The limit, offset clause as STRING or ARRAY.
         |  @param  array   The key => value paired prepare values.
         |  @param  multi   The PDO fetch mode, a class name to use "PDO::FETCH_CLASS",
         |                  NULL to use the default ("PDO::FETCH_OBJ") or false to return
         |                  the PDOStatement object itself on success!
         |
         |  @param  multi   The PDOStatement object, an array with the results or FALSE.
         */
        public function select($table, $dist = "*", $where = NULL, $order = NULL, $limit = NULL, $prepare = array(), $fetch = NULL){
            $table = $this->prefix($table);

            // Check Clauses
            if(!is_string($dist)){
                return false;
            }
            if(($where = $this->where($where, $prepare)) === false){
                return false;
            }
            if(($order = $this->order($order)) === false){
                return false;
            }
            if(($limit = $this->limit($limit)) === false){
                return false;
            }

            // Create SQL and Return
            $sql = rtrim("SELECT {$dist} FROM {$table} {$where} {$order} {$limit}").";";
            $query = $this->query($sql, $prepare);
            if($query === false || $query->rowCount() === 0){
                return false;
            }
            return $this->fetch($query, $fetch);
        }

        /*
         |  CORE :: SELECT UNIQUE DATA
         |  @since  0.1.0
         |
         |  @param  string  The table name with, or without, prefix.
         |  @param  string  The destination string.
         |  @param  multi   The where clause as STRING or ARRAY.
         |  @param  int     The number of rows as INTEGER to skip.
         |  @param  array   The key => value paired prepare values.
         |  @param  multi   The PDO fetch mode, a class name to use "PDO::FETCH_CLASS",
         |                  NULL to use the default ("PDO::FETCH_OBJ") or false to return
         |                  the PDOStatement object itself on success!
         |
         |  @param  multi   The PDOStatement object, an array with the results or FALSE.
         */
        public function single($table, $dist = "*", $where = NULL, $offset = NULL, $prepare = array(), $fetch = NULL){
            $query = $this->select($table, $dist, $where, NULL, array(1, $offset), $prepare, $fetch);
            if(is_array($query) && count($query) > 0){
                return $query[0];
            }
            return false;
        }

        /*
         |  CORE :: COUNT DATA
         |  @since  0.1.0
         |
         |  @param  string  The table name with, or without, prefix.
         |  @param  multi   The where clause as STRING or ARRAY.
         |  @param  array   The key => value paired prepare values.
         |
         |  @return multi   The respective number as INT on success, FALSE on failure.
         */
        public function count($table, $where = NULL, $prepare = array()){
            return $this->select($table, "COUNT(*)", $where, NULL, NULL, $prepare);
        }

        /*
         |  CORE :: INSERT DATA
         |  @since  0.1.0
         |
         |  @param  string  The table name with, or without, prefix.
         |  @param  array   The column => value paired data to insert.
         |  @param  array   The key => value paired prepare values.
         |
         |  @return multi   The new unique ID as INTEGER on sucess, FALSE on failure.
         */
        public function insert($table, $data, $prepare = array()){
            $table = $this->prefix($table);

            // Check Parameter
            if(!is_array($data)){
                return false;
            }

            // Create SQL
            $query = array();
            foreach($data AS $key => $value){
                $query[$key] = $this->data($value, $prepare);
            }
            $sql  = "INSERT INTO {$table} ";
            $sql .= "(`".implode("`, `", array_keys($query))."`) VALUES ";
            $sql .= "(".implode(", ", array_values($query)).");";

            // Return
            $result = $this->query($sql, $prepare);
            if($result !== false){
                return $this->lastID;
            }
            return false;
        }

        /*
         |  CORE :: UPDATE DATA
         |  @since  0.1.0
         |
         |  @param  string  The table name with, or without, prefix.
         |  @param  array   The column => value paired data to update.
         |  @param  multi   The where clause as STRING or ARRAY.
         |  @param  array   The key => value paired prepare values.
         |
         |  @return multi   The number of updated rows as INTEGER on success, FALSE on failure.
         */
        public function update($table, $data, $where, $prepare = array()){
            $table = $this->prefix($table);

            // Check Parameter
            if(!is_array($data)){
                return false;
            }
            if(($where = $this->where($where, $prepare)) == false){
                return false;
            }

            // Create SQL
            $query = array();
            foreach($data AS $key => $value){
                $query[] = "`{$key}`=".$this->data($value, $prepare);
            }
            $sql  = "UPDATE {$table} SET ".implode(", ", $query)." {$where};";

            // Return
            $result = $this->query($sql, $prepare);
            if($result !== false){
                return $this->lastRows;
            }
            return false;
        }

        /*
         |  CORE :: DELETE DATA
         |  @since  0.1.0
         |
         |  @param  string  The table name with, or without, prefix.
         |  @param  multi   The where clause as STRING or ARRAY.
         |  @param  array   The key => value paired prepare values.
         |
         |  @return multi   The number of deleted rows as INTEGER on success, FALSE on failure.
         */
        public function delete($table, $where, $prepare = array()){
            $table = $this->prefix($table);

            // Check Parameter
            if(($where = $this->where($where, $prepare)) == false){
                return false;
            }

            // Create SQL and Return
            $result = $this->query("DELETE FROM {$table} {$where};", $prepare);
            return $this->lastRows > 0;
        }

        /*
         |  TOOL :: (WITH)IN TRANSACTION
         |  @since  0.1.0
         |
         |  @return bool    TRUE if you are currently within a transation, FALSE if not.
         */
        public function within(){
            return $this->db->inTransaction();
        }
        public function inTransaction(){
            return $this->within();
        }

        /*
         |  TOOL :: BEGIN TRANSACTION
         |  @since  0.1.0
         |
         |  @return bool    TRUE if a transation could be started, FALSE if not.
         */
        public function begin(){
            if($this->db->inTransaction()){
                return false;
            }
            return $this->db->beginTransaction();
        }
        public function beginTransaction(){
            return $this->begin();
        }

        /*
         |  TOOL :: FINISH TRANSACTION
         |  @since  0.1.0
         |
         |  @return bool    TRUE if a transation could be finished, FALSE if not.
         */
        public function finish(){
            if(!$this->db->inTransaction()){
                return false;
            }
            return $this->db->commit();
        }
        public function commit(){
            return $this->finish();
        }

        /*
         |  TOOL :: RESET TRANSACTION
         |  @since  0.1.0
         |
         |  @return bool    TRUE if a transation could be reseted, FALSE if not.
         */
        public function reset(){
            if(!$this->db->inTransaction()){
                return false;
            }
            return $this->db->rollBack();
        }
        public function rollBack(){
            return $this->reset();
        }
    }
