<?php

class detailsType extends coreType {
	public $details_table = '';
	public $details_field = '';
	public $details_sort = 'id';
	public $master_id = 0;
	public $form = array();	
	
	private $data = array();

	function __construct($db, $name, $array) {
		parent::__construct($db, $name, $array);

		foreach($this->form as $name=>&$array) {
			$className = $array['type']."Type";
			$array = new $className($db, $name, $array);
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
		if(empty($row['id'])) {
			// добавление записи, данные брать неоткуда
			return;
		}
		$this->master_id = $row['id'];
		$rows = $this->db->getAll("SELECT * FROM {$this->details_table} WHERE {$this->details_field} = {$this->master_id} ORDER BY {$this->details_sort} ASC");
		$index = 0;
		$this->data = array();
		foreach($rows as $row) {
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
					if(!$field->validate($errors)) {
						$valid = false;
					}
				}
			}
		}
		if(!$valid) $errors[] = sprintf(_("Error in field '%s'"), $this->label);
		if($this->required && count($this->data) == 0) {
			$valid = false;
			$errors[] = sprintf(_("Fill at least one entry in '%s'"), $this->label);
		}
		$this->valid = $valid;
		return $valid;
	}
	
	
	
	
	public function toHtml() {
		$html = "<table class='table details_table'><thead><tr><th class='hidden'>ID</th>";
		foreach($this->form as $name=>$field) {
			$html .= "<th>".htmlspecialchars($field->label).($field->required?'*':'')."</th>";
		}
		$html .= "<th></th></tr></thead>";
		$html .= "<tbody>";
		
		$actions_td = "<td class='actions_td'><a href='#' onClick='return removeRow(this);'>["._('Delete')."]</a>"
					. "&nbsp; <a href='#' onClick=\"return addRow($(this).parents('tr:first'));\">["._('Copy')."]</a></td>";
					
		$actions_td = "<td class='actions_td'><div role='group' class='btn-group'>
					<div onClick=\"return addRow($(this).parents('tr:first'));\" class='btn btn-default'><span title='"._('Copy')."' class='glyphicon glyphicon-sound-stereo'></span></div> 
					<div onclick='return confirm(\""._('Delete?')."\")?removeRow(this):false;' class='btn btn-default'><span title='"._('Delete')."' class='glyphicon glyphicon-remove'></span></div> 
					</div></td>";
					
		$index = 0;
		foreach($this->data as $row) {
			$html .= "<tr data-index='{$index}'>";
			$html .= "<td class='id_td hidden'><input type='hidden' name='{$this->name}[{$index}][id]' value='{$row['id']}'>{$row['id']}</td>";
			foreach($row as $name=>$field) {
				if(is_object($field)) {
					$html .= "<td>".$field->toHtml()."</td>";
				}
			}
			$html .= $actions_td."</tr>";
			$index++;
		}
		$html .= "<tr class='new_row'>";
		$html .= "<td class='id_td hidden'><input type='hidden' name='{$this->name}[%%ID%%][id]' value=''></td>";
		foreach($this->form as $name=>$field) {
			$field->fromForm(array($name=>''));
			$oldName = $field->name;
			$field->name = "{$this->name}[%%ID%%][{$oldName}]";
			$html .= "<td>".$field->toHtml()."</td>";
			$field->name = $oldName;
		}
		$html .= $actions_td."</tr></tbody></table>";
		$html .= "<button type='button' class='pull-right btn btn-default' onClick=\"return addRow($(this).parents('.form-group:first').find('.details_table tr.new_row'));\"><i class='glyphicon glyphicon-plus'></i> "._('Add')."</button>";
		return $html;		
		
	}

	public function postSave($id, $params) { 
		$res = $this->db->query("DELETE FROM {$this->details_table} WHERE `{$this->details_field}` = '{$id}'");

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
				$res = $this->db->query($sql);
				if(!$res) {
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
	    var table = $(el).parents('.form-group:first').find('.details_table');
	    var last_row = table.find('tr.new_row');
		var index = table.find('tr:not(.new_row):last').data('index');
		if(index === undefined) 
			index = 0; 
		else 
			index++;
		var clone_row = new_row.html().replace(/\[(%%ID%%|\d+)\]\[/g, '['+index+'][' );
		clone_row = $('<tr>'+clone_row+'</tr>').attr('class','').data('index', index); 
//		console.log(clone_row);
		clone_row.find('td.id_td').contents().last()[0].textContent='';
		clone_row.find('td.id_td input').val('');
		clone_row.find('.select2-container').remove(); // фикс для select2
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