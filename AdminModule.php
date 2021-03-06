<?php

function AdminModuleAutoloader($class) {
	$file = dirname(__FILE__).'/types/' . $class . '.php';
	if(file_exists($file))
	    require_once $file;
	else {
		echo "$file not found";
		return false;
	}
		
}

spl_autoload_register('AdminModuleAutoloader');
require_once("Translate.class.php");			
require_once("Form.class.php");			
require_once("AdminDatabase.class.php");			
require_once("AdminChart.class.php");			
require_once("Navigation.class.php");			
require_once("Pagination.class.php");			
//require_once(PATH_ROOT ."classes/pageSplit.class.php");

class AdminModule {
	var $options = array(
			'table' => '',
			'form' => '',
			'perpage' => 25,
			'title' => '',
			'sort' => '',
			'export' => false,
			'date' => false,
			'user' => 'unknown',
			'datatables' => false,
			'logger' => false,
		);
	public $db, $navigation;
	public $form;
	public $baseUrl;
	public $baseUrlNoPaging;
	public $baseUrlNoFilter;
	public $helpersUrl;
	public $itemsCount;
	public $search = "", $filter = "", $dateFrom = "", $dateTo = "";
	public $filters = array();
	public $logger;
	
	private $gettextDomain;
	
	function __construct($options) {
//		global $this->db;
//! BANKER
		
		$this->options = array_merge($this->options, $options);	

		if(empty($this->options['db'])) throw new Exception("Empty required parameter 'db'");
		$this->db = new AdminDatabase($this->options['db']);

		$this->navigation = new Navigation();

		if(empty($this->options['helpersUrl'])) throw new Exception("Empty required parameter 'helpersUrl'");
		$this->helpersUrl = $this->options['helpersUrl'];

		if(isset($this->options['baseUrl']))
			$this->baseUrlNoFilter = $this->baseUrlNoPaging = $this->baseUrl = $this->options['baseUrl'];
//		else
//			$this->baseUrlNoFilter = $this->baseUrlNoPaging = $this->baseUrl = "/admin/datarouter.php?action=edit&id=".(int)$_GET['id'];
//! BANKER

		if($this->options['logger'] === true) { // default logger
			$this->logger = new DetailedLogger($this->db, $this);
		} elseif(is_object($this->options['logger']) && is_a($this->options['logger'], 'CoreLogger')) { // logger object
			$this->logger = $this->options['logger'];
		} elseif(is_string($this->options['logger']) && is_a($this->options['logger'], 'CoreLogger', true)) { // logger class name
			$this->logger = new $this->options['logger']($this->db, $this);
		} elseif(!empty($this->options['logger'])) { // wrong logger
			throw new Exception("Logger must be successor class Logger or true for default logger");
		} else { // null logger
			$this->logger = new CoreLogger($this->db, $this);
		}
		
		if(isset($_GET['block']))
			$this->baseUrl .= "&block=".$_GET['block'];
		if(isset($_GET['filter'])) { 
			$this->filter = $_GET['filter'];
			$this->baseUrl .= "&filter=".urlencode($_GET['filter']);
			$this->baseUrlNoPaging .= "&filter=".urlencode($_GET['filter']);
			
			foreach(explode(';', $this->filter) as $filter) {
				list($param, $value) = explode(':', $filter);
				if(empty($param)) continue;
				$this->filters[$param] = explode('|', $value);
			}
		}
		if(isset($_GET['query'])) { 
			$this->search = $_GET['query'];
			$this->baseUrl .= "&query=".urlencode($_GET['query']);
			$this->baseUrlNoPaging .= "&query=".urlencode($_GET['query']);
		}
		if(isset($_GET['s'])) {
			$this->baseUrl .= "&s=".(int)$_GET['s'];
		}
		if(!empty($_GET['df']) && !empty($_GET['dt'])) { 
			$this->dateFrom = date('Y-m-d', strtotime($_GET['df']));
			$this->dateTo = date('Y-m-d', strtotime($_GET['dt']));
			$this->baseUrl .= "&df=".urlencode($_GET['df'])."&dt=".urlencode($_GET['dt']);
			$this->baseUrlNoPaging .= "&df=".urlencode($_GET['df'])."&dt=".urlencode($_GET['dt']);
		}
		
		
		// преобразование формы в линейный список
		$tmp = $this->options['form'];
		$this->options['form'] = array();
		foreach($tmp as $name=>&$array) {
			if($array['type'] == 'group') {
				$arrayBegin = $arrayEnd = $array;
				$arrayBegin['begin'] = true;
				$arrayEnd['begin'] = false;
				unset($arrayBegin['form'], $arrayEnd['form']);
				$this->options['form'] = array_merge(
					$this->options['form'], 
					array($name => $arrayBegin),
					$array['form'],
					array($name.'-end' => $arrayEnd)
				);
			} else 
				$this->options['form'][$name] = $array;
		}
		unset($tmp);
		

// 		echo "<pre>";print_r($this->options['form']);exit;
		
		// преобразование в объекты
		foreach($this->options['form'] as $name=>&$array) {
			if(isset($this->options['role']) && isset($array['permissions'])) {
				$role = $this->options['role'];
				if(!isset($array['permissions'][$role]) || $array['permissions'][$role] == '') {  // нет прав на просмотр/изменение поля
					$array['type'] = 'none';
				} else if($array['permissions'][$role] == 'r') {  // только чтение
					$array['readonly'] = true;
				}
			}
			
			$className = $array['type']."Type";
			$array = new $className($this->db, $name, $array);
			$array->options = $this->options;
		}
		unset($array);


	}


