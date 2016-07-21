<?php

class textType extends coreType {
	public $placeholder;
	public $readonly = false;
	public $values = array();
	public $autocomplete = true;

	public function toHtml() {
		return "<input type='text' name='{$this->name}' id='{$this->name}' class='form-control {$this->class} ".(!$this->valid?'error':'')."' value='".$this->escape($this->value)."' ".
			(!empty($this->placeholder)?"placeholder='".htmlspecialchars($this->placeholder)."' ":'').
			(!empty($this->readonly)?"readonly":'').
			(!$autocomplete?"autocomplete='nope'":'').
			" />";
	}

	public function toSql() {
		if($this->readonly) return "";
		return "`{$this->name}`=".$this->db->escape($this->value);

	}
	public function getValues() {
		if(count($this->values)>0) {
			return $this->values;
		} else {
			return parent::getValues();
		}
	}
}