<?
require_once(dirname(__FILE__)."/../helpers/phpThumb/phpthumb.class.php");
require_once(dirname(__FILE__)."/../imageResizer.class.php");

class imageType extends coreType {
	public $width = 100;
	public $height = 100;
	public $format = '{id}_{width}x{height}';
	public $subfolders = true;
	public $relative = false; //сохранять путь относительно папки path
	public $path = ''; 
	public $quality = 95;
	public $zc = 1;
	public $value_url = '';
	public $size = 'medium'; // картинки какого размера показывать в рез-тах поиска (icon|medium|xxlarge|huge)
	
	public $x,$y,$h,$w;
	
	public function toString() {
		return 'картинко';		
	}
	
	public function fromRow($row) {
		parent::fromRow($row);
		if($this->relative && $this->value!='')
			$this->value = $this->path.$this->value;
	}	
	public function toHtml() {
		return ($this->width>0&&$this->height>0?"<div class='upload_field'>Размер не менее {$this->width}x{$this->height}<br>":"Произвольный размер<br>"). // width:{$this->width}px;height:{$this->height}px;
"<div class='img_mask' style='position:relative;'>
	<img id='{$this->name}' class='form_thumbnail {$this->class}' style='".($this->value==''?"display:none;'":"' src='{$this->value}'")." id='{$this->name}_uploadPreview' data-width='{$this->width}' data-height='{$this->height}'>
</div>Клинки по картинке для обрезки<br>
<button class='upload_remove' type='button'>Удалить картинку</button><br>
<span class='link' onClick='return searchPopup(\"{$this->name}_url\", \"{$this->size}\");'>найти картинку</span> или загрузить картинку по ссылке<br>
<input type='hidden' name='{$this->name}' value='{$this->value}'>
<input type='text' class='form_input upload_url' name='{$this->name}_url' id='{$this->name}_url' placeholder='http://' value='{$this->value_url}'><br>
<input type='file' class='form_input upload_image' name='{$this->name}_file' id='{$this->name}_file' placeholder='Загрузка файла'>
<input type='hidden' id='{$this->name}_x' name='{$this->name}_x' />
<input type='hidden' id='{$this->name}_y' name='{$this->name}_y' />
<input type='hidden' id='{$this->name}_w' name='{$this->name}_w' />
<input type='hidden' id='{$this->name}_h' name='{$this->name}_h' />
<input type='hidden' id='{$this->name}_remove' name='{$this->name}_remove' value='0' />
</div>
";
	}
	public function toSql() { return ""; }	
	
	
	public function postSave($id, $params) { 
		$this->x = $params[$this->name.'_x'];
		$this->y = $params[$this->name.'_y'];
		$this->w = $params[$this->name.'_w'];
		$this->h = $params[$this->name.'_h'];

		if($subfolders)
			$relative_path = chunk_split(substr(str_pad($id, 4, '0', STR_PAD_LEFT), 0, 4), 2,'/');
		else
			$relative_path = '';
		
	    $path = $this->path.$relative_path;		
	    @mkdir(PATH_ROOT.$path, 0777, true);
	    $fname = str_replace(array(
	    			'{id}',
	    			'{width}',
	    			'{height}',
	    		), array(
	    			$id,
	    			$this->width,
	    			$this->height,
	    		), $this->format);


		if(!empty($params[$this->name.'_url'])) { // загрузка по ссылке
			$fileName = $params[$this->name.'_url'];
		} elseif(!empty($_FILES[$this->name.'_file']['tmp_name'])) {
			$fileName = $_FILES[$this->name.'_file']['tmp_name'];
		} elseif(!empty($params[$this->name.'_remove'])) { // удаление картинки
			@unlink(PATH_ROOT.$path.$fname.".jpg");
			@unlink(PATH_ROOT.$path.$fname.".png");
			return "`{$this->name}` = ''";
		} else 
			return "";
			
			
		$image = file_get_contents($fileName);	
		



		// определяем тип файла 	    		
		$signature = bin2hex(substr($image,0,3));
	    if($signature == 'ffd8ff')
	    	$ext = 'jpg';
	    elseif($signature == '89504e') // png
	    	$ext = 'png';
	    elseif($signature == '474946') // gif
	    	$ext = 'png';
	    else {
	    	die("неизвестный формат файла в поле {$this->label}, сигнатура $signature");
	    }
	    
		$name= PATH_ROOT.$path.$fname.".".$ext; // имя файла
		if($this->relative)
			$url = $fname.".".$ext."?v=".time(); // ссылка на файл
		else
			$url = $path.$fname.".".$ext."?v=".time(); // ссылка на файл

		if($this->width==0 && $this->height==0) {
			file_put_contents($name, $image);
			return "`{$this->name}` = '".mysql_real_escape_string($url)."'";
		} elseif(!empty($this->w) && !empty($this->h)) {
			$img_r = imagecreatefromstring($image);
			$dst_r = ImageCreateTrueColor($this->width, $this->height);
			imagecopyresampled($dst_r,$img_r,0,0,$this->x,$this->y,
								$this->width,$this->height,$this->w,$this->h);
			if($ext == 'jpg')
				imagejpeg($dst_r, $name, $this->quality);
			else
				imagepng($dst_r, $name);
			return "`{$this->name}` = '".mysql_real_escape_string($url)."'";
				
		} else {
			$params = array('w'=>$this->width,'h'=>$this->height,'zc'=>$this->zc,'q'=>$this->quality);
			if(ImageResizer::resizeImgAdvanced($image, $name, $ext, $params)) {
				return "`{$this->name}` = '".mysql_real_escape_string($url)."'";
			}
		}

		return ''; 
	}

