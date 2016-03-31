<?php

class select2Type extends textType {
	public $values = array();
	public $lookup_table; // таблица по которой смотреть
	public $lookup_id = 'id'; // поле которое возвращать
	public $lookup_field; // поле по которому искать
	public $lookup_display; // поле которое отображать
	public $lookup_sort; // сортировка
	public $lookup_where = '1';
	public $create_variant = false; // можно ли создавать новые значения на лету, для работы необходима включенная опция ajax
	public $ajax = false;
	public $min_input = 2;
	
	function __construct($db, $name, $array) {
		
		parent::__construct($db, $name, $array);
		if(empty($this->lookup_display) && !empty($this->lookup_field))
			$this->lookup_display = $this->lookup_field;
		if(empty($this->lookup_sort) && !empty($this->lookup_field))
			$this->lookup_sort = $this->lookup_field." ASC ";
		if($this->lookup_table != '' && $this->lookup_field != '' && $this->ajax == false) {
			$this->values = $this->values + $this->getLookup();
		}
	}

	public static function pageHeader() {
	?>
<!--
<script src="/js/select2.js"></script>
<link href="/js/select2.css" media="screen" type="text/css" rel="stylesheet">
-->
	<?php
	}

	
	public function getLookup($value=NULL, $limit=NULL) {
		
		$result = array();
		$sql = "SELECT {$this->lookup_id} _key, ".$this->lookup_display." value FROM ".$this->lookup_table." WHERE {$this->lookup_where} ".
			(!empty($value) ? " AND {$this->lookup_field} LIKE '%".$this->db->escape($value, false)."%' " : "").
			" ORDER BY ".$this->lookup_sort.
			(!empty($limit) ? " LIMIT $limit" : "");
//		echo $sql;
		$values = $this->db->getAll($sql);
		foreach($values as $value) {
			$result[$value['_key']] = $value['value']; 
		}
		
		return $result+$this->values;
	}
	
	public function ajaxLookup() {
		$lookup = $this->getLookup($_REQUEST['q']);
		$data = array();
		foreach($lookup as $id => $value) {
			$data[] = array('id'=>$id, 'text'=>$value);
		}
		echo $this->json($data);
	}
	
	public function lookupValueById($id) {
		if(isset($this->values[$id])) 
			return $this->values[$id];
		if(empty($id)) 
			return "";
		$res = $this->db->getOne("SELECT {$this->lookup_display} value FROM ".$this->lookup_table." WHERE {$this->lookup_id} = ?", $id);
		if($res === NULL)
			return $id;
		return $res;
	}
	
	public function toString() {
		$value = $this->value;
		if($this->lookup_table != '' && $this->lookup_field !='') {
			$this->value = $this->lookupValueById($value);
		} elseif(isset($this->values[$value]))
			$this->value = $this->values[$value];
		else {
			array_walk_recursive($this->values, function ($val, $key) {
				if($key == $this->value) { 
					$this->value = $val;
				}
			});
		}
		$result = parent::toString();
		$this->value = $value;
		return $result;
	}
	
	public function toHtml() {
		
		
		if($this->ajax) {
			$value_text = $this->lookupValueById($this->value);
			$html = "<input type='hidden' name='{$this->name}' id='{$this->name}' class='form-control {$this->class}' value='{$this->value}'>";
			$html .= "
	<script>
		$(document.getElementById('{$this->name}')).select2({
			".($this->create_variant?"createSearchChoice: function (term) { return { id: term, text: term }; },":"")."
			minimumInputLength: {$this->min_input},";
			if($this->value!='') {
				$html .= "
				initSelection: function(element, callback) {
					callback({id: '{$this->value}', text: ".json_encode($value_text)."}); 
				},";
			}
			$html .= "
			ajax: {
				url: document.location+'&ajaxField='+encodeURIComponent('{$this->name}')+'&ajaxMethod=ajaxLookup',
				dataType: 'json',
				data: function (term, page) {
					return {
						q: term
					};
				},
				results: function (data, page) {
					return { results: data };
				}
			}
		})".($this->readonly?'.select2("readonly", true)':'').";
	</script>";
		} else {
		
			$html = "<select name='{$this->name}' placeholder='{$this->placeholder}' id='{$this->name}' class='form-control {$this->class} ".(!$this->valid?'error':'')."' ".($this->readonly?'readonly':'').">";
			foreach($this->values as $value=>$label) {
				if(is_array($label)) {
					$html .= "<optgroup label='{$value}'>";
					foreach($label as $subvalue => $sublabel) 
						$html .= "<option value='{$subvalue}' ".($this->value==$subvalue?"selected":'').">".htmlspecialchars($sublabel)."</option>";
					$html .= "</optgroup>";
					
				} else {
					$html .= "<option value='{$value}' ".($this->value==$value?"selected":'').">".htmlspecialchars($label)."</option>";
				}
			}
			$html .= "</select>
	<script>
		$('#{$this->name}').select2()".($this->readonly?'.select2("readonly", true)':'').";
	</script>";
		}
		return $html;
	}
	
	/* Get values list for filtering */
	public function getValues() {
		if(count($this->values)>0 && $this->ajax == false) {
			$values = [];
			foreach($this->values as $key=>$value) {
				if(is_array($value)) {
					$values += $value;
				} else {
					$values[$key] = $value;
				}
			}
			return $values;
		} else {
			return parent::getValues();
		}
	}	
	
}