	function sortFields() {
		// сортируем
		stable_uasort($this->options['form'], function ($a, $b) {
		    if ($a->order == $b->order) {
		        return 0;
		    }
		    return ($a->order < $b->order) ? -1 : 1;			
		});
	}

/* ================================= */
/* Просмотр списка записей в таблице */
/* ================================= */
	
	function listItems() {
		$this->sortFields();

		$per_page  = $this->options['perpage'];
		$limit = (empty($_GET['s'])?0:(int)$_GET['s']);
		
		if(!$this->options['datatables']) {
			$items = $this->getItems($limit, $per_page);
		}
//		echo "<hr>{$this->itemsCount}<hr>";
//		$pager = new pageSplit($page, $this->itemsCount, '', $per_page);		
//		$htmlPager = $pager->showNav();
		
		$pager = new Pagination($this->itemsCount, $per_page);
		$htmlPager  = $pager->display($this->baseUrlNoPaging);		
		
//		$headers = array('id'); // hide ID column for now
		$headers = array();
		$this->options['form'] = array_merge(array('id' => new textType($this->db, 'id', array('type'=>'text', 'label'=>'ID'))), $this->options['form']);

		foreach($this->options['form'] as $key=>$value) {
			if(!empty($value->header)) {
				$headers[] = $key;
			}
		}
		
		include('views/list_template.php');

	}
/* ================================= */
/* Печать списка записей в таблице */
/* ================================= */
	
