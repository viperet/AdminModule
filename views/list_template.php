<?= $this->listHeader(); ?>

<style>
.link { border-bottom: 1px white dotted; display: inline; cursor: pointer; }
.admin-pager  { text-align: center;}
div.filter-panel { margin: 0 0 10px 0; float: right;	/* max-width: 200px;  */}
div.filter-panel form { margin: 0;	}
.filter {  width: 100%; max-width: 150px; }
.clear { clear: both; }
table th { border-bottom: 0 none !important; border-top: 0 none !important;	}
#filter_form .form-group { position: relative; }
#date-from, #date-to { width: 100px; }
/* 	#date-to { margin-right: 30px; } */
.row_checkbox, .checkbox-td { cursor: pointer; }
.selected-items { margin: 5px 0; visibility: hidden; }
.selected-items i { cursor: pointer; }
nav.page-navigation { text-align: right; }
.pagination { margin-bottom: 0; }
table.dataTable thead > tr > th.cell-filter { padding-right: 8px;}
table.dataTable tr.totals-row { display: none; }
table.dataTable tr.totals-row th {
    border-bottom: 1px solid #e1e1e1 !important;
    border-top: 1px solid #e1e1e1 !important;
    padding: 5px;
}
.additional-buttons-top, .additional-buttons-bottom { display: inline-block; }

.mass-action .dropdown-menu > li > button {
    clear: both;
    color: #333333;
    display: block;
    font-weight: normal;
    line-height: 1.42857;
    padding: 3px 20px;
    white-space: nowrap;
    width: 100%;
    background: white;
    border: none;
    text-align: left;
}

.mass-action .dropdown-menu > li > button:hover { background-color: #f5f5f5; }


.filter .dropdown-toggle {
    overflow: hidden;
    padding-right: 24px /* Optional for caret */;
    text-align: left;
    text-overflow: ellipsis;
    width: 100%;
}

/* Optional for caret */
.filter .dropdown-toggle .caret {
    position: absolute;
    right: 12px;
    top: calc(50% - 2px);
}

.filter .dropdown-menu li input[type=checkbox] {
	margin-left: 10px;
	margin-right: 10px;
}
.filter .dropdown-menu li label {
	font-size: 12px;
	display: block;
	margin: 0;
	padding-bottom: 5px;
	padding-right: 10px;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}
.filter .btn .badge {
	padding: 2px 6px !important;
}

.select2-drop {
  font-size: 12px;
  font-weight: normal;
}
.select2-container.input-xs .select2-choice {
  height: 21px;
  line-height: 0.5;
  border-radius: 3px;
  font-size: 12px;
  font-weight: normal;
  box-shadow: none;
}
.select2-container.input-xs .select2-choice .select2-arrow b,
.select2-container.input-xs .select2-choice div b {
  background-position: 0 -2px;
}
.top-toolbar .btn-group,
.bottom-toolbar .btn-group { margin: 0 0 5px 0; }

.overlay {
	position: fixed;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	background-color: rgba(0,0,0,0.5);
	z-index: 1100;
}

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
			$('.overlay').show();
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

		$('.filter.multiple').on('hidden.bs.dropdown show.bs.dropdown triggerEvent', function (e) {

			console.log(e);

			var filters = getFilters();
			var el = $(this);
			values = [];

			el.find('input:checked').each(function () {
				values.push(this.value);
			})
			if(values.length>0) {
				el.find('button .badge').text(values.length);
				el.find('button .text').text('');
			} else {
				el.find('button .badge').text('');
				el.find('button .text').text('-');
			}
			var values_list = values.join('|');
			if(e.type == 'triggerEvent') {
				return;
			} else if(e.type == 'show') {
				el.data('old_values', values_list);
			} else {
				console.log(values);
				var old_values = el.data('old_values');
				if(values_list != old_values) {
					filters[el.data('field')] = values_list;
					setFilters(filters);
					$('#filter_form').submit();
				}
			}
		}).trigger('triggerEvent');
		$('select.filter').not('[multiple]').change( function () {
			var filters = getFilters();
			var el = $(this);
			filters[el.data('field')] = this.value;
			setFilters(filters);
			$('#filter_form').submit();
		});
	});
</script>


