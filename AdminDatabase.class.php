<?php

class AdminDatabaseException extends RuntimeException {
    const DUPLICATE_KEY = 1062;

    protected $sql;
    public function __construct ($message = "", $code = 0, $sql = "") {
        $this->sql = $sql;
        return parent::__construct($message, $code);
    }
}

class Rowset implements Iterator, Countable {
    public $resource;
    public $count = 0;
    public $foundRows = 0;
    public $affectedRows = NULL;

    private $_position = 0;
    private $_row = FALSE;

    function __construct($resource) {
        $this->resource = $resource;
        $this->count = mysqli_num_rows($this->resource);
        $this->fetchRow();
    }
    public function rewind() {
        if($this->_position != 0) {
            mysqli_data_seek($this->resource, 0);
            $this->_position = 0;

            $this->fetchRow();
        }
    }

    public function current() {
        return $this->_row;
    }

    public function key() {
        return $this->_position;
    }

    public function next() {
        ++$this->_position;
        $this->fetchRow();
    }

    public function valid() {
        return (boolean) $this->_row;
    }

    public function count() {
        return $this->count;
    }

    private function fetchRow() {
        return $this->_row = mysqli_fetch_assoc($this->resource);
    }

}


class AdminDatabase {
    public $linkId;
    public $insertId;
    public $foundRows;

    function __construct($linkId) {
        $args = func_get_args();
        if(count($args) == 1 && is_object($args[0]) && is_a($args[0], 'mysqli')) { // 1 argument - link id
            $this->linkId = $args[0];
        } elseif(count($args) >= 1 && is_string($args[0])) {
            $this->linkId = call_user_func_array('mysqli_connect', $args);
        } else {
            var_dump($args);
            echo "<pre>";
            debug_print_backtrace();
            echo "</pre>";
            die('You should pass either "mysqli" object or connection parameters host,user,password,dbname');
        }
    }

    function selectDb($name) {
        mysqli_select_db($this->linkId, $name);
    }

    static function isError($row) {
        return false;
    }

    function escape($values, $quote = true) {
        if(!is_array($values))
            return ($quote?"'":'').mysqli_real_escape_string($this->linkId, $values).($quote?"'":'');

        foreach($values as &$value) {
            $value = ($quote?"'":'').mysqli_real_escape_string($this->linkId, $value).($quote?"'":'');
        }
        return $values;
    }


    function showError($row) {
        return false;
    }

    private function refValues($arr){
        if (strnatcmp(phpversion(),'5.3') >= 0) //Reference is required for PHP 5.3+
        {
            $refs = array();
            foreach($arr as $key => $value)
                $refs[$key] = &$arr[$key];
            return $refs;
        }
        return $arr;
    }

    function query($sql, $args = NULL) {

        if($this->linkId === false) return false;

        if($args!==NULL && !is_array($args))
            $args = array_slice(func_get_args(), 1);

        if(is_array($args)) {
            preg_match_all("/\?/", $sql, $matches, PREG_OFFSET_CAPTURE);
            // if the number of items to replace is different than the number of items specified in $replacements
            if (count($matches[0]) != count($args)) {
                echo "<pre>";
                debug_print_backtrace();
                echo "</pre>";
                echo _("Query:")." {$sql}<br>";
                var_dump($args);
                die(_("Replacement count doesn't match"));
            }

        }

        $backtraceInfo = debug_backtrace();
        // get some variables from the back trace array
        for($i=0;$i<10;$i++) {
            $callerFile = $backtraceInfo[$i]["file"];
            $callerLine = $backtraceInfo[$i]["line"];
            $callerMethod = @$backtraceInfo[$i+1]["function"];
            $baseName =basename($callerFile);
            if($baseName!='AdminDatabase.class.php' && $baseName!='NewsEvents.class.php') break;
        }
        // unset the backtrace array
        unset($backtraceInfo);

        // executes the query
        $callerFileRel = str_replace(ROOT_PATH, '', $callerFile);

        if(is_array($args)) {
            if($stmt = mysqli_prepare($this->linkId, "/* {$callerFileRel}:{$callerLine} {$callerMethod}() */ ".$sql)) {
                $types = "";
                foreach($args as $arg) {
                    if(is_float($arg))
                        $types .= 'd';
                    elseif(is_integer($arg))
                        $types .= 'i';
                    elseif(is_string($arg))
                        $types .= 's';
                }
                call_user_func_array('mysqli_stmt_bind_param', array_merge(array($stmt, $types), $this->refValues($args)));
//                mysqli_stmt_bind_param($stmt, "d", $arg);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
            } else {
                echo "<pre>";
                debug_print_backtrace();
                echo "</pre>";
                echo _("Query:")." {$sql}<br>";
                die(_('Error preparing query:').' ' . mysqli_stmt_error($this->linkId));
            }
        } else {
            if(!$res = mysqli_query($this->linkId, "/* {$callerFileRel}:{$callerLine} {$callerMethod}() */ ".$sql)) {
                echo "<pre>";
                debug_print_backtrace();
                echo "</pre>";
                echo _("Query:")." {$sql}<br>";
                die(_('Error executing query:').' ' . mysqli_error($this->linkId));
            }
        }

        $this->insertId = mysqli_insert_id($this->linkId);
        $this->affectedRows = mysqli_affected_rows($this->linkId);
        if(is_object($res)) { // SELECT, SHOW, DESCRIBE, EXPLAIN
            if(strpos($sql, 'SQL_CALC_FOUND_ROWS') > 0) {
                $this->foundRows = $this->getOne("SELECT FOUND_ROWS()");
            } else {
                $this->foundRows = 0;
            }
            $rowset = new Rowset($res);
            $rowset->foundRows = $this->foundRows;
            return $rowset;
        } elseif(is_bool($res)) {
            return $res;
        } else { // INSERT, UPDATE, DELETE, DROP
            $this->insertId = mysqli_insert_id($this->linkId);
            $this->affectedRows = mysqli_affected_rows($this->linkId);
            return $this->insertId;
        }


    }

