<?

class checkboxType extends textType {
	public function toString() {
		return ($this->value?_('Yes'):_('No'));
	}
	public function toHtmlLabel() {
		return '<div class="col-sm-3"></div>';
	}
	public function toHtml() {
		return "<div class='checkbox'><label class='".(!$this->valid?'error':'')."'>
		<input type='checkbox' autocomplete='off' name='{$this->name}' id='{$this->name}' class='form-checkbox {$this->class} ".(!$this->valid?'error':'')."' ".($this->value?'checked':'')." />
		{$this->label}
		</label>".
		($this->label_hint?"<span id='helpBlock' class='help-block'>{$this->label_hint}</span>":"").
		"</div>";
	}
	
	public function toSql() {
		if($this->readonly) return "";
		return "`{$this->name}`='". ($this->value=='on'?1:0)."'";
		
	}	
	
	public function getValues() {
		return array(0 => _('No'), 1 => _('Yes'));
	}
}