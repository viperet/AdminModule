<?php

class Form {
	public $form;
	public $formId = "editForm";
	public $errorMessage;	
	public $id;
	public $options;
	public $adminModule;
	
	private $session_id = "";

	function __construct($form, $adminModule) {
		$this->form = $form;
		$this->adminModule = $adminModule;
		$this->options = $adminModule->options;
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
			array_unshift($this->errorMessage, _("Form has errors"));
		}
		return $valid;
	}
	
	function load($values, $source) {
		$this->id = @$values['id'];
		if(isset($values['_session_id'])) $this->session_id = $values['_session_id'];
		
		foreach($this->form as $id => &$item) {
			if($source == 'db') {
				$item->fromRow($values);
			} else
				$item->fromForm($values);
		}
	}

	function delete() {
		foreach($this->form as $id => &$item) {
			$item->delete($this->id);
		}
	}
	
	function build() {
		if(empty($this->session_id)) $this->session_id = uniqid('', true);
		
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
		return isset($params[$this->formId.'_save']) || isset($params[$this->formId.'_save_stay']);
	}

	function save($params) {
		foreach($this->form as $id => &$item) {
//			$item->fromForm($params);
			if(is_callable($item->onSave)) 
				$item->value = call_user_func($item->onSave, $item->value);
			$itemSql = $item->toSql();
			if(!empty($itemSql))
				$sql[] = $itemSql;
		}
		unset($item);
		return implode(', ', $sql);
	}

	function postSave($object_id, $params, $item) {
		$sql = array();
		foreach($this->form as $id => &$value) {
			$postSaveSql = $value->postSave($object_id, $params, $item);
			if($postSaveSql)
				$sql[] = $postSaveSql;

		}
		unset($value);
		if(count($sql)>0)
			return implode(', ', $sql)." WHERE id=".(int)$object_id;
		else
			return false;
	}
	
	
	
}