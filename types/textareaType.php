<?php

class textareaType extends textType {
	public $rows = 3;
	public function toString() {
		return $this->escape( strip_tags($this->value));		
	}
	public function toHtml() {
		return "<textarea name='{$this->name}' id='{$this->name}' rows='{$this->rows}' class='form-control {$this->class} ".(!$this->valid?'error':'')."'>".$this->escape($this->value)."</textarea>";
	}
}