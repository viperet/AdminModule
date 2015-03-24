<?= $this->listHeader(); ?>

<style>
	.link { border-bottom: 1px white dotted; display: inline; cursor: pointer; }
	.admin-pager  { text-align: center;}
	div.filter { margin: 0 0 10px 0; float: right;	/* max-width: 200px;  */}
	div.filter form { margin: 0;	}
	select.filter { max-width: 150px; }
	.clear { clear: both; }	
	table th { border-bottom: 0 none !important; border-top: 0 none !important;	}
	#filter_form .form-group { position: relative; }
	#date-from, #date-to { width: 100px; }
/* 	#date-to { margin-right: 30px; } */
</style>



<script>
	$(function () {
		$('#filter_form').submit(function () {
			var new_location = "<?=$this->baseUrlNoPaging?>&filter="+$('#filter_input').val();
			if($('#date-from').val())
				new_location = new_location + "&df="+$('#date-from').val()
			if($('#date-to').val())
				new_location = new_location + "&dt="+$('#date-to').val();
			document.location = new_location;
			return false;
		})
		
		$('#header_checkbox').change( function (event, value) {
			$('.row_checkbox').attr('checked', this.checked);
		});
		
		$('select.filter').change( function () {
			var el = $(this);
			if(this.value == '')
				document.location = "<?= $this->baseUrlNoFilter ?>";
			else {
				$('#filter_input').val(el.data('field')+':'+this.value);
				$('#filter_form').submit();
			}
		});
	});
</script>


<div class="filter">


		<form method="GET" action="" id="filter_form" class="form-inline">
<?php if($this->options['date']) { ?>			
			<div class="form-group">
				<input type='text' class="form-control" name="df" id='date-from' value="<?= htmlspecialchars(@$_GET['df']) ?>" placeholder="Дата от"/>
			</div>
			<div class="form-group">
				<input type='text' class="form-control" name="dt" id='date-to' value="<?= htmlspecialchars(@$_GET['dt']) ?>" placeholder="до"/>
			</div>
			<div class="btn-group" style="margin-right: 20px;">
			  <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
			    <i class="glyphicon glyphicon-calendar"></i> <span class="caret"></span>
			  </button>
			  <ul class="dropdown-menu" role="menu">
			    <li><a href="#" onClick="return setTime('today');">Сегодня</a></li>
			    <li><a href="#" onClick="return setTime('yesterday');">Вчера</a></li>
			    <li><a href="#" onClick="return setTime('week');">Прошлая неделя</a></li>
			    <li><a href="#" onClick="return setTime('month');">Прошлый месяц</a></li>
			    <li><a href="#" onClick="return setTime('7days');">Последние 7 дней</a></li>
			    <li><a href="#" onClick="return setTime('30days');">Последние 30 дней</a></li>
				<li class="divider"></li>
			    <li><a href="#" onClick="return setTime('reset');">Очистить</a></li>
			  </ul>
			</div>			
<?php } ?>
			<div class="form-group">
				<div class="input-group">
					<input type="text" name="filter" id="filter_input" class="form-control" value="<?= htmlspecialchars(@$_GET['filter']) ?>" placeholder="<?= _('filter') ?>"> 
					<span class="input-group-btn">
						<button type="submit" class="btn btn-default" name=""><span class="glyphicon glyphicon-search"></span></button>
					</span>
				</div>
			</div>
		</span>
	</form>
<? if(isset($_GET['filter'])) {
	echo "<a href='{$this->baseUrlNoFilter}'>"._('remove filter')."</a>";
}
?>
</div>
<form method="POST" action="<?= $this->baseUrl ?>">
	<div class="btn-group" role="group">
	<?= $this->topButtons(); ?>
	</div>
	<div class="btn-group" role="group">
		<a class="btn btn-default" href="<?= $this->baseUrl ?>&edit=0"><?= _('Add') ?></a>
		<button class="btn btn-default" type="submit" name="delete" onclick="return confirm('<?= _('Delete selected records?') ?>');"><?= _('Delete selected') ?></button>
	</div>
	<? if($this->options['export']) { ?>
	<div class="btn-group">
	  <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
	    <?= _('Export') ?> <span class="caret"></span>
	  </button>
	  <ul class="dropdown-menu" role="menu">
	    <li><a href="<?= $this->baseUrl ?>&export&format=csv&encoding=utf8">CSV (UTF-8)</a></li>
	    <li><a href="<?= $this->baseUrl ?>&export&format=csv&encoding=windows1251">CSV (windows-1251)</a></li>
	    <li><a href="<?= $this->baseUrl ?>&export&format=xls">XLS</a></li>
	  </ul>
	</div>				
	<? } ?>	
	<div class="clear"></div>

