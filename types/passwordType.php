<?

class passwordType extends textType {
	public $placeholder;
	public $min_length = 6;
	public $max_length = 100;
	public $label_hint = '';
	public $value_check = '';

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
			$errors[] = "Введенные пароли не совпадают";
			$this->errors[] = "Введенные пароли не совпадают";
			$this->valid = false;
		}

		if( $this->value != '' && strlen($this->value)<$this->min_length ) {
			$errors[] = "Минимальная длинна пароля {$this->min_length} символов";
			$this->errors[] = "Минимальная длинна {$this->min_length} символов";
			$this->valid = false;
		}
		if( $this->value != '' && strlen($this->value)>$this->max_length ) {
			$errors[] = "Максимальная длинна пароля {$this->max_length} символов";
			$this->errors[] = "Максимальная длинна {$this->max_length} символов";
			$this->valid = false;
		}
		
		return $this->valid && parent::validate($errors);
	}	

	public function toHtml() {
		return "<div class='row'>".
		"<div class='col-lg-6 form-password'><input type='password' autocomplete='off' name='{$this->name}' id='{$this->name}' class='form-control {$this->class} ".(!$this->valid?'error':'')."' value='' placeholder='Новый пароль' /></div>".
		"<div class='col-lg-6 form-password'><input type='password' autocomplete='off' name='{$this->name}_check' id='{$this->name}_check' class='form-control {$this->class} ".(!$this->valid?'error':'')."' value='' placeholder='Еще раз новый пароль' /></div>".
		"<div class='col-lg-12 help-block'>Для смены введите новый пароль два раза</div>".
		"</div>";
	}
	
	public function toSql() {
		if($this->value == '') return "";
		$password = password_hash($this->value, PASSWORD_DEFAULT);
		return "`{$this->name}`= '". mysql_real_escape_string($password)."'";
		
	}

	public static function pageHeader() {
	?>
<style>
	.row .form-password { margin-bottom: 10px; }
</style>
	<?
	}
	
}