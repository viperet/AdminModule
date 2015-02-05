<?

class setType extends textType {
	public $values = array();

	public function fromRow($row) {
		$this->value = explode(',', $row[$this->name]);
	}	
	public function toSql() {
		if($this->readonly) return "";
		return "`{$this->name}`='". implode(',', $this->value)."'";
	}
	public function toString() {
		$result = implode(', ', array_intersect_key($this->values, array_flip($this->value)));
		return $result;
	}
	
	public function toHtml() {
		foreach($this->values as $value=>$label)
			$html .= "<div class='checkbox'><label><input type='checkbox' name='{$this->name}[]' class='form_checkbox {$this->class} ".(!$this->valid?'error':'')."' id='{$this->name}_{$value}' value='{$value}' ".($this->readonly?"disabled":'')." ".(@in_array($value, $this->value)?"checked='1'":'').">".htmlspecialchars($label)."</label></div>";
		return $html;
	}
	
}