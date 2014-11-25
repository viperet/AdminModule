<?

class tagsType extends coreType {

	public function toSql() {
		return "";
	}
	
	private function parseTags($value) {
		global $db;
		$result = array();
		if(trim($value)=='') return $result;
		
		$tags = explode(',', $value);
		foreach($tags as $tag) {
			if(preg_match('/^"(.*)"$/', $tag, $matches)) {
				$result[] = array('id'=>$tag, 'text'=>$matches[1]);
			} else {
				$result[] = array('id'=>$tag, 'text'=>$db->getOne('SELECT tag FROM tags WHERE id='.$tag));
			}
		}
		return $result;
	}
	
	public function postSave($id, $params) { 
		global $db;
		$db->query("DELETE FROM tags_content WHERE content_id={$id} AND content_table='{$this->options['table']}'");
		
		foreach($this->value as $tag) {
			if(preg_match('/^"(.*)"$/', $tag['id'], $matches)) {
				$db->query("INSERT IGNORE tags SET tag=?", array($tag['text']));
				$tag_id = mysql_insert_id();
				if($tag_id == 0) { // такой тег уже есть в базе
					$tag_id =$db->getOne("SELECT id FROM tags WHERE tag=?", array($tag['text']));
				}
				$db->query("INSERT tags_content SET tag_id={$tag_id}, content_id={$id}, content_table='{$this->options['table']}'");	
			} else {
				$db->query("INSERT tags_content SET tag_id={$tag['id']}, content_id={$id}, content_table='{$this->options['table']}'");	
			}
		}
		return ''; 
	}
	
	public function fromRow($row) {
		global $db;
		$this->value = array();
		$tags = $db->getAll("SELECT tags_content.tag_id, tags.tag FROM tags_content 
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
	
	public function toHtml() {
		$value_json = json_encode($this->value);
		$value_ids = '';
		foreach($this->value as $tag) {
			if($value_ids != '') $value_ids .= ','; 
			$value_ids .= $tag['id'];
		}
		$html = <<< EOT
<input type="hidden" id="{$this->name}" name="{$this->name}" class="form_select" value="{$value_ids}"/>		
<script>
	$(document.getElementById('{$this->name}')).select2({
	  tags: true,
	  tokenSeparators: [","],
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
	    url: '/admin/tags.php',
	    dataType: "jsonp",
	    data: function(term, page) {
	      return {
	        q: term
	      };
	    },
	    results: function(data, page) {
	    	console.log(data);
	      return {
	        results: data.results
	      };
	    }
	  }
	});

</script>
EOT;
		return $html;
	}
	
}