<?php
require_once(dirname(__FILE__)."/../helpers/phpThumb/phpthumb.class.php");
require_once(dirname(__FILE__)."/../imageResizer.class.php");

class imageType extends fileType {
	public $width = 100;
	public $height = 100;
	public $format = '{id}_{width}x{height}';
	public $subfolders = true;
	public $relative = false; //сохранять путь относительно папки path
	public $path = ''; 
	public $quality = 95;
	public $zc = 1;
	public $size = 'medium'; // картинки какого размера показывать в рез-тах поиска (icon|medium|xxlarge|huge)
	public $validation = array('jpg', 'jpeg', 'png', 'gif');
	
	public $x,$y,$h,$w;
	
	protected $timestamp = '';
	protected $download_url = '';
	
	public function __construct($db, $name, $array) {
		parent::__construct($db, $name, $array);
		$this->timestamp = 'v='.time();
	}
	
	public function toHtml() {
		if($this->readonly) {
			return "<div class='col-sm-3 col-xs-3 img_mask' style='position:relative;'>
			<img id='{$this->name}' class='form_thumbnail {$this->class}' style='".($this->value==''?"display:none;'":"' src='{$this->value}?{$this->timestamp}'")." id='{$this->name}_uploadPreview' data-width='{$this->width}' data-height='{$this->height}'>
			</div>";
		} else 
			return "<div class='row upload_field'>
		<div class='col-sm-12'><p class='form-control-static'>".
			($this->width>0&&$this->height>0?_('Minimum size')." {$this->width}x{$this->height}":_("Any size"))
		."<br></p></div>".
"
<div class='col-sm-3 col-xs-3 img_mask' style='position:relative;'>
	<img id='{$this->name}' class='form_thumbnail form_thumbnail_crop {$this->class}' style='".($this->value==''?"display:none;'":"' src='{$this->value}?{$this->timestamp}'")." id='{$this->name}_uploadPreview' data-width='{$this->width}' data-height='{$this->height}'>
</div>
<div class='col-sm-9 col-xs-9'>
	<div>"._('Click on image to crop')."</div>
	<button class='btn btn-default upload_remove' type='button'>"._('Delete image')."</button>
	<span class='btn btn-default btn-file'>
	    "._('Upload from computer')." <input type='file' class='form_input upload_image' name='{$this->name}_file' id='{$this->name}_file'>
	</span>
	<div>
		<span class='link' onClick='return searchPopup(\"{$this->name}_url\", \"{$this->size}\");'>"._('find image')."</span> "._('or upload image from URL')."<br>
		<input type='hidden' name='{$this->name}' value='{$this->value}'>
		<input type='text' class='form-control upload_url' name='{$this->name}_url' id='{$this->name}_url' placeholder='http://' value=''>
	</div>
</div>
<input type='hidden' id='{$this->name}_size' value='{$this->size}' />
<input type='hidden' id='{$this->name}_x' name='{$this->name}_x' />
<input type='hidden' id='{$this->name}_y' name='{$this->name}_y' />
<input type='hidden' id='{$this->name}_w' name='{$this->name}_w' />
<input type='hidden' id='{$this->name}_h' name='{$this->name}_h' />
<input type='hidden' id='{$this->name}_remove' name='{$this->name}_remove' value='".(empty($this->value)?'1':'0')."' />
</div>
";
	}

	public function fromForm($value) {
		parent::fromForm($value);
		
		if(!empty($value[$this->name.'_url'])) { // загрузка по ссылке
			if(!preg_match('#^https?://#', $value[$this->name.'_url']))
				$value[$this->name.'_url'] = 'http://'.$value[$this->name.'_url'];
			
			$fileName = $this->download_url = $value[$this->name.'_url'];

			$translit_filename = 'tmp_image.jpg';
		
		    $path = $this->path.'/tmp/'.$this->session_id.'/';
		    @mkdir($this->options['root_path'].$path, 0777, true);
		    
		    $name = $this->options['root_path'].$path.$translit_filename; // имя файла
			$tmpData = file_get_contents($fileName);
			file_put_contents($name, $tmpData);
			unset($tmpData);
			    
		    $this->value = $path.$translit_filename;
			$_SESSION['uploads'][$this->session_id][$this->name] = array(
				'value' => $this->value,
			);

		} 
	}

