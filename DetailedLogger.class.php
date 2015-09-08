<?php

/*
CREATE TABLE IF NOT EXISTS `logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user` varchar(50) NOT NULL,
  `ip` varchar(40) NOT NULL,
  `operation` enum('read','update','insert','delete') NOT NULL,
  `table` varchar(100) NOT NULL,
  `row_id` int(10) unsigned NOT NULL,
  `row_title` varchar(200) DEFAULT NULL,
  `comment` varchar(100) NOT NULL,
  `details` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=548 ;
*/
	
	
	class DetailedLogger extends Logger {
		protected $itemBefore,
		          $itemAfter;		
		          
		protected function getChanges() {
			$data = array();
			foreach($this->itemAfter as $key => $item) {
				if(isset($this->itemBefore[$key]) && $this->itemBefore[$key] != $this->itemAfter[$key]) {
					$field = $this->admin->options['form'][$key];
					if(is_object($field)) {
						$field->fromRow($this->itemBefore);
						$old = $field->toString();
						$field->fromRow($this->itemAfter);
						$new = $field->toString();
						$label = $field->label;
					} else {
						$old = $this->itemBefore[$key];
						$new = $this->itemAfter[$key];
						$label = $key;
					}
					$data[] = array(
							'label' => $label,
							'field' => $key,
							'old_raw' => $this->itemBefore[$key],
							'new_raw' => $this->itemAfter[$key],
							'old' => $old,
							'new' => $new,
						);
				}
			}
			return json_encode($data, JSON_UNESCAPED_UNICODE);
		}	          
		          
		function beforeAction($id, $operation, $item = null) {
			if(empty($item)) {
				if(!empty($id))
					$this->itemBefore = $this->admin->getItem($id);
				else 
					$this->itemBefore = array();
			} else {
				$this->itemBefore = $item;
			}

			return parent::beforeAction($id, $operation, $item);			
		}
		
		function afterAction($id, $operation, $item = null) {
			if($operation == 'read') return true; // чтение не логируем

			if(empty($item)) {
				if(!empty($id))
					$this->itemAfter = $this->admin->getItem($id);
				else 
					$this->itemAfter = array();
			} else {
				$this->itemAfter = $item;
			}

			$title = array();
			$fields = $this->admin->options['form'];
			foreach($fields as $field) {
				if($field->primary) {
					if($operation == 'delete')
						$field->fromRow($this->itemBefore);
					else
						$field->fromRow($this->itemAfter);
					$str = $field->toString();
					if(trim($str)!='')
						$title[] = $str;
				}
			}
			$this->data['row_title'] = implode('; ', $title);
			
			$this->data['details'] = $this->getChanges();
			
			return parent::afterAction($id, $operation, $item);
		}
	}