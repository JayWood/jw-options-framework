<?php
/**
 *	JW Options Panel API
 *
 *	@package WordPress
 */
 
if(!class_exists('JW_Options')){

/**
 *	The JW Options Panel class.
 */
	class JW_Options{
		
		/**
		 *	@access private
		 *	@var string Current version of the options panel class.
		 */
		private $ver = "1.1b";
		
		/**
		 *	@access private
		 *	@var string Name of the options panel.
		 */
		private $frameworkName = "JJW Options Framework";
		
		/**
		 *	@access private
		 *	@var string A URL of where to download the options panel.
		 */
		private $frameworkURL = "http://www.plugish.com/jjw_framework";
		
		/**
		 *	@access private
		 *	@var array() A map of tabulated options data.
		 */
		private $tabs = array();
		
		/**
		 *	@access private
		 *	@var string For compatability and options purposes, defaults to "jw_"
		 */
		private $prefix;
		
		/**
		 *	Use ajax in the options form?
		 *
		 *	Removed, form is completely AJAX
		 *
		 *	@access private
		 *	@var boolean Defaults to TRUE.
		 */
		//private $ajax;
		
		/**
		 *	Used in menu pages and throughout the plugin
		 *
		 *	@access private
		 *	@var string Defaults to "JW Options Panel"
		 */
		private $plugin_title;
		
		/**
		 *	Used in menu pages.
		 *
		 *	@access private
		 *	@var string Defaults to "JW Options"
		 *	@see add_menu_page()
		 */
		private $menu_title;
		
		/**
		 *	Used in menu creation.
		 *
		 *	Must be 'page', 'link', 'comment', 'management', 'option', 'theme', 'plugin', 'user', 'dashboard', 'post', 'media', or 'new'
		 *
		 *	@access private
		 *	@var string
		 *	@see add_submenu_page()
		 *	@see add_menu_page()
		 */
		private $menu_type; // Determines where the menu shows up.
		
		/**
		 *	Capability required to see the menu.
		 *
		 *	@var string Defaults to 'manage_options'
		 *	@see add_menu_page()
		 */
		private $cap;
		
		/**
		 * The slug for the options page.
		 * 
		 * Relies on $prefix
		 *
		 * @var string Defaults to $prefix.'options_panel'
		 * @see add_menu_page()
		 */
		private $slug;
		
		/**
		 *	Icon of the menu.
		 *
		 *	Only useful if $menu_type is 'new'
		 *	@var string Defaults to NULL
		 *	@see add_menu_page()
		 */
		private $icon;
		
		/**
		 *	Position of the menu.
		 *
		 *	Only useful if $menu_type is 'new'
		 *	@var integer Defaults to NULL
		 */
		private $menu_pos;
		
		
		/**
		 *	What's the plugin's location?
		 *
		 *	Will invoke wp_die() if not set.
		 *	@var string Should be set to __FILE__
		 */
		private $file_data;
		
		/**
		 *	Should we show API warnings?
		 *
		 *	@var boolean Defaults to TRUE
		 */
		private $supress_warnings;
		
		/**
		 *	What folder is the class working with
		 *
		 *	@var string
		 */
		private $fType = 'unknown';
		
		/**
		 *	Handles any form related errors prior to processing.
		 *	
		 *	@var array Associative array of errors in id => error_msg format.
		 */
		private $errors;
		
		/**
		 *	Holds Option Data Array
		 */
		private $opData;
		 
		function JJW_Options($ops){
			$this->__construct($ops);
		}
		
		function __construct($ops){	
			
			add_action('admin_init', array(&$this,'regAdminStuff'));
			add_action('admin_menu', array(&$this, 'setupAdminMenu'));
			add_action('init', array(&$this, 'regStuff'));
			
			// Setup all the class variables, kinda messy no?
			$tmp_ops = !isset($ops['op_array']) || !is_array($ops['op_array']) ? wp_die($this->errorHandler('Options array does not exist within the data array, please read the documentation.',0)) : $ops['op_array'];
			$tmp_tabs = array();
			foreach ((array)$tmp_ops as $tab)
				array_push($tmp_tabs, $tab);
				
			$this->tabs = $tmp_tabs;
			unset($tmp_ops);
			unset($tmp_tabs);
			
			$this->prefix = !isset($ops['prefix']) ? 'jw_' : $ops['prefix'];
			/* Form is now AJAX only. */
			//$this->ajax = !isset($ops['use_ajax']) ? TRUE : $ops['use_ajax'];
			$this->plugin_title = !isset($ops['plugin_title']) ? 'JW Options Panel' : $ops['plugin_title'];
			$this->menu_title = !isset($ops['menu_title']) ? "JW Options" : $ops['menu_title'];
			$this->cap = !isset($ops['capability']) ? 'manage_options' : $ops['capability'];
			$this->slug = !isset($ops['slug']) ? $this->prefix.'options_panel' : $ops['slug'];
			$this->icon = !isset($ops['icon'])? NULL : $ops['icon'];
			$this->menu_pos = !isset($ops['menu_pos']) ? NULL : intval($ops['menu_pos']);
			$this->menu_type = !isset($ops['menu_type']) || !in_array($ops['menu_type'], array('page', 'link', 'comment', 'management', 'option', 'theme', 'plugin', 'user', 'dashboard', 'post', 'media', 'new')) ? 'new' : $ops['menu_type'];
			$this->file_data = !isset($ops['file_data']) ? wp_die(__('JW_Panel: Fatal ERROR. Could not retrieve plugin data.  Please read documentation.')) : $ops['file_data'];
			$this->supress_warnings = (isset($ops['supress_warnings']))?$ops['supress_warnings']:TRUE;
			
			if(preg_match('/\/themes\//i', $this->file_data)){
				$this->fType = 'theme';
			}elseif(preg_match('/\/plugins\//i', $this->file_data)){
				$this->fType = 'plugin';
			}
			
			add_action('wp_ajax_'.$this->slug.'_save_options', array(&$this, 'handleAjax'));
			
		} // END __construct		
		
		/**
		 *	Saves form data from AJAX calls
		 *
		 */
		function handleAjax(){
			$fData = array();
			//echo json_encode($_POST, JSON_FORCE_OBJECT);
			$formDecoded = urldecode($_POST['formInfo']);
			parse_str($formDecoded, $fData);
			
			/* Debug */
			$output['data'] = $fData;
			$output['decodedForm'] = $formDecoded;
			
			
			if(!wp_verify_nonce($fData['_wpnonce'], $this->prefix.$this->slug.'-options')){
				/* Kill script if we can't verify nonce. */
				$output['error'] = 'Fatal Error'; // Don't make it obvious
				echo json_encode($output);
				die();
			}
			$output['success'] = 'Nonce Verified';
			$output['nonce'] = wp_create_nonce($this->prefix.$this->slug.'-options');
			
			$options = $fData['jw_panel']['options'];
			$output['option_data'] = $options;	
			
			// Allow users to filter the option saving before it actually stores data.
			$nOptions = apply_filters($this->prefix.'panel_pre_update', $options);
			foreach($nOptions as $k => $v){
				update_option($k, $v);
			}
			
			
			echo json_encode($output);
			
			die();
			
		}
		
		/**
		 *	Custom Checked
		 *
		 *	Allows using arrays in checked variables
		 */
		function jop_checked($haystack, $cur, $show = FALSE){
			if(is_array($haystack) && in_array($cur, $haystack)){
					$cur = $haystack = 1;
			}
			return checked($haystack, $cur, $show);
		}
		
		
		/**
		*	Registers administrator stylesheets and scripts.
		*/
		function regAdminStuff(){
			$this->setupSettingsRegistry();
			if('theme' == $this->fType){
				$stylesheetDir = get_bloginfo('stylesheet_directory');
				$dirs = array(
					$stylesheetDir.'/'.basename( dirname(  dirname(__FILE__) ) ).'/jw-options-framework/css/admin.css',
					$stylesheetDir.'/'.basename( dirname(  dirname(__FILE__) ) ).'/jw-options-framework/js/admin.js',
				);
			}else{
				$dirs = array(
					plugins_url('css/admin.css', __FILE__),
					plugins_url('js/admin.js', __FILE__)
				);
			}
			wp_register_style('admin-css', $dirs[0] , array('thickbox'));
			wp_register_script('admin-js', $dirs[1], array('jquery', 'media-upload', 'thickbox'));
			
		}
		
		/**
		 *	Registers site-wide stylesheets.
		 */
		function regStuff(){
			
			if('theme' == $this->fType){
				$stylesheetDir = get_bloginfo('stylesheet_directory');
				$x = dirname(__FILE__);
				$dirs = array(
					$stylesheetDir.'/'.basename( dirname(  dirname(__FILE__) ) ).'/jw-options-framework/css/960.css'
				);
			}else{
				$dirs = array(
					plugins_url('css/960.css', __FILE__)
				);
			}
			
			wp_register_style('960-css', $dirs[0], array(), '1.0', 'all');
		}
		
		/**
		 *	Prints registered administrator stylesheets.
		 */
		function printAdminCss(){
			wp_enqueue_style('admin-css');
			//wp_enqueue_style('960-css');
		}
		
		/**
		 *	Prints administrator javascripts
		 */
		function printAdminJs(){
			wp_enqueue_script('admin-js');
			wp_localize_script('admin-js', 'jwPanelParams', array('ajaxAction'=>$this->slug.'_save_options'));
		}
		
		
		/**
		 *	Sets up the administrator menu based on $menu_type
		 *
		 *	Relies on menu_title, cap, slug, and plugin_title.  (icon & menu_pos optional)
		 *	@see add_menu_page()
		 *	@see add_submenu_page()
		 */
		function setupAdminMenu(){
			switch($this->menu_type){
				case 'page':
					$settingsPage = add_pages_page($this->plugin_title, $this->menu_title, $this->cap, $this->slug, array(&$this, 'renderOptionsPanel'));
					break;
				case 'link':
					$settingsPage = add_links_page($this->plugin_title, $this->menu_title, $this->cap, $this->slug, array(&$this, 'renderOptionsPanel'));
					break;
				case 'comment':
					$settingsPage = add_comments_page($this->plugin_title, $this->menu_title, $this->cap, $this->slug, array(&$this, 'renderOptionsPanel'));
					break;
				case 'management':
					$settingsPage = add_management_page($this->plugin_title, $this->menu_title, $this->cap, $this->slug, array(&$this, 'renderOptionsPanel'));
					break;
				case 'option':
					$settingsPage = add_options_page($this->plugin_title, $this->menu_title, $this->cap, $this->slug, array(&$this, 'renderOptionsPanel'));
					break;
				case 'theme':
					$settingsPage = add_theme_page($this->plugin_title, $this->menu_title, $this->cap, $this->slug, array(&$this, 'renderOptionsPanel'));
					break;
				case 'plugin':
					$settingsPage = add_plugins_page($this->plugin_title, $this->menu_title, $this->cap, $this->slug, array(&$this, 'renderOptionsPanel'));
					break;
				case 'user':
					$settingsPage = add_users_page($this->plugin_title, $this->menu_title, $this->cap, $this->slug, array(&$this, 'renderOptionsPanel'));
					break;
				case 'dashboard':
					$settingsPage = add_dashboard_page($this->plugin_title, $this->menu_title, $this->cap, $this->slug, array(&$this, 'renderOptionsPanel'));
					break;
				case 'post':
					$settingsPage = add_posts_page($this->plugin_title, $this->menu_title, $this->cap, $this->slug, array(&$this, 'renderOptionsPanel'));
					break;
				case 'media':
					$settingsPage = add_media_page($this->plugin_title, $this->menu_title, $this->cap, $this->slug, array(&$this, 'renderOptionsPanel'));
					break;
				default:
					$settingsPage = add_menu_page($this->plugin_title, $this->menu_title, $this->cap, $this->slug, array(&$this, 'renderOptionsPanel'), $this->icon, $this->menu_pos);				
					break;
			}
			
			// Action to hook into the menu.
			do_action('jw_menu_after');
			add_action('admin_print_styles-'.$settingsPage, array(&$this,'printAdminCss'));
			add_action('admin_print_scripts-'.$settingsPage, array(&$this,'printAdminJs'));
		} // END setupAdminMenu
		
		
		/**
		 *	Register ALL options for use in settings later.
		 *
		 *	Traverses the $tabs array and pulls options and their children to register them
		 *	@see register_setting()
		 */
		function setupSettingsRegistry(){
			// Run 'register_settigns' for options.
			$settingsArray = array();
			$optionsArray = array();
			
			for($y=0;$y < count($this->tabs);$y++):
				$curTab = $this->tabs[$y];
				$tmpTabs = $this->tabs[$y]['options'];
				if($this->tabHasChild($curTab)):
					$child = $this->tabHasChild($curTab);
					foreach($child as $tab):
						array_push($optionsArray, $this->getTabOptions($tab));
					endforeach;		
				else:
					array_push($optionsArray, $this->getTabOptions($curTab));
				endif;
			endfor;
			
			foreach($optionsArray as $o):
				foreach((array)$o as $option):
					if(is_array($option) && array_key_exists('id', $option))
						array_push($settingsArray, $option['id']);
				endforeach;
			endforeach;			
			
			foreach($settingsArray as $x)
				register_setting($this->prefix.$this->slug, $this->prefix.$x);
		} // END setupSettingsRegistry
		
		
		/**
		 *	@param array $tab Array of tabulated data.
		 *	@return mixed False on failure or and array of options data on success.
		 */
		function getTabOptions($tab){
			if(is_array($tab) && array_key_exists('options', $tab))
				return $tab['options'];
			else
				return false;
		} // END getTabOptions
		
		/**
		 *	@param array $tab Array of children
		 *	@return mixed False on failure or and array of children on success.
		 */
		function tabHasChild($tab){
			if(is_array($tab) && array_key_exists('children', $tab))
				return $tab['children'];
			else
				return false;
		} // END getTabChild
		
		/**
		 *	Displays the options panel.
		 */
		function renderOptionsPanel(){
			
			switch($this->fType){
				case 'plugin':
					$p_data = get_plugin_data($this->file_data);
					$op_name = $p_data['Name'];
					$op_ver = $p_data['Version'];
					$op_author = $p_data['Author'];
					break;
				case 'theme':
					$themeObject = wp_get_theme();
					$op_name = $themeObject->Name;
					$op_ver = $themeObject->Version;
					$op_author = $themeObject->Author;
					break;
				default:
					$op_name = $this->frameworkName;
					$op_ver = $this->ver;
					$op_author = "Unknown";
					break;
			}
			
			?>
            	<div class="wrap">
                	<div id="jw_options_panel">
                    	<form id="jw_panel_form">
                        	<?  echo settings_fields($this->prefix.$this->slug); ?>
                            <div id="jw_header">
                            	<div class="plugin_title"><h2><?php echo  $op_name; ?></h2></div>
                                <div class="plugin_info">
                                	<p class="info_text">Version: <? echo  $op_ver; ?></p>
                                	<p class="info_text">Author: <? echo  $op_author; ?></p>
                                </div>
                                <div class="framework_info">
                                	<p class="info_text"><a href="<? echo $this->frameworkURL; ?> " title="Get this framework for your next project."><? echo $this->frameworkName; ?></a></p>
                                    <p class="info_text">v. <? echo $this->ver; ?></p>
                                </div>
                                <div class="save_field">
                                	<a href="javascript:;" id="jw_panel_save"><? _e('Save Changes'); ?></a>
                                </div>
                            </div>
                            <div id="jw_tabs"><? echo $this->buildTabs(); ?></div>
                            <div id="jw_content"><? echo $this->buildContent(); ?></div>
                            <div id="jw_footer"></div>
                        </form>
                    	<div id="ajax_loader" style="display:none;"><span></span></div>
                    </div>
                </div>
            <?
		} // END renderOptionsPanel
		
		/**
		 *	Builds panel tabs.
		 *
		 *	@return string Unordered list (HTML) of tabs
		 */
		function buildTabs(){
			$output = "<!-- BEGIN TABS -->";
			$output .="<ul class='jw_tabs_list'>"; 
			
			for($a=0;$a<count($this->tabs);$a++):
				$curTab = $this->tabs[$a];
				$active = ($a==0) ? ' active' : ''; //Set tab 0 to active on every reload.
				$output .= '<li class="jw_tab'.$active.'" id="'.$curTab['id'].'"><a href="javascript:void(0)" id="'.$a.'" title="'.$curTab['title'].'">'.$curTab['name'].'</a><span class="indicator"></span>';
					
				if($this->tabHasChild($curTab)):
					$childTabs = $this->tabHasChild($curTab);
					$output .= "<!-- BEGIN CHILD TAB -->";
					$output .= "<ul class='jw_child_tab' >";
					
					for($b=0;$b<count($childTabs);$b++):
						$cur_child = $childTabs[$b];
						$output .= '<li class="jw_tab_child" id="'.$cur_child['id'].'"><a href="javascript:void(0)" id="'.$a.'_'.$b.'" title="'.$cur_child['title'].'">'.$cur_child['name'].'</a></li>';
					endfor;
					
					$output .= "</ul>";
					$output .= "<!-- END CHILD TAB -->";
				endif;
				
				$output .= '</li>';
			endfor;
			
			$output .= "</ul>";
			$output .= "<!-- END TABS -->";
			
			return $output;
			
		} //END buildTabs
		
		/**
		 *	Shows ALL content
		 *
		 *	@return string HTML of content structure.
		 */
		function buildContent(){
			$output = "<!-- BEGIN buildContent -->";
			$output .= '<div id="content_wrap">';
			
			for($t=0;$t<count($this->tabs);$t++):
				$curTab = $this->tabs[$t];
				$active = ($t == 0)? 'active' : '';
				$output .= '<div id="jw_cont_'.$t.'" class="jw_cont_tab '.$active.'">';
				$output .=  $this->buildOptions($curTab);
				$output .= '</div>';
				
				// Still not done, we now need to build any children this tab has.
				if($this->tabHasChild($curTab)):
					$childTab = $this->tabHasChild($curTab);
					for($c=0;$c<count($childTab);$c++):						
						$output .= '<div id="jw_cont_'.$t.'_'.$c.'" class="jw_cont_tab">';
						$output .=  $this->buildOptions($childTab[$c]);
						$output .= '</div>';
					endfor;
				endif;
			endfor;
			 $output .='</div>';
			$output .="<!-- END buildContent -->";
			
			return $output;
			
		} //END buildContent
		
		/**
		 *	Builds option form elements
		 *
		 *	@param array() $opt_data An array of the option data.
		 *	@return string HTML to display the form data.
		 */
		function buildOptions($opt_data){
			if(!isset($opt_data) || !is_array($opt_data))
				return false;
			
			$ops =$this->getTabOptions($opt_data);
			$output = "<!-- BEGIN buildOption -->";
			
			foreach($ops as $o):
				$output .="<!-- Option -->";
				$oType = (array_key_exists('type', $o) && !empty($o['type'])) ? $o['type'] : '';
				$oId = (array_key_exists('id', $o) && !empty($o['id'])) ? $o['id'] : '';
				
				if(empty($oType) || empty($oId)):
					$output .= $this->errorHandler("Option ID or TYPE not present.", 1);
				else:
					switch($oType){
						case 'html':
							$output .= $this->buildHTMLOption($o);
							break;
						case 'text':
							$output .= $this->buildTextOption($o);
							break;
						case 'text_box':
							$output .= $this->buildTextareaOption($o);
							break;
						case 'radio':
							$output .= $this->buildRadioOption($o);
							break;
						case 'check':
							$output .= $this->buildCheckOption($o);
							break;
						case 'dropdown':
							$output .= $this->buildDropdown($o);
							break;
						case 'media_upload':
							//$output .= $this->buildMedia($o);
							break;
						default:
							$output .= $this->errorHandler('Supplied option "'.$o['type'].'" is not a viable option.');
							break;
							
					}
				endif;
				$output .="<!-- End Option -->";
			endforeach;
			
			$output .="<!-- END buildOption -->";
			return $output;
			
		} // END buildOption
		
		/**
		 *	Displays errors.
		 *
		 *	@param string $txt Error text to display
		 *	@param integer $lvl 0 = Fatal Error, 1 = General Error, 2 = Warning (Default)
		 */
		function errorHandler($txt, $lvl = 2){
				$error_types = array(
					"Fatal Error",
					"General Error",
					"Warning"
				);
				if($this->supress_warnings == TRUE && $lvl == 2)
					return;
					
				return '<span class="panel_error error_level_'.$lvl.'"><strong>'.$error_types[$lvl].':</strong> '.$txt.'</span>';
		}
		
		/**
		*	Builds the media upload functionality
		*	
		*	Required:
		*		'id'			- Used for get_option in conjunction with $thsi->prefix
		*		'label'			- used in the HTML
		*	Optional:
		*		'upload_text'	- Default:NULL	String 		Controls the upload button text
		*		'default'		- Default:NULL  String 		The Full URL to the default image.
		*		'show_preview'	- Default:NULL	Boolean		Rather or not to show the preview field.  (Should set a default image if using this)
		*		'preview_size'	- Default:NULL	Array		Width and Height of the image.
		*		'desc'			- Default:NULL	String		Description of the upload field.
		*		'tooltip'		- Default:NULL	String		An added hint.
		*		'required'		- Default:NULL  Boolean		Rather or not to require this field.  (Should provide default)
		*/
		function buildMedia(Array $o){
			if(!array_key_exists('id', $o) || !array_key_exists('label', $o) || empty($o['id']) || empty($o['label']))
				return $this->errorHandler('Media uploader requires at least ID and Label to work.');
			
			$opID = $this->prefix.$o['id'];
			$def = empty( $o['default'] ) ? '' : $o['default'];
			$imgSrc = get_option($opID, $def);
			$uTxt = empty($o['upload_text']) ? "Upload Image" : $o['upload_text'];
			
			$required = isset($o['required']) && !empty($o['required']) ? 'required' : '';
			
			$output = '<div id="option_'.$o['id'].'" class="option">';
			$output .= '<label for="jw_panel_image_upload_'.$opID.'">'.$o['label'].'</label>';
			$output .= '<div class="jw_group_wrap">';
			if($o['show_preview'] && $o['show_preview'] == TRUE){
				// Setup sizes.
				$size = $o['preview_size'];
				if(!empty($size) && is_array($size)){
					$fSize = "width='".$size[0]."' height='".$size[1]."'";
				}else{
					$fSize = '';
				}
				$output .= '<div class="imgPreview"><img src="'.$imgSrc.'" '.$fSize.' id="img_prev_'.$opID.'"></div>';
			}
			$output .= '<input type="text" size="36" id="jw_panel_image_upload_'.$opID.'" name="jw_panel[options]['.$opID.']" value="" class="'.$required.'">';
			$output .= '<input id="jw_panel_image_btn_'.$opID.'" type="button" value="'.$uTxt.'" />';
			$output .= isset($o['desc']) && !empty($o['desc']) ? '<br /><small class="option_desc">'.$o['desc'].'</small>' : '';
			$output .= isset($o['tooltip']) && !empty($o['tooltip']) ? '<span class="tooltip_actuator"><div class="panel_tooltip" id="tooltip_'.$opID.'">'.$o['tooltip'].'</div></span>' : '';
			$output .= '</div>';
			$output .= '</div>';
			
			return $output;	
		}
		
		/**
		 *	Builds Dropdown option HTML
		 *
		 *	Required:
		 *		'id' 			- Used for get_option in conjunction with $this->prefix
		 *		'label'			- Used in the html
		 *		'options'		- The options for the radio buttons in an associative array.
		 *	Optional:
		 *		'desc'			- Default:NULL	String		Short description about item.
		 *		'tooltip'		- Default:NULL	String		Used to show a flyout tooltip about the option.
		 *		'default'		- A single key from the options array, defaults to the first key in the options array.
		 *
		 *	@param array() $o Array of option data
		 *	@return string HTML of checkbox form elements
		 */
		 function buildDropdown(Array $o){
			 if(!array_key_exists('id', $o) || !array_key_exists('label',$o) || empty($o['id']) || empty($o['label']) || !array_key_exists('options', $o))
				return $this->errorHandler("Dropdown options require at least ID, Label, and Options to work.", 1);
			
			$options = $o['options'];
			$tmpn = 0;
			$firstID = '';
			$opID = $this->prefix.$o['id'];
			$opVal = get_option($opID);
			$order = isset($o['order']) && $o['order']=='list' ? 'list' : '';
			//$multiple = isset($o['multiple']) && $o['multiple']==TRUE?'multiple="multiple"':NULL;
			
			$output = '<div id="option_'.$o['id'].'" class="option">';
			$output .= '<label for="jw_panel_options_'.$opID.'">'.$o['label'].'</label>';
			$output .='<select name="jw_panel[options]['.$opID.']" id="jw_panel_options_'.$opID.'">';
			
			foreach($options as $id=>$name){
				if($tmpn === 0){$firstID = $id;}
				
				if( empty($opVal)){
					$cur = (empty($o['default'])?$firstID:$o['default']); 
				}else{
					$cur = $opVal;
				}
				
				$output .= '<option value="'.$id.'" '.selected($cur, $id, false).'>'.$name.'</option>';
				$tmpn++;
			}
			
			$output .= '</select>';
			$output .= isset($o['desc']) && !empty($o['desc']) ? '<br /><small class="option_desc">'.$o['desc'].'</small>' : '';
			$output .= isset($o['tooltip']) && !empty($o['tooltip']) ? '<span class="tooltip_actuator"><div class="panel_tooltip" id="tooltip_'.$opID.'">'.$o['tooltip'].'</div></span>' : '';
			$output .= '</div>';
			return $output;
		 }
		
		/**
		 *	Builds checkbox option HTML
		 *
		 *	Required:
		 *		'id' 			- Used for get_option in conjunction with $this->prefix
		 *		'label'			- Used in the html
		 *		'options'		- The options for the radio buttons in an associative array.
		 *	Optional:
		 *		'desc'			- Default:NULL Short description about item.  Default:NULL
		 *		'required'		- Default:NULL Used for both PHP and AJAX
		 *		'tooltip'		- Default:NULL Used to show a flyout tooltip about the option.
		 *		'default'		- Default:NULL The default value, resorts to the first option.
		 *		'orientation' 	- Default:NULL Accepts RTL or LTR
		 *		'order'			- Default:Grid [grid||list] Rather to show a list or grid based layout.
		 *
		 *	@param array() $o Array of option data
		 *	@return string HTML of checkbox form elements
		 */
		function buildCheckOption(Array $o){
			if(!array_key_exists('id', $o) || !array_key_exists('label',$o) || empty($o['id']) || empty($o['label']) || !array_key_exists('options', $o))
				return $this->errorHandler("Checkbox options require at least ID, Label, and Options to work.", 1);
			
			$options = $o['options'];
			
			$output = '<div id="option_'.$o['id'].'" class="option">';
			$output .= '<label>'.$o['label'].'</label>';
			$output .= '<div class="jw_group_wrap">';
			
			$tmpn = 0;
			$firstID = '';
			$opID = $this->prefix.$o['id'];
			$opVal = get_option($opID);
			$order = isset($o['order']) && $o['order']=='list' ? 'list' : '';
			
			if($order == 'list'){$output .= '<ul>';}
			
			foreach ($options as $id => $name){
				
				if($order == 'list'){$output .= '<li>';}
				
				if($tmpn === 0){$firstID = $id;}
				
				$output .= '<label for="jw_panel_options_'.$opID.'_'.$id.'">';
				
				if( empty($opVal) && $o['required']==TRUE){
					$cur = (empty($o['default'])?$firstID:$o['default']); 
				}else{
					$cur = $opVal;
				}
				
				if(strtoupper($o['orientation']) == 'LTR'){
					$output .='<input type="checkbox" name="jw_panel[options]['.$opID.'][]" value="'.$id.'" id="jw_panel_options_'.$opID.'_'.$id.'" '. $this->jop_checked($cur,$id, false) .'> '.$name;
				}else{
					$output .= $name.' <input type="checkbox" name="jw_panel[options]['.$opID.'][]" value="'.$id.'" id="jw_panel_options_'.$opID.'_'.$id.'" '.$this->jop_checked($cur,$id, false).'> ';
				}
				$output .='</label>';
				
				if($order == 'list'){$output .= '</li>';}
				$tmpn++;
			}
			if($order == 'list'){$output .= '</ul>';}
			$output .= '</div>';
			$output .= isset($o['desc']) && !empty($o['desc']) ? '<br /><small class="option_desc">'.$o['desc'].'</small>' : '';
			$output .= isset($o['tooltip']) && !empty($o['tooltip']) ? '<span class="tooltip_actuator"><div class="panel_tooltip" id="tooltip_'.$opID.'">'.$o['tooltip'].'</div></span>' : '';
			$output .= '</div>';
			
			return $output;
		}
		
		/**
		 *	Builds radio option HTML
		 *
		 *	Required:
		 *		'id' 			- Used for get_option in conjunction with $this->prefix
		 *		'label'			- Used in the html
		 *		'options'		- The options for the radio buttons in an associative array.
		 *	Optional:
		 *		'desc'			- Default:NULL Short description about item.
		 *		'tooltip'		- Default:NULL Used to show a flyout tooltip about the option.
		 *		'default'		- A single key of the options array, defaults to the first key in the options array.
		 *							Since radios should be selected by default, there is no 'required' parameter, rather it's understood that
		 *							we will select the FIRST option in sequence.
		 *		'orientation' 	- Default: Null Accepts RTL or LTR
		 *		'order'			- Default:Grid [grid||list] Rather to show a list or grid based layout.
		 *
		 *	@param array() $o Array of option data
		 *	@return string HTML of checkbox form elements
		 */
		
		function buildRadioOption(Array $o){
			
			if(!array_key_exists('id', $o) || !array_key_exists('label',$o) || empty($o['id']) || empty($o['label']) || !array_key_exists('options', $o))
				return $this->errorHandler("Radio options require at least ID, Label, and Options to work.", 1);
			
			$options = $o['options'];
			
			$output = '<div id="option_'.$o['id'].'" class="option">';
			$output .= '<label>'.$o['label'].'</label>';
			$output .= '<div class="jw_group_wrap">';
			
			$tmpn = 0;
			$firstID = '';
			$opID = $this->prefix.$o['id'];
			$opVal = get_option($opID);
			$order = isset($o['order']) && $o['order']=='list' ? 'list' : '';
			
			if($order == 'list'){$output .= '<ul>';}
			
			foreach ($options as $id => $name){
				
				if($order == 'list'){$output .= '<li>';}
				
				if($tmpn === 0){$firstID = $id;}
				
				$output .= '<label for="jw_panel_options_'.$opID.'_'.$id.'">';
				
				if( empty($opVal)){
					$cur = (empty($o['default'])?$firstID:$o['default']); 
				}else{
					$cur = $opVal;
				}
				
				if(strtoupper($o['orientation']) == 'RTL'){
					$output .= $name.' <input type="radio" name="jw_panel[options]['.$opID.']" value="'.$id.'" id="jw_panel_options_'.$opID.'_'.$id.'" '.checked($cur,$id, false).'> ';
				}else{
					$output .='<input type="radio" name="jw_panel[options]['.$opID.']" value="'.$id.'" id="jw_panel_options_'.$opID.'_'.$id.'" '. checked($cur,$id, false) .'> '.$name;
				}
				$output .='</label>';
				
				if($order == 'list'){$output .= '</li>';}
				$tmpn++;
			}
			if($order == 'list'){$output .= '</ul>';}
			$output .= '</div>';
			$output .= isset($o['desc']) && !empty($o['desc']) ? '<br /><small class="option_desc">'.$o['desc'].'</small>' : '';
			$output .= isset($o['tooltip']) && !empty($o['tooltip']) ? '<span class="tooltip_actuator"><div class="panel_tooltip" id="tooltip_'.$opID.'">'.$o['tooltip'].'</div></span>' : '';
			$output .= '</div>';
			
			return $output;
		}
		
		/**
		 *	Builds textarea option HTML
		 *
		 *	Required:
		 *		'id' 			- Used for get_option in conjunction with $this->prefix
		 *		'label'			- Used in the html
		 *	Optional:
		 *		'desc'			- Default:NULL Short description about item.
		 *		'regEx'			- Default:NULL Regular Expression check used in both php and AJAX form processing
		 *		'required'		- Default:NULL Used for both PHP and AJAX
		 *		'tooltip'		- Default:NULL Used to show a flyout tooltip about the option.
		 *		'default'		- Default:NULL The default value.
		 *
		 *	@param array() $o Array of option data
		 *	@return string HTML of corresponding form element(s)
		 */
		function buildTextareaOption(Array $o){
			if(!array_key_exists('id', $o) || !array_key_exists('label',$o) || empty($o['id']) || empty($o['label']))
				return $this->errorHandler("Text areas require an ID and Label to function properly.", 1);
				
			$required = isset($o['required']) && !empty($o['required']) ? 'required' : '';
			
			$output = '<div id="option_'.$o['id'].'" class="option">';
			$output .='<label for="jw_panel_options_'.$this->prefix.$o['id'].'">'.$o['label'].':</label>';
			$output .='<textarea name="jw_panel[options]['.$this->prefix.$o['id'].']" id="jw_panel_options_'.$this->prefix.$o['id'].'" class="'.$required.'">'.get_option($this->prefix.$o['id'], $o['default']).'</textarea>';
			$output .= isset($o['desc']) && !empty($o['desc']) ? '<br /><small class="option_desc">'.$o['desc'].'</small>' : '';
			$output .= isset($o['tooltip']) && !empty($o['tooltip']) ? '<span class="tooltip_actuator"><div class="panel_tooltip" id="tooltip_'.$this->prefix.$o['id'].'">'.$o['tooltip'].'</div></span>' : '';
			$output .= '</div>';
			
			return $output;
		}
		
		/**
		 *	Builds text input option HTML
		 *
		 *	Required:
		 *		'id' 			- Used for get_option in conjunction with $this->prefix
		 *		'label'			- Used in the html
		 *	Optional:
		 *		'desc'			- Default:NULL Short description about item.
		 *		'regEx'			- Default:NULL Regular Expression check used in both php and AJAX form processing
		 *		'required'		- Default:NULL Used for both PHP and AJAX
		 *		'tooltip'		- Default:NULL Used to show a flyout tooltip about the option.
		 *		'default'		- Default:NULL The default value.
		 *
		 *	@param array() $o Array of option data
		 *	@return string HTML of corresponding form element(s)
		 */
		function buildTextOption(Array $o){
			
			if(!array_key_exists('id', $o) || !array_key_exists('label',$o) || empty($o['id']) || empty($o['label']))
				return $this->errorHandler("Text boxes require an ID and Label to function properly.", 1);
			
			$required = isset($o['required']) && !empty($o['required']) ? 'required' : '';
			
			$output = '<div id="option_'.$o['id'].'" class="option">';
			$output .='<label for="jw_panel_options_'.$this->prefix.$o['id'].'">'.$o['label'].':</label>';
			$output .='<input type="text" name="jw_panel[options]['.$this->prefix.$o['id'].']" value="'.get_option($this->prefix.$o['id'], $o['default']).'" id="jw_panel_options_'.$this->prefix.$o['id'].'" class="'.$required.'"/>';
			$output .= isset($o['desc']) && !empty($o['desc']) ? '<br /><small class="option_desc">'.$o['desc'].'</small>' : '';
			$output .= isset($o['tooltip']) && !empty($o['tooltip']) ? '<span class="tooltip_actuator"><div class="panel_tooltip" id="tooltip_'.$this->prefix.$o['id'].'">'.$o['tooltip'].'</div></span>' : '';
			$output .= '</div>';
			
			return $output;
			
		}
		
		/**
		 *	Builds HTML area.
		 *
		 *	Required:
		 *		'id' 			- Used for get_option in conjunction with $this->prefix
		 *		'desc'			- The HTML to display.  Un-Filtered.
		 *	Optional:
		 *		'label'			- Default:NULL Used to create a H3 title.
		 *
		 *	@param array() $o Array of option data
		 *	@return string HTML of corresponding form element(s)
		 */
		function buildHTMLOption(Array $o){
			// First check requireds.
			
			if(!array_key_exists('id', $o) || !array_key_exists('content',$o) || empty($o['id']) || empty($o['content']))
				return $this->errorHandler('Option type of "'.$o['type'].'" requires id &amp; content data to function.', 1);
			
			
			// Now we can build.
			$output =	'<div id="option_'.$o['id'].'" class="option">';
			$output	.=	$o['content'];
			$output .=	'</div>';
			
			return $output;
		}
		
	}// END JW_Options class
	
}// END If class exists...

?>