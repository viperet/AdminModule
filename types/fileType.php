<?
class fileType extends coreType {
	public $format = '{id}_{filename}';
	public $subfolders = true;
	public $relative = false; //сохранять путь относительно папки path
	public $path = ''; 
	public $maxsize = 5242880; // 5 MB
	public $blacklist = array('php', 'cgi', 'phtml'); 
	
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
<input type='hidden' id='{$this->name}_remove' name='{$this->name}_remove' value='".(empty($this->value)?'1':'0')."' />
</div>
";
	}
	public function toSql() { return ""; }	
	
	public function validate(&$errors) {

		$valid = true;
		if(!empty($_FILES[$this->name.'_file']['name'])) {
			$ext = pathinfo($_FILES[$this->name.'_file']['name'], PATHINFO_EXTENSION);
			if(in_array($ext, $this->blacklist)) {
						$this->valid = false;
						$this->errors[] = sprintf(_("Upload of files with extension '%s' is forbidden"), htmlspecialchars($ext));
						$errors[] = _('Invalid file format');
						return false;
			}
			if(isset($this->validation)) {
				if(is_array($this->validation)) {
					if(!in_array($ext, $this->validation)) {
						$this->valid = false;
						$this->errors[] = _('Allowed file formats: ').htmlspecialchars(implode(', ', $this->validation));
						$errors[] = sprintf(_("Invalid file format '%s'"), htmlspecialchars($ext));
						$valid = false;
					}
				} else {
					throw new Exception("Validation field in '{$this->name}' should be array");
				}
			}
		}
		if ($_FILES[$this->name.'_file']['size'] > $this->maxsize) {
			$this->valid = false;
			$this->errors[] = sprintf(_('Maximum file size %s'), fileType::formatSize($this->maxsize));
			$errors[] = _('File too large');
			$valid = false;
	    }
		if($this->required && !empty($params[$this->name.'_remove']) && empty($_FILES[$this->name.'_file']['name'])) {
			$errors[] = sprintf(_("Upload file in field '%s'"), htmlspecialchars($this->label));
			$this->errors[] = _('Required field');
			$this->valid = false;
			$valid = false;
		}
	    return $valid;
	}
	
	
	public function postSave($id, $params, $item) { 
		$this->fromRow($item);

		if($this->subfolders)
			$relative_path = chunk_split(substr(str_pad($id, 4, '0', STR_PAD_LEFT), 0, 4), 2,'/');
		else
			$relative_path = '';
		
	    $path = $this->path.$relative_path;		
	    @mkdir($this->options['root_path'].$path, 0777, true);
	    
	    
		if(!empty($_FILES[$this->name.'_file']['tmp_name']) &&
		   !empty($_FILES[$this->name.'_file']['name'])) {
			$fileName = $_FILES[$this->name.'_file']['tmp_name'];

		    $translit_filename = $this->translit($_FILES[$this->name.'_file']['name']);
			
			if(!empty($this->value)) { // удаляем предидущий файл 
				$oldfname = $this->getFilename();
				@unlink($oldfname);
			}

		    $fname = str_replace(array(
		    			'{id}',
		    			'{filename}',
		    		), array(
		    			$id,
		    			$translit_filename,
		    		), $this->format);

		} elseif(!empty($params[$this->name.'_remove']) && !empty($this->value)) { // удаление файла 
			$fname = $this->getFilename();
			@unlink($fname);
			return "`{$this->name}` = ''";
		} else 
			return "";
			
		$name= $this->options['root_path'].$path.$fname; // имя файла
			
		if (move_uploaded_file($fileName, $name)) {
		    // Файл корректен и был успешно загружен

			if($this->relative)
				$url = $fname; //."?v=".time(); // ссылка на файл
			else
				$url = $path.$fname; //."?v=".time(); // ссылка на файл

			return "`{$this->name}` = '".mysql_real_escape_string($url)."'";
		} else {
		     // Возможная атака с помощью файловой загрузки
			return ''; 
		}	    
		

		return ''; 
	}

	private static function translit($text) { 
		preg_match_all('/./u', $text, $text); 
		$text = $text[0]; 
		$simplePairs = array( 'а' => 'a' , 'л' => 'l' , 'у' => 'u' , 'б' => 'b' , 'м' => 'm' , 'т' => 't' , 'в' => 'v' , 'н' => 'n' , 'ы' => 'y' , 'г' => 'g' , 'о' => 'o' , 'ф' => 'f' , 'д' => 'd' , 'п' => 'p' , 'и' => 'i' , 'р' => 'r' , 'А' => 'A' , 'Л' => 'L' , 'У' => 'U' , 'Б' => 'B' , 'М' => 'M' , 'Т' => 'T' , 'В' => 'V' , 'Н' => 'N' , 'Ы' => 'Y' , 'Г' => 'G' , 'О' => 'O' , 'Ф' => 'F' , 'Д' => 'D' , 'П' => 'P' , 'И' => 'I' , 'Р' => 'R' , ); 
		$complexPairs = array( 'з' => 'z' , 'ц' => 'c' , 'к' => 'k' , 'ж' => 'zh' , 'ч' => 'ch' , 'х' => 'kh' , 'е' => 'e' , 'с' => 's' , 'ё' => 'jo' , 'э' => 'e' , 'ш' => 'sh' , 'й' => 'j' , 'щ' => 'shh' , 'ю' => 'ju' , 'я' => 'ja' , 'З' => 'Z' , 'Ц' => 'C' , 'К' => 'K' , 'Ж' => 'ZH' , 'Ч' => 'CH' , 'Х' => 'KH' , 'Е' => 'E' , 'С' => 'S' , 'Ё' => 'JO' , 'Э' => 'E' , 'Ш' => 'SH' , 'Й' => 'J' , 'Щ' => 'SHH' , 'Ю' => 'JU' , 'Я' => 'JA' , 'Ь' => "" , 'Ъ' => "" , 'ъ' => "" , 'ь' => "" , ); 
		$specialSymbols = array( "_" => "-", "'" => "", "`" => "", "^" => "", " " => "-", '.' => '.', ',' => '-', ':' => '-', '"' => '', "'" => '', '<' => '', '>' => '', '«' => '', '»' => '', ' ' => '-', '/' => '-', '\\' => '-' ); 
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
		$result = preg_replace('/[^A-Za-z0-9\-\.]/', '', $result);
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
				p.text(<?=_("File not uploaded")?>);
				$('#'+p.attr('id')+'_remove').val('1'); // устанавливаем флаг удаления 
			}
	});
	$('.file_upload_remove').click(function () { // удаление файла
		var el = $(this);
		var p = el.parents('.file_upload_field').find('.file_status');
		
		p.text(<?=_("File not uploaded")?>);

		var file_id = $('#'+p.attr('id')+'_file');
		file_id.val('').replaceWith( file_id = file_id.clone( true ) );
		$('#'+p.attr('id')+'_remove').val('1'); // устанавливаем флаг удаления 
		return false;
	});
});

</script>
<?	

	}

	
}