<?
include 'finediff.php';

class logDetailsType extends coreType {

	public function toHtml() {
		
		$html = "<div class='well well-sm'>
		<table class='table log-details'>
		<thead>
			<tr>
				<th>Поле</th>
				<th>Изменения</th>
			</tr>
		</thead>
		";
		$details = json_decode($this->value);
		foreach($details as $item) {
			$opcodes = FineDiff::getDiffOpcodes($item->old, $item->new, FineDiff::$wordGranularity);
			$rendered_diff = FineDiff::renderDiffToHTMLFromOpcodes($item->old, $opcodes);
			$html .= "<tr>
				<td>{$item->label}</td>
				<td>{$rendered_diff}</td>
			</tr>
			";
		}
		if(count($details)==0) 
			$html .= "<tr><td colspan='2'>Нет изменений</td></tr>";
		$html .= "</table></div>";
		
		return $html;
	}
	
	public function toSql() {
		return "";
	}
	
	public static function pageHeader() {
?>
<style>
	.log-details ins { background: yellow; padding: 0 3px; }
	.log-details del { background: red; color: white; padding: 0 3px; }
</style>
<?
	}
	
}