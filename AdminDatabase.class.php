<?php

class Rowset implements Iterator, Countable {
	public $resource;	
	public $count = 0;
	public $foundRows = 0;
	
	private $_position = 0;
	private $_row = FALSE;
	
	function __construct($resource) {
		$this->resource = $resource;
		$this->count = mysql_num_rows($this->resource);
		$this->fetchRow();
	}
	public function rewind() {
        if($this->_position != 0) {
	        mysql_data_seek($this->resource, 0);
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
		return $this->_row = mysql_fetch_assoc($this->resource);
	}   
	
}


class AdminDatabase {
	public $linkId;
	public $insertId;
	public $foundRows;
	
	function __construct($linkId) {
		$this->linkId = $linkId;
	}
	
	static function isError($row) {
		return false;
	}
	
	static function escape($values) {
		if(!is_array($values))
			return "'".mysql_real_escape_string($value)."'";
			
		foreach($values as &$value) {
			$value = "'".mysql_real_escape_string($value)."'";
		}
		return $values;
	}


	function showError($row) {
		return false;
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
			
	        // make preparations for the replacement
	        $pattern1 = array();
	
	        $pattern2 = array();
	
	        // prepare question marks for replacement
	        foreach ($matches[0] as $match) {
	
	            $pattern1[] = "/\\" . $match[0] . "/";
	
	        }
	
	        foreach ($args as $key=>$replacement) {
	
	            // generate a string
	            $randomstr = md5(microtime()) . $key;
	
	            // prepare the replacements for the question marks
	            $replacements1[] = $randomstr;
	
	            // mysql_real_escape_string the items in replacements
	            // also, replace anything that looks like $45 to \$45 or else the next preg_replace-s will treat
	            // it as references
	            $replacements2[$key] = "'" . preg_replace("/\\$([0-9]*)/", "\\\\$$1", mysql_real_escape_string($replacement)) . "'";
	
	            // and also, prepare the new pattern to be replaced afterwards
	            $pattern2[$key] = "/" . $randomstr . "/";
	
	        }
	
	        // replace each question mark with something new
	        // (we do this intermediary step so that we can actually have question marks in the replacements)
	        $sql = preg_replace($pattern1, $replacements1, $sql, 1);
	
	        // perform the actual replacement
	        $sql = preg_replace($pattern2, $replacements2, $sql, 1);
		}
		
		$res = mysql_query($sql, $this->linkId);
		if(!$res) {
			echo "<pre>";
			debug_print_backtrace();
			echo "</pre>";
			echo _("Query:")." {$sql}<br>";
		    die(_('Invalid query:').' ' . mysql_error());
		}
		
		
		if(is_resource($res)) { // SELECT, SHOW, DESCRIBE, EXPLAIN 
			
			if(strpos($sql, 'SQL_CALC_FOUND_ROWS') > 0) { 
				$this->foundRows = $this->getOne("SELECT FOUND_ROWS()");
			} else {
				$this->foundRows = 0;
			}
			$rowset = new Rowset($res);
			$rowset->foundRows = $this->foundRows;
			return $rowset;
			
			
		} else { // INSERT, UPDATE, DELETE, DROP
			$this->insertId = mysql_insert_id();
			return $this->insertId;
		}
		
		
	}
	
	function getOne($sql, $args = NULL) {
		if($args!==NULL && !is_array($args))
			$args = array_slice(func_get_args(), 1);
		$res = $this->query($sql, $args);
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
	
}