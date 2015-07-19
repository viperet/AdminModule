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
	
	
	
	class CoreLogger {
		protected $db, 
		          $admin;
		
		
		function __construct($db, $admin) {
			$this->db = $db;
			$this->admin = $admin;
		}
		
		
		
		function beforeAction($id, $operation, $item = null) {
		}
		
		function afterAction($id, $operation, $item = null) {
		}
	}