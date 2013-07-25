<?php //#########################################################
// The menu-pages class
//#########################################################

//==========================================================
// Execute the 'menupage' filter
//==========================================================
add_action('init', 'Init_Menupage_of_Oz', 1000);
function Init_Menupage_of_Oz(){
	Menupage::load();
}

class Menupage{
	//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// Properties
	//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	private $mp;						//The menu pages
	private $page; 						//The actual menu page ID, as built by add_*_page
	static public $loaded = array();	//Contains a list of all loaded menupages

	//==========================================================
	// Create all menu pages created with the 'menupage' filter
	//==========================================================
	public static function load(){
		$pages = apply_filters('menupage', array());
		foreach($pages as $mp)
			self::$loaded[] = new Menupage($mp);
	}

	//=============================================================================
	// Constructor
	// $mp 			(ARR) The first index contains the same values needed for add_menu_page
	//				The second index contains the menu type (add_menu_page, add_themes_page etc) and defaults to 'add_menu_page'
	//=============================================================================
	function __construct($mp){
		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Validate
		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		if(!self::def($mp['id'], false)) trigger_error(__('Menu Page requires an ID', 'framework-of-oz'));

		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Set Defaults
		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		self::def($mp['title'], 	$mp['id']);
		self::def($mp['type'],	 	'add_menu_page');
		self::def($mp['menu'], 		$mp['title']);
		self::def($mp['cap'], 		'manage_options');
		self::def($mp['position'], 	30.314 + count(self::$loaded));
		self::def($mp['content'],	false);
		$this->mp = $mp;

		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Create the menu item
		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		add_action('admin_menu', array(&$this, 'menu'));
		add_filter('metabox', array(&$this, 'ouput_save_metabox'));		
	}

	//==========================================================
	// Add the Menu item
	//==========================================================
	function menu(){
		switch($this->mp['type']){
			//==========================================================
			// Add Menu Page 
			//==========================================================
			case 'add_menu_page':
				$this->page = add_menu_page($this->mp['title'], $this->mp['menu'], $this->mp['cap'], $this->mp['id'], array(&$this, 'page'), null, $this->mp['position']);
			break;
			default: trigger_error(__('Invalid Menu Type', 'framework-of-oz'));
		}

		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Callbacks for this page only
		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		add_action('load-' . $this->page, array(&$this, 'actions'), 9);
		add_action('admin_footer-' . $this->page, array(&$this, 'scripts'));
	}

	//==========================================================
	// Footer Scripts
	//==========================================================
	function scripts(){ ?>
		<script>postboxes.add_postbox_toggles(pagenow);</script>
	<?php }

	//==========================================================
	// Load other actions
	//==========================================================
	function actions(){
		do_action('add_meta_boxes_'.$this->page, null);
		do_action('add_meta_boxes', $this->page, null);
 
		add_screen_option('layout_columns', array('max' => 2, 'default' => 2) );
 		wp_enqueue_script('postbox'); 		
	}

	//==========================================================
	// Build the page
	//==========================================================
	function page(){ 
		Metabox::load();
		?>
		 <div class="wrap">
 
			<?php screen_icon(); ?>
 
			<h2><?php echo esc_html($this->mp['title']);?></h2>
 
			<form name="my_form" method="post">  
				<input type="hidden" name="action" value="<?php echo $this->page ?>">
				<?php //==========================================================
				// Save closed metaboxes and their order
				//==========================================================
				wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
				wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
 
				<div id="poststuff">
		
					 <div id="post-body" class="metabox-holder columns-<?php echo get_current_screen()->get_columns() == 1 ? '1' : '2'; ?>"> 
 
						  <div id="post-body-content">
							<?php if($this->mp['content']) call_user_func($this->mp['content']); ?>
						  </div>    
 
						  <div id="postbox-container-1" class="postbox-container">
						        <?php do_meta_boxes('','side',null); ?>
						  </div>    
 
						  <div id="postbox-container-2" class="postbox-container">
						        <?php do_meta_boxes('','normal',null);  ?>
						        <?php do_meta_boxes('','advanced',null); ?>
						  </div>	     					
 
					 </div> <!-- #post-body -->
				
				 </div> <!-- #poststuff -->
 
	      		  </form>			
 
		 </div><!-- .wrap -->
	<?php }

	//==========================================================
	// Creates the page save metabox
	//==========================================================
	function ouput_save_metabox($mb){
		$mb[] = array(
			'id'	=> 'menupage',
			'label'	=> 'Save Changes',
			'page' => 'toplevel_page_theme-options',
			'context' => 'side',
			'fields'	=> array(
				array(
					'id'		=> 'save',
					'label'		=> 'Save',
					'type'		=> 'submit',
					'nolabel'	=> true
				)
			)
		);
		return $mb;
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