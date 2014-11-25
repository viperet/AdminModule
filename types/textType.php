<?

class textType extends coreType {
	public $placeholder;

	function __construct($name, $array) {
		if($array['type']."Type" == get_class($this))
			parent::__construct($name, $array);
	}
	
	public function toHtml() {
		return "<input type='text' name='{$this->name}' id='{$this->name}' class='form_input {$this->class} ".(!$this->valid?'error':'')."' value='".$this->escape($this->value)."' ".(!empty($this->placeholder)?"placeholder='".htmlspecialchars($this->placeholder)."' ":'')." />";
	}
	
	public function toSql() {
		if($this->readonly) return "";
		return "`{$this->name}`='". mysql_real_escape_string($this->value)."'";
		
	}
	
}