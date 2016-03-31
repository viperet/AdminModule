<?php

class labelType extends coreType {

	public function toHtmlLabel() {
		return "<label class='col-sm-3 control-label'></label>";

	}

	public function toHtml() {
		return "<p id='{$this->name}' class='form-control-static {$this->class}'>{$this->label}</p>";
	}
	
	public function toSql() {
		return "";
	}
	
}