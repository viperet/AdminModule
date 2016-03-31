<?php
class fileType extends coreType {
	public $format = '{id}_{filename}';
	public $subfolders = true;
	public $relative = false; //сохранять путь относительно папки path
	public $path = ''; 
	public $maxsize = 5242880; // 5 MB
	public $blacklist = array('php', 'cgi', 'phtml'); 
	
	protected $session_id;
	protected $original_name;
	
	static function formatSize($size, $precision = 1) {
	    $base = log($size) / log(1024);
	    $suffixes = array('', 'k', 'M', 'G', 'T');   
	
	    return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)].'B';
    }
	
	private function getFilename() {
		if($this->relative)
			$fname = $this->options['root_path'].$this->path.$this->value; 
		else
			$fname = $this->options['root_path'].$this->value; 
		return $fname;
	}
	
	public function toString() {
		$fname = $this->getFilename();
		$name = basename($fname);
		
		if(!file_exists($fname)) return _("File not uploaded");

		$size = filesize($fname);
		return ($this->value!=''?"<span class='glyphicon glyphicon-file'></span> ".htmlspecialchars($name).", ".fileType::formatSize($size):_("File not uploaded"));
	}

	public function fromForm($value) {
		$this->session_id = $value['_session_id'];

		parent::fromForm($value);
		if($this->relative && $this->value!='')
			$this->value = $this->path.$this->value;
		
		if(!empty($value[$this->name.'_remove'])) {
			if(isset($_SESSION['uploads'][$this->session_id][$this->name])) {
				$this->value = $_SESSION['uploads'][$this->session_id][$this->name]['value'];
				$this->delete();
			}
			$this->value = '';
		} elseif(!empty($_FILES[$this->name.'_file']['tmp_name']) &&
		   !empty($_FILES[$this->name.'_file']['name'])) { 
			$fileName = $_FILES[$this->name.'_file']['tmp_name'];
			$translit_filename = $this->translitFileName($_FILES[$this->name.'_file']['name']);

		
		    $path = $this->path.'/tmp/'.$this->session_id.'/';
		    @mkdir($this->options['root_path'].$path, 0777, true);
		    
		    $name = $this->options['root_path'].$path.$translit_filename; // имя файла
		    if (move_uploaded_file($fileName, $name)) {
			    
			    $this->value = $path.$translit_filename;
				$this->original_name = $_FILES[$this->name.'_file']['name']; // оригинальное имя файла
				$_SESSION['uploads'][$this->session_id][$this->name] = array(
					'original_name' => $this->original_name,
					'value' => $this->value,
				);

			} else {
				die("Upload error move_uploaded_file('{$fileName}', '{$name}');");
			}
		} elseif(isset($_SESSION['uploads'][$this->session_id][$this->name])) {
			$this->value = $_SESSION['uploads'][$this->session_id][$this->name]['value'];
			$this->original_name = $_SESSION['uploads'][$this->session_id][$this->name]['original_name'];
		}
		


	}	
	
	public function fromRow($row) {
		parent::fromRow($row);
		if($this->relative && $this->value!='')
			$this->value = $this->path.$this->value;
	}	

	public function toHtml() {
		return "<div class='row file_upload_field' data-maxsize='{$this->maxsize}'>
<div class='col-sm-3 col-xs-3' style='position:relative;'>
<p class='form-control-static file_status' id='{$this->name}'>".$this->toString()."</p>
</div>
<div class='col-sm-9 col-xs-9'>
	<button class='btn btn-default file_upload_remove' type='button'>"._("Delete file")."</button>
	<span class='btn btn-default btn-file'>
	    "._("Upload file")." <input type='file' class='form_input upload_file' name='{$this->name}_file' id='{$this->name}_file'>
	</span>
</div>
<input type='hidden' id='{$this->name}_remove' name='{$this->name}_remove' value='0' />
<input type='hidden' id='{$this->name}_loaded' name='{$this->name}_loaded' value='".(!empty($this->value)?'1':'0')."' />
</div>
";
	}
	public function toSql() { return ""; }	
	
	public function validate(&$errors) {
		$valid = true;
		if(!empty($this->value)) {
			$fname = $this->getFilename();
			$ext = mb_convert_case(pathinfo($fname, PATHINFO_EXTENSION), MB_CASE_LOWER);
			if(in_array($ext, $this->blacklist)) {
						$this->valid = false;
						$this->errors[] = sprintf(_("Upload of files with extension '%s' is forbidden"), htmlspecialchars($ext));
						$errors[] = _('Invalid file format');
						$this->delete();
						return false;
			}
			if(isset($this->validation)) {
				if(is_array($this->validation)) {
					if(!in_array($ext, $this->validation)) {
						$this->valid = false;
						$this->errors[] = _('Allowed file formats: ').htmlspecialchars(implode(', ', $this->validation));
						$errors[] = sprintf(_("Invalid file format '%s'"), htmlspecialchars($ext));
						$valid = false;
						$this->delete();
					}
				} else {
					throw new Exception("Validation field in '{$this->name}' should be array of allowed file formats");
				}
			}
			$stat = @stat($fname);
			if($stat) {
				$fsize = $stat['size'];
				if ($fsize > $this->maxsize) {
					$this->valid = false;
					$this->errors[] = sprintf(_('Maximum file size %s'), fileType::formatSize($this->maxsize));
					$errors[] = _('File too large');
					$valid = false;
					$this->delete();
			    }
			}
		}

	    // если обязательное поле и нет старого значения и нет нового файла - ошибка    
		if($this->required && empty($this->value)) {
			$errors[] = sprintf(_("Upload file in field '%s'"), htmlspecialchars($this->label));
			$this->errors[] = _('Required field');
			$this->valid = false;
			$valid = false;
		}
	    return $valid;
	}
	
	
	public function postSave($id, $params, $item) { 
		if($this->subfolders)
			$relative_path = chunk_split(substr(str_pad($id, 4, '0', STR_PAD_LEFT), 0, 4), 2,'/');
		else
			$relative_path = '';
		
	    $path = $this->path.$relative_path;		
	    @mkdir($this->options['root_path'].$path, 0777, true);
	    
	    
		if(!empty($this->value) &&
		   !empty($this->original_name)) { 
			$fileName = $this->options['root_path'].$this->value;

		    $translit_filename = $this->translitFileName($this->original_name);	    
						    			
		    $fname = str_replace(array(
		    			'{id}',
		    			'{filename}',
		    		), array(
		    			$id,
		    			$translit_filename,
		    		), $this->format);

		} elseif(empty($this->value)) { // удаление файла 
			$this->cleanup();
			return "`{$this->name}` = ''";
		} else {
			$this->cleanup();
			return "";
		}
			
		$name= $this->options['root_path'].$path.$fname; // имя файла
			
		if (rename($fileName, $name)) {
		    // Файл корректен и был успешно загружен
		    
			$this->cleanup();

			if($this->relative)
				$url = $fname; //."?v=".time(); // ссылка на файл
			else
				$url = $path.$fname; //."?v=".time(); // ссылка на файл

			return "`{$this->name}` = ".$this->db->escape($url);
		} else {
			$this->cleanup();
			die("Error rename('$fileName', '$name')");
			return ''; 
		}	    

		return ''; 
	}

	// удаление файла
	public function delete() { 
		if(!empty($this->value)) {
			$fname = $this->getFilename();
			@unlink($fname);
			$this->removeEmptySubFolders($this->options['root_path'].$this->path);
		}
	}
	
	// очистка временных файлов
	public function cleanup() { 
		if(!empty($this->session_id)) {
			$path = $this->options['root_path'].$this->path.'/tmp/'.$this->session_id.'/';
			if(file_exists($path)) $this->recRmDir($path);
			unset($_SESSION['uploads'][$this->session_id][$this->name]);
		}
		$this->removeEmptySubFolders($this->options['root_path'].$this->path);
	}

	private function recRmDir($path) {
		if(!file_exists($path)) return;
		if(is_file($path)) { 
			unlink($path);
			return;
		}
		$dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::CHILD_FIRST);
		
		for ($dir->rewind(); $dir->valid(); $dir->next()) {
		    if ($dir->isDir()) {
		        @rmdir($dir->getPathname());
		    } else {
		        @unlink($dir->getPathname());
		    }
		}
		rmdir($path);	
	}	

	private function removeEmptySubFolders($path) {
		$empty=true;
		foreach (glob($path.DIRECTORY_SEPARATOR."*") as $file) {
			$empty &= is_dir($file) && $this->removeEmptySubFolders($file);
		}
		return $empty && rmdir($path);
	}	
	
	private function translitFileName($translit_filename) {
	    return $this->translit(pathinfo($translit_filename, PATHINFO_FILENAME)).'.'.mb_convert_case(pathinfo($translit_filename, PATHINFO_EXTENSION), MB_CASE_LOWER);
	}

	private static function translit($text) { 
		$text = mb_convert_case($text, MB_CASE_LOWER);
		preg_match_all('/./u', $text, $text); 
		$text = $text[0]; 
		$simplePairs = array('і'=>'i', 'ї'=>'i', 'є'=>'e', 'а' => 'a' , 'л' => 'l' , 'у' => 'u' , 'б' => 'b' , 'м' => 'm' , 'т' => 't' , 'в' => 'v' , 'н' => 'n' , 'ы' => 'y' , 'г' => 'g' , 'о' => 'o' , 'ф' => 'f' , 'д' => 'd' , 'п' => 'p' , 'и' => 'i' , 'р' => 'r' ); 
		$complexPairs = array( 'з' => 'z' , 'ц' => 'c' , 'к' => 'k' , 'ж' => 'zh' , 'ч' => 'ch' , 'х' => 'h' , 'е' => 'e' , 'с' => 's' , 'ё' => 'jo' , 'э' => 'e' , 'ш' => 'sh' , 'й' => 'j' , 'щ' => 'shh' , 'ю' => 'ju' , 'я' => 'ja', 'ъ' => "" , 'ь' => "" ); 
		$specialSymbols = array( "_" => "-", "'" => "", "`" => "", "^" => "", " " => "-", '.' => '-', ',' => '-', ':' => '', '"' => '', "'" => '', '<' => '', '>' => '', '«' => '', '»' => '', ' ' => '-', '/' => '-', '\\' => '-' ); 
		$translitLatSymbols = array( 'a','l','u','b','m','t','v','n','y','g','o', 'f','d','p','i','r','z','c','k','e','s', 'A','L','U','B','M','T','V','N','Y','G','O', 'F','D','P','I','R','Z','C','K','E','S', ); 
		$simplePairsFlip = array_flip($simplePairs); 
		$complexPairsFlip = array_flip($complexPairs); 
		$specialSymbolsFlip = array_flip($specialSymbols); 
		$charsToTranslit = array_merge(array_keys($simplePairs),array_keys($complexPairs)); 
		$translitTable = array(); 
		foreach($simplePairs as $key => $val) $translitTable[$key] = $simplePairs[$key]; 
		foreach($complexPairs as $key => $val) $translitTable[$key] = $complexPairs[$key]; 
		foreach($specialSymbols as $key => $val) $translitTable[$key] = $specialSymbols[$key]; 
		$result = ""; 
		$nonTranslitArea = false; 
		foreach($text as $char) { 
			if(in_array($char,array_keys($specialSymbols))) { 
				$result.= $translitTable[$char]; 
			} elseif(in_array($char,$charsToTranslit)) { 
				if($nonTranslitArea) { 
					$result.= ""; 
					$nonTranslitArea = false; 
				} 
				$result.= $translitTable[$char]; 
			} else { 
				if(!$nonTranslitArea && in_array($char,$translitLatSymbols)) { 
					$result.= ""; 
					$nonTranslitArea = true; 
				} $result.= $char; 
			} 
		} 
		$result = preg_replace('/[^0-9a-z\-]/', '', $result);
		return strtolower(preg_replace("/[-]{2,}/", '-', $result)); 
	} 

	public static function pageHeader() {
?>
<style>
	.file_upload_field.hover {
		border: 1px dashed #CCCCCC;
		background: #F5F5F5;
	}
</style>
<script>
$(function() {
	
	$(document).on('drop', function (e)	{
	    e.stopPropagation().preventDefault();
	});
	
	function escapeHtml(text) {
	  var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
	  return text.replace(/[&<>"']/g, function(m) { return map[m]; });
	}
	function formatSize(a,b,c,d,e){
	 return (b=Math,c=b.log,d=1e3,e=c(a)/c(d)|0,a/b.pow(d,e)).toFixed(1)
	 +' '+(e?'kMGTPEZY'[--e]+'B':'Bytes')
	}
	$('.file_upload_field').on('change', 'input.upload_file', function () {
			var el = $(this);
			var p = el.parents('.file_upload_field').find('.file_status');
			if(this.files.length == 1) {
				var file = this.files[0];
				p.html("<span class='glyphicon glyphicon-file'></span> "+escapeHtml(file.name)+", "+formatSize(file.size));
				$('#'+p.attr('id')+'_remove').val('0'); // устанавливаем флаг удаления 
			} else {
				p.text('<?=_("File not uploaded")?>');
				$('#'+p.attr('id')+'_remove').val('1'); // устанавливаем флаг удаления 
			}
	});
	$('.file_upload_remove').click(function () { // удаление файла
		var el = $(this);
		var p = el.parents('.file_upload_field').find('.file_status');
		
		p.text('<?=_("File not uploaded")?>');

		var file_id = $('#'+p.attr('id')+'_file');
		file_id.val('').replaceWith( file_id = file_id.clone( true ) );
		$('#'+p.attr('id')+'_remove').val('1'); // устанавливаем флаг удаления 
		return false;
	});
});

</script>
<?php 

	}

	
}