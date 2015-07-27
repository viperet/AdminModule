<?php
	
	
/*
CREATE TABLE IF NOT EXISTS `logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user` varchar(50) NOT NULL,
  `ip` varchar(40) NOT NULL,
  `operation` enum('read','update','insert') NOT NULL,
  `table` varchar(100) NOT NULL,
  `row_id` int(10) unsigned NOT NULL,
  `comment` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
*/
	
	
	
	class Logger extends CoreLogger {
		protected $data,
		          $id,
		          $operation;
		function beforeAction($id, $operation, $item = null) {
			if(isset($_SERVER['HTTP_X_REAL_IP']))
				$ip = $_SERVER['HTTP_X_REAL_IP'];
			elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
				$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
			elseif($_SERVER['REMOTE_ADDR'] != '127.0.0.1') 
				$ip = $_SERVER['REMOTE_ADDR'];
			else
				$ip = 'unknown';
				
			switch($operation) {
				case 'read':  // чтение не логируем
					return true;
				case 'update':
					$comment = sprintf(_("Data modification in '%s'"), $this->admin->options['title']);
					break;
				case 'insert':
					$comment = sprintf(_("Data insertion in '%s'"), $this->admin->options['title']);
					break;
				case 'delete':
					$comment = sprintf(_("Data deletion in '%s'"), $this->admin->options['title']);
					break;
				default: 
					$comment = sprintf(_("Unknown operation in '%s'"), $this->admin->options['title']);
			}
			
			$this->data = array(
					'user' => $this->admin->options['user'],
					'ip' => $ip,
					'table' => $this->admin->options['table'],
					'operation' => $operation,
					'comment' => $comment,
				);
		}
		
		function afterAction($id, $operation, $item = null) {
			if($operation == 'read') return true; // чтение не логируем
			
			$this->data['row_id'] = $id;
			$this->db->insert('logs', $this->data);
		}
	}