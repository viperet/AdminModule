<?

class detailsType extends coreType {
	public $details_table = '';
	public $details_field = '';
	public $details_sort = 'id';
	public $master_id = 0;
	public $form = array();	
	
	private $data = array();

	function __construct($name, $array) {
		parent::__construct($name, $array);

		foreach($this->form as $name=>&$array) {
			$className = $array['type']."Type";
			$array = new $className($name, $array);
		}
		unset($array);	
	}
	
	public function toStringTruncated() {
		return "";		
	}
	public function toString() {
		return "";		
	}
	
	
	// загружает структуру из POST
	public function fromForm($value) {
		unset($value[$this->name]['%%ID%%']);
/* 		$this->data = $value[$this->name];		 */
		$this->data = array();
		$index = 0;
		foreach($value[$this->name] as $index=>$row) {
			foreach($this->form as $name=>$field) {
				$item = clone $field;
				$item->fromForm($row);
				$item->name = "{$this->name}[{$index}][{$name}]";
				$this->data[$index][$name] = $item; 
			}			
			$this->data[$index]['id'] = $row['id']; 
			$index++;
		}
/*
		echo "<pre>";
		print_r($value);
		print_r($this->data);
		exit;
*/
	}
	
	
	// загружает структуру из ДБ
	public function fromRow($row) {
		global $db;
		if(empty($row['id'])) {
			// добавление записи, данные брать неоткуда
			return;
		}
		$this->master_id = $row['id'];
		$res = $db->query("SELECT * FROM {$this->details_table} WHERE {$this->details_field} = {$this->master_id} ORDER BY {$this->details_sort} ASC");
		$index = 0;
		$this->data = array();
		while($row = $res->fetchRow()) {
			foreach($this->form as $name=>$field) {
				$item = clone $field;
				$item->fromRow($row);
				$item->name = "{$this->name}[{$index}][{$name}]";

				$this->data[$index][$name] = $item; 
			}
			$this->data[$index]['id'] = $row['id'];
			$index++;
		}
		
/*
		echo "<pre>";
		print_r($this->data);
		exit;
*/
	}
	
	
	public function validate(&$errors) {
		$valid = true;
		foreach($this->data as $row) {
			foreach($row as $name=>$field) {
				if(is_object($field)) {
					if(!$field->validate()) {
						$valid = false;
					}
				}
			}
		}
		if(!$valid) $errors[] = "Ошибка в заполнении поля '".htmlspecialchars($this->label)."'";
		if($this->required && count($this->data) == 0) {
			$valid = false;
			$errors[] = "Введите хотя бы одно значение в '".htmlspecialchars($this->label)."'";
		}
		$this->valid = $valid;
		return $valid;
	}
	
	
	
	
	public function toHtml() {
		global $db;
		$html = "<table class='details_table'><thead><tr><td>ID</td>";
		foreach($this->form as $name=>$field) {
			$html .= "<td>".$field->toHtmlLabel()."</td>";
		}
		$html .= "<td></td></tr></thead>";
		$html .= "<tbody>";
		
		$actions_td = "<td class='actions_td'><a href='#' onClick='return removeRow(this);'>[Удалить]</a>"
					. "&nbsp; <a href='#' onClick=\"return addRow($(this).parents('tr:first'));\">[Копировать]</a></td>";
		$index = 0;
		foreach($this->data as $row) {
			$html .= "<tr data-index='{$index}'>";
			$html .= "<td class='id_td'><input type='hidden' name='{$this->name}[{$index}][id]' value='{$row['id']}'>{$row['id']}</td>";
			foreach($row as $name=>$field) {
				if(is_object($field)) {
					$html .= "<td>".$field->toHtml()."</td>";
				}
			}
			$html .= $actions_td."</tr>";
			$index++;
		}
		$html .= "<tr class='new_row'>";
		$html .= "<td class='id_td'><input type='hidden' name='{$this->name}[%%ID%%][id]' value=''></td>";
		foreach($this->form as $name=>$field) {
			$field->fromForm(array($name=>''));
			$oldName = $field->name;
			$field->name = "{$this->name}[%%ID%%][{$oldName}]";
			$html .= "<td>".$field->toHtml()."</td>";
			$field->name = $oldName;
		}
		$html .= $actions_td."</tr></tbody></table>";
		$html .= "<button type='button' onClick=\"return addRow($(this).parents('.input:first').find('.details_table tr.new_row'));\">Добавить</button>";
		return $html;		
		
	}

	public function postSave($id, $params) { 
		global $db;

		$res = $db->query("DELETE FROM {$this->details_table} WHERE `{$this->details_field}` = '{$id}'");
		if($res !== 1) {
			echo "SQL error while deleting old details<br>";
			echo nl2br($res->result->userinfo);
			exit;
		}	
		foreach($this->data as $row) {
			$sql = "INSERT {$this->details_table} SET `{$this->details_field}` = '{$id}', ";
			$sql_values = array();
			foreach($this->form as $name=>$field) {
				$item = $row[$name];
				$oldName = $item->name;
				$item->name = $field->name;
				$sql_values[] = $item->toSql();
				$item->name = $oldName;
			}
			if(count($sql_values)>0) {
				if(!empty($row['id'])) $sql .= " `id` = '{$row['id']}', ";
				$sql .= implode(', ', $sql_values);
/* 				echo $sql."<br>"; */
				$res = $db->query($sql);
				if($res !== 1) {
					echo "SQL error while saving details<br>";
					echo nl2br($res->result->userinfo);
					exit;
				}	
				
			}
				
		}
		return false; 
	}
	
	public function toSql() {
		return "";
	}
	
	public static function pageHeader() {
		return <<< EOT
<script>
	function removeRow(el) {
		var row = $(el).parents('tr:first').remove();
		return false;
	}
	function addRow(el) {
		var new_row = $(el);
	    var table = $(el).parents('.input:first').find('.details_table');
	    var last_row = table.find('tr.new_row');
		var index = table.find('tr:not(.new_row):last').data('index');
		if(index === undefined) 
			index = 0; 
		else 
			index++;
		var clone_row = new_row.clone().attr('class','').data('index', index); 
		clone_row.find('td.id_td').contents().last()[0].textContent='';
		clone_row.find('td.id_td input').val('');
		var inputs = clone_row.find('[name]');
		console.log(index);
		inputs.each(function () {
			var input = $(this);
			console.log(input);
			var new_name = input.attr('name').replace(/\[(%%ID%%|\d+)\]\[/, '['+index+'][' );
			input.attr('name', new_name);
			input.attr('id', new_name);
		});
		last_row.before(clone_row);
		return false;
	}
</script>
<style>
.details_table .new_row { display: none; }
.details_table .id_td { width: 1px; }
</style>

EOT;
	}
	
}