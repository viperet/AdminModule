<?php

class Pagination {

	private $num_pages = 1;
	private $start = 0;
	private $display;
	private $start_display;

	function __construct($query, $display=10) {
		if (!empty($query)) {
			$this->display = $display;
			if (isset($_GET['display']) && is_numeric($_GET['display'])) $this->display = (int) $_GET['display'];
			if (isset($_GET['np']) && is_numeric($_GET['np']) && $_GET['np'] > 0) {
				$this->num_pages = (int) $_GET['np'];
			} else {
				if (is_numeric($query)) {
					$num_records = $query;
				} else {
					$result = db_query ($query);
					if ($result->num_rows > 1 || strstr($query, 'COUNT') === false) {
						$num_records = $result->num_rows;
					} else {
						$row = $result->fetch_row();
						$num_records = $row[0];
					}
				}
				if ($num_records > $this->display) $this->num_pages = ceil($num_records/$this->display);
			}
			if (isset($_GET['s']) && is_numeric($_GET['s']) && $_GET['s'] > 0) $this->start = (int) $_GET['s'];
			$this->start_display = " LIMIT {$this->start}, {$this->display}";
		}
	}


	public function display($url, $split=5) {
		//    global $page;
		$html = '';
		if ($this->num_pages <= 1) return $html;

		if(strpos($url, '?') === false)
			$delim = '?';
		else
			$delim = '&';
		$current_page = ($this->start/$this->display) + 1;
		$begin = $current_page - $split;
		$end = $current_page + $split;
		if ($begin < 1) {
			$begin = 1;
			$end = $split * 2;
		}
		if ($end > $this->num_pages) {
			$end = $this->num_pages;
			$begin = $end - ($split * 2);
			$begin++; // add one so that we get double the split at the end
			if ($begin < 1) $begin = 1;
		}
		if ($current_page != 1) {
			$html .= '<li class="first"><a title="В начало" href="' . $url.$delim.'s=0">&laquo;</a></li>';
			$html .= '<li class="prev"><a title="Предидущая страница" href="' . $url.$delim.'s='.($this->start - $this->display) . '">Предидущая</a></li>';
		} else {
			$html .= '<li class="disabled first"><span title="В начало">&laquo;</span></li>';
			$html .= '<li class="disabled prev"><span title="Предидущая страница">Предидущая</span></li>';
		}
		for ($i=$begin; $i<=$end; $i++) {
			if ($i != $current_page) {
				$html .= '<li><a title="' . $i . '" href="' . $url.$delim.'s='.(($this->display * ($i - 1))) . '">' . $i . '</a></li>';
			} else {
				$html .= '<li class="active"><span>' . $i . '</span></li>';
			}
		}
		if ($current_page != $this->num_pages) {
			$html .= '<li class="next"><a title="Следующая страница" href="' . $url.$delim.'s='.($this->start + $this->display) . '">Следующая</a></li>';
			$last = ($this->num_pages * $this->display) - $this->display;
			$html .= '<li class="last"><a title="В конец" href="' . $url.$delim.'s='.($last) . '">&raquo;</a></li>';
		} else {
			$html .= '<li class="disabled next"><span title="Следующая страница">Следующая</span></li>';
			$html .= '<li class="disabled last"><span title="В конец">&raquo;</span></li>';
		}
		return '<nav class="page-navigation"><ul class="pagination">' . $html . '</ul></nav>';
	}


	public function limit() {
		return $this->start_display;
	}


}