<div class="filter-panel">


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
	<div class="top-toolbar">
		<div class=" additional-buttons-top" role="group">
		<?= $this->topButtons(); ?>
		</div>
		<div class="btn-group main-buttons-top" role="group">
			<a class="btn btn-default" href="<?= $this->baseUrl ?>&edit=0"><i class="fa fa-plus"></i> <?= _('Add') ?></a>
			<button class="btn btn-default" type="submit" name="delete" onclick="return confirm('<?= _('Delete selected records?') ?>');"><i class="fa fa-trash-o"></i> <?= _('Delete selected') ?></button>
		</div>
		<? if($this->options['export']) { ?>
		<div class="btn-group">
		  <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
		    <i class="fa fa-cloud-download"></i> <?= _('Export') ?> <span class="caret"></span>
		  </button>
		  <ul class="dropdown-menu" role="menu">
		    <li><a class="export-link" href="<?= $this->baseUrl ?>&export&format=csv&encoding=utf8">CSV (UTF-8)</a></li>
		    <li><a class="export-link" href="<?= $this->baseUrl ?>&export&format=csv&encoding=windows1251">CSV (windows-1251)</a></li>
		    <li><a class="export-link" href="<?= $this->baseUrl ?>&export&format=xls">XLS</a></li>
		    <li role="separator" class="divider"></li>
		    <li><a class="export-link" href="<?= $this->baseUrl ?>&print">Print</a></li>
		  </ul>
		</div>
		<? } ?>
		<? if($this->options['import']) { ?>
		<div class="btn-group ">
			<a href="<?= $this->baseUrl ?>&import" class="btn btn-default"><?= _('Import') ?></a>
		</div>
		<? } ?>
	</div>

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
			<th class="checkbox-cell" data-orderable="0"></th>
	<?
			foreach($headers as $header) {
				echo "<th class='".str_replace('_', '-', $this->options['form'][$header]->name)."-cell' title='".@$this->options['form'][$header]->label_hint."'>".
					htmlspecialchars($this->options['form'][$header]->label);
				echo "</th>\n";
			}
	?>
			<th class="table-actions" data-orderable="0"><?= _('Actions') ?></th>
		</tr>
		<tr>
			<th class="checkbox-cell"><input id="header_checkbox" type="checkbox" name="" value="" autocomplete="off"></th>
	<?
			foreach($headers as $header) {
				echo "<th class='".str_replace('_', '-', $this->options['form'][$header]->name)."-cell cell-filter'>";
				if($this->options['form'][$header]->filterByClick) {
					$fieldValues = $this->getFieldValues($this->options['form'][$header]);

					// фильтрация
					if($this->options['form'][$header]->filterByClick === 'multiple') {
						$name = $this->options['form'][$header]->name;
?>
				<div class="filter multiple btn-group btn-group-xs" data-field="<?=$name?>">
					<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
						<span class="text">-</span> <span class="badge"></span> <span class="caret"></span>
					</button>
					<ul class="dropdown-menu">
<? 						foreach($fieldValues as $key => $value) { ?>
						<li>
							<label for="prio_{$priority}">
								<input type="checkbox" name="filter_<?=$name?>" id="filter_<?=$name?>" value="<?=$key?>" <?=isset($this->filters[$name])&&in_array($key, $this->filters[$name])?"checked":""?>>
								<?=$value?>
							</label></li>
<? } /* foreach */ ?>

						<li role="separator" class="divider"></li>
						<li class="text-center"><button class="btn btn-info" type="button"><i class="glyphicon glyphicon-filter"></i> фильтровать</button></li>
					</ul>
				</div>
<?


					} else {
						if($this->options['form'][$header]->filterByClick === 'search')
							$filterClass = 'select2';
						else
							$filterClass = 'selectpicker';
						echo "<select class='filter {$filterClass}' data-field='{$this->options['form'][$header]->name}'  data-title='-'>".
								"<option value=''>-</option>";
						foreach($fieldValues as $key => $value) {
							$name = $this->options['form'][$header]->name;
							echo "<option value='{$key}'".(isset($this->filters[$name])&&in_array($key, $this->filters[$name])?"selected":"").">{$value}</option>";
						}
						echo "</select>";
					}
				}
				echo "</th>\n";
			}
	?>
			<th class="table-actions"></th>
		</tr>
		<tr class="totals-row">
			<th class="checkbox-cell"></th>
	<?
			foreach($headers as $header) {
				echo "<th class='".str_replace('_', '-', $this->options['form'][$header]->name)."-cell'>";
				echo "</th>\n";
			}
	?>
			<th class="table-actions"></th>
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
	<tfoot>
		<tr class="totals-row">
			<th class="checkbox-cell"></th>
	<?
			$totals = $this->getTotals();
			foreach($headers as $header) {
				echo "<th class='".str_replace('_', '-', $this->options['form'][$header]->name)."-cell'>";
				if(isset($totals[$this->options['form'][$header]->name]))
					echo  $totals[$this->options['form'][$header]->name];
				echo "</th>\n";
			}
	?>
			<th class="table-actions"></th>
		</tr>
	</tfoot>
	</table>
<? if(!$this->options['datatables'] && count($items) == 0 && isset($_GET['filter'])) { ?>
	<div class="alert alert-info" role="alert"><?=_('Records not found')?>, <a href='<?=$this->baseUrlNoFilter?>'><?=_('remove filter')?></a>?</div>
<? } elseif(!$this->options['datatables'] && count($items) == 0 && !isset($_GET['filter'])) { ?>
	<div class="alert alert-info" role="alert"><?=_('No records yet,')?> <a href='<?= $this->baseUrl ?>&edit=0'><?=_('add records')?></a>?</div>
<? } ?>

