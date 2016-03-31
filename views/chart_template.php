<script src="https://code.highcharts.com/highcharts.js"></script>
<style>
.filter {
	width: 100%;
}
.filter.multiple > .btn {
	background: white;
	box-shadow: none;
}
.filter.multiple .btn .caret {
	color: #939393;
}

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
<div class="overlay" style="display: none;"></div> <!-- шторка для закрывания на время загрузки -->


<div class="panel panel-default">
	<div class="panel-heading"><?= _('Filters')?></div>
	<div class="panel-body">

	<form method="GET" action="" id="filter_form">
	<input type="hidden" name="filter" id="filter_input" class="form-control" value="<?= htmlspecialchars(@$_GET['filter']) ?>">
	<div class="row">

<?php if($this->options['date']) { ?>
		<div class="col-lg-4 pull-right">
			<label><?= _('Date')?></label>
			<div class="form-inline">
				<div class="form-group">
					<input type='text' class="form-control" name="df" id='date-from' value="<?= $this->dateFrom!=''?date('d.m.Y', strtotime($this->dateFrom)):'' ?>" placeholder="Дата от"/>
				</div>
				<div class="form-group">
					<input type='text' class="form-control" name="dt" id='date-to' value="<?= $this->dateTo!=''?date('d.m.Y', strtotime($this->dateTo)):'' ?>" placeholder="до"/>
				</div>
				<div class="btn-group" style="margin-right: 20px;">
				  <button type="submit" class="btn btn-default">
				    <?= _('Show'); ?>
				  </button>
				  <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
				    <i class="glyphicon glyphicon-calendar"></i> <span class="caret"></span>
				  </button>
				  <ul class="dropdown-menu pull-right" role="menu">
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
			</div>
		</div>
<?php } ?>

<?php	foreach($this->filter_fields as $field) { ?>
	<div class="col-lg-2 pull-left">
		<label for="filter_<?=$name?>"><?= htmlspecialchars($field->label) ?></label>
		<div>
<?php
		$fieldValues = $this->getFieldValues($field);
		$name = $field->name;

		// фильтрация
		if($field->filterByClick === 'multiple') {
?>
	<div id="filter_<?=$name?>" class="filter multiple btn-group" data-field="<?=$name?>">
		<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
			<span class="text">-</span> <span class="badge"></span> <span class="caret"></span>
		</button>
		<ul class="dropdown-menu">
<?php						foreach($fieldValues as $key => $value) { ?>
			<li>
				<label for="prio_{$priority}">
					<input type="checkbox" name="filter_<?=$name?>" id="filter_<?=$name?>" value="<?=$key?>" <?=isset($this->filters[$name])&&in_array($key, $this->filters[$name])?"checked":""?>>
					<?=$value?>
				</label></li>
<?php } /* foreach */ ?>

			<li role="separator" class="divider"></li>
			<li class="text-center"><button class="btn btn-info" type="button"><i class="glyphicon glyphicon-filter"></i> фильтровать</button></li>
		</ul>
	</div>
<?php


		} else {
			if($field->filterByClick === 'search')
				$filterClass = 'select2';
			else
				$filterClass = 'selectpicker';
			echo "<select id='filter_{$name}' class='filter form-control {$filterClass}' data-field='{$name}'  data-title='-'>".
					"<option value=''>-</option>";
			foreach($fieldValues as $key => $value) {
				echo "<option value='{$key}'".(isset($this->filters[$name])&&in_array($key, $this->filters[$name])?"selected":"").">{$value}</option>";
			}
			echo "</select>";
		}

?>
		</div>
	</div>
<?php	} ?>
		</div>
		</form>
	</div>
</div>


<div id="chart-container"></div>

<script>

	$('select.filter.selectpicker').selectpicker({
		 width: '100%',
		 style: 'btn-default btn-xs',
	});

	$('select.filter.select2').select2({
//		containerCssClass: 'input-xs',
		dropdownAutoWidth: true,
	});


    var chart_options = {
        chart: {
            type: '<?=$this->options['type']?>',
            height: <?=intval($this->options['height'])?>
        },
        title: {
            text: '<?=$this->options['title']?>',
        },
/*
        subtitle: {
            text: 'Source: WorldClimate.com',
            x: -20
        },
*/
        xAxis: {
            categories: <?= $this->json($this->xAxis, JSON_UNESCAPED_UNICODE)?>
        },
        yAxis: {
            title: {
                text: ''
            },
/*
            plotLines: [{
                value: 0,
                width: 1,
                color: '#808080'
            }]
*/
        },
/*
        tooltip: {
            valueSuffix: '°C'
        },
*/
        legend: {
            layout: 'horizontal',
            align: 'left',
            verticalAlign: 'bottom',
            borderWidth: 0
        },
        series:<?= $this->json($this->series, JSON_UNESCAPED_UNICODE)?>

/*
        [{
            name: 'Tokyo',
            data: [7.0, 6.9, 9.5, 14.5, 18.2, 21.5, 25.2, 26.5, 23.3, 18.3, 13.9, 9.6]
        }, {
            name: 'New York',
            data: [-0.2, 0.8, 5.7, 11.3, 17.0, 22.0, 24.8, 24.1, 20.1, 14.1, 8.6, 2.5]
        }, {
            name: 'Berlin',
            data: [-0.9, 0.6, 3.5, 8.4, 13.5, 17.0, 18.6, 17.9, 14.3, 9.0, 3.9, 1.0]
        }, {
            name: 'London',
            data: [3.9, 4.2, 5.7, 8.5, 11.9, 15.2, 17.0, 16.6, 14.2, 10.3, 6.6, 4.8]
        }]
*/
    };
    var custom_chart_options = {<?=$this->options['options']?>};

    $('#chart-container').highcharts($.extend(true, chart_options, custom_chart_options));


// Фильтрование

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

	$('#filter_form').submit(function () {
		$('.overlay').show();
		var new_location = "<?=$this->baseUrlNoFilter?>&filter="+$('#filter_input').val();
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


	// date pickers
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