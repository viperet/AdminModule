<?php

class Pagination extends Translate {

	private $num_pages = 1;
	private $start = 0;
	private $display;
	private $start_display;
	private $pageParam;
	function __construct($query, $display=10, $pageParam = 's') {
		$this->pageParam = $pageParam;
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
			if (isset($_GET[$pageParam]) && is_numeric($_GET[$pageParam]) && $_GET[$pageParam] > 0) $this->start = (int) $_GET[$pageParam];
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
			$html .= '<li class="first"><a title="'._('To the begining').'" href="' . $url . '">&laquo;</a></li>';
			$html .= '<li class="prev"><a title="'._('Previous page').'" href="' . $url.($this->start - $this->display>0?$delim.$this->pageParam.'='.($this->start - $this->display) : '') . '">'._('Previous').'</a></li>';
		} else {
			$html .= '<li class="disabled first"><span title="'._('To the begining').'">&laquo;</span></li>';
			$html .= '<li class="disabled prev"><span title="'._('Previous page').'">'._('Previous').'</span></li>';
		}
		for ($i=$begin; $i<=$end; $i++) {
			if ($i != $current_page) {
				$html .= '<li><a title="' . $i . '" href="' . $url .(($this->display * ($i - 1))>0?$delim.$this->pageParam.'='.(($this->display * ($i - 1))):'') . '">' . $i . '</a></li>';
			} else {
				$html .= '<li class="active"><span>' . $i . '</span></li>';
			}
		}
		if ($current_page != $this->num_pages) {
			$html .= '<li class="next"><a title="'._('Next page').'" href="' . $url.$delim.$this->pageParam.'='.($this->start + $this->display) . '">'._('Next').'</a></li>';
			$last = ($this->num_pages * $this->display) - $this->display;
			$html .= '<li class="last"><a title="'._('To the end').'" href="' . $url.$delim.$this->pageParam.'='.($last) . '">&raquo;</a></li>';
		} else {
			$html .= '<li class="disabled next"><span title="'._('Next page').'">'._('Next').'</span></li>';
			$html .= '<li class="disabled last"><span title="'._('To the end').'">&raquo;</span></li>';
		}
		return '<nav class="page-navigation"><ul class="pagination">' . $html . '</ul></nav>';
	}


	public function limit() {
		return $this->start_display;
	}


}
