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
		);
	public $db, $navigation;
	public $form;
	public $baseUrl;
	public $baseUrlNoPaging;
	public $baseUrlNoFilter;
	public $helpersUrl;
	public $itemsCount;
	public $filter = "";
	
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

		
		if(isset($_GET['block']))
			$this->baseUrl .= "&block=".$_GET['block'];
		if(isset($_GET['filter'])) { 
			$this->filter = $_GET['filter'];
			$this->baseUrl .= "&filter=".urlencode($_GET['filter']);
		}
		
		
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

/* ================================= */
/* Просмотр списка записей в таблице */
/* ================================= */
	
	function listItems() {
		

		$per_page  = $this->options['perpage'];
		$limit = (empty($_GET['s'])?0:(int)$_GET['s']);
		
		$items = $this->getItems($limit, $per_page);
//		echo "<hr>{$this->itemsCount}<hr>";
//		$pager = new pageSplit($page, $this->itemsCount, '', $per_page);		
//		$htmlPager = $pager->showNav();
		
		$pager = new Pagination($this->itemsCount, $per_page);
		$htmlPager  = $pager->display($this->baseUrl);		
		
//		$headers = array('id'); // hide ID column for now
		$headers = array();
		$this->options['form'] = array_merge(array('id' => new textType($this->db, 'id', array('type'=>'text', 'label'=>'ID'))), $this->options['form']);

		foreach($this->options['form'] as $key=>$value) {
			if(!empty($value->header)) {
				$headers[] = $key;
			}
		}
		
		include('list_template.php');

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
			$merged_object = array_merge($object, $_POST);
			$this->form->load($merged_object, 'form');
			$error = true;
			if(!$this->form->validate($_POST) ||
			  ($error = $this->validate($_POST))!== true
			) {	
				if($error !== true) {
					array_unshift($this->form->errorMessage, $error);
				}
			} else {// все ок
				if($id>0 && !$clone) { // сохранение
					$res = $this->updateItem($id, $_POST);
				} else { // создание
					$id = $this->insertItem($_POST);
				}

				$item = $this->getItem($id);
				$this->updateCache($id, $item);
				$this->updateFullCache();
				
				$postSql = $this->form->postSave($id, $_POST, $item);
				if($postSql) {
					$res = $this->db->query("UPDATE ".$this->options['table']." SET ".$postSql);
				}
				header("Location: ".$this->baseUrl);
				exit;
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
		
		$sql = "UPDATE ".$this->options['table']." SET ".$this->form->save($data)." WHERE id = ". (int)$id;
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
		$res = $this->db->query($sql);
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
		if(trim($this->filter) == '') return "1";

		// проверяем фильтрацию по списку полей "поле:значение"
		if(strpos($this->filter, ':')) {
			list($field, $filter) = explode(':', $this->filter);
			if(isset($this->options['form'][$field]))
				return " `{$this->options['table']}`.`{$field}` = '".mysql_real_escape_string($filter)."'";
		}

		$filter = $this->filter;
		
		
		$filter = '%'.mysql_real_escape_string($filter).'%';
		$sql = array();
		if(is_array($additionalFields)) {
			foreach($additionalFields as $key)
				$sql[] = "{$key} LIKE '{$filter}'";
		}
		foreach($this->options['form'] as $key=>$value)
			if($value->filter)
				$sql[] = "`{$this->options['table']}`.`{$key}` LIKE '{$filter}'";
				
		if(preg_match('/^%(\d+)%$/', $filter, $m)) {
			$sql[] = "`{$this->options['table']}`.`id` = '{$m[1]}'";
		}
		
		if(count($sql) == 0) return '0';
		return ' ('.implode(' OR ', $sql).') ';
	}

/* ====================== */
/* Получение значений поля для фильтрации */
/* ====================== */
	function getFieldValues($field) {
		
		$sql = "SELECT `{$field->name}` value FROM `{$this->options['table']}` GROUP By `{$field->name}`";
		$res = $this->db->getAll($sql);
		$items = array();
		foreach($res as $row) {
			$field->value = $row['value'];
			$items[$row['value']] = $field->toString();
		}
		asort($items);
		return $items;		
	}

/* ====================== */
/* Получение всех записей */
/* ====================== */
	function getItems($from=0, $count=100) {
		
		$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM {$this->options['table']} WHERE ".$this->getFilterSQL().
			($this->options['sort']?" ORDER By {$this->options['sort']}":"").
			" LIMIT $from,$count";
		$items = $this->db->getAll($sql);
		$this->itemsCount = $this->db->foundRows;
		return $items;	
	}

/* ====================== */
/* Получение записи по ID */
/* ====================== */
	function getItem($id) {
		
		$row = $this->db->getRow("SELECT * FROM `".$this->options['table']."` WHERE id=?", array($id));
		return $row;
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
			
			$sql = "DELETE FROM `".$this->options['table']."` WHERE id = ".intval($id);
			$res = $this->db->query($sql);
/*
			if($res !== 1) {
				echo "DELETE error<br>";
				echo nl2br($res->result->userinfo);
				exit;
			}			
*/
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
		return "";
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
				$html .= "<button class='btn btn-default' type='button' name='mass_{$value->name}_on' onclick=\"return confirm('{$value->label} "._('On')."?');\">{$value->label} "._('On')."</button>&nbsp;";
				$html .= "<button class='btn btn-default' type='button' name='mass_{$value->name}_off' onclick=\"return confirm('{$value->label} "._('Off')."?');\">{$value->label} "._('Off')."</button>&nbsp;";
			}
		}
		if($html!='') $html .= '&nbsp;&nbsp;&nbsp;';
		return $html;
	}
/* =============== */
/* Возвращает кнопки для формы редактирования	*/
/* =============== */	
	function formButtons() {
		return '<button type="submit" class="btn btn-primary" id="editForm_save" name="editForm_save">'._('Save').'</button>';
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
		
//		var_dump($items);exit;
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
			$items = $this->getItems($limit, $per_page);
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
		if($res !== 1) {
			echo "massAction error<br>";
			echo nl2br($res->result->userinfo);
			exit;
		}			
		
		foreach($ids as $id) {
			$item = $this->getItem($id);
			$this->updateCache($id, $item);
		}
		$this->updateFullCache();
		$this->returnUser();
	}

/* =============== */
/* Обработка действий по умолчанию	*/
/* =============== */
	function processCommands() {
		$this->gettextDomain = 'AdminModule';
		bindtextdomain($this->gettextDomain, dirname(__FILE__)."/locale"); 
		$oldDomain = textdomain($this->gettextDomain);	
		

		if(isset($_REQUEST['ajaxField']) && isset($_REQUEST['ajaxMethod'])) { // обработка AJAX
		
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