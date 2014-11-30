<style>
	.link { border-bottom: 1px white dotted; display: inline; cursor: pointer; }
	.pager { margin: 5px 0;	}
	div.filter { margin: 0 0 10px 0; float: right;	max-width: 200px; }
	div.filter form { margin: 0;	}
	select.filter { max-width: 150px; }
	.breadcrumbs { font-weight: bold; margin: 10px 0; }
	.clear { clear: both; }	
div.pagination {
	padding: 3px;
	margin: 3px;
	text-align: center;
	font-family: Tahoma,Helvetica,sans-serif;
	}
	
div.pagination a {
	color: #0645ad;
	border: 1px solid #a9b8dd;
	margin-right: 3px;
	padding: 2px 6px;
	background-position: bottom;
	text-decoration: none;
	}
	
div.pagination a:hover, div.pagination a:active {
	border: 1px solid #3c61a5;
	background-image: none;
	background-color: #6b92d7;
	color: #fff;
	}
	
div.pagination span.current {
	color: #000;
	font-weight: bold;
	margin-right: 3px;
	padding: 2px 6px;
	}
	
div.pagination span.disabled {
	border: 1px solid #ddd;
	color: #bbb;
	margin-right: 3px;
	padding: 2px 6px;
	}
	
div.pagination .next {
	margin: 0 3px 0 8px;
	}
	
div.pagination .prev {
	margin: 0 8px 0 0;
	}	
</style>



<script>
	$(function () {
		$('#filter_form').submit(function () {
			document.location = "<?=$this->baseUrlNoPaging?>&filter="+$('#filter_input').val();
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
	<form method="GET" action="" id="filter_form" class="input-group">
		<input type="text" name="filter" id="filter_input" class="form-control" value="<?= htmlspecialchars(@$_GET['filter']) ?>" placeholder="фильтр"> 
		<span class="input-group-btn">
			<button type="submit" class="btn btn-default" name=""><span class="glyphicon glyphicon-search"></span></button>
		</span>
	</form>
<? if(isset($_GET['filter'])) {
	echo "<a href='{$this->baseUrlNoFilter}'>убрать фильтр</a>";
}
?>
</div>
<form method="POST" action="<?= $this->baseUrl ?>">
	<div class="btn-group" role="group">
	<?= $this->topButtons(); ?>
	</div>
	<div class="btn-group" role="group">
		<button class="btn btn-default" type="button" onclick="document.location='<?= $this->baseUrl ?>&edit=0'; return false;">Добавить</button>
		<button class="btn btn-default" type="button" name="delete" onclick="return confirm('Удалить выбранные записи?');">Удалить выбранные</button>
	</div>
	
	<div class="clear"></div>
	<div class="pager"><?= $htmlPager ?></div>
	<table class="table table-hover table-bordered table-striped">	
	<thead>
		<tr>
			<th><input id="header_checkbox" type="checkbox" name="" value="" autocomplete="off"></th>
	<?		
			foreach($headers as $header) {
				echo "<th>".
					htmlspecialchars($this->options['form'][$header]->label);
				if($this->options['form'][$header]->filterByClick) {
					$fieldValues = $this->getFieldValues($this->options['form'][$header]);
					echo "<br>".
						 "<select class='filter' data-field='{$this->options['form'][$header]->name}'>".
							"<option value=''>-</option>";
					foreach($fieldValues as $key => $value) {
						echo "<option value='{$key}'".($this->filter=="{$this->options['form'][$header]->name}:{$key}"?"selected":"").">{$value}</option>"; 
					}
					echo "</select>";
				}
				echo "</th>\n";
			}
	?>
			<th>Действия</th>
		</tr>			
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
			<td>
				<div class="btn-group" role="group">
					<a class="btn btn-default btn-xs" href="<?= $this->baseUrl ?>&edit=<?= $item['id'] ?>"><span class="glyphicon glyphicon-edit" title="Редактировать"></span></a> 
					<a class="btn btn-default btn-xs" href="<?= $this->baseUrl ?>&edit=<?= $item['id'] ?>&clone"><span class="glyphicon glyphicon-sound-stereo" title="Копировать"></span></a> 
					<a class="btn btn-default btn-xs" href="<?= $this->baseUrl ?>&delete&item=<?= $item['id'] ?>" onclick="return confirm('Удалить?');"><span class="glyphicon glyphicon-remove" title="Удалить"></span></a> 
					<?= $this->actions($item) ?>
				</div>
			</td>
		</tr>
	<?
				$count++;
			}
	?>
	</tbody>
	</table>
	<div class="pager"><?= $htmlPager ?></div>
	<div class="btn-group" role="group">
	<?= $this->topButtons(); ?>
	</div>
	<div class="btn-group" role="group">
		<button class="btn btn-default" type="button" onclick="document.location='<?= $this->baseUrl ?>&edit=0'; return false;">Добавить</button>
		<button class="btn btn-default" type="button" name="delete" onclick="return confirm('Удалить выбранные записи?');">Удалить выбранные</button>
	</div>
</form>
