<?

abstract class coreType {
	public $id;
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
	public $primary = false; // true у главного поля, характеризующего запись (для логирования)
	public $permissions;
	public $inline = false; // разрешить редактировать прямо в таблице
	public $order = 0; // порядок столбцов
	
	public $onLoad = null; // callback for transforming data after loading from db
	public $onSave = null; // callback for transforming data before saving to db

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
		return $value_truncated;
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
		if(is_callable($this->onLoad)) {
			$this->value = call_user_func($this->onLoad, $this->value);
		}
		$this->id = $row['id'];
	}
	
	public function validate(&$errors) {
		$this->valid = true;

		if($this->required && (
			(is_string($this->value) && (trim($this->value) == '' ) 
			|| is_null($this->value))
		)) {
			$errors[] = sprintf(_("Please fill required field '%s'"), $this->label);
			$this->errors[] = _('Required field');
			$this->valid = false;
			return false;
		}
	
		if($this->type == 'time' && ($this->value!='' && !preg_match('/^\d?\d:\d?\d$/', $this->value))) {
			$this->valid = false;
			$this->errors[] = _('Time format - HH:MM');
			$errors[] = sprintf(_("Wrong time format in '%s'"), $this->label);
			return false;
		}
		if($this->type == 'datetime' && ( $this->value!='' && !preg_match('/^\d?\d.\d?\d.\d\d\d\d(\s+\d?\d:\d?\d(:\d?\d)?)?$/', $this->value))) {
			$this->valid = false;
			$this->errors[] = _('Date/time format - DD.MM.YYYY HH:MM');
			$errors[] = sprintf(_("Wrong date/time format in '%s'"), $this->label);
		}
		if($this->validation == 'email' && ($this->value!='' && !preg_match('#[A-Z0-9\._%\+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}#i', $this->value))) {
			$this->valid = false;
			$this->errors[] = _('Please enter correct email address');
		}
		if($this->validation == 'url' && ($this->value!='' && filter_var($this->value, FILTER_VALIDATE_URL) === false)) {
			$this->valid = false;
			$this->errors[] = _('Please enter correct URL (http://...)');
		}
		if($this->validation == 'integer' && ($this->value!='' && !preg_match('/^-?\d+$/', $this->value))) {
			$this->valid = false;
			$this->errors[] = _('Please enter integer number');
		}
		if($this->validation == 'money' && ($this->value!='' && !preg_match('/^[0-9]*\.?[0-9]+$/', $this->value))) {
			$this->valid = false;
			$this->errors[] = _('Please enter positive number');
		}
		if($this->validation == 'float' && ($this->value!='' &&!preg_match('/^[-+]?[0-9]*\.?[0-9]+$/', $this->value))) {
			$this->valid = false;
			$this->errorMessage[] = sprintf(_("Please enter number in '%s'"), $this->label);
			$this->errors[] = _('Please enter number');
		}
		if($this->validation == 'regexp' && ($this->value!='' && !preg_match($this->validation_regexp, $this->value))) {
			$this->valid = false;
			$this->errors[] = $this->validation_message;
		}
		if( is_string($this->validation) && preg_match('#^/.*/$#', $this->validation) && ($this->value!='' && !preg_match($this->validation, $this->value))) {
			$this->valid = false;
			$this->errors[] = $this->validation_message;
		}
		if( is_callable($this->validation) && ($message =call_user_func($this->validation, $this)) !== true ) {
			$this->valid = false;
			if($message === false)
				$this->errors[] = $this->validation_message;
			else 
				$this->errors[] = $message;
			
		}
		
		return $this->valid;
	}
	
	public function toHtmlLabel() {
		return "<label class='col-sm-3 control-label ".(!$this->valid?'error':'')."' for='{$this->name}'>
			{$this->label}".($this->required?'*':'').
		($this->label_hint?"<small class='show' style='font-weight:normal'>{$this->label_hint}</small>":"").
		"</label>";

	}

	public function toListElement() {
		return $this->toStringTruncated();
	}	
	public function toListItem() {
		ob_start();
		if($this->inline)
			echo "<a href='{$this->baseUrl}&edit={$this->id}' class='editable' data-type='text' data-pk='{$this->id}' >".$this->toStringTruncated()."</a>";
		elseif($this->options['datatables'])
			echo $this->toStringTruncated();
		elseif($this->filterByClick)		
			echo "<a href='{$this->baseUrlNoFilter}&filter=".urlencode($this->name.':'.$this->value)."'>".$this->toStringTruncated()."</a>";
		else
			echo "<a href='{$this->baseUrl}&edit={$this->id}'>".$this->toStringTruncated()."</a>";
		return ob_get_clean();
	}

	public function delete() { return; }
	
	public function postSave($id, $params, $item) { return ''; }
	
	public function escape($string) {
		if($this->escape) 
			return htmlspecialchars($string, ENT_QUOTES, $this->encoding);	
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