	public static function pageHeader() {
?>
<style>
.img_mask {
	float: left;
	margin-right: 20px;
/*	position: relative;
	background: #FFF url(/site_img/nophoto.jpg) center center no-repeat;
	overflow: hidden;
*/
}
.img_mask img {
/*	position: absolute; */
	max-width: 200px;
	max-height: 200px;
}
</style>
<script>
function cropImage(id) {
	var img = $('#'+id);
	if(img.attr('src') == '') return;
	
	var imgSize = {w: img[0].naturalWidth, h: img[0].naturalHeight};
	imgSize.aspect = imgSize.w/imgSize.h;
	var targetSize = {w: img.data('width'), h: img.data('height')};
	targetSize.aspect = targetSize.w/targetSize.h;
	
	if(imgSize.w<targetSize.w || imgSize.h<targetSize.h) {
		alert('Изображение слишком маленькое');
		img.attr('src', '').hide();
		$('#'+id+'_url').val('');
		var file_id = $('#'+id+'_file');
		file_id.val('').replaceWith( file_id = file_id.clone( true ) );
	}

	if(imgSize.aspect>targetSize.aspect) {
		// обрезаем по бокам
		selectSize = {w: Math.floor(imgSize.h*targetSize.aspect), h: imgSize.h, y: 0};
		selectSize.x = Math.floor((imgSize.w-selectSize.w)/2);
	} else {
		// обрезаем сверху/снизу
		selectSize = {w: imgSize.w, h: Math.floor(imgSize.w/targetSize.aspect), x: 0};
		selectSize.y = Math.floor((imgSize.h-selectSize.h)/2);
	}


//		img.width(selectSize.w).height(selectSize.h).css('top', selectSize.y).css('left', selectSize.x);
	$('#'+id+'_x').val(selectSize.x);
	$('#'+id+'_y').val(selectSize.y);
	$('#'+id+'_w').val(selectSize.w);
	$('#'+id+'_h').val(selectSize.h);
}


$(function() {
	$('.upload_remove').click(function () { // удаление картинки
		var el = $(this);
		var p = el.parents('.upload_field').find('.form_thumbnail');
		
		p.attr('src', '').hide();

		var file_id = $('#'+p.attr('id')+'_file');
		file_id.val('').replaceWith( file_id = file_id.clone( true ) );
		$('#'+p.attr('id')+'_url').val('');
		$('#'+p.attr('id')+'_remove').val('1'); // устанавливаем флаг удаления картинки
		return false;
	});

	$('.form_thumbnail').click(function () {
		var img = $(this);
		cropPopup(this.id, this.src, img.data('width'), img.data('height'));
	});
	
	$(".upload_url").bind('change preview', function(){ // загрузка по ссылке
		var el = $(this);
		var p = el.parents('.upload_field').find('.form_thumbnail');
		p.hide();
		if(this.value == '') return;
		p.one('load', function() { cropImage(p.attr('id')); });
		p.attr('src', this.value).show();
		var file_id = el.parents('.upload_field').find('.upload_image');
		file_id.val('').replaceWith( file_id = file_id.clone( true ) );
		$('#'+p.attr('id')+'_remove').val('0'); // убираем флаг удаления картинки
	});
	$(".upload_image").change(function(){
		var el = $(this);
		var p = el.parents('.upload_field').find('.form_thumbnail');
		// fadeOut or hide preview
		p.hide();
		
		// prepare HTML5 FileReader
		var oFReader = new FileReader();
		oFReader.readAsDataURL(this.files[0]);
		
		oFReader.onload = function (oFREvent) {
			console.log(oFREvent);
			if(oFREvent.total>1048576) {
//				alert("Изображение слишком большое (макс 1 МБ)");
//				var file_id = el.parents('.upload_field').find('.upload_image');
//				file_id.val('').replaceWith( file_id = file_id.clone( true ) );
			}
			p.one('load', function() { cropImage(p.attr('id')); });
			p.attr('src', oFREvent.target.result).show();
			el.parents('.upload_field').find('.upload_url').val('');
			$('#'+p.attr('id')+'_remove').val('0'); // убираем флаг удаления картинки
		};
	});


});
</script>
<?	
	
		$search_popup = <<< EOT
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<script type="text/javascript" src="https://code.jquery.com/jquery-1.11.1.min.js"></script>
<script src="http://www.google.com/jsapi" type="text/javascript"></script>
<script type="text/javascript">
	function search(start) {
		query = encodeURIComponent($('#q').val());
		$.getJSON('https://ajax.googleapis.com/ajax/services/search/images?v=1.0&rsz=8&start='+start+'&imgsz=image_size&q='+query+'&callback=?', function (data) {
			sr = $('#searchResults');
			sr.empty();
			$.each(data.responseData.results, function () {
				sr.append("<div class='searchResult'>"+
						"<a href='"+this.url+"'>"+
						"<img src='"+this.tbUrl+"'>"+
						"<div>"+this.width+"x"+this.height+"</div>"+
						"</a>"+
					"</div"
				);
			})
			$.each(data.responseData.cursor.pages, function () {
				sr.append("<div class='searchPage "+(start == this.start?'active':'')+"' onClick='search("+this.start+")'>"+
						this.label+
					"</div"
				);
			})
		})
	}
	$(function () {
		$('#searchForm').submit(function (e) {
			e.preventDefault();
			search(0);
		});
        $('#searchResults').on("click", ".searchResult a", function () {
        	var href = $(this).attr('href');
        	var id = 'image_url';
        	var i = window.opener.document.getElementById(id);
        	i.value=href;
        	window.opener.$('#'+id).trigger('change');
        	window.close();
        	return false;
        })
		$('#q').val('image_title');
		$('#searchForm').submit();
	});
</script>
</head>
<body>
<style>
	.searchResult {
		width: 150px;
		height: 150px;
		text-align: center;
		vertical-align: middle;
		float: left;
		padding: 3px;
	}
	.searchPage {
		cursor: pointer;
		color: gray;
		height: 20px;
	}
	.active {
		font-weight: bold;
		color: black;
	}
</style>
<form id="searchForm">
	<input type="text" id="q" style="width:300px;"> <button class="btn btn-primary" type="submit" id="search">Искать</button>
</form>
<div id="searchResults"></div>
</body>
</html>
EOT;
	
	
echo "
<script>
	function searchPopup(result_id, size) {
		title = $('#name').val() || $('#title').val() || $('#family').val() || '';
		popupWin = window.open('', 'search', 'location,width=700,height=400,top=0');
		dialog = '".strtr($search_popup, array('\\'=>'\\\\',"'"=>"\\'",'"'=>'\\"',"\r"=>'\\r',"\n"=>'\\n','</'=>'<\/'))."';
		dialog = dialog.replace(/image_url/i, result_id);
		dialog = dialog.replace(/image_title/i, title);
		dialog = dialog.replace(/image_size/i, size);
		popupWin.document.write(dialog);
		popupWin.document.close();
		
		popupWin.focus();
		return false;
	}
</script>
";

		$crop_popup = <<< EOT
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<script type="text/javascript" src="https://code.jquery.com/jquery-1.11.1.min.js"></script>
<link href="/js/jq/jcrop/jquery.Jcrop.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="/js/jq/jcrop/jquery.Jcrop.js"></script>
<script type="text/javascript">
	var Jcrop;
	
	$(function () {
		var img = $('#preview');
		img.attr('src', window.opener.$('#image_id').attr('src'));

		var targetSize = {w: image_width, h: image_height, aspect: image_width/image_height};
		
		$(img).Jcrop({
			minSize: [targetSize.w, targetSize.h],
			aspectRatio: targetSize.aspect,
			boxWidth: 800,
			boxHeight: 600,
			setSelect: [image_x, image_y, image_x+image_w, image_y+image_h]
		},function(){
			Jcrop = this;
		});
		
		$('#apply').click(function () {
			var crop = Jcrop.tellSelect();
			window.opener.$('#image_id_x').val(crop.x);
			window.opener.$('#image_id_y').val(crop.y);
			window.opener.$('#image_id_w').val(Math.round(crop.w));
			window.opener.$('#image_id_h').val(Math.round(crop.h));
        	window.close();
        	return false;			
		});
	});
</script>
</head>
<body>
<style>
#preview {
/*	max-width:800px;
	max-height:600px; */
}
</style>
<img src="" id="preview">
<button type="btn btn-primary" id="apply">Применить</button>
</body>
</html>
EOT;
	
	
echo "
<script>
	function cropPopup(id, image, width, height, x,y,w,h) {
	
		if($('#'+id+'_w').val() == '') cropImage(id);
	
		popupWin = window.open('', 'search', 'location,width=600,height=400,top=100,left=200');
		dialog = '".strtr($crop_popup, array('\\'=>'\\\\',"'"=>"\\'",'"'=>'\\"',"\r"=>'\\r',"\n"=>'\\n','</'=>'<\/'))."';
		dialog = dialog.replace(/image_id/ig, id);
//		dialog = dialog.replace(/image_url/ig, image);
		dialog = dialog.replace(/image_width/ig, width);
		dialog = dialog.replace(/image_height/ig, height);

		dialog = dialog.replace(/image_x/ig, $('#'+id+'_x').val());
		dialog = dialog.replace(/image_y/ig, $('#'+id+'_y').val());
		dialog = dialog.replace(/image_w/ig, $('#'+id+'_w').val());
		dialog = dialog.replace(/image_h/ig, $('#'+id+'_h').val());
		
		popupWin.document.write(dialog);
		popupWin.document.close();
		
		popupWin.focus();
		return false;
	}
</script>
";


	}

	
}