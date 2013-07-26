<?php //#########################################################
// The metabox classs
// :: Used to create metaboxes
//#########################################################

//==========================================================
// Execute the 'metabox' filter
//==========================================================
add_action('init', 'Init_Metabox_of_Oz', 1000);
function Init_Metabox_of_Oz(){
	Metabox::load();
}

//###########################################################################
// Create the Metabox Class
//###########################################################################
class Metabox{
	//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// Properties
	//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	protected $mb;		//Contains the metabox object
	protected $fields; 	//Alias for $this->mb['fields'];
	protected $flat;	//Contains flattened version of $this->$fields
	protected $meta;	//Stores the get_post_meta when display fields
	protected $now; 	//Whether to display now or later
	static public $loaded = array();	//Contains a list of all loaded metaboxes
	static public $saved = false;	//Determines whether we saved or not

	//==========================================================
	// Create all metaboxes created through the 'metabox' filter
	//==========================================================
	public static function load(){
		$metaboxes = apply_filters('metabox', array());
		foreach($metaboxes as $mb)
			self::$loaded[] = new Metabox($mb);
	}

	//=============================================================================
	// Constructor
	// $mb 		(ARR) Metabox array
	// $now (BOOL) Whether to show the metabox now (for menu pages) or later (for post types)
	//=============================================================================
	function __construct($mb){
		if(!is_admin()) return;
		$this->mb = $mb;
		$this->now = isset($mb['page']);

		//=============================================================================
		// Enqueue Styles/Scripts
		//=============================================================================
		if(!defined('METABOX_OF_OZ')){
			if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			    define( 'METABOX_OF_OZ', trailingslashit( str_replace( DIRECTORY_SEPARATOR, '/', str_replace( str_replace( '/', DIRECTORY_SEPARATOR, WP_CONTENT_DIR ), WP_CONTENT_URL, dirname(__FILE__) ) ) ) );
			} else {
			    define( 'METABOX_OF_OZ', apply_filters( 'cmb_meta_box_url', trailingslashit( str_replace( WP_CONTENT_DIR, WP_CONTENT_URL, dirname( __FILE__ ) ) ) ) );
			}
			add_action('admin_enqueue_scripts', array(&$this, 'styles'));
		}