	public function fromRow($row) {
		parent::fromRow($row);
		list($this->value, $this->timestamp) = explode('?', $this->value, 2);
		
		if($this->relative && $this->value!='')
			$this->value = $this->path.$this->value;
	}
	
	public function postSave($id, $params) { 
		
		if($this->readonly) return "";
	
		$this->x = $params[$this->name.'_x'];
		$this->y = $params[$this->name.'_y'];
		$this->w = $params[$this->name.'_w'];
		$this->h = $params[$this->name.'_h'];

		if($this->subfolders)
			$relative_path = chunk_split(substr(str_pad($id, 4, '0', STR_PAD_LEFT), 0, 4), 2,'/');
		else
			$relative_path = '';
		
	    $path = $this->path.$relative_path;		
	    @mkdir($this->options['root_path'].$path, 0777, true);
	    $fname = str_replace(array(
	    			'{id}',
	    			'{width}',
	    			'{height}',
	    		), array(
	    			$id,
	    			$this->width,
	    			$this->height,
	    		), $this->format);


		if(!empty($this->value)) {
			$fileName = $this->options['root_path'].$this->value;
		} elseif(empty($this->value)) { // удаление картинки
			return "`{$this->name}` = ''";
		} else 
			return "";
			
			
		$image = @file_get_contents($fileName);	
		if($image == "") return '';



		// определяем тип файла 	    		
		$signature = bin2hex(substr($image,0,3));
	    if($signature == 'ffd8ff')
	    	$ext = 'jpg';
	    elseif($signature == '89504e') // png
	    	$ext = 'png';
	    elseif($signature == '474946') // gif
	    	$ext = 'png';
	    else {
	    	die(strarg(_("Unknown file format in %1, signature %2"), $this->label, $signature));
	    }
	    
		$name= $this->options['root_path'].$path.$fname.".".$ext; // имя файла
		if($this->relative)
			$url = $fname.".".$ext."?v=".time(); // ссылка на файл
		else
			$url = $path.$fname.".".$ext."?v=".time(); // ссылка на файл

		if($this->width==0 && $this->height==0) {
			file_put_contents($name, $image);
			$this->cleanup();
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
			$this->cleanup();
			return "`{$this->name}` = '".mysql_real_escape_string($url)."'";
				
		} else {
			$params = array('w'=>$this->width,'h'=>$this->height,'zc'=>$this->zc,'q'=>$this->quality);
			if(ImageResizer::resizeImgAdvanced($image, $name, $ext, $params)) {
				$this->cleanup();
				return "`{$this->name}` = '".mysql_real_escape_string($url)."'";
			}
		}
		$this->cleanup();
		return ''; 
	}

