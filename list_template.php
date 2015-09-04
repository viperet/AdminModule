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
	.row_checkbox, .checkbox-td { cursor: pointer; }
	.selected-items { margin: 5px 0; visibility: hidden; }
	.selected-items i { cursor: pointer; }
</style>



<script>
	function getFilters() {
		var curFilter = $('#filter_input').val().split(';');
		var filters = {};
		for(var i=0;i<curFilter.length;++i) {
			var filter = curFilter[i].split(':');
			if(filter.length!=2) continue;
			filters[filter[0]] = filter[1];
		}
		return filters;
	}
	
	function setFilters(filters) {
		var tmpFilter = [];
		$.each(filters, function (key, value) {
			if(value != '') 
				tmpFilter.push(key+':'+value);
		});
		$('#filter_input').val(tmpFilter.join(';'));
		return true;
	}
	
	$(function () {
		$('#filter_form').submit(function () {
			var new_location = "<?=$this->baseUrlNoFilter?>&filter="+$('#filter_input').val()+"&query="+$('#search_input').val();
			if($('#date-from').val())
				new_location = new_location + "&df="+$('#date-from').val()
			if($('#date-to').val())
				new_location = new_location + "&dt="+$('#date-to').val();
			document.location = new_location;
			return false;
		})
		
		$('body').on('change', '#header_checkbox', function (event, value) {
			$('.row_checkbox').prop('checked', this.checked).trigger('change');
		});
		
		$('select.filter').change( function () {
			var filters = getFilters();			
			var el = $(this);
			filters[el.data('field')] = this.value;
			setFilters(filters);
			$('#filter_form').submit();
/*
			if(this.value == '')
				document.location = "<?= $this->baseUrlNoFilter ?>";
			else {
				$('#filter_input').val(el.data('field')+':'+this.value);
				$('#filter_form').submit();
			}
*/
		});
	});
</script>


<div class="filter">


		<form method="GET" action="" id="filter_form" class="form-inline">