	function printItems() {
		$this->sortFields();

		$per_page  = 1000000;
		$limit = 0;
		
		$items = $this->getItems($limit, $per_page);
		
		
		$headers = array();
		$this->options['form'] = array_merge(array('id' => new textType($this->db, 'id', array('type'=>'text', 'label'=>'ID'))), $this->options['form']);

		foreach($this->options['form'] as $key=>$value) {
			if(!empty($value->header)) {
				$headers[] = $key;
			}
		}
		
		include('views/print_template.php');

	}

/* ================================	 */
/* Редактирование/добавление записи	 */
/* ================================	 */
	function editItem($id, $clone = false) {
		
		$myObjectForm = $this->options['form'];
		if($id>0) {
			// редактирование 
			$object = $this->getItem($id);
		} else {
			// добавление
			$object = array();
			foreach($myObjectForm as $key => $value) {
				if(isset($value->value)) {
					// устанавливаем значения по умолчанию
					$object[$key] = $value->value;
				}
			}
		}
		$this->form = new Form($myObjectForm, $this);
		if($this->form->filled($_POST)) {	
//			$merged_object = array_merge($object, $_POST);
			$merged_object = $_POST;
			$this->form->load($merged_object, 'form');
			$error = true;
			if(!$this->form->validate($_POST) ||
			  ($error = $this->validate($_POST))!== true
			) {	
				if($error !== true) {
					array_unshift($this->form->errorMessage, $error);
				}
			} else {// все ок
				
				try {
					if($id>0 && !$clone) { // сохранение
						$this->logger->beforeAction($id, 'update');
						$res = $this->updateItem($id, $_POST);
						$item = $this->getItem($id);
						$this->logger->afterAction($id, 'update', $item);
					} else { // создание
						$this->logger->beforeAction(null, 'insert');
						$id = $this->insertItem($_POST);
						$item = $this->getItem($id);
						$this->logger->afterAction($id, 'insert', $item);
					}

					$this->updateCache($id, $item);
					$this->updateFullCache();
					
					$postSql = $this->form->postSave($id, $_POST, $item);
					if($postSql) {
						$res = $this->db->query("UPDATE ".$this->options['table']." SET ".$postSql);
					}
					if(isset($_POST['editForm_save'])) {
						header("Location: ".$this->baseUrl);
						exit;
					} else {
						header("Location: ".$this->baseUrl."&edit={$id}");
						exit;
					}
				} catch(AdminDatabaseException $e) {
					switch($e->getCode()) {
						case AdminDatabaseException::DUPLICATE_KEY:
							$error = _("Can't insert duplicate data").' ('.$e->getMessage().')'; break;
						default: 
							$error = $e->getMessage();
					}
					array_unshift($this->form->errorMessage, $error);						
				}
			}
			$htmlForm = $this->form->build();
		} else {
			$this->form->load($object, 'db');
			$htmlForm = $this->form->build();
		}
		echo $htmlForm;
	}	

/* ====================== */
/* Обновление записи      */
/* ====================== */
	function updateItem($id, $data) {
		$save = $this->form->save($data);
		if($save == '') return false;
		$sql = "UPDATE `".$this->options['table']."` SET ".$save." WHERE id = ". (int)$id;
//		echo $sql; exit;
		$res =$this->db->query($sql);
//		if($res !== 1) {
//			echo "UPDATE error<br>";
//			echo nl2br($res->result->userinfo);
//			exit;
//		}		
		return $res;
	}

/* ====================== */
/* Вставка записи         */
/* ====================== */
	function insertItem($data) {
		
		$sql = "INSERT ".$this->options['table']." SET ".$this->form->save($data);
		return $this->db->query($sql); // return insert id
//		if($res !== 1) {
//			echo "INSERT error<br>";
//			echo nl2br($res->result->userinfo);
//			exit;
//		}
		return mysql_insert_id();
	}

/* ====================== */
/* Получение SQL для фильтра */
/* ====================== */
	function getFilterSQL($additionalFields = NULL) {
		
		if($this->dateFrom != '' && $this->dateTo != '') {
			$dateSql = " `{$this->options['table']}`.`{$this->options['date']}` >=  '{$this->dateFrom} 00:00:00' AND `{$this->options['table']}`.`{$this->options['date']}` <=  '{$this->dateTo} 23:59:59' ";
		} else {
			$dateSql = "1";
		}
		
		
// 		if(trim($this->filter) == '') return $dateSql;


		if(count($this->filters)>0) {
			$filterSql = "1";
			foreach($this->filters as $field => $filter) {
				
				foreach($filter as &$filter_value) {
					$filter_value = "'".mysql_real_escape_string($filter_value)."'";
				}
				if(strpos($field, '.') === false) {
					$field_name = "`{$this->options['table']}`.`{$field}`";
				} else {
					$field_name = $field;
				}
				
				$filterSql .= " AND {$field_name} IN (".implode(',', $filter).")";
			}
// 			return $dateSql.$filterSql;
		} else {
			$filterSql = "1";
		}


		$search = $this->search;
		$sql = array();
		if(trim($search) != '') {
			$search = '%'.mysql_real_escape_string($search).'%';
			if(is_array($additionalFields)) {
				foreach($additionalFields as $key)
					$sql[] = "{$key} LIKE '{$search}'";
			}
			foreach($this->options['form'] as $key=>$value)
				if($value->filter)
					$sql[] = "`{$this->options['table']}`.`{$key}` LIKE '{$search}'";
					
			if(preg_match('/^%(\d+)%$/', $this->search, $m)) {
				$sql[] = "`{$this->options['table']}`.`id` = '{$m[1]}'";
			}
		}
		
		if(count($sql) > 0) 
			$searchSql = '( '.implode(' OR ', $sql).' )';
		else
			$searchSql = "1";

		return " {$dateSql} AND {$filterSql} AND {$searchSql} ";

	}

/* ====================== */
/* Получение значений поля для фильтрации */
/* ====================== */
	function getFieldValues($field) {
		return $field->getValues();
	}

/* ====================== */
/* Получение всех записей */
/* ====================== */
	function getItems($from=0, $count=100) {
		
		$sql = "SELECT * FROM {$this->options['table']} WHERE ".$this->getFilterSQL().
			($this->options['sort']?" ORDER By {$this->options['sort']}":"").
			(isset($from) && isset($count) ? " LIMIT $from,$count" : "");
		$items = $this->db->getAll($sql);
		$sql = "SELECT  COUNT(*) FROM {$this->options['table']} WHERE ".$this->getFilterSQL();
		
		$this->itemsCount = $this->db->getOne($sql);
		return $items;	
	}

/* ====================== */
/* Получение записи по ID */
/* ====================== */
	function getItem($id) {
		
		$row = $this->db->getRow("SELECT * FROM `".$this->options['table']."` WHERE id=?", array($id));
		return $row;
	}

/* ====================== */
/* Получение итоговых данных */
/* ====================== */
	function getTotals() {
		return false;		
	}

/* =============== */
/* Удаление записи */
/* =============== */
	function deleteItem($ids) {
		if(!is_array($ids)) $ids = array($ids);

		$myObjectForm = $this->options['form'];
		$this->form = new Form($myObjectForm, $this->options);
		
		foreach($ids as $id) {
			$item = $this->getItem($id);
			
// сообщаем полям записи об удалении, чтоб они могли подчистить за собой данные
// например связанные записи в таблицах, картинки, теги
			$this->form->load($item, 'db');
			$this->form->delete();
			
			$this->logger->beforeAction(intval($id), 'delete', $item);
 
 			$sql = "DELETE FROM `".$this->options['table']."` WHERE id = ".intval($id);
			$res = $this->db->query($sql);

			$this->logger->afterAction(intval($id), 'delete');

			$this->updateCache($id, $item);
		}
		$this->updateFullCache();
		$this->returnUser();
	}
	
	
	function returnUser() {
		if(!empty($_SERVER['HTTP_REFERER']))
			header("Location: ".$_SERVER['HTTP_REFERER']);
		else
			header("Location: ".$this->baseUrl);
		exit;
	}
	
/* =============== */
/* Обновление кеша	*/
/* =============== */
	function updateCache($id, $item = NULL) {
		return true;
	}

/* =============== */
/* Обновление кеша	*/
/* =============== */
	function updateFullCache() {
		return true;
	}
	
	
/* =============== */
/* Дополнительные действия над записями	*/
/* =============== */
	function actions($item) {
		return '
				<div class="btn-group" role="group">
					<a class="btn btn-default btn-xs" href="'.$this->baseUrl.'&edit='.$item['id'].'"><span class="glyphicon glyphicon-edit" title="'._('Edit').'"></span></a> 
					<a class="btn btn-default btn-xs" href="'.$this->baseUrl.'&edit='.$item['id'].'&clone"><span class="glyphicon glyphicon-sound-stereo" title="'._('Clone').'"></span></a> 
					<a class="btn btn-default btn-xs" href="'.$this->baseUrl.'&delete&item='.$item['id'].'" onclick="return confirm(\''._('Delete?').'\');"><span class="glyphicon glyphicon-remove" title="'._('Delete').'"></span></a> 
				</div>		
';
	}
	
/* =============== */
/* Возвращает класс для TR в списке	*/
/* =============== */
	function getListClass($item) {
		return "";
	}
	
/* =============== */
/* Возвращает кнопки в начале и конце списка	*/
/* =============== */
	function topButtons() {
		$html = "";
		foreach($this->options['form'] as $key=>$value) {
			if($value->massAction && $value->type == 'checkbox') {
				$html .= "
<div class='btn-group mass-action'>
  <button type='button' class='btn btn-default dropdown-toggle' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>
    {$value->label} <span class='caret'></span>
  </button>
  <ul class='dropdown-menu'>
    <li><button type='submit' name='mass_{$value->name}_on' onclick=\"return confirm('{$value->label} "._('On')."?');\">"._('On')."</button></li>
    <li><button type='submit' name='mass_{$value->name}_off' onclick=\"return confirm('{$value->label} "._('Off')."?');\">"._('Off')."</button></li>
  </ul>
</div>
";
				
				
/*
				
				$html .= "<button class='btn btn-default' type='submit' name='mass_{$value->name}_on' onclick=\"return confirm('{$value->label} "._('On')."?');\">{$value->label} "._('On')."</button>&nbsp;";
				$html .= "<button class='btn btn-default' type='submit' name='mass_{$value->name}_off' onclick=\"return confirm('{$value->label} "._('Off')."?');\">{$value->label} "._('Off')."</button>&nbsp;";
*/
			}
		}
		if($html!='') $html .= '&nbsp;&nbsp;&nbsp;';
		return $html;
	}
/* =============== */
/* Возвращает кнопки для формы редактирования	*/
/* =============== */	
	function formButtons() {
		return '<button type="submit" class="btn btn-primary" id="editForm_save" name="editForm_save">'._('Save').'</button> '.
		       '<button type="submit" class="btn btn-default" id="editForm_save_stay" name="editForm_save_stay">'._('Save and stay').'</button>';
	}
	
