<?

class htmlType extends textareaType {
	public $height = 300;
	public $download_images = null; // путь куда скачивать картинки найденные в тексте
	public $download_images_url = null; // путь куда скачивать картинки найденные в тексте
	public $download_images_hook = null; // callback для преобразования url картинок
	public $rows = 10;
	private $id = null;

	public static function pageHeader() {
	?>
	<?
	}
	
	public function upload() {
		if(!empty($this->download_images)) {
			$callback = $_GET['CKEditorFuncNum'];
			$dir = $this->download_images;
			$full_path = $dir.$_FILES['upload']['name'];
			$http_path = $this->download_images_url.$_FILES['upload']['name'];
			$error = '';
			mkdir($dir, 0777, true);
			if( move_uploaded_file($_FILES['upload']['tmp_name'], $full_path) ) {
			} else {
				$error = 'Что-то пошло не так!';
				$http_path = '';
			}
		} else {
			$error = 'Не установлен путь загрузки';
			$http_path = '';
		}
		echo "<script type=\"text/javascript\">window.parent.CKEDITOR.tools.callFunction(".$callback.",\"".$http_path."\", \"".$error."\" );</script>";
	}
	
	public function toHtml() {
		return "<textarea name='{$this->name}' id='{$this->name}' rows='{$this->rows}' class='form-control {$this->class} '>".$this->escape($this->value)."</textarea>
<script>
    CKEDITOR.replace('{$this->name}', {
	    filebrowserImageUploadUrl: document.location+'&ajaxField='+encodeURIComponent('{$this->name}')+'&ajaxMethod=upload',
	});
</script>";
	}
	
	public function postSave($id, $params, $item) { 
		if(!empty($this->download_images)) {
			$this->value = $item[$this->name];
			$this->id = $id;
			$this->value = preg_replace_callback("#(?<! !!! -->)(<img .*src=['\"])(http://.*)(['\"])#Us", array($this, 'downloadImage'), $this->value);
			
//			echo htmlspecialchars($this->value); exit;
			return "`{$this->name}` = '".mysql_real_escape_string($this->value)."'";
		} else {
			return ''; 
		}
	}
	
	private function downloadImage($matches) {
		$url = $matches[2];
		$image = file_get_contents($url);
		if($image === false) 
			return "<!-- ошибка скачивания !!! -->".$matches[1]."{$url}".$matches[3];

		if(substr_compare($url, $this->download_images_url, 0, strlen($this->download_images_url)) == 0) {
			return $matches[1]."{$url}".$matches[3];
		}
			

		// определяем тип файла 	    		
		$signature = bin2hex(substr($image,0,3));
	    if($signature == 'ffd8ff')
	    	$ext = 'jpg';
	    elseif($signature == '89504e') // png
	    	$ext = 'png';
	    elseif($signature == '474946') // gif
	    	$ext = 'gif';
	    else {
			return "<!-- неизвестный тип изображения !!! -->".$matches[1]."{$url}".$matches[3];
	    }
			
		$path = $this->download_images.'/'.chunk_split(substr(str_pad($this->id, 4, '0', STR_PAD_LEFT), 0, 4), 2,'/');
		$index = 0;
		do {
			$index++;
			$name = "{$this->name}_{$index}.{$ext}";
		} while(file_exists($path.$name));
		mkdir($path, 0777, true);
		file_put_contents($path.$name, $image);
		
		if(is_callable($this->download_images_hook))
			$url = call_user_func($this->download_images_hook, $path.$name);
		elseif(!empty($this->download_images_url))
			$url = $this->download_images_url.chunk_split(substr(str_pad($this->id, 4, '0', STR_PAD_LEFT), 0, 4), 2,'/').$name;
		else
			$url = $path.$name;
		return $matches[1]."{$url}".$matches[3];
	}
	
	
}