<?php
Auth::checkPermission('logs');	
	
	
require 'admin_form.php';
require 'logDetailsType.php';
 
class LogsAdmin extends AdminModule {
	function topButtons() {
		return <<< EOF
<style>
	#header_checkbox, .row_checkbox, .main-buttons-top, .main-buttons-bottom { display: none; }
</style>		
EOF;
	}
	function bottomButtons() {
		return "";
	}
	function actions($item) {
		return '
				<div class="btn-group" role="group">
					<a class="btn btn-default btn-xs" href="'.$this->baseUrl.'&edit='.$item['id'].'"><span class="glyphicon glyphicon-zoom-in" title="Детали"></span> детали</a> 
				</div>		
';
	}
	function formButtons() {
		return '<button type="submit" class="btn btn-primary" id="editForm_save" name="editForm_save">Вернуться</button>
<style>
	.form-group.table {
		width: auto;
		max-width: none;
	}
</style>';
	}
			

}

$admin = new LogsAdmin(array(
		'title' => 'Журнал действий',
		'table' => 'logs',
		'form' => $objectForm,
		'baseUrl' => '/logs/?foo',
		'helpersUrl' => '/_libs/AdminModule/',
		'db' => $db->linkId,
		'root_path' => ROOT_PATH,
		'sort' => 'date DESC',
		'date' => 'date',
	));
 
ob_start();
$admin->processCommands();	
$content = ob_get_clean();
$smarty->assign('content', $content);

$smarty->display(ROOT_PATH.'_tpl/index.tpl');
