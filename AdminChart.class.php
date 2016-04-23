<?php

class AdminChart extends AdminModule {
	public $series = [];
	public $xAxis = [];
	public $filter_fields = [];


	function __construct($options) {
		if(empty($options['type'])) $options['type'] = 'line';
		if(empty($options['date_default'])) $options['date_default'] = 7;
		if(empty($options['height'])) $options['height'] = 400;
		parent::__construct($options);
		if(empty($_GET['df']) && empty($_GET['dt'])) {
			$this->dateFrom = date('Y-m-d', time()-($options['date_default'] * 24 * 60 * 60));
			$this->dateTo = date('Y-m-d');
			$this->baseUrl .= "&df=".urlencode($_GET['df'])."&dt=".urlencode($_GET['dt']);
			$this->baseUrlNoPaging .= "&df=".urlencode($_GET['df'])."&dt=".urlencode($_GET['dt']);
		}
	}

	function listItems() {
		$this->sortFields();

		$per_page  = $this->options['perpage'];
		$limit = (empty($_GET['s'])?0:(int)$_GET['s']);

		$items = $this->getItems($limit, $per_page);

		foreach($this->options['form'] as $key=>$value) {
			if(!empty($value->series)) {
				$series = ['name' => $value->label, 'data' => []];
				foreach($items as $item) {
					$series['data'][] = $item[$value->name];
				}

				$this->series[] = $series;
			}
			if(!empty($value->filterByClick)) {
				$this->filter_fields[] = $value;
			}
		}

		$xaxis_value = $this->options['form'][$this->options['xaxis']];
		foreach($items as $item) {
			$xaxis_value->fromRow($item);
			$this->xAxis[] = $xaxis_value->toString();
		}

		include('views/chart_template.php');

	}

	function renderFilters() {
    	return "";
    }

	function draw() {
		if($this->options['title']!='')
			$this->navigation->add($this->options['title'], $this->baseUrl);
		echo $this->navigation->get();
		$this->listItems();

	}

	function json($data) {
		return json_encode($data, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
	}

	function updateItem($id, $data) {
		return false;
	}
	function insertItem($data) {
		return false;
	}
	function processCommands() {
		return false;
	}

}