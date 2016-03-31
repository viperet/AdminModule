<?php if(!isset($_SESSION['import_filename'])) { ?>
<div class="jumbotron">
	<h1>Импорт данных из CSV</h1>
	<p>Вы можете загрузить данные из CSV файла созданного в Excel или другой программе.</p>
	<p>В процессе импорта необходимо указать кодировку, значения столбцов и другие параметры</p>
	<form method="post" enctype="multipart/form-data" id="import-form">
		<span class='btn btn-default btn-file'>
		    <?= _("Upload file") ?> <input type='file' class='form_input upload_file' name='file'>
		</span>
	</form>
</div>


<script>
	$('input.upload_file').on('change', function () {
		var el = $(this);
		if(this.files.length == 1) {
			$('#import-form').submit();
		}
	});	
</script>
<?php } elseif(is_array($csv)) { ?>
<style>
	.import-table .bootstrap-select { width: 160px !important; }	
	.import-table tr.skip { color: #DDDDDD; }
</style>


<form class="form-inline clearfix" method="post" id="import-form">
	<div class="form-group">
		<label for="encoding" data-toggle="tooltip" data-placement="right" title="Выберите кодировку чтобы символы кирилицы отображались правильно">Кодировка  <i class="glyphicon glyphicon-question-sign"></i></label>
		<select name="encoding" class="selectpicker form-control" id="encoding">
			<option value="utf-8">UTF-8</option>
			<option <?= $_REQUEST['encoding']=='windows-1251'?'selected':''?> value="windows-1251">Windows 1251</option>
			<option <?= $_REQUEST['encoding']=='macOS'?'selected':''?> value="macOS">Mac OS</option>
		</select>
	</div>
	<div class="form-group">
		<label for="skip" data-toggle="tooltip" data-placement="right" title="Позволяет пропустить пустые или заголовочные строчки в начале файла">Пропуск строк <i class="glyphicon glyphicon-question-sign"></i></label>
		<select name="skip" class="selectpicker form-control" id="skip">
		<?php for($i=0;$i<=10;++$i) { ?>
			<option <?= $_REQUEST['skip']==$i?'selected':''?> value="<?=$i?>"><?=$i?></option>
		<?php } ?>
		</select>
	</div>
	<div class="form-group">
		<label for="delimiter" data-toggle="tooltip" data-placement="right" title="Разделитель полей в файле, обычно точка с запятой">Разделитель <i class="glyphicon glyphicon-question-sign"></i></label>
		<select name="delimiter" class="selectpicker form-control" id="delimiter">
			<option <?= $_REQUEST['delimiter']==';'?'selected':''?> value=";">;</option>
			<option <?= $_REQUEST['delimiter']==','?'selected':''?> value=",">,</option>
			<option <?= $_REQUEST['delimiter']=="\t"?'selected':''?> value="&#9;">табуляция</option>
		</select>
	</div>

<hr>

<div class="alert alert-info" role="alert">Выберите тип значения в каждом столбце или "пропустить" если данные не надо использовать</div>

<div class="table-responsive">
	<table class="import-table table table-bordered table-striped">
		<thead>
			<tr>
	<?php 		foreach(reset($csv) as $num => $col) {
	?>
				<td>
					<select class="selectpicker" name="col_<?= $num ?>">
						<option value="">Тип поля</option>
						<option value="-">Пропустить</option>
						<option disabled="disabled" data-content="<hr>">-</option>
<?php foreach($this->options['form'] as $id=>$item) { 
		if($item->type != 'text') continue;
?>
						<option value="<?= $id ?>" <?= $_POST["col_{$num}"]==$id?'selected':'' ?>><?= $item->label . ($item->required?'*':'') ?></option>
<?php } ?>
					</select>
				</td>
	<?php 		}
	?>			
			</tr>
		</thead>
		<tbody>
	<?php foreach($csv as $row_num => $row) { 
			if(empty($row)) continue;
	?>
			<tr class="row_<?= $row_num ?>">
					
	<?php foreach($row as $col) { ?>
				<td>
					<?=  htmlspecialchars($col) ?>
				</td>
	<?php } ?>
			</tr>
	<?php } ?>			
			
		</tbody>
	</table>
</div>
<div class="text-center">
		<button type="submit" name="cancel" class="btn btn-danger"><i class="glyphicon glyphicon-remove"></i> Отменить</button>
		<button type="submit" name="import" class="btn btn-primary" id="import-btn"><i class="glyphicon glyphicon-save"></i> Импортировать</button>
</div>
</form>

<script>
$(function () {
	try{
		$('[data-toggle="tooltip"]').tooltip()
	} catch(e) {
	}  
	
	
	$('#encoding, #delimiter').change(function () {
		$('#import-form').submit();
	});
	$('#skip').change(function () {
		$('.import-table tr').removeClass('skip');
		var skip = +this.value;
		for(var i=0;i<skip;++i) {
			$('.import-table tr.row_'+i).addClass('skip');
		}
	}).change();
	
	$('#import-btn').click(function () {
	});
	
})	
</script>
<?php } elseif(is_array($stats)) { ?>
<div class="jumbotron">
  <h1>Импорт завершен</h1>
  <p>Всего записей обработано: <b><?=$stats['total']?></b>, вставлено: <b><?=$stats['inserted']?></b></p>
  <p><a class="btn btn-primary btn-lg" href="<?= $this->baseUrl ?>" role="button">Ок</a></p>
</div>
<?php } ?>