<? if(!$this->options['datatables']) { ?>
	<div class="admin-pager"><?= $htmlPager ?></div>
<? } ?>


	<table id="admin-table" class="table table-hover table-bordered table-striped table-condensed">	
	<thead>
		<tr>
			<th data-orderable="0"><input id="header_checkbox" type="checkbox" name="" value="" autocomplete="off"></th>
	<?		
			foreach($headers as $header) {
				echo "<th title='".@$this->options['form'][$header]->label_hint."'>".
					htmlspecialchars($this->options['form'][$header]->label);
				echo "</th>\n";
			}
	?>
			<th data-orderable="0"><?= _('Actions') ?></th>
		</tr>			
		<tr>
			<th></th>
	<?		
			foreach($headers as $header) {
				echo "<th>";
				if($this->options['form'][$header]->filterByClick) {
					$fieldValues = $this->getFieldValues($this->options['form'][$header]);
					echo "<select class='filter' data-field='{$this->options['form'][$header]->name}'>".
							"<option value=''>-</option>";
					foreach($fieldValues as $key => $value) {
						echo "<option value='{$key}'".($this->filter=="{$this->options['form'][$header]->name}:{$key}"?"selected":"").">{$value}</option>"; 
					}
					echo "</select>";
				}
				echo "</th>\n";
			}
	?>
			<th></th>
		</tr>
	</thead>
	</thead>
	<tbody>
	<?
			$count = 0;
			foreach($items as $item) {
	?>
		<tr class="<?= $this->getListClass($item); ?>">
			<td>
				<input type="checkbox" class="row_checkbox" name="item[]" value="<?= $item['id'] ?>" autocomplete="off">
			</td>
	<?
				foreach($headers as $header) {
					$formItem = $this->options['form'][$header];
					$formItem->fromRow($item);
	?>
			<td class="table-data" <? $s=$formItem->toString(); if(mb_strlen($s)>$formItem->truncate) echo ' title="'.htmlspecialchars(str_replace("\n", " ", $s), ENT_QUOTES, $formItem->encoding, false).'" '; ?> >
	<?	if($formItem->filterByClick)		
			echo "<a href='{$this->baseUrlNoPaging}&filter=".urlencode($formItem->name.':'.$formItem->value)."'>";
		else
			echo "<a href='{$this->baseUrl}&edit={$item['id']}'>";
	?>
				<?= $formItem->toStringTruncated() ?>
				</a>
			</td>
	<?
					
				}
	?>
			<td class="table-actions btn-toolbar">
				<?= $this->actions($item) ?>
			</td>
		</tr>
	<?
				$count++;
			}
	?>
	</tbody>
	</table>
<? if(count($items) == 0 && isset($_GET['filter'])) { ?>	
	<div class="alert alert-info" role="alert">Записи не найдены, <a href='<?=$this->baseUrlNoFilter?>'><?=_('remove filter')?></a>?</div>
<? } elseif(count($items) == 0 && !isset($_GET['filter'])) { ?>	
	<div class="alert alert-info" role="alert">Записей пока нет, <a href='<?= $this->baseUrl ?>&edit=0'><?=_('add records')?></a>?</div>
<? } ?>	

<? if(!$this->options['datatables']) { ?>
	<div class="admin-pager"><?= $htmlPager ?></div>
<? } ?>

	
	<div class="btn-group" role="group">
	<?= $this->bottomButtons(); ?>
	</div>
	<div class="btn-group" role="group">
		<a class="btn btn-default" href="<?= $this->baseUrl ?>&edit=0"><?= _('Add') ?></a>
		<button class="btn btn-default" type="submit" name="delete" onclick="return confirm('<?= _('Delete selected records?') ?>');"><?= _('Delete selected') ?></button>
	</div>
	<? if($this->options['export']) { ?>
	<div class="btn-group dropup">
	  <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
	    <?= _('Export') ?> <span class="caret"></span>
	  </button>
	  <ul class="dropdown-menu" role="menu">
	    <li><a href="<?= $this->baseUrl ?>&export&format=csv&encoding=utf8">CSV (UTF-8)</a></li>
	    <li><a href="<?= $this->baseUrl ?>&export&format=csv&encoding=windows1251">CSV (windows-1251)</a></li>
	    <li><a href="<?= $this->baseUrl ?>&export&format=xls">XLS</a></li>
	  </ul>
	</div>				
	<? } ?>
</form>

<script>
<? if($this->options['datatables']) { ?>
	$('#admin-table').dataTable( {
// 		paginate: false,
		pageLength: <?=$this->options['perpage']?>,
		orderCellsTop: true,
		order: [],
		serverSide: true,
		ajax: '<?= $this->baseUrl ?>&data-source',
//		ordering: false,
// 		scrollY: 300
	});
<? } ?>
	
	
	
	
	moment.locale('ru');
	
	function setTime(mode) {
		var from, to;
		switch(mode) {
			case 'today':
				from = moment().startOf('day');
				to = moment().endOf('day');
				break;
			case 'yesterday':
				from = moment().startOf('day').subtract(1, 'day');
				to = moment().endOf('day').subtract(1, 'day');
				break;
			case 'week':
				from = moment().startOf('week').subtract(1, 'week');
				to = moment().endOf('week').subtract(1, 'week');
				break;
			case 'month':
				from = moment().subtract(1, 'month').startOf('month');
				to = moment().subtract(1, 'month').endOf('month');
				break;
			case '7days':
				from = moment().subtract(7, 'days');
				to = moment();
				break;
			case '30days':
				from = moment().subtract(30, 'days');
				to = moment();
				break;
			case 'reset':
//				$('#date-from, #date-to').val('');
				document.location = "<?=$this->baseUrlNoFilter?>";
				return;
		}
		$('#date-from').val( from.format('DD.MM.YYYY') );
		$('#date-to').val( to.format('DD.MM.YYYY') );
		$('#filter_form').submit();
	}
	
	$('select.filter').selectpicker({ 
		 width: '100%',
		 style: 'btn-default btn-xs',
	});
	
	if($.fn.datetimepicker.defaults.locale !== undefined)
		options = {locale: 'ru'};
	else
		options = {language: 'ru'};

	options.format = 'DD.MM.YYYY';
	$('#date-from, #date-to').datetimepicker(options);
	
	$("#date-from").on("dp.change",function (e) {
	$('#date-to').data("DateTimePicker").minDate(e.date);
	});
	$("#date-to").on("dp.change",function (e) {
	$('#date-from').data("DateTimePicker").maxDate(e.date);
	});	
</script>
