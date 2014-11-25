<?php

class Form {
	public $form;
	public $formId = "editForm";
	public $errorMessage;	
	public $id;
	public $options;
	
	function __construct($form, $options) {
		global $db;
		$this->form = $form;
		$this->options = $options;
	}

	function validate($params) {
		$valid = true;
		$this->errorMessage = array();
		foreach($this->form as $id => &$item) {
			if(!$item->validate($this->errorMessage))
				$valid = false;
		}
		unset($item);
		if(!$valid) {
			array_unshift($this->errorMessage, "Ошибки в заполнении формы");
		}
		return $valid;
	}
	
	function load($values, $source) {
		$this->id = $values['id'];
		foreach($this->form as $id => &$item) {
			if($source == 'db')
				$item->fromRow($values);
			else
				$item->fromForm($values);
		}
	}
	
	function build() {
		global $db;
		$types = array();
		$data = "";
		foreach($this->form as $id => &$item) {
			if($item->type == 'details') {
				foreach($item->form as $id => &$details_item) {
					if(!in_array($details_item->type, $types)) {
						$data .= $details_item->pageHeader();
						$types[] = $details_item->type;
					}
				}
			}
			if(!in_array($item->type, $types)) {
				$data .= $item->pageHeader();
				$types[] = $item->type;
			}
		}
		unset($item);
		ob_start();
		include('form_template.php');
		$data .= ob_get_clean();
		return $data;
	}
	
	function filled($params) {
		return isset($params[$this->formId.'_save']);
/*
		$filled = true;
		foreach($this->form as $id => &$item) {
			if($id == '-' || $item['type'] == 'label' 
					|| $item['type'] == 'checkbox' 
					|| $item['type'] == 'image' 
					|| !empty($item['readonly'])
				) continue;
			if(!isset($params[$id])) {
				echo $id;
				$filled = false;
				break;
			}
		}
		return $filled;
*/
	}

	function save($params) {
		global $db;
		foreach($this->form as $id => &$item) {
			$item->fromForm($params);
			$itemSql = $item->toSql();
			if(!empty($itemSql))
				$sql[] = $itemSql;
/*
			if($id == '-' || !empty($item['readonly']) || $item['type'] == 'label' || isset($item['dontsave'])) continue;
			if($item['type'] == 'text' && isset($item['lookup_table'])) {
				if(trim($params[$id]) == '') {
					$lookup_id = 0;
				} else {
					$lookup_id = $db->getOne("SELECT id FROM `{$item['lookup_table']}` WHERE `{$item['lookup_field']}` = '".addslashes($params[$id])."'");
					if(empty($lookup_id)) {
						$db->query("INSERT `{$item['lookup_table']}` SET `{$item['lookup_field']}` = '".addslashes($params[$id])."'");
						$lookup_id = mysql_insert_id();
					}
				}
				$sql[] = "`{$id}` = '{$lookup_id}'";
			}elseif($item['type'] == 'datetime') {
				$sql[] = "`{$id}` = '".strftime('%Y-%m-%d %H:%M', strtotime($params[$id]))."'";
			}elseif($item['type'] == 'date') {
				if(trim($params[$id])=='')
					$sql[] = "`{$id}` = NULL";
				else
					$sql[] = "`{$id}` = '".strftime('%Y-%m-%d', strtotime($params[$id]))."'";
			}elseif($item['type'] == 'checkbox') {
				$sql[] = "`{$id}` = '".($params[$id]=='on'?1:0)."'";
			}elseif($item['type'] == 'html') {
				$sql[] = "`{$id}` = '".addslashes($params[$id])."'";
			}elseif($item['type'] == 'image') {
				continue;			
			}elseif($item['type'] == 'set') {
				if($params[$id] != NULL)
					$sql[] = "`{$id}` = '".implode(',', array_keys($params[$id]))."'";
			} else {
				$sql[] = "`{$id}` = '".addslashes($params[$id])."'";
			}
*/
		}
		unset($item);
		return implode(', ', $sql);
	}

	function postSave($object_id, $params) {
		foreach($this->form as $id => &$item) {
			$postSaveSql = $item->postSave($object_id, $params);
			if($postSaveSql)
				$sql[] = $postSaveSql;

		}
		unset($item);
		if(count($sql)>0)
			return implode(', ', $sql)." WHERE id=".(int)$object_id;
		else
			return false;
	}
	
	
	
}