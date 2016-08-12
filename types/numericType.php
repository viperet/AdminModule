<?php

class numericType extends textType {
	public $validation = 'float';
	public $decimals = 2;

	public function toString() {
		if($this->value === "" || $this->value === NULL)
			return "";
		else
			return $this->escape(number_format($this->value, $this->decimals, '.', ' '));
	}

	public function toListElement() {
		return "<div class='text-right'>".$this->toStringTruncated()."</div>";
	}	
	public function toListItem() {
		return "<div class='text-right'>".$this->toStringTruncated()."</div>";
	}	

	public function toSql() {
		if($this->readonly) return "";
		if($this->value === "" || $this->value === NULL)
			return "`{$this->name}`=NULL";
		else
			return "`{$this->name}`=".$this->db->escape($this->value);
		
	}	
}