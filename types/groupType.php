<?

class groupType extends coreType {
	public $raw = true;
	public $begin = true;

	public function toHtmlLabel() {
		return "<label class='col-sm-3 control-label'></label>";

	}

	public function toHtml() {
		if($this->begin)
			return "<div id='{$this->name}' class='{$this->class}'>";
		else
			return "</div>";
		
	}

	public function validate(&$errors) {
		return $this->valid = true;
	}
		
	public function fromForm($value) {
	}
	
	public function fromRow($row) {
	}
	
	public function toSql() {
		return "";
	}
	
}