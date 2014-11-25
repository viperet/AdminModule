<?php


class AdminDatabase {
	public $linkId;
	public $foundRows;
	
	function __construct($linkId) {
		$this->linkId = $linkId;
	}
	
	function isError($row) {
		return false;
	}

	function showError($row) {
		return false;
	}
	
	function query($sql, $args = NULL) {
		if($args!==NULL && !is_array($args))
			$args = array_slice(func_get_args(), 1);
		if(!$args!==NULL && count($args)>0){
			$sql = str_replace('?', "'?'", $sql);
			$sql = str_replace(
				array_fill(0,count($args),'?'), 
				array_map('mysql_real_escape_string', $args),
				$sql);
		}
//		echo $sql;
		$res = mysql_query($sql, $this->linkId);
		if(!$res) {
			echo "<pre>";
			debug_print_backtrace();
			echo "</pre>";
			echo "Query: {$sql}<br>";
		    die('Invalid query: ' . mysql_error());
		}
		
		
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