<?php if($this->options['date']) { ?>			
			<div class="form-group">
				<input type='text' class="form-control" name="df" id='date-from' value="<?= $this->dateFrom!=''?date('d.m.Y', strtotime($this->dateFrom)):'' ?>" placeholder="Дата от"/>
			</div>
			<div class="form-group">
				<input type='text' class="form-control" name="dt" id='date-to' value="<?= $this->dateTo!=''?date('d.m.Y', strtotime($this->dateTo)):'' ?>" placeholder="до"/>
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
			<input type="hidden" name="filter" id="filter_input" class="form-control" value="<?= htmlspecialchars(@$_GET['filter']) ?>"> 
			<div class="form-group">
				<div class="input-group">
					<input type="text" name="query" id="search_input" class="form-control" value="<?= htmlspecialchars(@$_GET['query']) ?>" placeholder="<?= _('filter') ?>"> 
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
	<div class="btn-group additional-buttons-top" role="group">
	<?= $this->topButtons(); ?>
	</div>
	<div class="btn-group main-buttons-top" role="group">
		<a class="btn btn-default" href="<?= $this->baseUrl ?>&edit=0"><?= _('Add') ?></a>
		<button class="btn btn-default" type="submit" name="delete" onclick="return confirm('<?= _('Delete selected records?') ?>');"><?= _('Delete selected') ?></button>
	</div>
	<? if($this->options['export']) { ?>
	<div class="btn-group">
	  <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
	    <?= _('Export') ?> <span class="caret"></span>
	  </button>
	  <ul class="dropdown-menu" role="menu">
	    <li><a class="export-link" href="<?= $this->baseUrl ?>&export&format=csv&encoding=utf8">CSV (UTF-8)</a></li>
	    <li><a class="export-link" href="<?= $this->baseUrl ?>&export&format=csv&encoding=windows1251">CSV (windows-1251)</a></li>
	    <li><a class="export-link" href="<?= $this->baseUrl ?>&export&format=xls">XLS</a></li>
	  </ul>
	</div>				
	<? } ?>	
	<? if($this->options['import']) { ?>
	<div class="btn-group ">
		<a href="<?= $this->baseUrl ?>&import" class="btn btn-default"><?= _('Import') ?></a>
	</div>				
	<? } ?>	
	
	<div class="clear"></div>

<? if(!$this->options['datatables']) { ?>
	<div class="admin-pager"><?= $htmlPager ?></div>
<? } else { ?>
<? } ?>
	<div class="selected-items">
		Выбрано <b id="selected-items-count">0</b> записей <i class="fa fa-times-circle" title="снять выделение"></i>
	</div>
	<div id="selected-items-container"></div>

	<table id="admin-table" class="table table-hover table-bordered table-striped table-condensed" width="100%">	
	<thead>
		<tr>
			<th data-orderable="0"></th>
	<?		
			foreach($headers as $header) {
				echo "<th class='".str_replace('_', '-', $this->options['form'][$header]->name)."-cell' title='".@$this->options['form'][$header]->label_hint."'>".
					htmlspecialchars($this->options['form'][$header]->label);
				echo "</th>\n";
			}
	?>
			<th data-orderable="0"><?= _('Actions') ?></th>
		</tr>			
		<tr>
			<th><input id="header_checkbox" type="checkbox" name="" value="" autocomplete="off"></th>
	<?		
			foreach($headers as $header) {
				echo "<th class='".str_replace('_', '-', $this->options['form'][$header]->name)."-cell cell-filter'>";
				if($this->options['form'][$header]->filterByClick) {
					$fieldValues = $this->getFieldValues($this->options['form'][$header]);
					echo "<select class='filter' data-field='{$this->options['form'][$header]->name}'>".
							"<option value=''>-</option>";
					foreach($fieldValues as $key => $value) {
						$name = $this->options['form'][$header]->name;
						echo "<option value='{$key}'".(isset($this->filters[$name])&&$this->filters[$name]==$key?"selected":"").">{$value}</option>"; 
					}
					echo "</select>";
				}
				echo "</th>\n";
			}
	?>
			<th></th>
		</tr>
	</thead>
	<tbody>
	<?
			$count = 0;
			if(!$this->options['datatables']) {
				foreach($items as $item) {
	?>
		<tr class="<?= $this->getListClass($item); ?>">
			<td class="checkbox-td">
				<input type="checkbox" class="row_checkbox" name="item[]" value="<?= $item['id'] ?>" autocomplete="off">
			</td>
	<?
				foreach($headers as $header) {
					$formItem = $this->options['form'][$header];
					$formItem->fromRow($item);
	?>
			<td class="table-data <?=str_replace('_', '-', $formItem->name)?>-cell" <? $s=$formItem->toString(); if(mb_strlen($s)>$formItem->truncate) echo ' title="'.htmlspecialchars(str_replace("\n", " ", $s), ENT_QUOTES, $formItem->encoding, false).'" '; ?> >
	<?	if($formItem->filterByClick)		
			echo "<a href='{$this->baseUrlNoFilter}&filter=".urlencode($formItem->name.':'.$formItem->value)."'>";
		else
			echo "<a href='{$this->baseUrl}&edit={$item['id']}'>";
	?>
				<?= $formItem->toListElement() ?>
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
			} else {
?>
		<tr>
			<td colspan="<?= count($headers)+2; ?>"><center>Loading...</center></td>
		</tr>
<?				
			}
	?>
	</tbody>
	</table>
<? if(!$this->options['datatables'] && count($items) == 0 && isset($_GET['filter'])) { ?>	
	<div class="alert alert-info" role="alert"><?=_('Records not found')?>, <a href='<?=$this->baseUrlNoFilter?>'><?=_('remove filter')?></a>?</div>
<? } elseif(!$this->options['datatables'] && count($items) == 0 && !isset($_GET['filter'])) { ?>	
	<div class="alert alert-info" role="alert"><?=_('No records yet,')?> <a href='<?= $this->baseUrl ?>&edit=0'><?=_('add records')?></a>?</div>
<? } ?>	

<? if(!$this->options['datatables']) { ?>
	<div class="admin-pager"><?= $htmlPager ?></div>
<? } ?>

	
	<div class="btn-group additional-buttons-bottom" role="group">
	<?= $this->bottomButtons(); ?>
	</div>
	<div class="btn-group main-buttons-bottom" role="group">
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
	<? if($this->options['import']) { ?>
	<div class="btn-group ">
		<a href="<?= $this->baseUrl ?>&import" class="btn btn-default"><?= _('Import') ?></a>
	</div>				
	<? } ?>	
</form>

<script>
	var checkboxed_storage = [];
	
	function updateSelectedItems() {
		var container = $('#selected-items-container').empty();
		for(var i=0;i<checkboxed_storage.length;++i) {
			container.append("<input type='hidden' name='item[]' value='"+checkboxed_storage[i]+"'/>");
		}
		if(checkboxed_storage.length > 0) {
			$("#selected-items-count").text(checkboxed_storage.length);
			$(".selected-items").css('visibility', 'visible');
		} else {
			$(".selected-items").css('visibility', 'hidden');
		}	

		// добавляем к ссылкам параметр по списком ID выбранных записей
		$('.export-link').each(function () {
			var el = $(this);
			var href = el.data('href');
			if(href === undefined) el.data('href', href = this.href);
			if(checkboxed_storage.length > 0)
				this.href = href + '&id=' + checkboxed_storage.join(',');
			else
				this.href = href;
		})
	}

	$('body').on('change', '.row_checkbox', function () {
		var pos = checkboxed_storage.indexOf(this.value);
		if(this.checked) {
			if(pos==-1) checkboxed_storage.push(this.value);
		} else {
			if(pos!=-1) checkboxed_storage.splice(pos, 1);
		}

		updateSelectedItems();
	});
	$(".selected-items i").click(function () { // очистка выделения
		checkboxed_storage = [];
		$('.row_checkbox, #header_checkbox').removeAttr('checked');
		updateSelectedItems();
	});

<? if($this->options['datatables']) { ?>

	$('#admin-table').dataTable( {
// 		paginate: false,
		pageLength: <?=$this->options['perpage']?>,
		orderCellsTop: true,
		order: [],
		serverSide: true,
		ajax: '<?= $this->baseUrl ?>&data-source',
		stateSave: true,
		pagingType: "full_numbers",
		language: {
			paginate: {
				first: "<?=_('To the begining')?>",	
				last: "<?=_('To the end')?>",	
				previous: "<?=_('Previous page')?>",
				next: "<?=_('Next page')?>",
			},
		},
//		ordering: false,
// 		scrollY: 300
	}).on('draw.dt', function () {
		$('.row_checkbox').each(function () {
			if(checkboxed_storage.indexOf(this.value) != -1) {
				$(this).attr('checked', true);
			} else {
				$(this).removeAttr('checked');
			}
		});
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
	
	
	$('body').on('click', '.checkbox-td, #admin-table td', function (e) {
		if($(e.target).hasClass('checkbox-td') )
			$(this).find('input.row_checkbox').click();
		else if(e.target.tagName == 'TD')
			$(this).parent().find('input.row_checkbox').click();
	});
</script>
