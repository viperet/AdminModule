<?php

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

Translate::setTranslationDomain();