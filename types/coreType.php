<?

abstract class coreType {
	public $name;
	public $type;
	public $value = '';
	public $label;
	public $label_hint;
	public $readonly;
	public $header;
	public $validation, $validation_regexp, $validation_message;
	public $class;
	public $truncate = 80;
	public $escape = true;
	public $required = false;
	public $errors = array();
	public $valid = true;
	public $encoding = 'UTF-8';
	public $filter = false;
	public $filterByClick = false;
	public $massAction = false;
	public $raw = false; // raw - не использовать обрамляющие HTML блоки для отображения элемента
	public $permissions;
	
	public $options;
	public $db;
	
	function __construct($db, $name, $array) {
		$this->name = $name;
		$this->db = $db;
		foreach($array as $field => $value) {
			if(property_exists(get_class($this), $field)) {
				$this->$field = $value;
			}
		}
	}
	
	public function toStringTruncated() {
		$value = $this->toString();
		$value_truncated = mb_substr($value, 0, $this->truncate, $this->encoding);
		if($value_truncated != $value) $value_truncated .= '...';
		return $this->escape($value_truncated);		
	}
	public function toString() {
		return $this->escape($this->value);		
	}
	
	public function fromForm($value) {
/* 		echo $this->name." - ".$value[$this->name]."<br>"; */
		$this->value = $value[$this->name];
	}
	
	public function fromRow($row) {
		if (AdminDatabase::isError($row)) {
			echo "<pre>";
			debug_print_backtrace();
			echo "</pre>";
			die(AdminDatabase::showError($row));
		}
		$this->value = $row[$this->name];
	}
	
	public function validate(&$errors) {
		$this->valid = true;

		if($this->required && is_string($this->value) && trim($this->value) == '' ) {
			$errors[] = "Заполните обязательное поле '".htmlspecialchars($this->label)."'";
			$this->errors[] = 'Обязательное поле';
			$this->valid = false;
			return false;
		}
	
		if($this->type == 'time' && ($this->value!='' && !preg_match('/^\d?\d:\d?\d$/', $this->value))) {
			$this->valid = false;
			$this->errors[] = 'Формат времени - ЧЧ:ММ';
			$errors[] = "Неправильный формат времени в '".htmlspecialchars($this->label)."'";
			return false;
		}
		if($this->type == 'datetime' && ( $this->value!='' && !preg_match('/^\d?\d.\d?\d.\d\d\d\d(\s+\d?\d:\d?\d(:\d?\d)?)?$/', $this->value))) {
			$this->valid = false;
			$this->errors[] = 'Формат даты/времени - ЧЧ.ММ.ГГГГ ЧЧ:ММ:СС';
			$errors[] = "Неправильный формат даты/времени в '".htmlspecialchars($this->label)."'";
		}
		if($this->validation == 'email' && ($this->value!='' && !preg_match('#[A-Z0-9\._%\+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}#i', $this->value))) {
			$this->valid = false;
			$this->errors[] = 'Требуется ввести действительный email';
		}
		if($this->validation == 'url' && ($this->value!='' && !preg_match('#^http\://[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(/\S*)?$#i', $this->value))) {
			$this->valid = false;
			$this->errors[] = 'Требуется ввести действительный URL (http://…)';
		}
		if($this->validation == 'integer' && ($this->value!='' && !preg_match('/^-?\d+$/', $this->value))) {
			$this->valid = false;
			$this->errors[] = 'Требуется целое число';
		}
		if($this->validation == 'money' && ($this->value!='' && !preg_match('/^[0-9]*\.?[0-9]+$/', $this->value))) {
			$this->valid = false;
			$this->errors[] = 'Требуется положительное число';
		}
		if($this->validation == 'float' && ($this->value!='' &&!preg_match('/^[-+]?[0-9]*\.?[0-9]+$/', $this->value))) {
			$this->valid = false;
			$this->errorMessage[] = "Введите число в поле '".$item['label']."'";
			$this->errors[] = 'Требуется число';
		}
		if($this->validation == 'regexp' && ($this->value!='' && !preg_match($this->validation_regexp, $this->value))) {
			$this->valid = false;
			$this->errors[] = $this->validation_message;
		}
		if( preg_match('#^/.*/$#', $this->validation) && ($this->value!='' && !preg_match($this->validation, $this->value))) {
			$this->valid = false;
			$this->errors[] = $this->validation_message;
		}
		
		return $this->valid;
	}
	
	public function toHtmlLabel() {
		return "<label class='col-sm-3 control-label ".(!$this->valid?'error':'')."' for='{$this->name}'>
			{$this->label}".($this->required?'*':'').
		($this->label_hint?"<small class='show' style='font-weight:normal'>{$this->label_hint}</small>":"").
		"</label>";

	}

	public function delete() { return; }
	
	public function postSave($id, $params, $item) { return ''; }
	
	public function escape($string) {
		if($this->escape) 
			return htmlspecialchars($string, ENT_NOQUOTES, $this->encoding);	
		else
			return $string;
	}
	
	public static function pageHeader() { return ''; }
	
	abstract public function toHtml();
	abstract public function toSql();
	
	
	public function json($obj) {
//		return json_encode($obj);
		return preg_replace_callback(
		'/\\\u([0-9a-fA-F]{4})/',
		create_function('$match', 'return mb_convert_encoding("&#" . intval($match[1], 16) . ";", "UTF-8", "HTML-ENTITIES");'),
			json_encode($obj)
		);
	}
	
}