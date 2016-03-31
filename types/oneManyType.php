<?php

class oneManyType extends coreType {
	public $details_table = '';
	public $details_field = '';
	public $details_display = '';
	public $details_sort = 'id ASC';
	public $master_id = 0;
	public $placeholder = 'Добавить...';
	
	private $data = array();

	public function toStringTruncated() {
		return "";		
	}
	public function toString() {
		return "";		
	}
	
	
	public function ajaxLookup() {
		$tagsJSON = $this->db->getAll("SELECT id, {$this->details_display} text FROM {$this->details_table} WHERE {$this->details_display} LIKE '".$this->db->escape($_GET['q'], false)."%' ORDER BY {$this->details_sort}");
	
		echo $this->json($tagsJSON);
	}	
	
	private function parseTags($value) {
		$result = array();
		if(trim($value)=='') return $result;
		$tags = explode(',', $value);
		foreach($tags as $tag) {
			if(is_numeric($tag)) 
				$result[] = array('id'=>(int)$tag, 'text'=>$this->db->getOne("SELECT {$this->details_display} text FROM {$this->details_table} WHERE id=?", (int)$tag));
		}
		return $result;
	}	
	
	// загружает структуру из POST
	public function fromForm($values) {
		$this->value = $this->parseTags($values[$this->name]);
	}
	
	// загружает структуру из ДБ
	public function fromRow($row) {
		global $db;
		if(empty($row['id'])) {
			// добавление записи, данные брать неоткуда
			return;
		}
		$this->master_id = $row['id'];
		$this->value = $this->db->getAll("SELECT id, {$this->details_display} text FROM {$this->details_table} WHERE {$this->details_field} = {$this->master_id} ORDER BY {$this->details_sort}");
	}
	
	
	public function validate(&$errors) {
		$valid = true;
/*
		foreach($this->data as $row) {
			foreach($row as $name=>$field) {
				if(is_object($field)) {
					if(!$field->validate(&$errors)) {
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
*/
		return $valid;
	}
	
	public function toHtml() {
		$values = $this->value;
		$value_json = $this->json($values);
		$value_ids = '';
		foreach($values as $tag) {
			if($value_ids != '') $value_ids .= ','; 
			$value_ids .= $tag['id'];
		}
		$value_ids = htmlspecialchars($value_ids);
		$html = <<< EOT
<input type="hidden" id="{$this->name}" name="{$this->name}" class="form-control one-many" value="{$value_ids}"/>		
<script>
	$('#{$this->name}').select2({
//	  tags: true,
//	  tokenSeparators: [","],
		tokenizer: function(input, selection, callback) {
			// no comma no need to tokenize
			if (input.indexOf(',') < 0)
				return;
	
			var parts = input.split(/,/);
			for (var i = 0; i < parts.length; i++) {
				var part = parts[i];
				part = part.trim();
	
				callback({id:part,text:part});
			}
		},
/*
		createSearchChoice: function(term, data) {
			if ($(data).filter(function() {
				return this.text.localeCompare(term) === 0;
			}).length === 0) {
				return {
					id: '"'+term+'"',
					text: term
				};
			}
		},
*/
		multiple: true,
		minimumInputLength: 3,
		initSelection: function(element, callback) {
			callback({$value_json});	  
		},
		ajax: {
		url: document.location+'&ajaxField='+encodeURIComponent('{$this->name}')+'&ajaxMethod=ajaxLookup',
			dataType: 'json',
			data: function(term, page) {
		    return {
		    	q: term
		    };
		},
		results: function(data, page) {
		    return {
		    	results: data
		    };
		  }
		}
	}).select2("container").find('.select2-search-field input.select2-input').attr('placeholder', '{$this->placeholder}');

</script>
EOT;
		return $html;
	}
	


	public function postSave($id, $params) { 
		
		$ids = array();
		foreach($this->value as $tag) {
			$ids[] = $tag['id'];
		}
		$this->db->query("UPDATE {$this->details_table} SET {$this->details_field}=NULL WHERE {$this->details_field}={$id} AND id NOT IN (".implode(',',$ids).")");
		$this->db->query("UPDATE {$this->details_table} SET {$this->details_field}={$id} WHERE id IN (".implode(',',$ids).")");
		return ''; 
	}

	
	public function toSql() {
		return "";
	}
	public static function pageHeader() {
		return <<< EOT
<style>
.one-many.select2-container-multi .select2-choices li {
    float: none;
}	
.one-many.select2-container-multi .select2-choices li.select2-search-field input.select2-input {
	width: 100% !important;
}
</style>

EOT;
	}	
	
	
}