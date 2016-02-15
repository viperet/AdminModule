<h1><?= $this->navigation->title() ?></h1>
<?= $this->listHeader(); ?>

<style>
table.table {
	border-collapse: collapse;
}
.no-break {
	page-break-inside: avoid;
	position: relative;
	 -webkit-region-break-inside: avoid; 
}

table.table td, table.table th {
	border: 1px solid black;
	padding: 0.5em;
}
table.table th {
	border-top: 2px solid black;
	border-bottom: 2px solid black;
	padding: 1em 0.5em;
	vertical-align: top;
}
</style>

	<table id="admin-table" class="table table-hover table-bordered table-striped table-condensed" width="100%">	
	<thead>
		<tr>
	<?		
			foreach($headers as $header) {
				if(!$this->options['form'][$header]->print) continue;
				echo "<th class='".str_replace('_', '-', $this->options['form'][$header]->name)."-cell' title='".@$this->options['form'][$header]->label_hint."'>".
					htmlspecialchars($this->options['form'][$header]->label);
				echo "</th>\n";
			}
	?>
		</tr>			
	</thead>
	<tbody>
	<?
			$count = 0;
			foreach($items as $item) {
	?>
		<tr class="<?= $this->getListClass($item); ?>">
	<?
				foreach($headers as $header) {
					$formItem = $this->options['form'][$header];
					if(!$formItem->print) continue;
					$formItem->fromRow($item);
					
	?>
			<td class="table-data <?=str_replace('_', '-', $formItem->name)?>-cell" <? $s=$formItem->toString(); if(mb_strlen($s)>$formItem->truncate) echo ' title="'.htmlspecialchars(str_replace("\n", " ", $s), ENT_QUOTES, $formItem->encoding, false).'" '; ?> >
				<div class="no-break"><?= $formItem->toListElement() ?></div>
			</td>
	<?
					
				}
	?>
		</tr>
	<?
				$count++;
			}
	?>
	</tbody>
	</table>
<? if(count($items) == 0 && isset($_GET['filter'])) { ?>	
	<div class="alert alert-info" role="alert"><?=_('Records not found')?>, <a href='<?=$this->baseUrlNoFilter?>'><?=_('remove filter')?></a>?</div>
<? } elseif(count($items) == 0 && !isset($_GET['filter'])) { ?>	
	<div class="alert alert-info" role="alert"><?=_('No records yet,')?> <a href='<?= $this->baseUrl ?>&edit=0'><?=_('add records')?></a>?</div>
<? } ?>	




<script>
if (window==window.top) { 
	/* I'm not in a frame! */ 
	window.print();
	window.onfocus=function(){ window.history.back(); }
	
}	
</script>

