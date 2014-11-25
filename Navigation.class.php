<?php


class Navigation {
	var $breadcrumbs;
	
	
	function add($title, $url) {
		$this->breadcrumbs[$url] = $title;
	}
	
	function get() {
		if(count($this->breadcrumbs) == 0) return false;
		$html = "<div class='breadcrumbs'>";
		$count = 0;
		foreach($this->breadcrumbs as $url => $title) {
			$count++;
			if($count == count($this->breadcrumbs))
				$html .= htmlspecialchars($title);
			else
				$html .= "<a href='{$url}'>".htmlspecialchars($title)."</a> &gt; ";
		}
		$html .= "</div>";
		return $html;
		
	}
}