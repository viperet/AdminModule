<?

class passwordType extends textType {
	public $placeholder;
	public $min_length = 6;
	public $max_length = 100;
	public $label_hint = '';
	public $value_check = '';
	public $compat = false;

	private function hashPassword() {
		if($this->compat) 
			return md5($this->value);
		else
			return password_hash($this->value, PASSWORD_DEFAULT);
	}

	public function fromRow($row) {
		$this->value = '';
	}
	
	public function fromForm($value) {
		parent::fromForm($value);
		$this->value_check = $value[$this->name.'_check'];
	}
	
	public function validate(&$errors) {
		$this->valid = true;

		if( $this->value !=  $this->value_check) {
			$errors[] = _("Passwords don't match");
			$this->errors[] = _("Passwords don't match");
			$this->valid = false;
		}

		if( $this->value != '' && strlen($this->value)<$this->min_length ) {
			$errors[] = sprintf(_("Min password length %s chars"), $this->min_length);
			$this->errors[] = sprintf(_("Min length %s chars"), $this->min_length);
			$this->valid = false;
		}
		if( $this->value != '' && strlen($this->value)>$this->max_length ) {
			$errors[] = sprintf(_("Max password length %s chars"), $this->max_length);
			$this->errors[] = sprintf(_("Max length %s chars"), $this->max_length);
			$this->valid = false;
		}
		
		return $this->valid && parent::validate($errors);
	}	

	public function toHtml() {
		return "<div class='row'>".
		"<div class='col-lg-6 form-password'><input type='password' autocomplete='off' name='{$this->name}' id='{$this->name}' class='form-control {$this->class} ".(!$this->valid?'error':'')."' value='' placeholder='"._('New password')."' /></div>".
		"<div class='col-lg-6 form-password'><input type='password' autocomplete='off' name='{$this->name}_check' id='{$this->name}_check' class='form-control {$this->class} ".(!$this->valid?'error':'')."' value='' placeholder='"._('New password again')."' /></div>".
		"<div class='col-lg-12 help-block'>"._('To change password enter new password in both fields')."</div>".
		"</div>";
	}
	
	public function toSql() {
		if($this->value == '') return "";
		$password = $this->hashPassword();
		return "`{$this->name}`= ".$this->db->escape($password);
		
	}

	public static function pageHeader() {
	?>
<style>
	.row .form-password { margin-bottom: 10px; }
</style>
	<?
	}
	
}