    function getOne($sql, $args = NULL) {
        if($args!==NULL && !is_array($args))
            $args = array_slice(func_get_args(), 1);
        $res = $this->query($sql, $args);
        if($res->count == 0) return NULL;
        $row = $res->current();
        return current($row);
    }

    function getRow($sql, $args = NULL) {
        if($args!==NULL && !is_array($args))
            $args = array_slice(func_get_args(), 1);
        $res = $this->query($sql, $args);
        $row = $res->current();
        return $row;
    }

    function getAll($sql, $args = NULL) {
        if($args!==NULL &&!is_array($args))
            $args = array_slice(func_get_args(), 1);
        $res = $this->query($sql, $args);
        $data = array();
        foreach($res as $row) {
            if(isset($row['id']))
                $data[$row['id']] = $row;
            else
                $data[] = $row;
        }
        return $data;
    }


    // Executes SQL and returns list of values of first SQL result field
    function getList($sql, $args = NULL) {
        if($args!==NULL &&!is_array($args))
            $args = array_slice(func_get_args(), 1);
        $res = $this->query($sql, $args);
        $data = array();
        foreach($res as $row) {
                $data[] = reset($row);
        }
        return $data;
    }

    function replace($table, $fields) {
        $sql = "REPLACE $table SET ";
        $i = 0;
        foreach($fields as $key=>$value) {
            $sql .= "`{$key}` = ".AdminDatabase::escape($value);
            if(++$i !== count($fields)) $sql .= ', ';
        }
        return $this->query($sql); // return inserted id
    }
    function insert($table, $fields) {
        $sql = "INSERT $table SET ";
        $i = 0;
        foreach($fields as $key=>$value) {
            $sql .= "`{$key}` = ".AdminDatabase::escape($value);
            if(++$i !== count($fields)) $sql .= ', ';
        }
        $this->query($sql);
        // return inserted id
        return $this->insertId;
    }
    function insertIgnore($table, $fields) {
        return $this->insert("IGNORE ".$table, $fields);
    }

    function insertUpdate($table, $fields) {
        $sql = "INSERT $table SET ";
        $i = 0;
        foreach($fields as $key=>$value) {
            $sql .= "`{$key}` = ".AdminDatabase::escape($value);
            if(++$i !== count($fields)) $sql .= ', ';
        }
        $i = 0;
        $sql .= ' ON DUPLICATE KEY UPDATE ';
        foreach($fields as $key=>$value) {
            $sql .= "`{$key}` = VALUES(`{$key}`)";
            if(++$i !== count($fields)) $sql .= ', ';
        }
        $this->query($sql);
        // return inserted id
        return $this->insertId;
    }

    function update($table, $id, $fields) {
        $sql = "UPDATE $table SET ";
        $i = 0;
        foreach($fields as $key=>$value) {
            $sql .= "`{$key}` = ".AdminDatabase::escape($value);
            if(++$i !== count($fields)) $sql .= ', ';
        }
        $sql .= " WHERE id = ".AdminDatabase::escape($id);
        $this->query($sql); // return inserted id
        return $this->affectedRows;
    }

}