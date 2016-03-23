<?

class noneType extends coreType {
	
	function __construct($db, $name, $array) {
		parent::__construct($db, $name, $array);
		$this->header=false;
	}	

	public function toStringTruncated() {
		return "";		
	}
	public function toString() {
		return "";		
	}
	
	public function fromForm($value) {
		return;
	}
	
	public function fromRow($row) {
		return;
	}
	
	public function validate(&$errors) {
		return true;
	}
	
	public function toHtmlLabel() {
		return "";

	}
	
	
	public function toHtml() {
		return "";
	}
	
	public function toSql() {
		if(empty($this->value)) return "";
		if($this->readonly) return "";
		return "`{$this->name}`=".$this->db->escape($this->value);
	}
}