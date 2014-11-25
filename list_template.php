<style>
	.list-table thead tr { background-color: #B50505; color: white; }
	.list-table tr a,
	.list-table tr { color: #009EC2; }
	.list-table tr.active a,
	.list-table tr.active { color: black; }
	.list-table thead a { color: white; }
	.list-table thead td { vertical-align: top; }
	.list-table td.table-data a { text-decoration: none; }
	.link { border-bottom: 1px white dotted; display: inline; cursor: pointer; }
	.pager { margin: 5px 0;	}
	div.filter { margin: 5px 0 20px 0; float: right;	}
	div.filter form { margin: 0;	}
	#filter_input { width: 200px; }
	select.filter { max-width: 150px; }



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
	<form method="GET" action="" id="filter_form">
		<input type="text" name="filter" id="filter_input" value="<?= htmlspecialchars(@$_GET['filter']) ?>" placeholder="фильтр"> 
		<button type="submit" name="">&gt;</button>
	</form>
<? if(isset($_GET['filter'])) {
	echo "<a href='{$this->baseUrlNoFilter}'>убрать фильтр</a>";
}
?>
</div>
<form method="POST" action="<?= $this->baseUrl ?>">
<?= $this->topButtons(); ?>
<button type="button" onclick="document.location='<?= $this->baseUrl ?>&edit=0'; return false;">Добавить</button>
<button type="submit" name="delete" onclick="return confirm('Удалить выбранные записи?');">Удалить выбранные</button>
<div class="pager"><?= $htmlPager ?></div>
<table class="list-table" width="100%" border="0" cellpadding="5" cellspacing="1" bgcolor="#CCCCCC">	
<thead>
	<tr bgcolor="#BEBEBE" >
		<td><input id="header_checkbox" type="checkbox" name="" value="" autocomplete="off"></td>
<?		
		foreach($headers as $header) {
			echo "<td>".
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
			echo "</td>\n";
		}
?>
		<td>Действия</td>
	</tr>			
</thead>
<tbody>
<?
		$count = 0;
		foreach($items as $item) {
?>
	<tr bgcolor="<?= ($count%2==0)?'#FFFFFF':'#EEEEEE' ?>" class="<?= $this->getListClass($item); ?>">
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
				<a href="<?= $this->baseUrl ?>&edit=<?= $item['id'] ?>">[Редактировать]</a> 
				<a href="<?= $this->baseUrl ?>&edit=<?= $item['id'] ?>&clone">[Копировать]</a> 
				<a href="<?= $this->baseUrl ?>&delete&item=<?= $item['id'] ?>" onclick="return confirm('Удалить?');">[Удалить]</a> 
				<?= $this->actions($item) ?>
		</td>
	</tr>
<?
			$count++;
		}
?>
</tbody>
</table>
<br>
<div class="pager"><?= $htmlPager ?></div>
<br>
<?= $this->topButtons(); ?>
<input type="button" onclick="document.location='<?= $this->baseUrl ?>&edit=0'; return false;" value="Добавить">
<button type="submit" name="delete" onclick="return confirm('Удалить выбранные записи?');">Удалить выбранные</button>
</form>
