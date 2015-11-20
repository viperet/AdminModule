<?php

function strarg($str/*, $varargs... */) {
	$tr = array();
	$p = 0;
	$nargs = func_num_args();
	for ($i = 1; $i < $nargs; $i++) {
		$arg = func_get_arg($i);
		if (is_array($arg)) {
			foreach ($arg as $aarg) {
				$tr['%' . ++$p] = $aarg;
			}
		} else {
			$tr['%' . ++$p] = $arg;
		}
	}
	return strtr($str, $tr);
}

class Translate {
	static function setTranslationDomain(){
	
/*
		putenv("LANG=$language"); 
		setlocale(LC_ALL, $language);
*/
		
		// Set the text domain as 'messages'
		$domain = 'messages';
		bindtextdomain($domain, dirname(__FILE__)."/locale"); 
		textdomain($domain);	
	}
}