	public static function pageHeader() {
?>
<style>
	.form_thumbnail_crop {
		cursor: pointer;
	/*	position: relative;
		background: #FFF url(/site_img/nophoto.jpg) center center no-repeat;
		overflow: hidden;
	*/
	}
	.img_mask img {
	/*	position: absolute; */
		max-width: 100%;
		max-height: 200px;
	}
	.searchResult {
		width: 25%;
		height: 30%;
		text-align: center;
		vertical-align: middle;
		display: inline-block;
		padding: 3px;
		vertical-align: bottom;
	}
	/* Small devices (tablets, 768px and up) */
	@media (max-width: 992px) {
		.searchResult {
			width: 33%;
		}
	}
	@media (max-width: 768px) {
		.searchResult {
			width: 50%;
		}
	}
	.searchResult img {
		max-width: 100%;
		max-height: 100%;
	}
	.searchResult div {
		font-size: 12px;
	}
	.searchPages {
	    padding: 10px 0 0;
	    text-align: center;
	}
	.searchPage {
	    background: none repeat scroll 0 0 #e5e5e5;
	    color: gray;
	    cursor: pointer;
	    display: inline-block;
	    height: 20px;
	    line-height: 15px;
	    margin: 2px;
	    padding: 3px;
	}
	.searchPage.active {
		font-weight: bold;
		color: black;
	}
/*
	#imageCrop #previewCrop {
		max-width: 100%;
	}
*/
</style>
<script src="http://www.google.com/jsapi" type="text/javascript"></script>
<div class="modal fade" id="imageSearch" tabindex="-1" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only"><?= _('Close'); ?></span></button>
        <h4 class="modal-title" id="exampleModalLabel"><?= _('Image search'); ?></h4>
        <form id="searchForm" role="form">
	        <div class="input-group">
				<input type="text" id="q" class="form-control"> 
				<span class="input-group-btn">
					<button class="btn btn-primary" type="submit" id="search"><?= _('Search'); ?></button>
				</span>
	        </div>
		</form>
      </div>
      <div class="modal-body">
	  	<div id="searchResults" class="clearfix"></div>
      </div>
    </div>
  </div>
</div>


<div class="modal fade" id="imageCrop" tabindex="-1" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only"><?= _('Close'); ?></span></button>
        <h4 class="modal-title" id="exampleModalLabel"><?= _('Image cropping'); ?></h4>
      </div>
      <div class="modal-body">

			<img src="" id="previewCrop">

      </div>
      <div class="modal-footer">
		<button type="btn btn-primary" id="apply" data-dismiss="modal"><?= _('Apply'); ?></button>
      </div>      
    </div>
  </div>
</div>

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
	return selectSize;
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

	$('.form_thumbnail_crop').click(function () {
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


	function search(id, start) {
		var query = encodeURIComponent($('#imageSearch #q').val());
		var size = encodeURIComponent($('#'+id+'_size').val());
		
		$.getJSON('https://www.googleapis.com/customsearch/v1?q='+query+'&start='+start+'&filetype=png&cx=002678885553542504836:qge42psoiiq&searchType=image&num=10&key=AIzaSyCiGJKDOHAU5W5Tno2ku-dfiDCH4X3SPkA&callback=?', function (data) {
			console.log(data);
			var sr = $('#searchResults');
			sr.empty();
			var results = $('<div class="searchResults"></div>').appendTo(sr);
			var pages = $('<div class="searchPages"></div>').appendTo(sr);
			$.each(data.items, function () {
				results.append("<div class='searchResult'>"+
						"<a href='"+this.link+"'>"+
						"<img src='"+this.image.thumbnailLink+"'>"+
						"<div>"+this.image.width+"x"+this.image.height+"</div>"+
						"</a>"+
					"</div"
				);
			})
			for(var s=0;s<data.searchInformation.totalResults && s<100;s+=11) {
				pages.append("<div class='searchPage "+(start == s?'active':'')+"' onClick='search(\""+id+"\", "+s+")'>"+
						(s/11+1)+
					"</div"
				);
			}
		})
	}

$('#imageSearch #searchForm').submit(function (e) {
	var id = $('#imageSearch').data('id');
	e.preventDefault();
	search(id, 0);
});
$('#imageSearch #searchResults').on("click", ".searchResult a", function () {
	var id = $('#imageSearch').data('id');
	var href = $(this).attr('href');
	$('#'+id).val(href).trigger('change');
	$('#imageSearch').modal('hide')
	return false;
})


function searchPopup(result_id, size) {
	title = $('#name').val() || $('#title').val() || $('#family').val() || '';
	$('#imageSearch #q').val(title);
	$('#imageSearch #searchForm').submit();		
	$('#imageSearch').data('id', result_id).modal();
	return false;
}

function cropPopup(id, image, image_width, image_height, x,y,w,h) {
	var Jcrop;
	if($('#'+id+'_w').val() == '') cropImage(id);

	var image_x = $('#'+id+'_x').val(),
	    image_y = $('#'+id+'_y').val(),
	    image_w = $('#'+id+'_w').val(),
	    image_h = $('#'+id+'_h').val();
	
	var img = $('#imageCrop #previewCrop');

	var targetSize = {w: image_width, h: image_height, aspect: image_width/image_height};

	img.attr('src', image);
	$('#imageCrop').modal().one('shown.bs.modal', function () {
		var max_width = $('#imageCrop .modal-body').width();
		$(img).Jcrop({
			minSize: [targetSize.w, targetSize.h],
			aspectRatio: targetSize.aspect,
			boxWidth: max_width,
			boxHeight: 600,
			setSelect: [image_x, image_y, image_x+image_w, image_y+image_h]
		},function(){
			Jcrop = this;
		});		
	}).one('hidden.bs.modal', function () {
		Jcrop.destroy();
	});

	$('#apply').one('click', function () {
		var crop = Jcrop.tellSelect();
		$('#'+id+'_x').val(crop.x);
		$('#'+id+'_y').val(crop.y);
		$('#'+id+'_w').val(Math.round(crop.w));
		$('#'+id+'_h').val(Math.round(crop.h));
    	$('#imageCrop').modal('close');
    	return false;			
	});		

	

	return false;
}
</script>
<?php 

	}

	
}