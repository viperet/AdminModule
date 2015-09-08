<?php


class Navigation {
	var $breadcrumbs;
	
	
	function add($title, $url) {
		$this->breadcrumbs[$url] = $title;
	}
	
	function get() {
		if(count($this->breadcrumbs) == 0) return false;
		$html = '<ol class="breadcrumb">';
		$count = 0;
		foreach($this->breadcrumbs as $url => $title) {
			$count++;
			if($count == count($this->breadcrumbs))
				$html .= '<li class="active">'.htmlspecialchars($title).'</li>';
			else
				$html .= "<li><a href='{$url}'>".htmlspecialchars($title)."</a></li>";
		}
		$html .= "</ol>";
		return $html;
		
	}
	
	function title() {
		if(count($this->breadcrumbs) == 0) return false;
		return implode(' - ', array_reverse($this->breadcrumbs));
		
	}	
}
