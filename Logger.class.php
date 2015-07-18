<?php
	
	
	class Logger {
		private $db, 
		        $admin,
		        $data;
		
		
		function __construct($db, $admin) {
			$this->db = $db;
		}
		
		function beforeAction() {
			if(isset($_SERVER['HTTP_X_REAL_IP']))
				$id = $_SERVER['HTTP_X_REAL_IP'];
			elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
				$id = $_SERVER['HTTP_X_FORWARDED_FOR'];
			elseif($_SERVER['REMOTE_ADDR'] != '127.0.0.1') 
				$ip = $_SERVER['REMOTE_ADDR'];
			else
				$ip = 'unknown';
			
			
			
			$this->data = array(
					'user' => $admin->options['user'],
					'date' => date('Y-m-d H:M'),
					'ip' => $ip,
				);
		}
		
		function afterAction() {
		}
	}