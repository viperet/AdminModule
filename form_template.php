<script src="/js/jquery.autocomplete.js"></script>
<link href="/css/jquery.autocomplete.css" media="screen" type="text/css" rel="stylesheet">
<script type="text/javascript" src="http://banker.ua/css/jquery/ui.datepicker.js"></script>
<script type="text/javascript" src="http://banker.ua/css/jquery/ui.datepicker-ru.js"></script>
<script type="text/javascript" src="http://banker.ua/css/jquery/jquery.bgiframe.min.js"></script>
<link rel="stylesheet" href="http://banker.ua/css/jquery/css/calendar_banker.css" type="text/css"/>

<style>
	.breadcrumbs { font-weight: bold; margin: 10px 0; }

	.select2-container,
	.form_input { width: 100%; max-width: 500px;}
 	textarea { width: 100%;  max-width: 500px;} 
	span.link { border-bottom: 1px dotted blue; cursor: pointer; color: blue; }
	input.error { box-shadow: 0 0 2px 1px #FF7873; }
	label.error { color: red; }
	td.error {
	    background: none repeat scroll 0 0 #FF0000;
	    color: #FFFFFF;
	    font-weight: bold;
	    padding: 6px 5px;
    }
	img.image_preview { max-height: 100px; }
	.input_error {
		border: 1px solid red !important;
	}
	.error_message { 	
		color: red; 
		border: 1px solid red;
		border-radius: 10px;
		background: #ffc2c2;
		padding: 10px;
		margin: 10px 0;
		font-weight: bold;
	}
	.input_error_msg {
		color: red;
		float: left;
	}
		
	.form_table {
		background: #CCCCCC;
	}
	.form_table tr.even { background: #EEEEEE; }
	.form_table tr.odd 	{ background: #FFFFFF; }
</style>

<? if( count($this->errorMessage)>0 ) { ?>
<div class="error_message"><?= implode('<br>', $this->errorMessage) ?></div>
<? } ?>

<form method="POST" id="editForm" enctype="multipart/form-data">
	<input type="hidden" name="id" value="<?= $this->id ?>">
    <table width="100%" border="0" align="center" cellpadding="3" cellspacing="1" class="form_table">
<?	$count = 0;
	foreach($this->form as $id=>$item) { ?>
<?		if(!$item->valid) { ?>
			<tr>
				<td colspan="2" class="error">
					<?= implode('<br>', $item->errors) ?>
				</td>
			</tr>
<?		} ?>
		<tr class="<?= $count%2?'even':'odd' ?> <?=$item->required?'required':'' ?> <?= $item->class ?>">
			<td width="10%" nowrap class="label">
				<?= $item->toHtmlLabel() ?>
			</td>
			<td width="90%" class="input">
				<?= $item->toHtml() ?>
			</td>		
		</tr>
<?		$count++;
	} ?>
	</table>
	<button type="submit" id="editForm_save" name="editForm_save">Сохранить</button>
</form>

