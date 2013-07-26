<?php //#########################################################
// The Oz Framework
// :: Contains common, helpful functions for use in your site
// :: This is a singleton (yea yea), so to use a method simply do oz::get() (for example)
//#########################################################
class oz{
	//===============================================
	// Register a new post type with i18n
	// $cpt 	(STR)	The Post Type
	//===============================================
	function cpt($cpt, $args = array()){
		//===============================================
		// Setup Defaults
		//===============================================
		$label = ucwords(str_replace('-', ' ', $cpt));
		$domain = oz::def($args['domain'], 'custom-post-type');
		oz::def($args['labels'], array());
		//- - - - - - - - - - - - - - - - - - - - - - - -
		// Name and Plural Name
		//- - - - - - - - - - - - - - - - - - - - - - - -
		oz::def($args['isPlural'], true);	//Determines if the string is already plural
		if($args['isPlural']){
			$name = oz::def($args['label'], substr($label, 0, -1));
			$names = oz::def($args['labels']['name'], $name . 's');
		} else {
			$name = oz::def($args['label'], $label . 's');
			$names = oz::def($args['labels']['name'], $label);
		}
		//- - - - - - - - - - - - - - - - - - - - - - - -
		// Alternate Name for items
		//- - - - - - - - - - - - - - - - - - - - - - - -
		$altName = __(oz::def($args['alt'], $name), $domain);
		$altNames = __($altName . 's', $domain);
		//- - - - - - - - - - - - - - - - - - - - - - - -
		// Labels
		//- - - - - - - - - - - - - - - - - - - - - - - -
		oz::def($args['labels']['singular_name'], __($altName, $domain));
		oz::def($args['labels']['add_new'], __('Add New', $domain));
		oz::def($args['labels']['add_new_item'], sprintf(__('Add New %1$s', $domain), $altName));
		oz::def($args['labels']['edit_item'], sprintf(__('Edit %1$s', $domain), $altName));
		oz::def($args['labels']['new_item'], sprintf(__('New %1$s', $domain), $altName));
		oz::def($args['labels']['all_items'], sprintf(__('All %1$s', $domain), $altNames));
		oz::def($args['labels']['view_item'], sprintf(__('View %1$s', $domain), $altName));
		oz::def($args['labels']['search_items'], sprintf(__('Search %1$s', $domain), $altNames));
		oz::def($args['labels']['not_found'], sprintf(__('No %1$s found', $domain), $altNames));
		oz::def($args['labels']['not_found_in_trash'], sprintf(__('No %1$s found in Trash', $domain), $altNames));
		oz::def($arts['labels']['parent_item_colon'], '');
		oz::def($args['labels']['menu_name'], __($names, $domain));
		//- - - - - - - - - - - - - - - - - - - - - - - -
		// Remove Extras
		//- - - - - - - - - - - - - - - - - - - - - - - -
		unset($args['isPlural']);
		unset($args['domain']);
		unset($args['alt']);

		//- - - - - - - - - - - - - - - - - - - - - - - -
		// Queue up the CPT to be registered
		//- - - - - - - - - - - - - - - - - - - - - - - -
		register_post_type($cpt, $args);		
	}

	//==========================================================
	// Get a value or default from $_GET, $_POST, $_REQUEST and $SESSION.
	//
	// :: $key 		(STR) key to check
	// :: $def 		(*) [false] Default value to use
	//==========================================================
	static function get($key, $def=false){
		return isset($_GET[$key]) ? $_GET[$key] : $def;
	}
	static function post($key, $def=false){
		return isset($_POST[$key]) ? $_POST[$key] : $def;
	}
	static function request($key, $def=false){
		return isset($_REQUEST[$key]) ? $_REQUEST[$key] : $def;
	}
	static function session($key, $def=false){
		return isset($SESSION[$key]) ? $SESSION[$key] : $def;
	}

	//=============================================================================
	// Set a default
	// ::$var 		[*]	The variable, by reference, to set a default value on
	// ::$default 	[*] The default value to use if the variable is not set
	//=============================================================================
	static function def(&$var, $def){
		if(!isset($var) && isset($def)) return $var = $def;
		if(!isset($var) && !isset($def)) return false;
		return $var;
	}		
}