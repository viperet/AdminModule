<?

class switchType extends textType {
	
	public $on_text = 'On';
	public $off_text = 'Off';
	
	function __construct($db, $name, $array) {
		$this->on_text = _('Yes');
		$this->off_text = _('No');
		parent::__construct($db, $name, $array);
	}
	
	public function toString() {
		return ($this->value?$this->on_text:$this->off_text);
	}
	public function toHtml() {
		return "<input type='checkbox' autocomplete='off' name='{$this->name}' id='{$this->name}' class='form-checkbox bootstrap-switch {$this->class} ".(!$this->valid?'error':'')."' ".($this->value?'checked':'')." />
		<script>$('#{$this->name}').bootstrapSwitch({onText: '{$this->on_text}', offText: '{$this->off_text}'});</script>";
	}
	
	public function toSql() {
		if($this->readonly) return "";
		return "`{$this->name}`='". ($this->value=='on'?1:0)."'";
		
	}		
	
	/* Get values list for filtering */
	public function getValues() {
		return array(0 => _('No'), 1 => _('Yes'));
	}
}