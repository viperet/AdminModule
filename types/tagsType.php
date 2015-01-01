<?

class tagsType extends coreType {

	public function toSql() {
		return "";
	}
	
	
	public function ajaxLookup() {
		$tags = $this->db->getAll("SELECT * FROM tags WHERE tag LIKE '".mysql_real_escape_string($_GET['q'])."%' ORDER By tag ASC LIMIT 10");
	
		$tagsJSON = array();
		foreach($tags as $tag){
			$tagsJSON[] = array('id'=>$tag['id'], 'text'=>$tag['tag']);
		}
		echo $this->json($tagsJSON);
	}
	
	private function parseTags($value) {
		$result = array();
		if(trim($value)=='') return $result;
		$tags = explode(',', $value);
		foreach($tags as $tag) {
			if(preg_match('/^"(.*)"$/', $tag, $matches)) {
				$result[] = array('id'=>$tag, 'text'=>$matches[1]);
			} else {
				$result[] = array('id'=>$tag, 'text'=>$this->db->getOne('SELECT tag FROM tags WHERE id=?', $tag));
			}
		}
		return $result;
	}
	
	public function postSave($id, $params) { 
		$this->db->query("DELETE FROM tags_content WHERE content_id={$id} AND content_table='{$this->options['table']}'");
		
		foreach($this->value as $tag) {
			if(preg_match('/^"(.*)"$/', $tag['id'], $matches)) {
				$this->db->query("INSERT IGNORE tags SET tag=?", array($tag['text']));
				$tag_id = mysql_insert_id();
				if($tag_id == 0) { // такой тег уже есть в базе
					$tag_id =$this->db->getOne("SELECT id FROM tags WHERE tag=?", array($tag['text']));
				}
				$this->db->query("INSERT tags_content SET tag_id={$tag_id}, content_id={$id}, content_table='{$this->options['table']}'");	
			} else {
				$this->db->query("INSERT tags_content SET tag_id={$tag['id']}, content_id={$id}, content_table='{$this->options['table']}'");	
			}
		}
		return ''; 
	}
	
	public function fromRow($row) {
		if(empty($row['id'])) return $this->value = array();
		$this->value = array();
		$tags = $this->db->getAll("SELECT tags_content.tag_id, tags.tag FROM tags_content 
			INNER JOIN tags ON (tags.id = tags_content.tag_id)
			 WHERE content_id={$row['id']} AND content_table='{$this->options['table']}'");
		foreach($tags as $tag) {
			$this->value[] = array('id'=>$tag['tag_id'], 'text'=>$tag['tag']);
		}
	}
	public function fromForm($values) {
		$this->value = $this->parseTags($values[$this->name]);
	}	
	
	public function toString() {
		$result = '';
		foreach($this->value as $tag) {
			if($result != '') $result .= ', '; 
			$result .= $tag['text'];
		}
		return $result;
	}
	
	public function validate(&$errors) {
		if($this->required && count($this->value) == 0 ) {
			$errors[] = sprintf(_("Fill required field '%s'"),htmlspecialchars($this->label));
			$this->errors[] = _('Required field');
			$this->valid = false;
			return false;
		}
		return true;
	}
	
	public function toHtml() {
		$value_json = $this->json($this->value);
		$value_ids = '';
		foreach($this->value as $tag) {
			if($value_ids != '') $value_ids .= ','; 
			$value_ids .= $tag['id'];
		}
		$html = <<< EOT
<input type="hidden" id="{$this->name}" name="{$this->name}" class="form-control" value="{$value_ids}"/>		
<script>
	$('#{$this->name}').select2({
//	  tags: true,
//	  tokenSeparators: [","],
		tokenizer: function(input, selection, callback) {
			// no comma no need to tokenize
			if (input.indexOf(',') < 0 && input.indexOf(' ') < 0)
				return;
	
			var parts = input.split(/,| /);
			for (var i = 0; i < parts.length; i++) {
				var part = parts[i];
				part = part.trim();
	
				callback({id:part,text:part});
			}
		},
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
		multiple: true,
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
	});

</script>
EOT;
		return $html;
	}
	
}