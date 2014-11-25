<?

class select2Type extends textType {
	public $values = array();
	public $lookup_table; // таблица по которой смотреть
	public $lookup_field; // поле по которому искать
	public $lookup_display; // поле которое отображать
	public $lookup_sort; // сортировка
	public $lookup_where = '1';
	public $create_variant = false; // можно ли создавать новые значения на лету, для работы необходима включенная опция ajax
	public $ajax = false;
	
	function __construct($db, $name, $array) {
		
		parent::__construct($db, $name, $array);
		if(empty($this->lookup_display) && !empty($this->lookup_field))
			$this->lookup_display = $this->lookup_field;
		if(empty($this->lookup_sort) && !empty($this->lookup_field))
			$this->lookup_sort = $this->lookup_field." ASC ";
		if($this->lookup_table != '' && $this->lookup_field != '' && $this->ajax == false) {
			$this->values = $this->values + $this->getLookup();
		}
	}

	public static function pageHeader() {
	?>
<script src="/js/select2.js"></script>
<link href="/js/select2.css" media="screen" type="text/css" rel="stylesheet">
	<?
	}

	
	public function getLookup($value, $limit) {
		
		$result = array();
		$sql = "SELECT id,".$this->lookup_display." value FROM ".$this->lookup_table.
			(!empty($value) ? " WHERE {$this->lookup_where} AND {$this->lookup_field} LIKE '%".mysql_real_escape_string($value)."%' " : "").
			" ORDER BY ".$this->lookup_sort.
			(!empty($limit) ? " LIMIT $limit" : "");
//		echo $sql;
		$values = $this->db->getAll($sql);
		foreach($values as $value) {
			$result[$value['id']] = $value['value']; 
		}
		return $result;
	}
	
	public function ajaxLookup() {
		$lookup = $this->getLookup($_REQUEST['q']);
		$data = array();
		foreach($lookup as $id => $value) {
			$data[] = array('id'=>$id, 'text'=>$value);
		}
		echo $this->json($data);
	}
	
	public function lookupValueById($id) {
		
		return $this->db->getOne("SELECT {$this->lookup_display} value FROM ".$this->lookup_table." WHERE id = '{$id}'");
	}
	
	public function toString() {
		$value = $this->value;
		if($this->lookup_table != '' && $this->lookup_field !='') {
			$this->value = $this->lookupValueById($value);
		} elseif(isset($this->values[$value]))
			$this->value = $this->values[$value];
		$result = parent::toString();
		$this->value = $value;
		return $result;
	}
	
	public function toHtml() {
		
		
		if($this->ajax) {
			$value_text = $this->lookupValueById($this->value);
			$html = "<input type='hidden' name='{$this->name}' id='{$this->name}' class='{$this->class}' value='{$this->value}'>";
			$html .= "
	<script>
		$(document.getElementById('{$this->name}')).select2({
			".($this->create_variant?"createSearchChoice: function (term) { return { id: term, text: term }; },":"")."
			minimumInputLength: 2,";
			if($this->value!='') {
				$html .= "
				initSelection: function(element, callback) {
					callback({id: {$this->value}, text: ".json_encode($value_text)."}); 
				},";
			}
			$html .= "
			ajax: {
				url: document.location+'&ajaxField='+encodeURIComponent('{$this->name}')+'&ajaxMethod=ajaxLookup',
				dataType: 'json',
				data: function (term, page) {
					return {
						q: term
					};
				},
				results: function (data, page) {
					return { results: data };
				}
			}
		});
	</script>";
		} else {
		
			$html = "<select name='{$this->name}' id='{$this->name}' class='form_select {$this->class} ".(!$this->valid?'error':'')."'>";
			foreach($this->values as $value=>$label) {
				if(is_array($label)) {
					$html .= "<optgroup label='{$value}'>";
					foreach($label as $subvalue => $sublabel) 
						$html .= "<option value='{$subvalue}' ".($this->value==$subvalue?"selected":'').">".htmlspecialchars($sublabel)."</option>";
					$html .= "</optgroup>";
					
				} else {
					$html .= "<option value='{$value}' ".($this->value==$value?"selected":'').">".htmlspecialchars($label)."</option>";
				}
			}
			$html .= "</select>
	<script>
		$('#{$this->name}').select2();
	</script>";
		}
		return $html;
	}
	
}