<?

/*
-- SQL:
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tag` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tag` (`tag`),
  FULLTEXT KEY `tag_2` (`tag`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=856 ;

-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tags_content` (
  `tag_id` int(10) unsigned NOT NULL,
  `content_id` int(10) unsigned NOT NULL,
  `content_table` varchar(255) NOT NULL,
  PRIMARY KEY (`tag_id`,`content_id`,`content_table`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
*/



class tagsType extends coreType {

	public function toSql() {
		return "";
	}
	
	
	public function ajaxLookup() {
		$tags = $this->db->getAll("SELECT * FROM tags WHERE tag LIKE '".$this->db->escape($_GET['q'], false)."%' ORDER By tag ASC LIMIT 10");
	
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
			if(is_numeric($tag)) 
				$result[] = array('id'=>(int)$tag, 'text'=>$this->db->getOne('SELECT tag FROM tags WHERE id=?', (int)$tag));
			else if(preg_match('/^"(.*)"$/', $tag, $matches))
				$result[] = array('id'=>NULL, 'text'=>$matches[1]);
			else
				$result[] = array('id'=>NULL, 'text'=>$tag);
		}
		return $result;
	}
	
	public function postSave($id, $params, $item) { 
		$this->db->query("DELETE FROM tags_content WHERE content_id={$id} AND content_table='{$this->options['table']}'");
		
		foreach($this->value as $tag) {
			if($tag['id'] ===  NULL) {
				$tag_id = $this->db->query("INSERT IGNORE tags SET tag=?", array($tag['text']));
				if($tag_id == 0) { // такой тег уже есть в базе
					$tag_id =$this->db->getOne("SELECT id FROM tags WHERE tag=?", array($tag['text']));
				}
				$this->db->query("INSERT tags_content SET tag_id={$tag_id}, content_id={$id}, content_table='{$this->options['table']}'");	
			} else if(is_numeric($tag['id'])) {
				$this->db->query("INSERT tags_content SET tag_id={$tag['id']}, content_id={$id}, content_table='{$this->options['table']}'");	
			} else {
				throw new Exception("Tag error: ".print_r($tag, true));
			}
		}
		return ''; 
	}
	public function delete() {
		if(!empty($this->value) && !empty($this->id)) {
			$this->db->query("DELETE FROM tags_content WHERE content_id={$this->id} AND content_table='{$this->options['table']}'");
		}
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
		$values = array();
		foreach($this->value as $value)
			if(is_numeric($value['id']))
				$values[] = $value;
			else if(is_null($value['id']))
				$values[] = array('id'=>'"'.$value['text'].'"', 'text'=>$value['text']);
		
		$value_json = $this->json($values);
		$value_ids = '';
		foreach($values as $tag) {
			if($value_ids != '') $value_ids .= ','; 
			$value_ids .= $tag['id'];
		}
		$value_ids = htmlspecialchars($value_ids);
		$html = <<< EOT
<input type="hidden" id="{$this->name}" name="{$this->name}" class="form-control" value="{$value_ids}"/>		
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