<?php
$objectForm = array(
	'date' => array(
			'label' => 'Дата',
			'type' => 'datetime',
			'header' => true,
			'readonly' => true,
		),
	'user' => array(
			'label' => 'Пользователь',
			'type' => 'text',
			'readonly' => true,
			'header' => true,
			'filter' => true,
			'filterByClick' => true,
		),
	'ip' => array(
			'label' => 'IP адрес',
			'type' => 'text',
			'readonly' => true,
			'header' => true,
			'filter' => true,
		),
	'operation' => array(
			'label' => 'Действие',
			'type' => 'select',
			'readonly' => true,
			'header' => true,
			'filter' => true,
			'filterByClick' => true,
			'values' => array(
					'read' => 'чтение',
					'update' => 'изменение',
					'insert' => 'вставка',
					'delete' => 'удаление',
				),
		),
	'table' => array(
			'label' => 'Таблица',
			'type' => 'text',
			'readonly' => true,
			'header' => true,
			'filter' => true,
			'filterByClick' => true,
		),
	'row_id' => array(
			'label' => 'ID записи',
			'type' => 'text',
			'readonly' => true,
			'header' => true,
			'filter' => true,
		),

	'comment' => array(
			'label' => 'Комментарий',
			'type' => 'text',
			'readonly' => true,
			'header' => true,
		),
	'row_title' => array(
			'label' => 'Запись',
			'type' => 'text',
			'readonly' => true,
			'header' => true,
		),
	'details' => array(
			'label' => 'Подобности',
			'type' => 'logDetails',
			'readonly' => true,
		),
	); 
 	