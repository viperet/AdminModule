<!--
<script src="/js/jquery.autocomplete.js"></script>
<link href="/css/jquery.autocomplete.css" media="screen" type="text/css" rel="stylesheet">
<script type="text/javascript" src="http://banker.ua/css/jquery/ui.datepicker.js"></script>
<script type="text/javascript" src="http://banker.ua/css/jquery/ui.datepicker-ru.js"></script>
<script type="text/javascript" src="http://banker.ua/css/jquery/jquery.bgiframe.min.js"></script>
<link rel="stylesheet" href="http://banker.ua/css/jquery/css/calendar_banker.css" type="text/css"/>
-->

<style>
	span.link { border-bottom: 1px dotted blue; cursor: pointer; color: blue; }
	.label-hint { text-align: right; font-size: 75%; }	
</style>

<? if( count($this->errorMessage)>0 ) { ?>
<div class="alert alert-danger" role="alert"><?= implode('<br>', $this->errorMessage) ?></div>
<? } ?>

<form method="POST" id="editForm" class="form-horizontal" enctype="multipart/form-data" role="form">
	<input type="hidden" name="id" value="<?= $this->id ?>">
<?	$count = 0;
	foreach($this->form as $id=>$item) { ?>
	<div class="form-group <?=$item->name?> <?=($item->required?'required':'') .' '. (!$item->valid?' has-error':'') ?>">
		<?= $item->toHtmlLabel() ?>
		<div class="col-sm-8">
			<?= $item->toHtml() ?>
		</div>
<?		if(!$item->valid) { ?>
		<span id="helpBlock" class="col-sm-8  col-sm-offset-3 help-block">
			<?= implode('<br>', $item->errors) ?>
		</span>
<?		} ?>
	</div> <!-- form-group -->
<?		$count++;
	} ?>
	<hr>
	<div class="form-group">
		<div class="col-sm-offset-3 col-sm-8">
			<?= $this->adminModule->formButtons(); ?>
		</div>
	</div>
</form>