	function bottomButtons() {
		return self::topButtons();
	}

/* =============== */
/* Возвращает заголовок над списком	*/
/* =============== */	
	function listHeader() {
		return '';
	}

/* =============== */
/* Возвращает заголовок над формой	*/
/* =============== */	
	function formHeader() {
		return '';
	}



/* =============== */
/* Доп валидация формы	*/
/* =============== */
	function validate() {
		return true;
	}


/* =============== */
/* Экспорт данных	*/
/* =============== */
	function export($format, $encoding) {
		//$this->itemsCount
		
//		var_dump($_REQUEST);exit;
		$csv = array();

		// заголовоки столбцов
		$row = array();
		foreach($this->options['form'] as $name => $field) {
			if($field->type == 'group' || $field->type == 'label' || $field->type == 'none') continue;
			$row[] = $field->label;
		}
		$csv[] = $row;

		$per_page = 100;
		$limit = 0;
		do {
			if(isset($_GET['id'])) {
				$items = array();
				$ids =  array_slice(explode(',', $_GET['id']), $limit, $per_page);
				foreach($ids as $id) {
					$items[] = $this->getItem((int)$id);
				}
			}
			else {
				$items = $this->getItems($limit, $per_page); // все записи попадающие под фильтр
			}
			$limit += $per_page;	
			//  данные
			foreach($items as $item) {
				$row = array();
				foreach($this->options['form'] as $name => $field) {
					if($field->type == 'group' || $field->type == 'label' || $field->type == 'none') continue;
					$field->fromRow($item);
					$row[] = $field->toString();
				}
				$csv[] = $row;
			}
		} while(count($items)>0);
		
		if($format == 'csv') {
			ob_start();
			// вывод		
			$df = fopen("php://output", 'w');
			foreach ($csv as $row) {
				fputcsv($df, $row);
			}
			fclose($df);		
			$csv = ob_get_clean();
			header("Content-Disposition: attachment;filename={$this->options['table']}.csv"); 
			header("Content-Transfer-Encoding: binary");			
			if($encoding == 'windows1251') {
				header('Content-type: text/plain; charset=windows-1251');
				echo iconv('UTF-8', 'windows-1251', $csv);
			} else {
				header('Content-type: text/plain; charset=utf-8');
				echo $csv;
			}
		} elseif($format == 'xls') {
			header("Content-Type: application/force-download");
			header("Content-Type: application/octet-stream");
			header("Content-Type: application/download");
			header("Content-Disposition: attachment;filename={$this->options['table']}.xls"); 
			header("Content-Transfer-Encoding: binary");			
			SimpleXLS::begin();
			foreach($csv as $line => $row) {
				$col = 0;
				foreach($row as $column => $item) {
					if(is_numeric($item))
						SimpleXLS::number($line, $column, $item);
					else
						SimpleXLS::label($line, $column, iconv('UTF-8', 'windows-1251', mb_substr($item, 0, 255, 'UTF-8')));
				}
				$row++;
			}
			SimpleXLS::end();
		}
		
		
		exit;		
	}

/* =============== */
/* Массовые действия	*/
/* =============== */
	function massAction($ids, $field, $value) {
		
		if($ids == NULL) $this->returnUser();
		if(!is_array($ids)) $ids = array($ids);

		$sql = "UPDATE `".$this->options['table']."` SET `{$field}`='{$value}' WHERE id IN (".implode(',', $ids).")";
		$res = $this->db->query($sql);
		
		foreach($ids as $id) {
			$item = $this->getItem($id);
			$this->updateCache($id, $item);
		}
		$this->updateFullCache();
		$this->returnUser();
	}

/* =============== */
/* Импорт CSV	*/
/* =============== */
	function import() {
		if($_FILES['file']['tmp_name']) {
			$name = tempnam('/tmp', 'csv_import');
			if (move_uploaded_file($_FILES['file']['tmp_name'], $name)) {
				$_SESSION['import_filename'] = $name;
			} else {
				unlink($name);
				unset($_SESSION['import_filename']);
			}
		} 
		
		if(isset($_POST['import'])) {
			$this->form = new Form($this->options['form'], $this);
			
			$f = fopen($_SESSION['import_filename'], 'r');
			$inserted = 0;
			for($i=0;!feof($f);$i++) {
				$line = fgetcsv($f, 1000, (isset($_REQUEST['delimiter'])?$_REQUEST['delimiter']:';'));
				if($i<(int)$_REQUEST['skip']) {
//					echo "Skip<br>";
					continue; // пропуск
				}
				if(empty($line)) {
//					echo "Empty<br>";
					continue; // пропуск
				}
				if($_REQUEST['encoding'] == 'windows-1251')
					$line = @array_map( function ( $str ) { return iconv( "windows-1251", "UTF-8", $str ); }, $line );
				elseif($_REQUEST['encoding'] == 'macOS')
					$line = @array_map( function ( $str ) { return iconv( "MacUkraine", "UTF-8", $str ); }, $line );
				
				$data = array();
				foreach($line as $index => $row) {
					if(empty($_POST["col_{$index}"]) || $_POST["col_{$index}"] == '-') continue;
					$data[$_POST["col_{$index}"]] = $row;
				}
				$this->form->load($data, 'form');
				if(!$this->form->validate($data)) {
//					echo "Validate<br>";
					continue; // пропуск
				} // если данные не проходят валидацию - не вставляем
//				$id = $this->insertItem($data);
				$sql = "INSERT IGNORE ".$this->options['table']." SET ".$this->form->save($data);
				$id = $this->db->query($sql); // return insert id
				if($id>0) $inserted++;

			}
			$stats = array('total' => $i, 'inserted' => $inserted);
			unlink($_SESSION['import_filename']);
			unset($_SESSION['import_filename']);
		} elseif(isset($_POST['cancel'])) {
			unlink($_SESSION['import_filename']);
			unset($_SESSION['import_filename']);
			$this->returnUser();
		} elseif(isset($_SESSION['import_filename'])) {
			$csv = array();
			
			$f = fopen($_SESSION['import_filename'], 'r');
			for($i=0;$i<12;$i++) {
				$line = fgetcsv($f, 1000, (isset($_REQUEST['delimiter'])?$_REQUEST['delimiter']:';'));
				if($_REQUEST['encoding'] == 'windows-1251')
					$line = @array_map( function ( $str ) { return iconv( "windows-1251", "UTF-8", $str ); }, $line );
				elseif($_REQUEST['encoding'] == 'macOS')
					$line = @array_map( function ( $str ) { return iconv( "MacUkraine", "UTF-8", $str ); }, $line );
				$csv[] = $line;
			}
		}
		
		include "views/import_template.php";
	}	

/* =============== */
/* Выдача данных в формате JSON для dataTables	*/
/* =============== */
	function dataSource() {
		$this->sortFields();
		$headers = array();		
		foreach($this->options['form'] as $key=>$value) {
			if(!empty($value->header)) {
				$headers[] = $key;
			}
		}
		
		if(count($_GET['order'])>0) {
			$this->options['sort'] = '';
			foreach($_GET['order'] as $order) {
				if($this->options['sort'] != '') $this->options['sort'] .= ', ';
				$this->options['sort'] .= $headers[$order['column']-1].' '.$order['dir'];
			}
		}
		
		$items = $this->getItems($_GET['start'], $_GET['length']);

		$data = array();


		foreach($items as $item) {
			$row = array('checkbox-cell' => '<input type="checkbox" class="row_checkbox" name="" value="'.$item['id'].'" autocomplete="off">');
			foreach($this->options['form'] as $key=>$value) {
				if(!empty($value->header)) {
					
					$value->fromRow($item);
										
					$row[$value->cell_class] = $value->toListItem();
				}
			}
			$row['actions-cell'] = $this->actions($item);
			$row['DT_RowClass'] = $this->getListClass($item);
			$data[] = $row;
		}
		


		$result = array(
			'draw' => (int)$_GET['draw'],
			'recordsTotal' => $this->itemsCount,
			'recordsFiltered' => $this->itemsCount,
			'data' => $data,
		);

		return $result;
	}
	

/* =============== */
/* Обработка действий по умолчанию	*/
/* =============== */
	function processCommands() {
		$this->gettextDomain = 'AdminModule';
		bindtextdomain($this->gettextDomain, dirname(__FILE__)."/locale"); 
		$oldDomain = textdomain($this->gettextDomain);	
		

		if(isset($_REQUEST['data-source']) ) { // выдача данных для dataTables
		
			echo json_encode($this->dataSource(), JSON_UNESCAPED_UNICODE);
			exit;
			
		} elseif(isset($_REQUEST['ajaxField']) && isset($_REQUEST['ajaxMethod'])) { // обработка AJAX
		
			call_user_func( array( $this->options['form'][$_REQUEST['ajaxField']], $_REQUEST['ajaxMethod'] ) );
			exit;
			
		} else { // обработка обычных запросов

			if($this->options['title']!='')
				$this->navigation->add($this->options['title'], $this->baseUrl);
	
			foreach($this->options['form'] as $key=>$value) {
				if($value->massAction && $value->type == 'checkbox') {
					if(isset($_REQUEST["mass_{$value->name}_on"])) {
						$this->massAction($_REQUEST['item'], $value->name, 1);
					} elseif(isset($_REQUEST["mass_{$value->name}_off"])) {
						$this->massAction($_REQUEST['item'], $value->name, 0);
					}
				}
			}
	
			if(isset($_REQUEST['export'])) {
				$this->export($_REQUEST['format'], $_REQUEST['encoding']);
			} elseif(isset($_REQUEST['print'])) {
				ob_clean();
				ob_start();
				$this->printItems();
				ob_end_flush();
				exit;
			} elseif(isset($_REQUEST['import'])) {
				$this->navigation->add(_("Import"),"addarticle.php");
				echo $this->navigation->get();				
				$this->import();
			} elseif(isset($_REQUEST['delete'])) {
				$this->deleteItem($_REQUEST['item']);
			} elseif(isset($_GET['edit'])) {
				if($_GET['edit'] == 0)
					$this->navigation->add(_("Add record"), "addarticle.php");
				else
					$this->navigation->add(_("Edit record"),"addarticle.php");
				echo $this->navigation->get();
//				pageStart('foobar',false);
//! BANKER
				$this->editItem($_GET['edit'], isset($_GET['clone']));
			} else {
				echo $this->navigation->get();
//				pageStart('foobar',false);
//! BANKER
				$this->listItems();
			}
		}
		
		textdomain($oldDomain);
		
		return true;
	}
	
	function move($url = NULL) {
		header("Location: ".($url?$url:$this->baseUrl));	
		exit;
	}


}


function stable_uasort(&$array, $cmp_function) {
    if(count($array) < 2) {
        return;
    }
    $halfway = count($array) / 2;
    $array1 = array_slice($array, 0, $halfway, TRUE);
    $array2 = array_slice($array, $halfway, NULL, TRUE);

    stable_uasort($array1, $cmp_function);
    stable_uasort($array2, $cmp_function);
    if(call_user_func($cmp_function, end($array1), reset($array2)) < 1) {
        $array = $array1 + $array2;
        return;
    }
    $array = array();
    reset($array1);
    reset($array2);
    while(current($array1) && current($array2)) {
        if(call_user_func($cmp_function, current($array1), current($array2)) < 1) {
            $array[key($array1)] = current($array1);
            next($array1);
        } else {
            $array[key($array2)] = current($array2);
            next($array2);
        }
    }
    while(current($array1)) {
        $array[key($array1)] = current($array1);
        next($array1);
    }
    while(current($array2)) {
        $array[key($array2)] = current($array2);
        next($array2);
    }
    return;
}
