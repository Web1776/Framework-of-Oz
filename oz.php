<?php //#########################################################
// The Oz Framework
// :: Contains common, helpful functions for use in your site
// :: This is a singleton (yea yea), so to use a method simply do oz::get() (for example)
//#########################################################
class oz{
	//==========================================================
	// Get a value or default from $_GET, $_POST, $_REQUEST and $SESSION.
	//
	// :: $key 		(STR) key to check
	// :: $def 		(*) [false] Default value to use
	//==========================================================
	function get($key, $def=false){
		return isset($_GET[$key]) ? $_GET[$key] : $def;
	}
	function post($key, $def=false){
		return isset($_POST[$key]) ? $_POST[$key] : $def;
	}
	function request($key, $def=false){
		return isset($_REQUEST[$key]) ? $_REQUEST[$key] : $def;
	}
	function session($key, $def=false){
		return isset($SESSION[$key]) ? $SESSION[$key] : $def;
	}	
}