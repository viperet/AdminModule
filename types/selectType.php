<?

class selectType extends textType {
	public $values = array();
	public $lookup_table;
	public $lookup_field;
	
	function __construct($db, $name, $array) {
		parent::__construct($db, $name, $array);
		if($this->lookup_table != '' && $this->lookup_field) {
			$values = $this->db->getAll("SELECT id,".$this->lookup_field." value FROM ".$this->lookup_table." ORDER BY ".$this->lookup_field." ASC ");
			foreach($values as $value) {
				$this->values[$value['id']] = $value['value']; 
			}
		}
	}
	
	public function toString() {
		$value = $this->value;
		if(isset($this->values[$value]))
			$this->value = $this->values[$value];
		$result = parent::toString();
		$this->value = $value;
		return $result;
	}
	
	public function toHtml() {
		$html = "<select name='{$this->name}' placeholder='{$this->placeholder}' id='{$this->name}' class='form-control selectpicker {$this->class} ".(!$this->valid?'error':'')."'>";
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
		$html .= "</select>";
		return $html;
	}
	
}