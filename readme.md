# Framework of Oz

**Version**: 0.2.0  
**Requires at least**: ?  
**Tested up to**: 3.5.2  
**License**: GPLv2  

**Contributors**:  
* Oz Ramos ([@labofoz](https://twitter.com/labofoz) / [LabofOz.com](http://labofoz.com) )



##Description
Create custom WordPress metaboxes and menupages, with repeatable [[nested] groups of] fields, anywhere. Fast.

![Preview](https://raw.github.com/labofoz/Framework-of-Oz/master/preview.jpg)


##Supported Fields
* Text
* Textarea
* File Uploader
* WYSIWYG (Redactor)
* Select boxes

All fields can be set to allow dynamic arrays/copies of that field!



##Installation
Include this folder in your theme and simply require `/framework-of-oz/init.php`. Redundancy checks will ensure the framework and associated scripts are loaded only once.

##Adding Metaboxes
Next, add your metaboxes using the `metabox` filter:
```php
add_filter('metabox', 'my_metaboxes');
function my_metaboxes($mb){
	$mb[] = array($ARRAY);
	return $mb;
}
```
Add a $mb[] for each metabox you'd like to add using the values below. It's that easy!

##Adding Menupages
If you'd like to create menu pages (so you can add metaboxes!), you do the same except with the `menupage` filter:
```php
add_filter('menupage', 'my_menupage');
function my_menupage($mp){
	$mp[] = array($ARRAY);
	return $mp;
}


##Creating a new Metabox
In the function below, every `$mb[]` assignment creates a new metabox;
```php
add_filter('metabox', 'my_metaboxes');
function my_metaboxes($mb){
	$mb[] = array($ARRAY);
	return $mb;
}
```
`$ARRAY` takes the following values (with the defaults):
```php
'id'			=> '', 			//[STR] *REQUIRED* Meta key used in [get_post_meta](http://codex.wordpress.org/Function_Reference/get_post_meta) which holds all the data in this metabox 
'label'			=> '',			//[STR] Label for the Metabox  
'context'		=> 'normal',	//[STR] [Metabox context](http://codex.wordpress.org/Function_Reference/add_meta_box)  
'priority'		=> 'high',		//[STR] [Metabox priority](http://codex.wordpress.org/Function_Reference/add_meta_box)  
'post_types'	=> '*',			//[STR, ARR, NULL] can be spelled `post_type` as well and must be either an array or comma separated string listing all the [custom] Post Types this metabox will appear on. If `'*'` is passed, then the metabox gets applied to every post type!  
'fields'		=> array(), 	//[ARR] *REQUIRED* List of arrays, each containing a field to display. Possible values are listed below.  
'templates'		=> array() 		//[STR, ARR, NULL] Taking the same argument types as `post_type`, this list contains all the post/page templates this metabox will appear on. Note that the template name must contain the extension, ie `template-demo.php`
```



###Fields
The `fields` containes one array for each field you'd like to add to the metabox. So to create three fields you would do:  
```php	
'fields'	=> array(
	array($FIELD_1),
	array($FIELD_2),
	array($FIELD_3)
)
```  
Where $FIELD_1/2/3 is an array containing the build-up of the field. Each field has the following defaults:  
```php
array(
	'id'		=> '',			//[STR] 	The name attribute of the field's element and the key used in [get_post_meta()](http://codex.wordpress.org/Function_Reference/get_post_meta). Note that this also sets the elements id, setting is as id="metabox-NAME" where NAME is the slugified version of this value.  
	'label' 	=> '',			//[STR] 	The label to display next to the field.
	'nolabel'	=> false,		//[BOOL] 	whether to add display a label (false) or not (true)  
	'full'		=> false,		//[BOOL] 	Whether to stack the label ontop of the control, making them both full width. Useful for editors.
	'desc'		=> '',			//[STR] 	A description to display below the field. Go nuts with HTML if you'd like!
	'value' 	=> '',			//[STR] 	The default value to use  
	'type'		=> 'text',		//[STR] 	The field type, see below for a list of possible values.  
	'value'		=> '',			//[STR] 	The default value to use  
	'dynamic'	=> false,		//[BOOL] 	Adds an "Add More" button, which let's you duplicate the current field dynamically  
	'disabled'  => false,		//[BOOL] 	Whether this field is disabled or not, useful for displaying fields that require the user to copy/paste eg shortcodes  
	'button' 	=> [label],		//[STR] 	Label for the extra button (for example, in file fields). Defaults to label  
	'settings'	=> array(),		//[ARR] 	Additional settings to use for certain fields (like editor)  
	'options'	=> array(), 	//[ARR] 	The list of options for select boxes. The key will be used as the value, and the value will be used as the label. See `select` in the field descriptions below for more details.
	'class'		=> '',			//[STR] 	Extra classes to apply. Classes should be space separated and without the dot (this will essentially get dumped into the elements `class` attribute)
)
```


###Field Types
The full list of field types include  
```php
text
textarea
file
editor [wysiwyg]
```


#### `text`
The standard text input.

#### `textarea`
The standard textarea box.

#### `file`
Allows you to upload files into your media library. This field takes an additional attribute, `button` which is used to define the upload buttons label (defaults to `label`). ex)
```php
array(
	'id' 		=> 'file-uploader',
	'type'		=> 'file',
	'button'	=> 'Upload File'	
)
```

#### `editor` **or** `wysiwyg`
Creates a TinyMCE WYSIWYG editor. Notice that this field has the alias `editor`, meaning you can use either or.

#### `select`
A dropdown/select box. This field takes in the extra property `options`, which defines the list of options to show in the dropdown.  
In an associative array, the arrays keys are the options values, and the keys value is the options label. Confusing to describe, but easy to invision:  
```php
'options' => array(
	'dog'		=> 'German Shepherd',
	'sport'		=> 'Football',
	'city'		=> 'Boston, MA'
)
```
Here, the value stored in the meta is "sport" if the user chooses "Football" from the dropdown.  
For standard arrays, the index is used as the value:
```php
'options'	=> array(
	'red', 'yellow', 'orange', 'green', 'blue', 'indigo', 'violet'
)
Should the user select "Orange" from this dropdown, the value stored would be 2.


###Field Groups
Field Groups let you group together related controls, while keeping them all under one metabox. And because groups themselves can be duplicated and moved around dynamically, you can power complex, self-contained mini-apps with very little code!

While `dynamic` allows you to duplicate a single field, you can duplicate a group of fields by simply putting the group of fields in an array:
```php
$mb[] = array(
	...
	'label'		=> 'Group Label',
	//Single Field
	array(
		'id'	=> 'a-single-field'
	),

	//Group of Fields
	array(
		array(
			'id'	=> 'first-field-in-group',
		),
		array(
			'id'	=> 'second-field-in-group'
		)
	)
)
```
When you do this, you'll get an "Add Group" button below the group. Fields inside the group array are created as normal.

You can also add a label (`label =>`) to display above the group (defaults to `__('Group')`, and a description (`group => `) to display below the label.

Groups can become dynamic by adding `dynamic => true`.


###Metadata Structure

    Single Fields                      $field
    Dynamic Fields                     $field[i]
    Fields in groups                   $group-$field
    Dynamic fields in groups           $group-$field[i]
    Dynamic fields in dynamic groups   $group-$field[g][i]

Fields are all grouped together by metabox into one large array. This makes pulling the data as easy as doing `get_post_meta($post->ID, $MB, true);`, where `$MB` is the id of the metabox your field is in.  
For example, if you created a metabox with `id=>my_metabox`, then you would pull it with `get_post_meta($post->ID, 'my_metabox', true);`  
To quickly see the contents of the metabox, copy/paste the following:  

    global $post;
    $my_metabox = 'id';	//Set this string to your metabox's id
    echo '<pre>', print_r(get_post_meta($post->ID, $my_metabox, true)), '</pre>';



##Creating a new Menupage
Menupages are designed to be populated with our metaboxes (or not!), and each menupage comes with a Save panel. Creating one is simple:
```php
add_filter('menupage', 'my_menupage');
function my_menupage($mp){
	$mp[] = array(
		'id'		=> 'theme-options',
		'title'		=> 'Theme Options',
		'type'		=> 'add_menu_page',
		'position'	=> 58
	);
	return $mp;
}
```
In order to attach metaboxes to this new menu page, **you must set the `post_type` field of the metabox to this page's hande!** The handle is usually in the form of 'location_`id`'. So for a top-level page with an id of 'theme-options', the metaboxes `post_type` field must be set to `toplevel_page_theme-options`.

You can quickly get the page's handle by right clicking the menu item and inspecting the item with your browsers inspector (Chrome works best). The ID of the &lt;li&gt; element wrapping that menu item is the handle of the page you must use for your metabox.

###Values
The $mp[] array takes in the following values:
```php
$mp[] = array(
	'id'		=> '',				//[STR] 	The slug/name of the page. Used in determining the page handle for metaboxes
	'title'		=> `id`,			//[STR] 	The title of the page
	'menu'		=> `title`, 		//[STR] 	The menu items label
	'cap'		=> 'manage_options' //[STR] 	The capability type needed to view the page	
	'type'		=> 'add_menu_page',	//[STR] 	The type of menu to build. Currently only 'add_menu_page' works.
	'position'	=> 30.314 + count($mp[]), //[INT] The menu items position. The framework keeps an internal count of all the menupages built so far, and applies that number by default to try and maintain unique values
	'content'	=> false 			//[STR] 	The HTML to display just below the title
);
```



##Known Issue

* iFrame WYSIWYG freezes after being moved



##To Do
* Display/Hide on specific post ID's
* Custom logic to display on posts
* Add custom attributes to fields
* Only load resources in Metabox pages
* Hide previews for file uploaders


**Add the following fields**

* Checkboxes
* text money
* date picker
* date picker (unix timestamp)
* date time picker combo (unix timestamp)
* time picker
* color picker
* textarea small
* textarea code
* select
* radio
* radio inline
* taxonomy radio
* taxonomy select
* checkbox
* multicheck
* Image/file upload
* oEmbed



##Change Log

###0.2.0
* Can use a metabox everywhere with `*` wildcard

###0.1.0
* Can build text input fields with dynamic buttons.

---
*This project was inspired by Jared Atchison's [Custom Metaboxes and Fields for WordPress](https://github.com/jaredatch/Custom-Metaboxes-and-Fields-for-WordPress)*