<? if(!$this->options['datatables']) { ?>
	<div class="admin-pager"><?= $htmlPager ?></div>
<? } ?>

	<div class="bottom-toolbar">
		<div class="additional-buttons-bottom" role="group">
		<?= $this->bottomButtons(); ?>
		</div>
		<div class="btn-group main-buttons-bottom" role="group">
			<a class="btn btn-default" href="<?= $this->baseUrl ?>&edit=0"><i class="fa fa-plus"></i> <?= _('Add') ?></a>
			<button class="btn btn-default" type="submit" name="delete" onclick="return confirm('<?= _('Delete selected records?') ?>');"><i class="fa fa-trash-o"></i> <?= _('Delete selected') ?></button>
		</div>
		<? if($this->options['export']) { ?>
		<div class="btn-group dropup">
		  <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
		    <i class="fa fa-cloud-download"></i> <?= _('Export') ?> <span class="caret"></span>
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
	</div>
</form>


<div class="overlay" style="display: none;"></div> <!-- шторка для закрывания на время загрузки -->

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

	var datatable = $('#admin-table').dataTable( {
// 		paginate: false,
		pageLength: <?=$this->options['perpage']?>,
		orderCellsTop: true,
		order: [],
		serverSide: <?= ($this->options['datatables'] === 'client' ? 'false' : 'true') ?>,
		ajax: {
			url: '<?= $this->baseUrl ?>&data-source',
			data: function(data) { // добавление параметров к запросу
				return $.extend((typeof datatablesCustomParams === 'undefined' ? {} : datatablesCustomParams), data);
			},
		},
		columnDefs: [ {
			targets: "_all",
			orderSequence: [ "desc", "asc"]
		} ],
		columns: [
		        { data: 'checkbox-cell', className: 'checkbox-cell' },
	<?		foreach($headers as $header) { ?>
		        { data: '<?= $this->options['form'][$header]->cell_class ?>', },
	<?		}	?>
		        { data: 'actions-cell', className: 'table-actions' }
	    ],
		stateSave: true,
		stateDuration: 0, // хранить настройки без ограничения по времени
		pagingType: "full_numbers",
//		fixedHeader: true,
		buttons: [
	        {
		        extend: 'colvis',
		        text: '<i class="fa fa-table"></i> <?=_('Columns')?> <span class="caret"></span>',
		    }
	    ],
		dom:
			"<'row'<'col-sm-3'i><'col-sm-9'Bp>>" +
			"<'row'<'col-sm-12'tr>>" +
//			"<'row'<'col-sm-12'p>>" +
			"<'row'<'col-sm-3'l><'col-sm-9'p>>",
		lengthMenu: [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "Все"] ],
		language: {
			paginate: {
				first: "&laquo;",
				last: "&raquo;",
				previous: "<?=_('Previous')?>",
				next: "<?=_('Next')?>",
			},
			"processing": "Подождите...",
			"search": "Поиск:",
			"lengthMenu": "Показать _MENU_ записей",
			"info": "Записи с _START_ до _END_ из _TOTAL_ записей",
			"infoEmpty": "Записи с 0 до 0 из 0 записей",
			"infoFiltered": "(отфильтровано из _MAX_ записей)",
			"infoPostFix": "",
			"loadingRecords": "Загрузка записей...",
			"zeroRecords": "Записи отсутствуют.",
			"emptyTable": "В таблице отсутствуют данные",
			"aria": {
				"sortAscending": ": активировать для сортировки столбца по возрастанию",
				"sortDescending": ": активировать для сортировки столбца по убыванию"
			}
		},
        fnDrawCallback: function(oSettings) { // hides paging when only one page to display
            if (oSettings._iDisplayLength == -1
                || oSettings._iDisplayLength > oSettings.fnRecordsDisplay())
            {
                jQuery(oSettings.nTableWrapper).find('.dataTables_paginate').hide();
            } else {
                jQuery(oSettings.nTableWrapper).find('.dataTables_paginate').show();
            }
        }
//		ordering: false,
// 		scrollY: 300
	}).on('preXhr.dt', function (e) {
		$('.overlay').show();
	}).on('xhr.dt', function (e, settings, json, xhr) {
		$('.overlay').hide();
		if(json === null) return;
		if(json.header) {
			var cells = $('.dataTable thead .totals-row th').empty();
/*
			cells.each(function (index, cell) {
				$(cell).html(json.header[index]);
			});
*/
			$.each(json.header, function (key, value) {
				cells.filter('.'+key).html(value);
			});
			$('.dataTable thead .totals-row').show();
		}
	}).on('draw.dt', function () {
		$('.row_checkbox').each(function () {
			if(checkboxed_storage.indexOf(this.value) != -1) {
				$(this).attr('checked', true);
			} else {
				$(this).removeAttr('checked');
			}
		});
	}).api();

new $.fn.dataTable.FixedHeader( datatable, {
    // options
} );


	try {
		datatable.buttons().container().appendTo('.top-toolbar');
	} catch(e) {
    	console.log('datatables buttons error');
	}


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

	$('select.filter.selectpicker').selectpicker({
		 width: '100%',
		 style: 'btn-default btn-xs',
	});

	$('select.filter.select2').select2({
		containerCssClass: 'input-xs',
		dropdownAutoWidth: true,
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

