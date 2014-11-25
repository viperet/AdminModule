<?

class htmlType extends textareaType {
	public $height = 300;

	public static function pageHeader() {
	?>
<script src="/admin/ckeditor/ckeditor.js"></script>
	<?
	}
	
	public function toHtml() {
		return "<textarea name='{$this->name}' id='{$this->name}' rows='{$this->rows}' class='form_textarea {$this->class}'>".$this->escape($this->value)."</textarea>
<script>
    CKEDITOR.replace('{$this->name}');
</script>";
	}
}