		add_action('admin_menu', array(&$this, 'prepare'));

		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Save
		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		if(!$this->now)
			add_action('save_post', array(&$this, 'save'));
		else
			add_action('load-'.$this->mb['page'], array(&$this, 'save_options'), 99999);
	}



	//=============================================================================
	// Prepares the metabox object 
	//=============================================================================
	function prepare(){

		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Get the post ID
		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		if(!$this->now){
			$postID = false;
			if(isset($_GET['post']))
				$postID = $_GET['post'];
			elseif(isset($_POST['post_ID']))
				$postID = $_POST['post_ID'];
			if(!$postID && !isset($_GET['post_type'])) return false;
			$template = get_post_meta($postID, '_wp_page_template', true);
		}

		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Error Messages
		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		if(!isset($this->mb['id'])) return trigger_error('"id" is required to create metabox');
		if(!isset($this->mb['fields'])) return trigger_error('"fields" is required to create metabox for ['.$this->mb['id'].']');
		if(!is_array($this->mb['fields'])) return trigger_error('"fields" must be an array to create metabox for ['.$this->mb['id'].']');

		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Convert 'post_types' to an array
		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		//Alias
		if(isset($this->mb['post_type']))
			$this->mb['post_types'] = $this->mb['post_type'];
		//String to Array
		if(isset($this->mb['post_types']) && is_string($this->mb['post_types']))
			$this->mb['post_types'] = explode(',', $this->mb['post_types']);

		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Add page as a post type
		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		if($this->now)
			$this->mb['post_types'][] = $this->mb['page'];


		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Convet 'templates' to an array
		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -		
		if(isset($this->mb['template']))
			$this->mb['templates'] = $this->mb['template'];
		//String to Array
		if(isset($this->mb['templates']) && is_string($this->mb['templates']))
			$this->mb['templates'] = explode(',', $this->mb['templates']);
		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Defaults
		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		$this->mb['id'] = sanitize_title($this->mb['id']);
		oz::def($this->mb['post_types'],	array('*'));
		oz::def($this->mb['context'], 	'normal');
		oz::def($this->mb['priority'], 	'high');
		oz::def($this->mb['label'], 		'Metabox');
		oz::def($this->mb['templates'],	array());
		oz::def($this->mb['callback'], 	false);

		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Apply to all post types if a wildcard is provided
		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		if($this->mb['post_types'][0] == '*'){
			$this->mb['post_types'] = array();
			foreach(get_post_types() as $cpt)
				$this->mb['post_types'][] = $cpt;
		}

		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Add metaboxes to the selected post types
		// :: only add if the post has the specified template
		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		$this->fields = $this->mb['fields'];
		foreach($this->mb['post_types'] as $postType){
			$skip = false;
			if(count($this->mb['templates'])){
				$skip = true;
				foreach($this->mb['templates'] as $temp){
					if($template == $temp){
						$skip = false;
						break;
					}
				}
			}
			if($skip) continue;
			add_meta_box($this->mb['id'], $this->mb['label'], array(&$this, 'show'), $postType, $this->mb['context'], $this->mb['priority']);
		}		
	}



	//=============================================================================
	// Show the metabox
	//=============================================================================
	function show($post){
		if(!$this->flat) $this->flatten();
		if(!$this->now)
			$this->meta = get_post_meta($post->ID, $this->mb['id'], true);
		else{
			$this->meta = get_option($this->mb['id']);
		}

		echo '<div class="metabox-of-oz">';

			//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// This array stores dynamic group information for looping purposes
			// Each index represents 1 group deep, so $group[2] would be:
			//  - group 		//$group[0]
			//		- group 	//$group[1]
			// 			- group //$group[2]
			//
			// Each index contains an associative array:
			// array(
			//		'id'		=> $field['id'],
			// 		'start'		=> $f,
			//		'repeat'	=> count($this->flat[$f+1]),
			//		'counter'	=> 0
			// )
			//
			// 'id' is 
			// 'start' is used to restart the loop at $f (to build another group)
			// 'repeat' is used to determine how many times to restart
			// 'counter' is used to set the actual dynamic groups meta index
			//
			// Because groups can be nested we always work in reverse order
			//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			$group = array();

			//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// Create the metabox
			//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			wp_nonce_field('save_metabox', 'metabox_of_oz');
			for($f = 0; $f < count($this->flat); $f++){
				$field = $this->flat[$f];
				//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
				// Create the attributes
				//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
				$att['id'] 		= $field['id'];
				$att['name']	= $field['_name'];
				$att['dynamic'] = $field['dynamic'] ? 'dynamic="' . $att['id'] . '"' : '';
				$att['value'] 	= oz::def($this->meta[$field['id']], $field['default']);
				$att['button'] 	= $field['button'];
				$att['settings']= $field['settings'];
				$nolabel 		= $field['nolabel'] ? 'nolabel' : '';
				$fullWidth 		= $field['full'] ? ' full-width' : '';

				//- - - - - - - - - - - - - - - - - - - - - - - -
				// The field's class
				//- - - - - - - - - - - - - - - - - - - - - - - -
				$att['class'] = '';
				if($field['_dynamicGroup']){
					$att['class'] = 'watch-index';
					if($field['dynamic'])
						$att['class'] .= ' dynamic';
				}
				$att['class'] .= ' ' . $field['class'];

				//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
				// Disabled fields
				//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
				if($field['disabled']) $att['name'] = '';
				if($field['value']) $att['value'] = $field['value'];
				$disabled = $field['disabled'] ? 'disabled="disabled"' : '';

				//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
				// Dynamic Groups
				//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
				if(count($group) && ($field['type'] != 'GROUP_OPEN' && $field['type'] != 'GROUP_CLOSE')){
					$g = count($group) - 1;
					$gCounter = $group[$g]['counter'];
					if(isset($att['value'][$group[$g]['counter']])) $att['value'] = $att['value'][$group[$g]['counter']];
					else $att['value'] = '';
				}
				if(!is_array($att['value'])) $att['value'] = array($att['value']);

				//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
				// Dynamic Fields
				//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
				$dynamic['fields'] = count($att['value']);


				//=============================================================================
				// Add field rows (label + input field)
				//=============================================================================
				if(($field['type'] != 'GROUP_OPEN' && $field['type'] != 'GROUP_CLOSE')){
					//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
					// Add the label
					//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
					echo '<div class="field-row ', $nolabel, $fullWidth, '">',
						'<div class="label-wrap">',
							'<label for="field-',$att['id'],'">', esc_html($field['label']), '</label>',
						'</div>';

						//=============================================================================
						// Build up the array name
						//=============================================================================
						if(!$field['_dynamicGroup'] && $att['dynamic'] && count($group == 0))
							$att['name'] .= '[]';
						elseif($group)
							$att['name'] .= '[]';

						//=============================================================================
						// Add the field
						// :: Recurse through and add each array
						//=============================================================================
						$preview = false;
						for($i = 0; $i < $dynamic['fields']; $i++){
							echo '<div class="field" ',$att['dynamic'], '>',
								'<div class="field-wrap">';

								//=============================================================================
								// Pick a field to add
								//=============================================================================
								switch($field['type']){
									//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
									// Text
									//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
									case 'text':
										echo '<input class="oz ', $att['class'],'" type="text" id="field-',$att['id'],'" name="',$att['name'] ,'" value="', esc_attr($att['value'][$i]),'" ', $disabled ,' />';
									break;
									//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
									// Textarea
									//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
									case 'textarea':
										echo '<textarea class="oz ', $att['class'],'" id="field-',$att['id'],'" name="',$att['name'] ,'" ', $disabled ,' >', esc_attr($att['value'][$i]), '</textarea>';
									break;
									//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
									// File Uploader
									//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
									case 'file':
										echo '<input class="oz paired left ', $att['class'],'" type="text" id="field-',$att['id'],'" name="',$att['name'] ,'" value="', esc_attr($att['value'][$i]),'" ', $disabled ,' />';
										echo '<input type="button" class="oz paired right button button-upload enabled" value="',$att['button'],'">';
										$preview = true;
									break;
									//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
									// WYSIWYG
									//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
									case 'wysiwyg': case 'editor':
										echo '<textarea class="oz redactor ', $att['class'],'" id="field-',$att['id'],'" name="',$att['name'] ,'" ', $disabled ,' >', esc_html($att['value'][$i]), '</textarea>';
									break;
									//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
									// Select
									//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
									case 'select':
										echo '<select class="oz ', $att['class'],'" id="field-',$att['id'],'" name="',$att['name'] ,'" ', $disabled ,'>';
											foreach($field['options'] as $val=>$option){
												$selected = '';
												if($att['value'][$i] == $val)
													$selected = 'selected="selected"';
												echo '<option value="',$val,'" ',$selected,'>',$option,'</option>';
											}
										echo '</select>';
									break;
									//- - - - - - - - - - - - - - - - - - - - - - - - - - - - -
									// Submit Button
									//- - - - - - - - - - - - - - - - - - - - - - - - - - - - -
									case 'submit':
										echo '<input type="submit" class="oz paired right button button-primary enabled" value="',$att['button'],'">';
									break;


									//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
									// Catch typos etc (true, built in default is "text")
									//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
									default:
										echo '<b>', __('Unknown input field: </b><code>' . $field['type'] . '</code>');
									break;
								}

							echo '<p>', $field['desc'], '</p>',
								($preview) ? '<div class="preview"><img src="'. $att["value"][$i] . '"></div>' : '',
								'</div>',
							'</div>';

							//- - - - - - - - - - - - - - - - - - - - - - - -
							// Execute callback
							//- - - - - - - - - - - - - - - - - - - - - - - -
							if($field['callback']) call_user_func($field['callback'], $this, $field);
						}

					echo '</div>',
					'<div style="clear:both;"></div>';

				//=============================================================================
				// Start/Close groups
				//=============================================================================
				} else {
					//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
					// Open a new group
					//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
					if($field['type'] == 'GROUP_OPEN'){
						//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
						// Dynamic Groups
						//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
						$att['dynamic'] = '';
						$att['dynamic'] = $field['dynamic'] ? 'dynamic="' . $att['id'] . '"' : '';

						//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
						// Create the group block
						//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
						echo '<div class="group-block-wrap clear" ',$att['dynamic'],'>',
							'<div class="group-block-heading"><b>', $field['label'], $field['desc'] != '' ? ' -</b> ' . $field['desc'] : '</b>' ,'</div>',
							'<div class="group-block">';

								//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
								// Add a group counter
								//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
								if($field['dynamic']){
									$g = count($group);
									if(($g > 0 && $group[$g-1]['id'] != $field['id']) || $g == 0 ){
										//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
										// Get the next meta data
										//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
										$val = oz::def($this->meta[$this->flat[$f+1]['id']], '');
										if(!is_array($val)) $val = array($val);

										$group[] = array(
											'id'		=> $field['id'],
											'start' 	=> $f-1,
											'repeat'	=> count($val),
											'counter'	=> 0
										);
									}

									echo '<input type="hidden" value="true" name="counter-',$att['id'],'[]">';
								}

					//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
					// Close the old group
					//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
					} else {
								//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
								// Reset the loop counter
								//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
								if($field['dynamic']){
									$g = count($group)-1;
									if(--$group[$g]['repeat'] > 0){
										$f = $group[$g]['start'];
										++$group[$g]['counter'];
									} else {
										array_pop($group);
									}
								}

						echo '<div style="clear: both;"></div>',
							'</div>',
						'</div>';
					}
				}
			}
		echo '</div>';

		//- - - - - - - - - - - - - - - - - - - - - - - -
		// Execute callback
		//- - - - - - - - - - - - - - - - - - - - - - - -
		if($this->mb['callback']) call_user_func($this->mb['callback'], $this);
	}




	//###########################################################################
	// Save
	//###########################################################################
	//==========================================================
	// Save Post meta
	//==========================================================
	function save($postID){
		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Verify nonce and check autosave and permissions
		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		if(!isset($_POST['metabox_of_oz']) || !wp_verify_nonce($_POST['metabox_of_oz'], 'save_metabox'))
			return $postID;
		if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
			return $postID;
		if($_POST['post_type'] == 'page' || $_POST['post_type'] == 'post'){
			if(!current_user_can('edit_page', $postID) || !current_user_can('edit_post', $postID))
				return $postID;
		}
		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Compare the post type of the page being saved and the defined post type of the metabox
		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		$postType = get_post_type($postID);
		$metaType = $this->mb['post_types'];
		if(!in_array($postType, $metaType) && $metaType !== '*') return $postID;

		$this->flatten();
		$save = array();
		foreach($this->flat as $field){
			//=============================================================================
			// Non-Dynamic Groups
			//=============================================================================
			if(!$field['_dynamicGroup']){
				if($field['type'] == 'GROUP_OPEN' || $field['type'] == 'GROUP_CLOSE' || !isset($_POST[$field['id']])) 
					continue;
				$save[$field['id']] = $_POST[$field['id']];

			//=============================================================================
			// Dynamic Groups
			//=============================================================================
			} else {
				if(!isset($_POST['counter-' . $field['_parents']])) 
					continue;

				//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
				// Loop for every dynamic field
				//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
				for($g = 0; $g < count($_POST['counter-' . $field['_parents']]); $g++){
					$save[$field['id']][$g] = array();
					$save[$field['id']][$g] = $_POST[$field['id']][$g];
				}
			}
		}

		update_post_meta($postID, $this->mb['id'], $save);
	}

	//==========================================================
	// Save Options
	// :: Used to save metaboxes on a menu page
	//==========================================================
	function save_options(){
		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Verify nonce and check autosave and permissions
		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		if(!isset($_POST['action'])) return false;
		if(!isset($_POST['metabox_of_oz']) || !wp_verify_nonce($_POST['metabox_of_oz'], 'save_metabox'))
			return false;
		if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
			return false;

		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Compare the page being saved with the one defined
		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		$page = get_current_screen();
		if($page->id != $_POST['action']) return false;
		$this->flatten();
		$save = array();

		foreach($this->flat as $field){
			//=============================================================================
			// Non-Dynamic Groups
			//=============================================================================
			if(!$field['_dynamicGroup']){
				if($field['type'] == 'GROUP_OPEN' || $field['type'] == 'GROUP_CLOSE' || !isset($_POST[$field['id']])) 
					continue;
				$save[$field['id']] = $_POST[$field['id']];

			//=============================================================================
			// Dynamic Groups
			//=============================================================================
			} else {
				if(!isset($_POST['counter-' . $field['_parents']])) 
					continue;

				//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
				// Loop for every dynamic field
				//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
				for($g = 0; $g < count($_POST['counter-' . $field['_parents']]); $g++){
					$save[$field['id']][$g] = array();
					$save[$field['id']][$g] = $_POST[$field['id']][$g];
				}
			}
		}
		update_option($this->mb['id'], $save);

		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Admin Notices
		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		if(self::$saved) return false;
		self::$saved = true;
		add_action('admin_notices', array(&$this, 'page_saved_message'));
	}
	function page_saved_message(){
		echo '<div class="updated"><p>Changes saved.</p></div>';
	}


	//=============================================================================
	// Enqueue Styles and Scripts
	//
	// :: Also include our namespaced global variable
	//=============================================================================
	function styles(){
		wp_enqueue_style('metaboxes-of-oz', METABOX_OF_OZ . 'style.css');
		wp_enqueue_script('metaboxes-of-oz', METABOX_OF_OZ . 'metaboxes.js', array('jquery'));

    		?>
			<script>
				Metabox_of_Oz = {
					groups: [],
					dir: '<?php echo get_template_directory_uri() ?>'
				}
			</script>
		<?php 
	}



	//###########################################################################
	// Helpers
	//###########################################################################
	//=============================================================================
	// Sanitizes a field by setting defaults
	// ::$field 	[ARR] 	Field to sanitize
	// ::$parents 	[ARR]	The list of parent groups this field belongs to
	// ::$isDynamicGroup [ARR] Does this belong to a dynamic group?
	//=============================================================================
	static function sanitize(&$field, $parents = array(), $isDynamicGroup = false){
		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// User Defaults
		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		oz::def($field['type'], 'text');
		oz::def($field['desc'], '');
		oz::def($field['label'], $field['id']);
		oz::def($field['dynamic'], false);
		oz::def($field['_dynamicGroup'], $isDynamicGroup);
		oz::def($field['_parents'], '');
		oz::def($field['button'], $field['label']);
		oz::def($field['value'], '');
		oz::def($field['disabled'], false);
		oz::def($field['settings'], array());
		oz::def($field['nolabel'], false);
		oz::def($field['full'], false);
		oz::def($field['options'], array());
		oz::def($field['class'], '');
		oz::def($field['callback'], false);
		oz::def($field['default'], '');

		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Add field specific styles
		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		if($field['type'] == 'file'){
		    wp_enqueue_style( 'thickbox' ); // Stylesheet used by Thickbox
		    wp_enqueue_script( 'thickbox' );
		    wp_enqueue_script( 'media-upload' );			
		}

		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Preppend the parents to the id
		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		$id = '';
		foreach($parents as $parent){
			$id .= $parent . '-';
		}
		$field['id'] = $id . $field['id'];
		$field['_parents'] = implode('-', $parents);
		$field['_name']	= $field['id'];
		$field['settings']['textarea_name'] = $field['id'];

		return $field;
	}

	//=============================================================================
	// Flattens the metabox object so that we don't have to perform recursion. 
	// 	I've tried 3 times to create deeply-nested, dynamic groups with recursion - my brain exploded. It was terrible.
	//
	// Groups will now be wrapped with GROUP_OPEN and GROUP_CLOSE keys vs being in arrays
	//
	// :: $fields 	[ARR]	List of fields to add to $this->flatten
	// :: $parents 	[ARR]	The list of parent groups these fields belongs to
	//=============================================================================
	private function flatten(&$fields = false, $parents = array(), $isDynamicGroup = false){
		if(!$fields)
			$fields = $this->fields;

		//=============================================================================
		// Add each field as a direct child of $this->flat
		//=============================================================================
		foreach($fields as $key=>$field){
			//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// Sanitize field
			//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			if(!isset($field['id'])){
				trigger_error('"id" is required to create the fields and groups');
				continue;	
			} 
			self::sanitize($field, $parents, $isDynamicGroup);

			//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// Add a single field
			//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			if($field['type'] != 'group'){
				$this->flat[] = $field;
			//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// Open a new group, add each child, and then close it
			//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			} else {
				//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
				// Sanitize and strip fields for the group entry
				//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
				if(!isset($field['fields'])){
					trigger_error('Group ['.$field['id'].'] requires fields');					
					continue;
				}
				$simpleField = $field;
				unset($simpleField['fields']);

				//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
				// Open and start a group
				//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
				$simpleField['type'] = 'GROUP_OPEN';
				$this->flat[] = $simpleField;

				array_push($parents, $field['id']);
					$this->flatten($field['fields'], $parents, $field['dynamic']);

					//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
					// Remember this group in JavaScript
					//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
					if(!isset($_POST['metabox_of_oz'])){
						if($field['dynamic']) echo '<script>',
							'Metabox_of_Oz.groups.push("', $field['id'], '");',
						'</script>';
					}

				//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
				// Close the group
				//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
				$simpleField['type'] = 'GROUP_CLOSE';
				$simpleField['dynamic'] = $field['dynamic'];
				$this->flat[] = $simpleField;
				array_pop($parents);
			}
		}
	}
}