<?php


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

	function showError($row) {
		return false;
	}
	
	function query($sql, $args = NULL) {
		if($args!==NULL && !is_array($args))
			$args = array_slice(func_get_args(), 1);
		
		if(is_array($args)) {
			preg_match_all("/\?/", $sql, $matches, PREG_OFFSET_CAPTURE);
			// if the number of items to replace is different than the number of items specified in $replacements
			if (count($matches[0]) != count($args)) {
				echo "<pre>";
				debug_print_backtrace();
				echo "</pre>";
				echo "Query: {$sql}<br>";
			    die("Replacement count doesn't match");
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
			echo "Query: {$sql}<br>";
		    die('Invalid query: ' . mysql_error());
		}
		
		$this->insertId = mysql_insert_id();
		
		if(strpos($sql, 'SQL_CALC_FOUND_ROWS') > 0) {
			$this->foundRows = $this->getOne("SELECT FOUND_ROWS()");
		} else {
			$this->foundRows = 0;
		}
		return $res;
	}
	
	function getOne($sql, $args = NULL) {
		if($args!==NULL && !is_array($args))
			$args = array_slice(func_get_args(), 1);
		$res = $this->query($sql, $args);
		$row = mysql_fetch_row($res);
		return $row[0];
	}
	
	function getRow($sql, $args = NULL) {
		if($args!==NULL && !is_array($args))
			$args = array_slice(func_get_args(), 1);
		$res = $this->query($sql, $args);
		$row = mysql_fetch_assoc($res);
		return $row;
	}
	
	function getAll($sql, $args = NULL) {
		if($args!==NULL &&!is_array($args))
			$args = array_slice(func_get_args(), 1);
		$res = $this->query($sql, $args);
		$data = array();
		while($row = mysql_fetch_assoc($res)) {
			$data[] = $row;
		}
		return $data;
	}
	
}