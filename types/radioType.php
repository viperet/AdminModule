<?

class radioType extends textType {
	public $values = array();
	
	public function toString() {
		$value = $this->value;
		if(isset($this->values[$value]))
			$this->value = $this->values[$value];
		$result = parent::toString();
		$this->value = $value;
		return $result;
	}
	
	public function toHtml() {
		if($this->value == '') { // если пусто - по умолчанию значение первого чекбокса
			$this->value = array_keys($this->values);
			$this->value = $this->value[0];
		}
		foreach($this->values as $value=>$label)
			$html .= "<input type='radio' name='{$this->name}' class='form_radio {$this->class} ".(!$this->valid?'error':'')."' id='{$this->name}_{$value}' value='{$value}' ".($this->value==$value?"checked='1'":'')."><label for='{$this->name}_{$value}'>".htmlspecialchars($label)."</label>";
		return $html;
	}
	
}