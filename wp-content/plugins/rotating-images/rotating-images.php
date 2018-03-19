<?php
/**
 *
 * Plugin Name: Rotating Images
 * Plugin URI: http://ithemes.com/purchase/displaybuddy
 * Description: DisplayBuddy Series - Rotate images using transitions, such as fade or slide, or static random image on page load.
 * Version: 1.3.7
 * Author: iThemes
 * Author URI: http://ithemes.com/
 * iThemes Package: rotating-images
 */

/*
Written by Chris Jean for iThemes.com
Extended by Dustin Bolton

Version History
	See history.txt
*/


if ( ! class_exists( 'iThemesRotatingImages' ) ) {
	class iThemesRotatingImages {
		var $_var = 'ithemes-rotating-images';
		var $_name = 'Rotating Images';
		var $_title = 'Rotating Images';
		var $_wp_minimum = '3.2.1';
		var $_series = 'DisplayBuddy';
		var $_page = 'ithemes-rotating-images';
		var $_groupID;
		
		var $_defaults = array(
			'width'					=> '100',
			'height'				=> '100',
			'enable_responsive'     => '0',
			'sleep'					=> '2',
			'fade'					=> '1',
			'fade_sort'				=> 'ordered',
			'fade_sort'				=> 'ordered',
			'enable_fade'				=> '1',
			'link'					=> '',
			'open_new_window'			=> '',
			'enable_overlay'			=> '0',
			'enable_slide'				=> '0',
			'double_fade'				=> '0',
			'overlay_text_alignment'		=> 'center',
			'overlay_text_vertical_position'	=> 'middle',
			'overlay_text_padding'			=> '10',
			'overlay_header_text'			=> '',
			'overlay_header_size'			=> '36',
			'overlay_header_color'			=> '#FFFFFF',
			'overlay_subheader_text'		=> '',
			'overlay_subheader_size'		=> '18',
			'overlay_subheader_color'		=> '#FFFFFF',
			'images'                        => array(),
			'variable_width'			=> true,
			'variable_height'			=> true,
			'force_disable_overlay'			=> false,
			'groups'				=> array(),
		);
		
		var $_options = array();
		var $_optionsupdater = array();
		var $_groups = array(); // All group options.
		
		var $_class = '';
		var $_initialized = false;
		
		var $_usedInputs = array();
		var $_selectedVars = array();
		var $_pluginPath = '';
		var $_pluginRelativePath = '';
		var $_pluginURL = '';
		var $_pageRef = '';
		
		var $_instanceCount = 0; // Counter for widget numbering for jquery.
		
		function __construct() {
			$this->_pluginPath = dirname( __FILE__ );
			$this->_pluginRelativePath = ltrim( str_replace( '\\', '/', str_replace( rtrim( ABSPATH, '\\\/' ), '', $this->_pluginPath ) ), '\\\/' );
			$this->_pluginURL = site_url() . '/' . $this->_pluginRelativePath;
			if ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' ) { $this->_pluginURL = str_replace( 'http://', 'https://', $this->_pluginURL ); }
			$selflinkvar = explode( '?', $_SERVER['REQUEST_URI'] );
			$this->_selfLink = array_shift( $selflinkvar ) . '?page=' . $this->_var;
			
			
			$this->_defaults['link'] = get_option( 'home' );
			$this->_defaults['overlay_header_text'] = get_bloginfo( 'name' );
			$this->_defaults['overlay_subheader_text'] = get_bloginfo( 'description' );
			
			$this->_defaults = apply_filters( 'it_rotating_images_options', $this->_defaults );
			
			
			$this->_setVars();
			
			// Only run admin backend if on admin page for this plugin or non-admin page below...
			if ( is_admin() && isset( $_GET['page'] ) && ( $_GET['page'] === $this->_page ) ) {
				add_action( 'admin_init', array( &$this, 'init' ) );
			} else {
				add_action( 'template_redirect', array( &$this, 'init' ) ); // non-admin page.
				add_shortcode('it-rotate', array( &$this, 'shortcode' ) );
			}
			
			add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
			add_action( 'ithemes_rotating_images_fade_images', array( &$this, 'fadeImages' ), 10, 2 );
			
			if ( is_admin() ) {
			add_action( 'admin_enqueue_scripts', array( &$this, 'run_rotate_ajax_script' ) );
			add_action( 'wp_ajax_it_edit_rotate_group', array( &$this, 'it_edit_rotate_group' ) );
			add_action( 'wp_ajax_nopriv_it_edit_rotate_group', array( &$this,'it_edit_rotate_group' ) );
			add_action('wp_ajax_it_medialibrary_extract_id', array( &$this, 'it_medialibrary_extract_id' ) );
			add_action('wp_ajax_nopriv_it_medialibrary_extract_id', array( &$this, 'it_medialibrary_extract_id' ) );
				require_once( $this->_pluginPath . '/lib/medialibrary/load.php' );
				add_action( 'init', array( &$this, 'init_medialibrary' ) );
				register_activation_hook(__FILE__, array(&$this, '_activate'));
			}
		}
		
		function init_medialibrary() {
			global $wp_version;

			// Check for Wordpress Version for media library.
			if ( version_compare( $wp_version, $this->_wp_minimum, '<=' ) ) {
				$media_lib_version =  array(
						'select_button_text'			=>			'Select this Image',
						'tabs'							=>			array( 'pb_uploader' => 'Upload Images to Media Library', 'library' => 'Select from Media Library' ),
						'show_input-image_alt_text'		=>			false,
						'show_input-url'				=>			false,
						'show_input-image_align'		=>			false,
						'show_input-image_size'			=>			false,
						'show_input-description'		=>			true,
						'custom_help-caption'			=>			'Overlaying text to be displayed if captions are enabled.',
						'custom_help-description'		=>			'Optional URL for this image to link to.',
						'custom_label-description'		=>			'Link URL',
						'use_textarea-caption'			=>			true,
						'use_textarea-description'		=>			false,
					);
			} else {
				$media_lib_version =  array(
						'select_button_text'			=>			'Select this Image',
						'tabs'							=>			array( 'type' => 'Upload Images to Media Library', 'library' => 'Select from Media Library' ),
						'show_input-image_alt_text'		=>			false,
						'show_input-url'				=>			false,
						'show_input-image_align'		=>			false,
						'show_input-image_size'			=>			false,
						'show_input-description'		=>			true,
						'custom_help-caption'			=>			'Overlaying text to be displayed if captions are enabled.',
						'custom_help-description'		=>			'Optional URL for this image to link to.',
						'custom_label-description'		=>			'Link URL',
						'use_textarea-caption'			=>			true,
						'use_textarea-description'		=>			false,
					);
			}
			$this->_medialibrary = new IT_Media_Library( $this, $media_lib_version );
		}

		function add_media_gallery_strings( $strings ) { 
            $strings['itMediaLibraryAddImageTitle'] = __( 'Add an Image', 'it-l10n-rotating-images' );
            $strings['setITMediaLibraryAddImage']   = __( 'Add image', 'it-l10n-rotating-images' );
            return $strings;
        } 

		// REMOVE THIS EVENTUALLY - migrates from 0.1.32 to newer.
		function _activate() {
			$old_ver = get_option('ithemes_rotating_images');
			//echo'<i>Migrated old version of PluginBuddy Rotating Images to new.</i>';
			if ( is_array( $old_ver) ) {
				add_option($this->_var, $old_ver, '', 'no'); // No autoload.
				update_option($this->_var, $old_ver);
				delete_option('ithemes_rotating_images');
			}
		}
		// END REMOVE
			
			
		function init() {
			$this->load();
		}
		
		function shortcode($atts) {
			extract(shortcode_atts(array(
				'group' => '0'
			), $atts));
			return $this->fadeImages($atts['group'],false);
		}

		function run_rotate_ajax_script() { 

			$it_nonce = wp_create_nonce('it_rotate_ajax_nonce');

			if (is_user_logged_in()) { 
				wp_enqueue_script( 'it-edit-rotate-group-ajax', $this->_pluginURL . '/js/rotate-group-ajax.js' );	
				wp_localize_script( 'it-edit-rotate-group-ajax', 'ajax_object', array( 'it_ajax_url' => admin_url( 'admin-ajax.php' ), 'it_ajax_nonce' => $it_nonce  ) );
			}

		}
		
		function addPages() {
			global $wp_theme_name, $wp_theme_page_name;
			
			//add_menu_page('Rotating Images', $this->_name, 'administrator', $this->_name, array( &$this, 'index' ), $this->_pluginURL.'/images/pluginbuddy.png');
			/*
			if ( ! empty( $wp_theme_page_name ) )
				$this->_pageRef = add_submenu_page( $wp_theme_page_name, $this->_name, 'Rotating Images', 'edit_themes', $this->_page, array( &$this, 'index' ) );
			else
				$this->_pageRef = add_theme_page( $wp_theme_name . ' ' . $this->_name, $wp_theme_name . ' ' . $this->_name, 'edit_themes', $this->_page, array( &$this, 'index' ) );
			*/
			add_action( 'admin_print_scripts-' . $this->_pageRef, array( $this, 'addAdminScripts' ) );
			add_action( 'admin_print_styles-' . $this->_pageRef, array( $this, 'addAdminStyles' ) );
		}
		
		function admin_menu() {
			// Handle series menu. Create series menu if it does not exist.
			global $menu;
			$found_series = false;
			foreach ( $menu as $menus => $item ) {
				if ( $item[2] == 'pluginbuddy-' . strtolower( $this->_series ) ) {
					$found_series = true;
				}
			}
			if ( $found_series === false ) {
				add_menu_page( $this->_series . ' Getting Started', $this->_series, apply_filters( 'rotatingimages_capability', 'switch_themes' ), 'pluginbuddy-' . strtolower( $this->_series ), array(&$this, 'view_gettingstarted'), $this->_pluginURL.'/images/displaybuddy16.png' );
				add_submenu_page( 'pluginbuddy-' . strtolower( $this->_series ), $this->_name.' Getting Started', 'Getting Started', apply_filters( 'rotatingimages_capability', 'switch_themes' ), 'pluginbuddy-' . strtolower( $this->_series ), array(&$this, 'view_gettingstarted') );
			}
			// Register for getting started page
			global $pluginbuddy_series;
			if ( !isset( $pluginbuddy_series[ $this->_series ] ) ) {
				$pluginbuddy_series[ $this->_series ] = array();
			}
			$pluginbuddy_series[ $this->_series ][ $this->_name ] = $this->_pluginPath;
			
			add_submenu_page( 'pluginbuddy-' . strtolower( $this->_series ), $this->_name, $this->_name, apply_filters( 'rotatingimages_capability', 'switch_themes' ), $this->_var, array(&$this, 'index'));
		}

		function josh_print( $print_this ) { 
			if ( isset( $print_this ) ) {
				echo '<pre>' . print_r( $print_this, true ) . '</pre>';
			} else {
				echo 'The data you are trying to print is not currently set.';
			}
		}
		
		function view_gettingstarted() {
			echo '<link rel="stylesheet" href="' . $this->_pluginURL . '/css/admin.css" type="text/css" media="all" />';
			require('classes/view_gettingstarted.php');
		}
		function admin_scripts() {
			$this->addAdminStyles();
			$this->addAdminScripts();
		}
		function get_feed( $feed, $limit, $append = '', $replace = '' ) {
			require_once(ABSPATH.WPINC.'/feed.php');
			$rss = fetch_feed( $feed );
			if (!is_wp_error( $rss ) ) {
				$maxitems = $rss->get_item_quantity( $limit ); // Limit 
				$rss_items = $rss->get_items(0, $maxitems); 
				
				echo '<ul class="pluginbuddy-nodecor">';

				$feed_html = get_transient( md5( $feed ) );
				if ( $feed_html == '' ) {
					foreach ( (array) $rss_items as $item ) {
						$feed_html .= '<li>- <a href="' . $item->get_permalink() . '">';
						$title =  $item->get_title(); //, ENT_NOQUOTES, 'UTF-8');
						if ( $replace != '' ) {
							$title = str_replace( $replace, '', $title );
						}
						if ( strlen( $title ) < 30 ) {
							$feed_html .= $title;
						} else {
							$feed_html .= substr( $title, 0, 32 ) . ' ...';
						}
						$feed_html .= '</a></li>';
					}
					set_transient( md5( $feed ), $feed_html, 300 ); // expires in 300secs aka 5min
				}
				echo $feed_html;
				
				echo $append;
				echo '</ul>';
			} else {
				echo 'Temporarily unable to load feed...';
			}
		}

		function it_edit_rotate_group() { 
			$unserialized = maybe_unserialize( $this->_POST( 'encoded_data' ) );

			if ( is_string( $unserialized ) ) {
				$unserialized = json_decode( $unserialized );
				$unserialized = get_object_vars($unserialized[0]);

			}
			$this->load();
			$edit_group = $this->_options['groups'][$unserialized[0]];

			echo json_encode(array(
				'id'             => $unserialized[0],
				'responsive_op'  => $edit_group['options']['enable_responsive'],
				'width'          => $edit_group['options']['width'],
				'height'         => $edit_group['options']['height'],
				'enable_overlay' => $edit_group['options']['enable_overlay'],
				'fade'           => $edit_group['options']['enable_fade'],
				'name'           => $edit_group['name'],
			));

			die();
		}
		

		
		function addAdminStyles() {
			wp_enqueue_style( 'thickbox' );
			
			wp_enqueue_style( $this->_var . '-rotating-images', $this->_pluginURL . '/css/admin-style.css' );
		}
		
		function addAdminScripts() {
			global $wp_scripts;
			$queue = array();
			
			foreach ( (array) $wp_scripts->queue as $item )
				if ( ! in_array( $item, array( 'page', 'editor', 'editor_functions', 'tiny_mce', 'media-upload', 'post' ) ) )
					$queue[] = $item;
			
			$wp_scripts->queue = $queue;
			
			
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'thickbox' );
			wp_enqueue_script( $this->_var . '-prototype', $this->_pluginURL . '/js/prototype.js' );
			wp_print_scripts( $this->_var . '-prototype' );
			wp_enqueue_script( $this->_var . '-color-methods', $this->_pluginURL . '/js/colorpicker/ColorMethods.js' );
			wp_print_scripts( $this->_var . '-color-methods' );
			wp_enqueue_script( $this->_var . '-color-value-picker', $this->_pluginURL . '/js/colorpicker/ColorValuePicker.js' );
			wp_print_scripts( $this->_var . '-color-value-picker' );
			wp_enqueue_script( $this->_var . '-slider', $this->_pluginURL . '/js/colorpicker/Slider.js' );
			wp_print_scripts( $this->_var . '-slider' );
			wp_enqueue_script( $this->_var . '-color-picker', $this->_pluginURL . '/js/colorpicker/ColorPicker.js' );
			wp_print_scripts( $this->_var . '-color-picker' );
			wp_enqueue_script( $this->_var . '-it-tooltip', $this->_pluginURL . '/js/tooltip.js' );
			wp_print_scripts( $this->_var . '-it-tooltip' );
			wp_enqueue_script( $this->_var . '-toolkit', $this->_pluginURL . '/js/javascript-toolbox-toolkit.js' );
			wp_print_scripts( $this->_var . '-toolkit' );
			if ( isset( $_GET['group_id'] ) ) { // Only show when viewing a group to avoid errors.
				wp_enqueue_script( $this->_var . '-rotating-images', $this->_pluginURL . '/js/admin-rotating-images.js' );
				wp_print_scripts( $this->_var . '-rotating-images');
				wp_enqueue_script( $this->_var . '-reorder-js', $this->_pluginURL . '/js/tablednd.js' );
				wp_print_scripts( $this->_var . '-reorder-js' );
			}
			wp_enqueue_script( $this->_var . '-adminjs', $this->_pluginURL . '/js/admin.js' );
			wp_print_scripts( $this->_var . '-adminjs' );
		}
		
		function _setVars() {
			$this->_class = get_class( $this );
			
			$this->_pluginPath = dirname( __FILE__ );
			$this->_pluginRelativePath = ltrim( str_replace( '\\', '/', str_replace( rtrim( ABSPATH, '\\\/' ), '', $this->_pluginPath ) ), '\\\/' );
			$this->_pluginURL = get_option( 'siteurl' ) . '/' . $this->_pluginRelativePath;
			if ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' ) {
				$this->_pluginURL = str_replace( 'http://', 'https://', $this->_pluginURL );
			}
			$selflinkvar = explode( '?', $_SERVER['REQUEST_URI'] );
			$this->_selfLink = array_shift( $selflinkvar ) . '?page=' . $this->_page;
		}
		
		
		// Options Storage ////////////////////////////
		
		function save() {
			// This is re-added later in this function.
			$options_updater = $this->_options['updater'];
			unset( $this->_options['updater'] );
			
			// Copy array of groups under options into groups array
			if ( isset( $this->_options['groups'] ) )
				$this->_groups['groups'] = $this->_options['groups'];
			
			if ( isset( $this->_groupID ) ) { // Saving within a group to copy current settings into groups array
				$this->_groups['groups'][$this->_groupID]['options']=$this->_options; // Copy current settings into proper groups array position
				if ( is_array($this->_groups['groups'][$this->_groupID]['options']['groups']) ) {
					unset($this->_groups['groups'][$this->_groupID]['options']['groups']); // clear temporary groups holder
				}
				// Moved the following into array checker.
				//unset($this->_groups['groups'][$this->_groupID]['options']['groups']);
			}
			
			$this->_groups['updater'] = $options_updater;
			
			add_option($this->_var, $this->_groups, '', 'no'); // No autoload.
			update_option($this->_var, $this->_groups);
			
			$this->_options['updater'] = $this->_groups['updater'];
			unset( $this->_groups['updater'] );
			
			return true;
		}
		
		function load() {
			$temp_options = get_option($this->_var);
			
			
			$options_updater = $temp_options['updater'];
			unset( $temp_options['updater'] );
			
			if (isset($_REQUEST['group_id'])) {  // Set group ID if passed via querystring
				$this->_groupID = (int) $_REQUEST['group_id']; // assign current group id number into variable
			}
			
			$errorcount = 0; 
			
			if ( isset( $this->_groupID ) && isset( $temp_options['groups'][$this->_groupID]['options'] ) ) { // Load settings for within a group.
				$this->_options=$temp_options['groups'][$this->_groupID]['options']; // Load group settings into options.
				
				$this->_options['sleep'] = floatval( $this->_options['sleep'] );
				$this->_options['fade'] = floatval( $this->_options['fade'] );
				
				if ( $this->_options['sleep'] <= 0 )
					$this->_options['sleep'] = $this->_defaults['sleep'];
				if ( $this->_options['fade'] <= 0 )
					$this->_options['fade'] = $this->_defaults['fade'];
				if ( empty( $this->_options['fade_sort'] ) )
					$this->_options['fade_sort'] = 'ordered';
				
				foreach ( array( 'width', 'height', 'sleep', 'fade' ) as $option ) {
					if ( ! is_numeric( $this->_defaults[$option] ) )
						$this->_options[$option] = $GLOBALS[$this->_defaults[$option]];
					else if ( ( empty( $this->_options[$option] ) ) && ( '0' !== $this->_options[$option] ) )
						$this->_options[$option] = $this->_defaults[$option];
				}
				if ( empty( $this->_options['image_ids'] ) ) {
					if ( (! isset($_GET['group_id']) ) && ( ! isset($_POST['add_group']) ) ) { // Do not display warning in admin or group creation.
						$this->_errors[] = 'Warning: Empty Rotating Images Group! Upload images for this widget to function.';
						$errorcount = 1;
					}
				} else if ( ! is_array( reset( $this->_options['image_ids'] ) ) ) {
					$entries = array();
					
					$order = 1;
					
					foreach ( (array) $this->_options['image_ids'] as $id ) {
						$entry = array();
						$entry['attachment_id'] = $id;
						$entry['url'] = '';
						$entry['order'] = $order;
						
						$entries[] = $entry;
						
						$order++;
					}
					
					$this->_options['image_ids'] = $entries;
				}
				
			/*	if ( ( false === $this->_defaults['variable_height'] ) && is_numeric( $this->_defaults['height'] ) )
					$this->_options['height'] = $this->_defaults['height'];
				if ( ( false === $this->_defaults['variable_width'] ) && is_numeric( $this->_defaults['width'] ) )
					$this->_options['width'] = $this->_defaults['width']; */
			}
			
			
			if ( isset( $temp_options['groups'] ) ) {
				$this->_options['groups']=$temp_options['groups']; // Load all group names into options variable.
			}
			$this->_options['updater'] = $options_updater;

		}
		
		
		// Pages //////////////////////////////////////
		
		function index() {
			$action = ( isset( $_REQUEST['action'] ) ) ? $_REQUEST['action'] : '';
			
			if ( 'save' === $action )
				$this->saveForm();
			else if ( 'save_image' === $action )
				$this->saveImage();
			else if ( ! empty( $_POST['save_entry_order'] ) )
				$this->saveOrder();
			else if ( 'upload' === $action )
				$this->_uploadImage();
			else if ( ! empty( $_REQUEST['delete_images'] ) )
				$this->_deleteImages();
			else {
				if ( ! empty( $_POST['add_group'] ) )
					$this->_groupsCreate();
				elseif ( ! empty( $_POST['delete_group'] ) )
					$this->_groupsDelete();
				elseif ( ! empty( $_POST['edit_group'] ) )
					$this->_groupedit();
			}
			if ( !isset( $_GET['image_id'] ) ) {
			$this->admin_scripts();
			}
			$this->_showForm();
		}

		function _groupedit() { 
			$edit_name = (string) $_POST[$this->_var . '-name'];
			$edit_width = htmlentities( $_POST[$this->_var . '-width'] );
			$edit_height = htmlentities( $_POST[$this->_var . '-height'] );
			if ( ! empty( $_POST[$this->_var . '-enable_responsive'] ) ) { 
				$edit_responsive_op = htmlentities( $_POST[$this->_var . '-enable_responsive'] );
			} else { 
				$edit_responsive_op = 0;
			}
			if ( ! empty( $_POST[$this->_var . '-enable_overlay'] ) ) { 
				$edit_enable_overlay = htmlentities( $_POST[$this->_var . '-enable_overlay'] );
			} else { 
				$edit_enable_overlay = 0;
			}
			if ( ! empty( $_POST[$this->_var . '-enable_fade'] ) ) { 
				$edit_enable_fade = htmlentities( $_POST[$this->_var . '-enable_fade'] );
			} else {
				$edit_enable_fade = 0;
			}

			$edit_id = $_POST['it_group_id'];

			if ( empty( $edit_name ) ) {
				$this->_errors[] = 'name';
				$this->_showErrorMessage( 'A group name is required to edit existing group settings.' );
			}
			if ( empty( $edit_width ) ) {
				$this->_errors[] = 'width';
				$this->_showErrorMessage( 'A width is required to edit existing group settings.' );
			}
			if ( empty( $edit_height ) ) {
				$this->_errors[] = 'height';
				$this->_showErrorMessage( 'A height is required to edit existing group settings.' );
			}

			if ( isset( $this->_errors ) )
				$this->_showErrorMessage( 'Please correct the ' . _n( 'error', 'errors', count( $this->_errors ) ) . ' in order to edit the existing Group' );
			else {

				$this->_options['groups'][$edit_id]['name'] = $edit_name;
				$this->_options['groups'][$edit_id]['options']['width'] = $edit_width;
				$this->_options['groups'][$edit_id]['options']['height'] = $edit_height;
				$this->_options['groups'][$edit_id]['options']['enable_responsive'] = $edit_responsive_op;
				$this->_options['groups'][$edit_id]['options']['enable_fade'] = $edit_enable_fade;
				$this->_options['groups'][$edit_id]['options']['enable_overlay'] = $edit_enable_overlay;
				

				$this->save();
				$this->load(); // Temporary fix for defaults not showing until refresh. - Dustin
				$updated_name = $this->_options['groups'][$edit_id]['name'];
				$this->_showStatusMessage( "Rotating Image Group \"$updated_name\" updated" );



			}

		}
		function _groupsCreate() {
			$name = (string) $_POST[$this->_var . '-name'];
			$width = htmlentities( $_POST[$this->_var . '-width'] );
			$height = htmlentities( $_POST[$this->_var . '-height'] );
			if ( ! empty( $_POST[$this->_var . '-enable_responsive'] ) ) {
				$responsive_op = htmlentities( $_POST[$this->_var . '-enable_responsive'] );
			} else {
				$responsive_op = 0;
			}
			if ( ! empty( $_POST[$this->_var . '-enable_overlay'] ) ) {
				$enable_overlay = htmlentities( $_POST[$this->_var . '-enable_overlay'] );
			} else { 
				$enable_overlay = 0;
			}
			if ( ! empty( $_POST[$this->_var . '-enable_fade'] ) ) { 
				$enable_fade = htmlentities( $_POST[$this->_var . '-enable_fade'] );
			} else { 
				$enable_fade = 0;
			}
			
			

			if ( empty( $name ) ) {
				$this->_errors[] = 'name';
				$this->_showErrorMessage( 'A Name is required to create a new Image Group' );
			}
			if ( empty( $width ) ) {
				$this->_errors[] = 'width';
				$this->_showErrorMessage( 'A width is required to create a new Image Group' );
			}
			if ( empty( $height ) ) {
				$this->_errors[] = 'height';
				$this->_showErrorMessage( 'A height is required to create a new Image Group' );
			}
			elseif ( is_array( $this->_options['groups'] ) ) {
				foreach ( (array) $this->_options['groups'] as $id => $group ) {
					if ( $group['name'] == $name ) {
						$this->_errors[] = 'name';
						$this->_showErrorMessage( 'An Image Group with that Name already exists' );
						
						break;
					}
				}
			}
			if ( isset( $this->_errors ) )
				$this->_showErrorMessage( 'Please correct the ' . _n( 'error', 'errors', count( $this->_errors ) ) . ' in order to add the new Image Group' );
			else {
				$group = array();
				
				$group['name'] = $name;
				//$group['entries'] = array();
				
				if ( is_array( $this->_options['groups'] ) && ! empty( $this->_options['groups'] ) )
					$newID = max( array_keys( $this->_options['groups'] ) ) + 1;
				else
					$newID = 0;
				
				$this->_options['groups'][$newID]['name'] = $name; // Set name.


				$this->_groups=$this->_options['groups']; // Copy existing groups so won't be overwritten by defaults.
				
				$this->_groupID=$newID;
				
				$options_updater = $this->_options['updater'];
				$this->_options=$this->_defaults; // Load defaults into current settings.
				$this->_options['updater'] = $options_updater;
				
				$this->_options['groups']=$this->_groups; // Restore other groups.
				$this->_options['width'] = $width;
				$this->_options['height'] = $height;
				$this->_options['enable_responsive'] = $responsive_op;
				$this->_options['enable_overlay'] = $enable_overlay;
				$this->_options['enable_fade'] = $enable_fade;
				$this->save();
				$this->load(); // Temporary fix for defaults not showing until refresh. - Dustin
				$this->_showStatusMessage( "Rotating Image Group \"$name\" added" );
			}
		}
		
		function saveForm() {
			check_admin_referer( $this->_var . '-nonce' );

			foreach ( (array) explode( ',', $_POST['used-inputs'] ) as $name ) {
				$is_array = ( preg_match( '/\[\]$/', $name ) ) ? true : false;
				
				$name = str_replace( '[]', '', $name );
				$var_name = preg_replace( '/^' . $this->_var . '-/', '', $name );
				
				if ( $is_array && empty( $_POST[$name] ) )
					$_POST[$name] = array();
				
				if ( isset( $_POST[$name] ) && ! is_array( $_POST[$name] ) )
					$this->_options[$var_name] = stripslashes( $_POST[$name] );
				else if ( isset( $_POST[$name] ) )
					$this->_options[$var_name] = $_POST[$name];
				else
					$this->_options[$var_name] = '';
			}
			
			$errorCount = 0;
			
			if ( ( $this->_options['sleep'] != floatval( $this->_options['sleep'] ) ) || ( floatval( $this->_options['sleep'] ) <= 0 ) )
				$errorCount++;
			if ( ( $this->_options['fade'] != floatval( $this->_options['fade'] ) ) || ( floatval( $this->_options['fade'] ) <= 0 ) )
				$errorCount++;
		//	if ( ( $this->_options['height'] != intval( $this->_options['height'] ) ) || ( intval( $this->_options['height'] ) < 0 ) )
				//$errorCount++;
			
			if ( $errorCount < 1 ) {
				$this->_options['sleep'] = floatval( $this->_options['sleep'] );
				$this->_options['fade'] = floatval( $this->_options['fade'] );
				
				if ( $this->_options['sleep'] <= 0 )
					$this->_options['sleep'] = $this->_defaults['sleep'];
				if ( $this->_options['fade'] <= 0 )
					$this->_options['fade'] = $this->_defaults['fade'];
				if ( empty( $this->_options['fade_sort'] ) )
					$this->_options['fade_sort'] = 'ordered';
				
				foreach ( array( 'sleep', 'fade' ) as $option )
					if ( ! is_numeric( $this->_defaults[$option] ) )
						$this->_options[$option] = $GLOBALS[$this->_defaults[$option]];
					elseif ( ( empty( $this->_options[$option] ) ) && ( '0' !== $this->_options[$option] ) )
						$this->_options[$option] = $this->_defaults[$option];
				
				if ( $this->save() )
					$this->_showStatusMessage( __( 'Settings updated', $this->_var ) );
				else
					$this->_showErrorMessage( __( 'Error while updating settings', $this->_var ) );
			}
			else {
				$this->_showErrorMessage( __( 'The fade options timing values must be numeric values greater than 0.', $this->_var ) );
				
				$this->_showErrorMessage( _n( 'Please fix the input marked in red below.', 'Please fix the inputs marked in red below.', $errorCount ) );
			}
		}
		
		function saveOrder() {
			check_admin_referer( $this->_var . '-nonce' );
			$i =0;
			foreach ( (array) $_POST as $var => $value ) {
				if ( preg_match( '/^' . $this->_var . '-entry-order-(\d+)$/', $var, $matches ) ) {
					$image_id = $matches[1];

					$this->_options['image_ids'][$image_id]['order'] = $i;
					$i++;
				}
			}
			
			$this->_options['max_order'] = 500; // Reset
			
			$this->save();
			
			
			$this->_showStatusMessage( 'Successfully updated the entry order' );
		}
		
		function saveImage() {
			check_admin_referer( $this->_var . '-nonce' );
			$attachment_id = $this->_POST('attachment_id');
			if ( ! isset( $_POST['attachment_id'] ) )
				$this->_errors[] = 'You must use the add image button to select an image to upload.';
			else if ( empty( $attachment_id ) ) {
				$attachment_id = $this->_options['image_ids'][$_POST['image_id']]['attachment_id'];
			}
			
			if ( ! empty( $this->_errors ) ) {
				$this->_attachment_id = $attachment_id;
				return;
			}
			
			if ( !empty( $this->_options['max_order'] ) ) {
				$this->_options['max_order'] = $this->_options['max_order'] + 1;
			} else {
				$this->_options['max_order'] = 500;
			}
			
			$entry = array();
			$entry['attachment_id'] = $attachment_id;
			$entry['url'] = $_POST[$this->_var . '-url'];
			$entry['order'] = $this->_options['max_order'];
			if ( isset( $_POST['image_id'] ) && is_array( $this->_options['image_ids'][$_POST['image_id']] ) ) {
				$this->_options['image_ids'][$_POST['image_id']] = $entry;
			} else {
				$this->_options['image_ids'][] = $entry;
			}
			$this->save();
			
			if ( isset( $_POST['image_id'] ) ) {
				$this->_showStatusMessage( 'Updated Image Settings' );
				unset( $_POST['image_id'] );
				unset( $_REQUEST['image_id'] );
			}
			else
				$this->_showStatusMessage( 'Added New Image' );
			
			unset( $_POST['action'] );
			unset( $_REQUEST['action'] );
		}
		
		function _deleteImages() {
			check_admin_referer( $this->_var . '-nonce' );
			
			require_once( $this->_pluginPath . '/lib/file-utility/file-utility.php' );
			
			$names = array();
			
			if ( ! empty( $_POST['entries'] ) && is_array( $_POST['entries'] ) ) {
				foreach ( (array) $_POST['entries'] as $id ) {
					$file_name = basename( get_attached_file( $this->_options['image_ids'][$id]['attachment_id'] ) );
					$names[] = $file_name;
					
					//iThemesFileUtility::delete_file_attachment( $this->_options['image_ids'][$id]['attachment_id'] );*/
					
					if (isset($this->_options['image_ids'][$id])) {
						unset( $this->_options['image_ids'][$id] );
					}
				}
			}
			
			natcasesort( $names );
			
			if ( ! empty( $names ) ) {
				$this->save();
				$this->_showStatusMessage( 'Successfully deleted the following ' . _n( 'image', 'images', count( $names ) ) . ': ' . implode( ', ', $names ) );
			}
			else
				$this->_showErrorMessage( 'No entries were selected for deletion' );
		}
		
		function _POST( $value = null ) {
			if ( isset( $_POST[$value] ) | ( $value === null ) ) {
				if ( $value === null ) { // Requesting $_POST variable.
					return stripslashes_deep( $_POST );
				} else {
					return stripslashes_deep( $_POST[$value] ); // Remove WordPress' magic-quotes style escaping of data. *shakes head*
				}
			} else {
				return '';
			}
		} // End _POST().
		
		function it_medialibrary_extract_id() {
			$unserialized = maybe_unserialize( $this->_POST( 'encoded_data' ) ); // Unserialize
			
			if ( is_string( $unserialized ) ) {
				$unserialized = json_decode( $unserialized );
				$unserialized = get_object_vars($unserialized[0]);
			}

			$image_data = wp_get_attachment_image_src( $unserialized['attachment_id'], 'thumbnail' ); // Grab thumbnail URL info.
			echo json_encode(array(
						'url' => $image_data[0],
						'id'  => $unserialized['attachment_id'],
					));
			die();
		}
		
		function _showForm() {
		
			if ( isset( $this->_addedAnimatedFile ) && ( true === $this->_addedAnimatedFile ) )
				$this->_showStatusMessage( 'An animated image was just uploaded. It may take a moment for this screen to fully render as the animation is resized.' );
			
	
	if ( isset( $_REQUEST['group_id'] ) && empty( $_REQUEST['cancelsave_group'] ) ) { // dustin
		
		if ( empty( $this->_options['height'] ) ) {
			echo 'WARNING: You must set a valid image width and height.';
		}
		
		$ratio = $this->_options['width'] / $this->_options['height'];
		
		$thumb_height = $thumb_width = 100;
		
		if ( $ratio > 1 )
			$thumb_height = intval( 100 / ( $this->_options['width'] ) * $this->_options['height'] );
		else
			$thumb_width = intval( 100 / ( $this->_options['height'] ) * $this->_options['width'] );
		
		
		require_once( $this->_pluginPath . '/lib/file-utility/file-utility.php' );
			
		if ( ! isset( $this->_errors ) && ! isset( $_REQUEST['image_id'] ) && ( ! isset( $_REQUEST['action'] ) || ( 'save_image' !== $_REQUEST['action'] ) ) ) : ?>
			<div class="wrap">
				<form id="posts-filter" enctype="multipart/form-data" method="post" action="<?php echo $this->_selfLink; ?>&group_id=<?php echo $this->_groupID; ?>">
					<?php wp_nonce_field( $this->_var . '-nonce' ); ?>
					
					<h2>Rotating Images in Group (<a href="<?php echo $this->_selfLink; ?>">group list</a>)</h2>
					
					<?php if ( isset( $this->_options['image_ids'] ) && ( count( $this->_options['image_ids'] ) > 0 ) ) : ?>
						<div class="tablenav">
							<div class="alignleft actions">
								<?php $this->_addSubmit( 'delete_images', array( 'value' => 'Delete', 'class' => 'button-secondary delete' ) ); ?>
							</div>

							
							<br class="clear" />
						</div>
						
						<br class="clear" />
						
						<table class="widefat">
							<thead>
								<tr class="thead">
									<th scope="col" class="check-column"><input type="checkbox" class="check-all-entries" /></th>
									<th>Image</th>
									<th>File Name</th>
									<th>Link</th>
									<th class="num">Reorder</th>
								</tr>
							</thead>
							<tfoot>
								<tr class="thead">
									<th scope="col" class="check-column"><input type="checkbox" class="check-all-entries" /></th>
									<th>Image</th>
									<th>File Name</th>
									<th>Link</th>
									<th class="num">Reorder</th>
								</tr>
							</tfoot>
							<tbody id="it_reorder">
								<?php
									$class = 'alternate';
									$order = 1;
									
									uksort( $this->_options['image_ids'], array( &$this, '_orderedSort' ) );
								?>
								<?php foreach ( (array) $this->_options['image_ids'] as $id => $entry ) : ?>
								
									<?php
										flush();
										
										$file_name = basename( get_attached_file( $entry['attachment_id'] ) );
										$thumb = iThemesFileUtility::resize_image( $entry['attachment_id'], $thumb_width, $thumb_height, true );
										$this->_options['entry-order-' . $id] = $entry['order'];
									?>
									<tr class="entry-row <?php echo $class; ?>" id="entry-<?php echo $id; ?>">
										<th scope="row" class="check-column">
											<input type="checkbox" name="entries[]" class="entries" value="<?php echo $id; ?>" />
										</th>
										<td>
											<?php if ( ! is_wp_error( $thumb ) ) : ?>
												<img src="<?php echo $thumb['url']; ?>" alt="<?php echo $thumb['file']; ?>" style="float:left; margin-right:10px;" />
											<?php else : ?>
												Thumbnail generation error: <?php echo $thumb->get_error_message(); ?>
											<?php endif; ?>
											<div class="row-actions" style="margin:0; padding:0;">
												<span class="edit"><a href="<?php echo $this->_selfLink; ?>&image_id=<?php echo $id; ?>&group_id=<?php echo $this->_groupID; ?>">Edit Image Settings</a></span>
											</div>
										</td>
										<td>
											<?php echo $file_name; ?>
										</td>
										<td>
											<a href="<?php echo $entry['url']; ?>" target="_blank" title="<?php echo $entry['url']; ?>"><?php echo $entry['url']; ?></a>
										</td>
										<td class="dragHandle">
											<img width="24px" height="24px" src="<?php echo $this->_pluginURL; ?>/images/icon-reorder.png" title="Click and drag to reorder" />
											<?php $this->_addHidden( 'entry-order-' . $id, array( 'class' => 'entry-order' ) ); ?>
										</td>
									</tr>
									<?php $class = ( $class === '' ) ? 'alternate' : ''; ?>
									<?php $order++; ?>
								<?php endforeach; ?>
							</tbody>
						</table>
						
						<div class="tablenav">
							<div class="alignleft actions">
								<?php $this->_addSubmit( 'delete_images', array( 'value' => 'Delete', 'class' => 'button-secondary delete' ) ); ?>
								<?php $this->_addSubmit( 'save_entry_order', array( 'value' => 'Save Order', 'class' => 'button-secondary' ) ); ?>
							</div>
							
							<br class="clear" />
						</div>
					<?php endif; ?>
				</form>
			</div>
			
			<br class="clear" />
		<?php endif; ?>
		
		
		<?php if ( ! isset( $this->_errors ) || isset( $_REQUEST['image_id'] ) || ( isset( $_REQUEST['action'] ) && ( 'save_image' === $_REQUEST['action'] ) ) ) : 
			wp_enqueue_script( 'thickbox' );
			wp_print_scripts( 'thickbox' );
			wp_print_styles( 'thickbox' );
			
			// Add WP 3.5 Media Library dependants
			if ( ! $this->_medialibrary->_pre_wp_3_5_compatibility ) {
				wp_enqueue_media();
				wp_enqueue_script( 'it-medialibrary-add-image', $this->_pluginURL . '/lib/medialibrary/medialibrary.js' );
			}

			// Handles resizing thickbox.
			if ( !wp_script_is( 'media-upload' ) ) {
				wp_enqueue_script( 'media-upload' );
				wp_print_scripts( 'media-upload' );
			}

		?>
		
		<script type="text/javascript">
			function pb_medialibrary( response ) {

				jQuery('#attachment_id').val( response );
				
				jQuery.ajax({
					url: '<?php echo admin_url('admin-ajax.php')?>',
					type: 'POST',
					dataType: 'json',
					data: { 
					encoded_data : response,
					action: 'it_medialibrary_extract_id'
					},
					success: function( response ) {
						jQuery( '#it_imagepreview' ).attr( 'src', response.url );
						jQuery( '#attachment_id' ).attr( 'value', response.id );
						jQuery( '#it_imagepreview' ).slideDown();
						
					}
				});
				
			}
			</script>
			<?php if ( ! empty( $image ) && ! is_wp_error( $image ) ) { ?>
			<script type="text/javascript">
			function pb_medialibrary_edit( $response ) {
				window.location.href = '<?php echo $this->_selfLink; ?>&group_id=<?php echo  $this->_groupID; ?>&image_update=true';
			}
			</script>
			<?php } else {  ?>
			
			<script type="text/javascript">
			function pb_medialibrary_edit( $response ) {
				window.location.href = '<?php echo $this->_selfLink; ?>&group_id=<?php echo  $this->_groupID; ?>&image_update=true';
			}
			</script>
			<?php } ?> 
			<div id="poststuff">
				<div class="postbox">
					<?php if ( ! isset( $_REQUEST['image_id'] ) ) : ?>
						<h3 id="addnew">Add New Image</h3>
					<?php else : ?>
						<h3>Edit Image Settings</h3>
					<?php endif; ?>
					<div class="inside">	
						<p>The uploaded image should be <?php echo "{$this->_options['width']}x{$this->_options['height']}"; ?> (<?php echo $this->_options['width']; ?> pixels wide by <?php echo $this->_options['height']; ?> pixels high).</p>
						<p>Images not matching the exact size will be resized and cropped to fit upon display.</p>
						<?php
							$this->_options['order'] = -1;
							$this->_options['group_id'] = $this->_groupID;
							
							if ( isset( $this->_errors ) ) {
								$this->_options['attachment_id'] = $this->_attachment_id;
								$this->_options['url'] = $_POST[$this->_var . '-url'];
								$this->_options['order'] = $_POST[$this->_var . '-order'];
								$this->_options['group_id'] = $_GET['group_id']; // new image group feature
							}
							else if ( isset( $_REQUEST['image_id'] ) ) {
								$entry = $this->_options['image_ids'][$_REQUEST['image_id']];
								
								$this->_options['attachment_id'] = $entry['attachment_id'];
								$this->_options['url'] = $entry['url'];
								$this->_options['order'] = $entry['order'];
							}
							
							$image = '';
							if ( ! empty( $this->_options['attachment_id'] ) ) {
								require_once( $this->_pluginPath . '/lib/file-utility/file-utility.php' );
								
								$image = iThemesFileUtility::resize_image( $this->_options['attachment_id'], $thumb_width, $thumb_height, true );
							}
							
							if ( isset( $this->_errors ) && is_array( $this->_errors ) ) {
 								foreach ( (array) $this->_errors as $error )
									$this->_showErrorMessage( $error );
							}
						?>
					
						
						<form enctype="multipart/form-data" method="post" action="<?php echo $this->_selfLink; ?>&group_id=<?php echo  $this->_groupID; ?>">
							<?php wp_nonce_field( $this->_var . '-nonce' ); ?>
							<table class="form-table">
								<tr><th scope="row">Image</th>
									<td>
										<?php if ( ! empty( $image ) && ! is_wp_error( $image ) ) : ?>
											<img id="it_imagepreview" src="<?php echo $image['url']; ?>" style="margin-left: 40px; margin-bottom: 10px;" width="150" height="150">

											<p>Upload a new file to replace the current image.</p>
										<?php endif; ?>
										<input type="hidden" name="attachment_id" id="attachment_id" />
										<?php
										echo '<img id="it_imagepreview" style="display: none; margin-left: 40px; margin-bottom: 10px;" width="150" height="150"><br>';
										$link_args = array(
											'text' => __( 'Browse Images', 'it-l10n-rotating-images' ),
											'classes' => 'button button-secondary',
										);
										echo '<br><div class="actions" >' . $this->_medialibrary->get_add_link( $link_args ) . '</div>';

										?>
									</td>
								</tr>
								<tr><th scope="row">Link URL</th>
									<td>
										<?php $this->_addTextBox( 'url', array( 'size' => '60' ) ); ?>
										<br />
										<i>Example: http://site.domain/</i>
									</td>
								</tr>
							</table>
							
							<p class="submit">
								<?php if ( ! isset( $_REQUEST['image_id'] ) ) : ?>
									<?php $this->_addSubmit( 'save_image', 'Save Image' ); ?>
								<?php else : ?>
									<?php $this->_addSubmit( 'save_image', 'Update Image Settings' ); ?>
									<?php $this->_addHiddenNoSave( 'image_id', $_REQUEST['image_id'] ); ?>
								<?php endif; ?>
							</p>
							
							<?php $this->_addHiddenNoSave( 'action', 'save_image' ); ?>
							<?php $this->_addHidden( 'order' ); ?>
						</form>
					</div>
				</div>
		<?php
		endif;
		
		if ( ! isset( $_REQUEST['image_id'] ) && ( ! isset( $_REQUEST['action'] ) || ( 'save_image' !== $_REQUEST['action'] ) ) ) : ?>
		<div class="postbox">
				<h3 id="rotating-images-settings"><?php _e( 'Rotating Images Settings', $this->_var ); ?></h3>
			<div class="inside" >	
				<?php
					if ( isset( $this->_errors ) && is_array( $this->_errors ) ) {
						foreach ( (array) $this->_errors as $error )
							$this->showErrorMessage( $error );
					}
				?>
				
				<form enctype="multipart/form-data" method="post" action="<?php echo $this->_selfLink; ?>&group_id=<?php echo $this->_groupID; ?>">
					<table class="form-table">
								<tr>
									<th scope="row">Default&nbsp;URL<?php $this->tip( 'This is the default link that will be used if an image doesnt have a URL.' ); ?></th>
									<td>
										<?php $this->_addTextBox( 'link', array( 'size' => '70' ) ); ?>
									</td>
								</tr>
								<tr>
									<th scope="row">Open URL in New Tab/Window <?php $this->tip( 'When clicking a linked image it will now open in a new window.' ); ?></th>
									<td>
										<?php $this->_addCheckBox( 'open_new_window', '1' ); ?>
									</td>
								</tr>
								<tr>
									<th scope="row">Center in Displayed Area<?php $this->tip( 'This setting will center your Rotating Images slider inside the area where you are displaying it.' ); ?></th>
									<td>
										<?php $this->_addCheckBox( 'widget_align', 'center' ); ?>
									</td>
								</tr>
								<table class="form-table" <?php if ( '1' != $this->_options['groups'][$_GET['group_id']]['options']['enable_fade'] ) { echo 'style="display: none"'; } ?>>
								<?php if ( '1' == $this->_options['groups'][$_GET['group_id']]['options']['enable_fade'] ) { ?>
									<h2>Fade Options</h2><hr>
								<?php } ?>
								
								<tr>
									<th scope="row">Image Sort Order<?php $this->tip( 'This setting will change the order of the images in the slideshow.' ); ?></th>
									<td>
										<?php $this->_addDropDown( 'fade_sort', array( 'ordered' => 'As ordered (default)', 'alpha' => 'Alphabetical by file name', 'random' => 'Random' ) ); ?>
									</td>
								</tr>
								<tr>
									<th scope="row">Image Display Time<?php $this->tip( 'Length of time to display each image in seconds.'); ?></th>
									<?php if ( ( ! empty( $_POST['save'] ) ) && ( floatval( $_POST[$this->_var . '-sleep'] ) <= 0 ) ) : ?>
											<td style="background-color:red;">
									<?php else: ?>
											<td>
									<?php endif; ?>
									<?php $this->_addTextBox( 'sleep', array( 'size' => '3', 'maxlength' => '5' ) ); ?>
											</td>
								</tr>
								<tr>
									<th scope="row">Image Transition Delay<?php $this->tip( 'Length of time to fade each image in seconds.'); ?></th>
									<?php if ( ( ! empty( $_POST['save'] ) ) && ( floatval( $_POST[$this->_var . '-fade'] ) <= 0 ) ) : ?>
										<td style="background-color:red;">
									<?php else: ?>
										<td>
									<?php endif; ?>
										<?php $this->_addTextBox( 'fade', array( 'size' => '3', 'maxlength' => '5' ) ); ?>
									</td>
								</tr>
								<tr>
									<th scope="row">Enable Sliding Effect<?php $this->tip( ' Overrides display and fade times. Original images must be larger than configured dimensions to function properly.'); ?></th>
									<td>
										<?php $this->_addCheckBox( 'enable_slide', '1' ); ?>
									</td>
								</tr>
								<tr>
									<th scope="row">Double Fade Transparent Images<?php $this->tip( ' When using images with transparency, this can fix problems with one image showing through and changing suddenly. Only use if needed.'); ?></th>
									<td>
										<?php $this->_addCheckBox( 'double_fade', '1' ); ?>
									</td>
								</tr>
								</table>
								<table class="form-table" <?php if ( '1' != $this->_options['groups'][$_GET['group_id']]['options']['enable_overlay'] ) { echo 'style="display: none"'; } ?>aw>
								<?php if ( '1' == $this->_options['groups'][$_GET['group_id']]['options']['enable_overlay'] ) { ?>
									<h2>Overlay Options</h2><hr>
								<?php } ?>
								<tr>
									<th scope="row">Text Horizontal Alignment<?php $this->tip( 'Horizontal alignment of overlay text. '); ?></th>
									<td>
										<?php $this->_addDropDown( 'overlay_text_alignment', array( 'center' => 'Center (default)', 'left' => 'Left', 'right' => 'Right' ) ); ?>
									</td>
								</tr>
								<tr>
									<th scope="row">Text Vertical Position<?php $this->tip( 'Vertical alignment of overlay text. '); ?></th>
									<td>
										<?php $this->_addDropDown( 'overlay_text_vertical_position', array( 'bottom' => 'Bottom', 'middle' => 'Middle (default)', 'top' => 'Top' ) ); ?>
									</td>
								</tr>
								<tr>
									<th scope="row">Text Padding in Pixels<?php $this->tip( 'Padding for text overlay.'); ?></th>
									<td>
										<?php $this->_addTextBox( 'overlay_text_padding', array( 'size' => '4' ) ); ?>
									</td>
								</tr>

								<tr>
									<th scope="row">Overlay Header Text<?php $this->tip( 'Header text for the Rotating Images overlay.'); ?></th>
									<td>
										<?php $this->_addTextBox( 'overlay_header_text', array( 'size' => '40' ) ); ?>
									</td>
								</tr>
								<tr>
									<th scope="row">Header Text Size<?php $this->tip( 'Size of header text overlay in pixels.'); ?></th>
									<td>
										<?php $this->_addTextBox( 'overlay_header_size', array( 'size' => '4' ) ); ?>
									</td>
								</tr>
								<tr>
									<th scope="row">Header Text Color<?php $this->tip( 'Color of header text overlay.'); ?></th>
									<td>
										<?php $this->_addTextBox( 'overlay_header_color', array( 'size' => '7' ) ); ?>&nbsp;<?php $this->_addButton( 'show_overlay_header_color_picker', 'Show Picker' ); ?>
									</td>
								</tr>
								<tr>
									<th scope="row">Header Text Font Family<?php $this->tip( 'Font of header text overlay(leave blank for default).'); ?></th>
									<td>
										<?php $this->_addTextBox( 'overlay_header_font', array( 'size' => '20' ) ); ?>
									</td>
								</tr>
								<tr>
									<th scope="row">Overlay Subheader Text<?php $this->tip( 'Subheader text for the Rotating Images overlay.'); ?></th>
									<td>
										<?php $this->_addTextBox( 'overlay_subheader_text', array( 'size' => '40' ) ); ?>
									</td>
								</tr>
								<tr>
									<th scope="row">Subheader Text Size<?php $this->tip( 'Size of subheader text overlay in pixels.'); ?></th>
									<td>
										<?php $this->_addTextBox( 'overlay_subheader_size', array( 'size' => '4' ) ); ?>
									</td>
								</tr>
								<tr>
									<th scope="row">Subheader Text Color<?php $this->tip( 'Color of subheader text overlay.'); ?></th>
									<td>
										<?php $this->_addTextBox( 'overlay_subheader_color', array( 'size' => '7' ) ); ?>&nbsp;<?php $this->_addButton( 'show_overlay_subheader_color_picker', 'Show Picker' ); ?>
									</td>
								</tr>
								<tr>
									<th scope="row">Subheader Text Font Family<?php $this->tip( 'Font of subheader text overlay(blank for default).'); ?></th>
									<td>
										<?php $this->_addTextBox( 'overlay_subheader_font', array( 'size' => '20' ) ); ?>
									</td>
								</tr>
								</tr>
							</table>	
							</table>
							<br />
							
							<p class="submit"><?php $this->_addSubmit( 'save', 'Save' ); ?></p>
							<?php $this->_addHiddenNoSave( 'action', 'save' ); ?>
							<?php $this->_addUsedInputs(); ?>
							<?php wp_nonce_field( $this->_var . '-nonce' ); ?>
							
							<div id="overlay_header_color_ColorPickerWrapper" style="padding:10px; border:1px solid black; position:absolute; z-index:10; background-color:white; display:none;">
								<table><tr>
									<td style="vertical-align:top;"><div id="overlay_header_color_ColorMap"></div><br /><a href="javascript:void(0);" style="float:right;" id="overlay_header_color_hide_div">save selection</a></td>
									<td style="vertical-align:top;"><div id="overlay_header_color_ColorBar"></div></td>
									<td style="vertical-align:top;">
										<table>
											<tr><td colspan="3"><div id="overlay_header_color_Preview" style="background-color:#fff; width:95px; height:60px; padding:0; margin:0; border:solid 1px #000;"><br /></div></td></tr>
											<tr><td><input type="radio" id="overlay_header_color_HueRadio" name="overlay_header_color_Mode" value="0" /></td><td><label for="overlay_header_color_HueRadio">H:</label></td><td><input type="text" id="overlay_header_color_Hue" value="0" style="width: 40px;" /> &deg;</td></tr>
											<tr><td><input type="radio" id="overlay_header_color_SaturationRadio" name="overlay_header_color_Mode" value="1" /></td><td><label for="overlay_header_color_SaturationRadio">S:</label></td><td><input type="text" id="overlay_header_color_Saturation" value="100" style="width: 40px;" /> %</td></tr>
											<tr><td><input type="radio" id="overlay_header_color_BrightnessRadio" name="overlay_header_color_Mode" value="2" /></td><td><label for="overlay_header_color_BrightnessRadio">B:</label></td><td><input type="text" id="overlay_header_color_Brightness" value="100" style="width: 40px;" /> %</td></tr>
											<tr><td colspan="3" height="5"></td></tr>
											<tr><td><input type="radio" id="overlay_header_color_RedRadio" name="overlay_header_color_Mode" value="r" /></td><td><label for="overlay_header_color_RedRadio">R:</label></td><td><input type="text" id="overlay_header_color_Red" value="255" style="width: 40px;" /></td></tr>
											<tr><td><input type="radio" id="overlay_header_color_GreenRadio" name="overlay_header_color_Mode" value="g" /></td><td><label for="overlay_header_color_GreenRadio">G:</label></td><td><input type="text" id="overlay_header_color_Green" value="0" style="width: 40px;" /></td></tr>
											<tr><td><input type="radio" id="overlay_header_color_BlueRadio" name="overlay_header_color_Mode" value="b" /></td><td><label for="overlay_header_color_BlueRadio">B:</label></td><td><input type="text" id="overlay_header_color_Blue" value="0" style="width: 40px;" /></td></tr>
											aw<tr><td>#:</td><td colspan="2"><input type="text" id="overlay_header_color_Hex" value="FF0000" style="width: 60px;" /></td></tr>
										</table>
									</td>
								</tr></table>
							</div>
							
							<div id="overlay_subheader_color_ColorPickerWrapper" style="padding:10px; border:1px solid black; position:absolute; z-index:10; background-color:white; display:none;">
								<table><tr>
									<td style="vertical-align:top;"><div id="overlay_subheader_color_ColorMap"></div><br /><a href="javascript:void(0);" style="float:right;" id="overlay_subheader_color_hide_div">save selection</a></td>
									<td style="vertical-align:top;"><div id="overlay_subheader_color_ColorBar"></div></td>
									<td style="vertical-align:top;">
										<table>
											<tr><td colspan="3"><div id="overlay_subheader_color_Preview" style="background-color:#fff; width:95px; height:60px; padding:0; margin:0; border:solid 1px #000;"><br /></div></td></tr>
											<tr><td><input type="radio" id="overlay_subheader_color_HueRadio" name="overlay_subheader_color_Mode" value="0" /></td><td><label for="overlay_subheader_color_HueRadio">H:</label></td><td><input type="text" id="overlay_subheader_color_Hue" value="0" style="width: 40px;" /> &deg;</td></tr>
											<tr><td><input type="radio" id="overlay_subheader_color_SaturationRadio" name="overlay_subheader_color_Mode" value="1" /></td><td><label for="overlay_subheader_color_SaturationRadio">S:</label></td><td><input type="text" id="overlay_subheader_color_Saturation" value="100" style="width: 40px;" /> %</td></tr>
											<tr><td><input type="radio" id="overlay_subheader_color_BrightnessRadio" name="overlay_subheader_color_Mode" value="2" /></td><td><label for="overlay_subheader_color_BrightnessRadio">B:</label></td><td><input type="text" id="overlay_subheader_color_Brightness" value="100" style="width: 40px;" /> %</td></tr>
											<tr><td colspan="3" height="5"></td></tr>
											<tr><td><input type="radio" id="overlay_subheader_color_RedRadio" name="overlay_subheader_color_Mode" value="r" /></td><td><label for="overlay_subheader_color_RedRadio">R:</label></td><td><input type="text" id="overlay_subheader_color_Red" value="255" style="width: 40px;" /></td></tr>
											<tr><td><input type="radio" id="overlay_subheader_color_GreenRadio" name="overlay_subheader_color_Mode" value="g" /></td><td><label for="overlay_subheader_color_GreenRadio">G:</label></td><td><input type="text" id="overlay_subheader_color_Green" value="0" style="width: 40px;" /></td></tr>
											<tr><td><input type="radio" id="overlay_subheader_color_BlueRadio" name="overlay_subheader_color_Mode" value="b" /></td><td><label for="overlay_subheader_color_BlueRadio">B:</label></td><td><input type="text" id="overlay_subheader_color_Blue" value="0" style="width: 40px;" /></td></tr>
											<tr><td>#:</td><td colspan="2"><input type="text" id="overlay_subheader_color_Hex" value="FF0000" style="width: 60px;" /></td></tr>
										</table>
									</td>
								</tr></table>
							</div>
							
							<div style="display:none;">
								<?php
									$images = array( 'rangearrows.gif', 'mappoint.gif', 'bar-saturation.png', 'bar-brightness.png', 'bar-blue-tl.png', 'bar-blue-tr.png', 'bar-blue-bl.png', 'bar-blue-br.png', 'bar-red-tl.png',
										'bar-red-tr.png', 'bar-red-bl.png', 'bar-red-br.png', 'bar-green-tl.png', 'bar-green-tr.png', 'bar-green-bl.png', 'bar-green-br.png', 'map-red-max.png', 'map-red-min.png',
										'map-green-max.png', 'map-green-min.png', 'map-blue-max.png', 'map-blue-min.png', 'map-saturation.png', 'map-saturation-overlay.png', 'map-brightness.png', 'map-hue.png' );
									
									foreach( (array) $images as $image )
										echo '<img src="' . $this->_pluginURL . '/js/colorpicker/images/' . $image . "\" />\n";
								?>
								
							</div>
						</form>
					</div> 
				</div>
				</div>
				<?php endif;
			} else { // SHOW GROUP LISTING - Dustin Bolton
				?> 
				<script type="text/javascript"> 
					var it_rotate_ajax_location = '<?php echo esc_js( admin_url("admin-ajax.php") ); ?>';
				</script>
				<?php 
				echo '<div class="wrap">';
				?>
				<form id="posts-filter" enctype="multipart/form-data" method="post" action="<?php echo $this->_selfLink; ?>">
					<h2>
						Rotating Images
						<a href="#" class="add-new-h2 add-new-toggle" onclick="toggle_add_new_rotating_images_group(); return false;">Add New Group</a>
					</h2>
					<br />
				<div id="poststuff">
					<div class="postbox" id="add_new_rotating_images_group" <?php if ( ! empty( $this->_options['groups'] ) ) { echo 'style="display:none"'; } ?>  >
						<h3><span>Add New Rotating Images Group</span></h3>
						<div class="inside">
							<form name="addnew" id="addnew" enctype="multipart/form-data" method="post" action="<?php echo $this->_selfLink; ?>">
							<?php wp_nonce_field( $this->_var . '-nonce' ); ?>
							<table class="form-table">
								<tr><th scope="row"><label for="name">Group Name<?php $this->tip( 'Name of the new group to create. This is not publicly displayed.' ); ?></label></th>
									<td><?php $this->_addTextBox( 'name' ); ?></td>
								</tr>
								<?php if ( true === $this->_defaults['variable_width'] ) : ?>
								<tr>
									<th scope="row">Rotating&nbsp;Images&nbsp;Width<?php $this->tip( 'This controls the width of the images you upload into your rotating images group. Images will be generated from the original images uploaded. Images will not be upscaled larger than the originals. You may change this at any time.' ); ?></th>
									<td>
										<table>
											<tr>
												<td>Width in pixels:</td>
												<?php if ( ( ! empty( $_POST['add_group'] ) ) && ( intval( $_POST[$this->_var . '-width'] ) < 0 ) ) : ?>
													<td style="background-color:red;">
												<?php else: ?>
													<td>
												<?php endif; ?>
													<?php $this->_addTextBox( 'width', array( 'size' => '3', 'maxlength' => '5' ) ); ?>
												</td>
											</tr>
										</table>
									</td>
								</tr>
								<?php endif; ?>
								<?php if ( true === $this->_defaults['variable_height'] ) : ?>
								<tr>
									<th scope="row">Rotating&nbsp;Images&nbsp;Height<?php $this->tip( 'This controls the height of the images you upload into your rotating images group. Images will be generated from the original images uploaded. Images will not be upscaled larger than the originals. You may change this at any time.' ); ?></th>
									<td>
										<table>
											<tr>
												<td>Height in pixels:</td>
												<?php if ( ( ! empty( $_POST['add_group'] ) ) && ( intval( $_POST[$this->_var . '-height'] ) < 0 ) ) : ?>
													<td style="background-color:red;">
												<?php else: ?>
													<td>
												<?php endif; ?>
													<?php $this->_addTextBox( 'height', array( 'size' => '3', 'maxlength' => '5' ) ); ?>
												</td>
											</tr>
										</table>
									</td>
								</tr>
								<tr>
									<th scope="row">Enable Fade Animation<?php $this->tip( 'This setting will enable the fade transition. You can edit the fade options on the edit group page.' ); ?></th>
									<td>										
										<?php $this->_addCheckBox( 'enable_fade', '1' ); ?>
									</td>
								</tr>
								<tr>
									<th scope="row">Enable Responsive<?php $this->tip( 'This setting will make rotating images responsive.' ); ?></th>
									<td>
										<?php $this->_addCheckBox( 'enable_responsive', '1' ); ?>
									</td>
								</tr>
								<tr id="text-overlay">
									<th scope="row">Enable Text Overlay<?php $this->tip( 'When enabling this setting you can setup a text overlay for your rotating images slider in the groups edit page.' ); ?></th>
									<td>
										<?php $this->_addCheckBox( 'enable_overlay', '1' ); ?>
									</td>
								</tr>
								<?php endif; ?>
							</table>
							<input type="hidden" name="it_group_id" id="edit_group_id" value="" />
							<p class="submit">
								<?php $this->_addSubmit( 'add_group', 'Add Group' ); ?>
							</p>
							</form>
						</div>
					</div>
					<?php if ( isset( $this->_options['groups'] ) && ( count( $this->_options['groups'] ) > 0 ) ) :
					uksort( $this->_options['groups'], array( &$this, '_sortGroupsByName' ) );
					?>
						<div class="tablenav">
							<div class="alignleft actions">
								<?php $this->_addSubmit( 'delete_group', array( 'value' => 'Delete', 'class' => 'button-secondary delete' ) ); ?>
							</div>
							
							<br class="clear" />
						</div>
						
						<br class="clear" />
						
						<table class="widefat">
							<thead>
								<tr class="thead">
									<th scope="col" class="check-column"><input type="checkbox" id="check-all-groups" /></th>
									<th>Group Name</th>
									<th>Images</th>
									<th>Fading</th>
									<th>Responsive</th>
									<th>Text Overlay</th>
									<th>Shortcode</th>
									<th class="num">Dimensions (W x H)</th>
								</tr>
							</thead>
							<tfoot>
								<tr class="thead">
									<th scope="col" class="check-column"><input type="checkbox" id="check-all-groups" /></th>
									<th>Group Name</th>
									<th>Images</th>
									<th>Fading</th>
									<th>Responsive</th>
									<th>Text Overlay</th>
									<th>Shortcode</th>
									<th class="num">Dimensions (W x H)</th>
								</tr>
							</tfoot>
							<tbody id="users" class="list:user user-list">
								<?php $class = ' class="alternate"'; ?>
								<?php foreach ( (array) $this->_options['groups'] as $id => $group ) : ?>
									<?php
										$entriesDescription = ( ! empty( $group['entries'] ) && is_array( $group['entries'] ) && ( count( $group['entries'] ) > 0 ) ) ? 'Manage Images' : 'Manage Images';
										
										$css_class = strtolower( $group['name'] );
										$css_class = preg_replace( '/\s+/', '-', $css_class );
										$css_class = $this->_class . '-' . preg_replace( '/[^\w\-]/', '', $css_class );
									?>
									<tr id="group-<?php echo $id; ?>"<?php echo $class; ?>>
										<th scope="row" class="check-column"><input type="checkbox" name="groups[]" class="administrator groups" value="<?php echo $id; ?>" /></th>
										<td>
											<?php echo stripslashes($group['name']); ?>
											<div class="row-actions" style="margin:0; padding:0;">
												<a href="<?php echo $this->_selfLink; ?>&group_id=<?php echo $id; ?>" title="Modify Group Settings">Edit Group|</a><a href="#" id="it_rotate_group-<?php echo $id; ?>" class="edit_rotate_group_settings" >Edit Group Name</a>
											</div>
										</td>
										<td>
											<?php
											if ( ! empty( $group['options']['image_ids'] ) && is_array( $group['options']['image_ids'] ) ) {
												echo count( $group['options']['image_ids'] ); // Number of images in this groups array.
											} else {
												echo '0';
											}
											?>
											<div class="row-actions" style="margin:0; padding:0;">
												<a href="<?php echo $this->_selfLink; ?>&group_id=<?php echo $id; ?>&view_entries=1" title="Add, Modify, and Delete Entries"><?php echo $entriesDescription; ?></a>
											</div>
										</td>
										<td>
											<?php
											if ( $group['options']['enable_fade'] == '1' ) {
												echo 'Yes';
											} else {
												echo 'No';
											}
											?>
											<div class="row-actions" style="margin:0; padding:0;">
												<a href="#" id="it_rotate_group-<?php echo $id; ?>" class="edit_rotate_group_settings" >Edit Setting</a>
											</div>
										</td>
										<td>
											<?php
											if ( $group['options']['enable_responsive'] == '1' ) {
												echo 'Yes';
											} else {
												echo 'No';
											}
											?>
											<div class="row-actions" style="margin:0; padding:0;">
												<a href="#" id="it_rotate_group-<?php echo $id; ?>" class="edit_rotate_group_settings" >Edit Setting</a>
											</div>
										</td>
										<td>
											<?php
											if ( $group['options']['enable_overlay'] == '1' ) {
												echo 'Yes';
											} else {
												echo 'No';
											}
											?>
											<div class="row-actions" style="margin:0; padding:0;">
												<a href="#" id="it_rotate_group-<?php echo $id; ?>" class="edit_rotate_group_settings" >Edit Setting</a>
											</div>
										</td>
										<td>
											<?php echo $group['options']['width'].' x '.$group['options']['height']; ?> px

											<div class="row-actions" style="margin:0; padding:0;">
												<a href="#" id="it_rotate_group-<?php echo $id; ?>" class="edit_rotate_group_settings" >Edit Setting</a>
											</div>
										</td>
										<td class="num">
											[it-rotate group="<?php echo $id; ?>"]
										</td>
									</tr>
									<?php $class = ( $class == '' ) ? ' class="alternate"' : ''; ?>
								<?php endforeach; ?>
							</tbody>
						</table>
						
						<div class="tablenav">
							<div class="alignleft actions">
								<?php $this->_addSubmit( 'delete_group', array( 'value' => 'Delete', 'class' => 'button-secondary delete' ) ); ?>
							</div>
							
							<br class="clear" />
						</div>
					<?php endif; ?>
					
					<?php wp_nonce_field( $this->_var . '-nonce' ); ?>
				</form>

				<?php
				echo '</div>';
				echo '</div>';
			} // End if.
		} // End function.
		
		
		
		function _groupsDelete() {
			$names = array();
			
			if ( ! empty( $_POST['groups'] ) && is_array( $_POST['groups'] ) ) {
				foreach ( (array) $_POST['groups'] as $id ) {
					$names[] = $this->_options['groups'][$id]['name'];
					unset( $this->_options['groups'][$id] );
				}
				$this->save();
			}
	
			natcasesort( $names );
			
			if ( $names )
				$this->_showStatusMessage( 'Successfully deleted the group.' );
			else
				$this->_showErrorMessage( 'No Image Groups were selected for deletion' );
		}
		
		// Form Functions ///////////////////////////
		
		function _newForm() {
			$this->_usedInputs = array();
		}
		
		function _addSubmit( $var, $options = array(), $override_value = true ) {
			if ( ! is_array( $options ) )
				$options = array( 'value' => $options );
			
			$options['type'] = 'submit';
			$options['name'] = $var;
			$options['class'] = ( empty( $options['class'] ) ) ? 'button-primary' : $options['class'];
			
			$this->_addSimpleInput( $var, $options, $override_value );
		}
		
		function _addButton( $var, $options = array(), $override_value = true ) {
			if ( ! is_array( $options ) )
				$options = array( 'value' => $options );
			
			$options['type'] = 'button';
			$options['name'] = $var;
			
			$this->_addSimpleInput( $var, $options, $override_value );
		}
		
		function _addTextBox( $var, $options = array(), $override_value = false ) {
			if ( ! is_array( $options ) )
				$options = array( 'value' => $options );
			
			$options['type'] = 'text';
			
			$this->_addSimpleInput( $var, $options, $override_value );
		}
		
		function _addTextArea( $var, $options = array(), $override_value = false ) {
			if ( ! is_array( $options ) )
				$options = array( 'value' => $options );
			
			$options['type'] = 'textarea';
			
			$this->_addSimpleInput( $var, $options, $override_value );
		}
		
		function _addFileUpload( $var, $options = array(), $override_value = false ) {
			if ( ! is_array( $options ) )
				$options = array( 'value' => $options );
			
			$options['type'] = 'file';
			$options['name'] = $var;
			
			$this->_addSimpleInput( $var, $options, $override_value );
		}
		
		function _addCheckBox( $var, $options = array(), $override_value = false ) {
			if ( ! is_array( $options ) )
				$options = array( 'value' => $options );
			
			$options['type'] = 'checkbox';
			
			$this->_addSimpleInput( $var, $options, $override_value );
		}
		
		function _addMultiCheckBox( $var, $options = array(), $override_value = false ) {
			if ( ! is_array( $options ) )
				$options = array( 'value' => $options );
			
			$options['type'] = 'checkbox';
			$var = $var . '[]';
			
			$this->_addSimpleInput( $var, $options, $override_value );
		}
		
		function _addRadio( $var, $options = array(), $override_value = false ) {
			if ( ! is_array( $options ) )
				$options = array( 'value' => $options );
			
			$options['type'] = 'radio';
			
			$this->_addSimpleInput( $var, $options, $override_value );
		}
		
		function _addDropDown( $var, $options = array(), $override_value = false ) {
			if ( ! is_array( $options ) )
				$options = array();
			else if ( ! isset( $options['value'] ) || ! is_array( $options['value'] ) )
				$options = array( 'value' => $options );
			
			$options['type'] = 'dropdown';
			
			$this->_addSimpleInput( $var, $options, $override_value );
		}
		
		function _addHidden( $var, $options = array(), $override_value = false ) {
			if ( ! is_array( $options ) )
				$options = array( 'value' => $options );
			
			$options['type'] = 'hidden';
			
			$this->_addSimpleInput( $var, $options, $override_value );
		}
		
		function _addHiddenNoSave( $var, $options = array(), $override_value = true ) {
			if ( ! is_array( $options ) )
				$options = array( 'value' => $options );
			
			$options['name'] = $var;
			
			$this->_addHidden( $var, $options, $override_value );
		}
		
		function _addDefaultHidden( $var ) {
			$options = array();
			$options['value'] = $this->defaults[$var];
			
			$var = "default_option_$var";
			
			$this->_addHiddenNoSave( $var, $options );
		}
		
		function _addUsedInputs() {
			$options['type'] = 'hidden';
			$options['value'] = implode( ',', $this->_usedInputs );
			$options['name'] = 'used-inputs';
			
			$this->_addSimpleInput( 'used-inputs', $options, true );
		}
		
		function _addSimpleInput( $var, $options = false, $override_value = false ) {
			if ( empty( $options['type'] ) ) {
				echo "<!-- _addSimpleInput called without a type option set. -->\n";
				return false;
			}
			
			
			$scrublist['textarea']['value'] = true;
			$scrublist['file']['value'] = true;
			$scrublist['dropdown']['value'] = true;
			
			$defaults = array();
			$defaults['name'] = $this->_var . '-' . $var;
			
			$var = str_replace( '[]', '', $var );
			
			if ( 'checkbox' === $options['type'] )
				$defaults['class'] = $var;
			else
				$defaults['id'] = $var;
			
			$options = $this->_merge_defaults( $options, $defaults );
			
			if ( ( false === $override_value ) && isset( $this->_options[$var] ) ) {
				if ( 'checkbox' === $options['type'] ) {
					if ( $this->_options[$var] == $options['value'] )
						$options['checked'] = 'checked';
				}
				elseif ( 'dropdown' !== $options['type'] )
					$options['value'] = $this->_options[$var];
			}
			
			if ( ( preg_match( '/^' . $this->_var . '/', $options['name'] ) ) && ( ! in_array( $options['name'], $this->_usedInputs ) ) )
				$this->_usedInputs[] = $options['name'];
			
			$attributes = '';
			
			if ( false !== $options )
				foreach ( (array) $options as $name => $val )
					if ( ! is_array( $val ) && ( ! isset( $scrublist[$options['type']][$name] ) || ( true !== $scrublist[$options['type']][$name] ) ) )
						if ( ( 'submit' === $options['type'] ) || ( 'button' === $options['type'] ) )
							$attributes .= "$name=\"$val\" ";
						else
							$attributes .= "$name=\"" . htmlspecialchars( $val ) . '" ';
			
			if ( 'textarea' === $options['type'] )
				echo '<textarea ' . $attributes . '>' . $options['value'] . '</textarea>';
			elseif ( 'dropdown' === $options['type'] ) {
				echo "<select $attributes>\n";
				
				foreach ( (array) $options['value'] as $val => $name ) {
					$selected = ( $this->_options[$var] == $val ) ? ' selected="selected"' : '';
					echo "<option value=\"$val\"$selected>$name</option>\n";
				}
				
				echo "</select>\n";
			}
			else
				echo '<input ' . $attributes . '/>';
		}
		
		
		// Plugin Functions ///////////////////////////
		
		function print_scripts() {
			wp_enqueue_script( 'jquery-cross-slide', $this->_pluginURL . '/js/jquery.cross-slide.js', array(), false, true );
			wp_print_scripts( 'jquery-cross-slide' );
		}
		
		// If param2=true, data is echoed. If false, it is returned (for shortcode)
		function fadeImages($group, $widget = true) {
			require_once( $this->_pluginPath . '/lib/file-utility/file-utility.php' );
			$return = "";
			
			
			$this->_groupID = (int) $group;
			$this->load();
			
			if ( empty ($this->_options['image_ids']) ) { // Dont proceed if there are no images.
				echo 'Warning: Empty Rotating Images Group! Upload images for this widget to function.';
				return;
			}
			
			if ( ! empty($this->_errors) ) { // Report errors.
				echo $this->_errors[0]; // Give first error.
			} else {
				
				$this->_sortImages();
				
				$files = array();
				
				foreach ( (array) $this->_options['image_ids'] as $entry ) {
					$id = $entry['attachment_id'];
					
					$link = ( ! empty( $entry['url'] ) ) ? $entry['url'] : $this->_options['link'];
					
					if ( wp_attachment_is_image( $id ) ) {
						$file = get_attached_file( $id );
						
						if ( ! empty( $this->_options['enable_slide'] ) ) { // Sliding enabled!
							$sizemult=2;
						} else {
							$sizemult=1;
						}
						
						$data = iThemesFileUtility::resize_image( $file, $this->_options['width']*$sizemult, $this->_options['height']*$sizemult, true );
						
						if ( ! is_array( $data ) && is_wp_error( $data ) )
							$return .= "<!-- Resize Error: " . $data->get_error_message() . " -->";
						else
							$files[] = array( 'image' => $data['url'], 'url' => $link );
					}
				}
				if ( 0 === count( $files ) )
					return;
					
				$this->_instanceCount++; // Increment instance count for javascript for unique instances

				if ( ! wp_script_is( 'jquery' ) )
						wp_print_scripts( 'jquery' );

				if ( ! empty( $this->_options['enable_responsive'] ) ) { 
					if ( !wp_script_is( $this->_var . '-it_imagesloaded' ) ) {
						wp_enqueue_script( $this->_var . '-it_imagesloaded', $this->_pluginURL . '/js/imagesloaded.min.js' );
						wp_print_scripts( $this->_var . '-it_imagesloaded' );
					}
				}

				/*if ( ! empty( $this->_options['enable_responsive'] ) ) { 
					if ( !wp_script_is( $this->_var . '-it_waitforimages' ) ) {
						wp_enqueue_script( $this->_var . '-it_waitforimages', $this->_pluginURL . '/js/jquery.waitforimages.min.js' );
						wp_print_scripts( $this->_var . '-it_waitforimages' );
					}
				}*/
				
				
				if ( ( '1' == $this->_options['enable_fade'] ) && ( count( $files ) > 1 ) ) {
					$list = '';
					$slidevar=", dir: 'up'";
					
					foreach ( (array) $files as $id => $file ) {
						if ($slidevar==", dir: 'up'") { $slidevar=", dir: 'down'"; } else { $slidevar=", dir: 'up'"; }
						
						if ( ! empty( $list ) )
							$list .= ",\n";
						
						
						if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') { // If SSL in use, use https.
							$file['image'] = str_replace('http://', 'https://', $file['image']);
						}
						
						if ( ! empty( $link ) )
							$list .= "{src: '{$file['image']}', href: '{$file['url']}'".$slidevar."}";
						else
							$list .= "{src: '{$file['image']}'".$slidevar."}";
					}
					
					
					


					
					add_action( 'wp_footer', array( &$this, 'print_scripts' ) );
					$target = ( ! empty( $this->_options['open_new_window'] ) ) ? ', open_new_window: true' : '';
	
					
	$return .="
		<script type='text/javascript'>
			/* <![CDATA[ */
				jQuery(document).ready(
					function() {
						yo = jQuery('#rotating-images-rotator_".$this->_instanceCount."').crossSlide(
							{
							";
							if ( ! empty( $this->_options['enable_slide'] ) ) { // Sliding enabled!
								$return .= 'speed: '.(40+$this->_options['sleep']).',';
							} else {
								$return .= 'sleep: '.$this->_options['sleep'].',';
							}
							if ( ! empty( $this->_options['double_fade'] ) ) { // Double fade enabled!
								$return .= 'doubleFade: true,';
							}
							$return .= "
							fade: ".$this->_options['fade'].$target."},
							[
								".$list."
							]
						);
					}
				);
			/* ]]> */
		</script>
	";
					
				} else {
					shuffle( $files );
				}
				
				if ( 'bottom' === $this->_options['overlay_text_vertical_position'] )
					$title_overlay_vertical = "bottom: 0;\n";
				else
					$title_overlay_vertical = "top: 0;\n";
				
				
				$target = ( ! empty ( $this->_options['open_new_window'] ) ) ? ' target="_blank"' : '';
				
				$link_start = "\n";
				$link_end = "\n";
				
				if ( ! empty( $files[0]['url'] ) ) {
					$link_start = "					<a href=\"{$files[0]['url']}\" class=\"rotating-images-link_".$this->_instanceCount." rotating-images-header-text_".$this->_instanceCount."\"{$target} style=\" font-size: " . $this->_options['overlay_header_size'] . "px;\">\n";
					$link_end = "					</a>\n";
					
					$link_start_sub = "					<a href=\"{$files[0]['url']}\" class=\"rotating-images-link_".$this->_instanceCount." rotating-images-subheader-text_".$this->_instanceCount."\"{$target} style=\" font-size: " . $this->_options['overlay_subheader_size'] . "px;\">\n";
					$link_end_sub = "					</a>\n";
				}
				
				$overlay_text = "					<div class=\"rotating-images-title-overlay-header_".$this->_instanceCount."\">\n$link_start";
				$overlay_text .= "						{$this->_options['overlay_header_text']}\n$link_end";
				$overlay_text .= "					</div>\n";
				
				if ( ! empty( $this->_options['overlay_subheader_text'] ) ) {
					$overlay_text .= "					<div class=\"rotating-images-title-overlay-subheader_".$this->_instanceCount."\">\n$link_start_sub";
					$overlay_text .= "						{$this->_options['overlay_subheader_text']}\n$link_end_sub";
					$overlay_text .= "					</div>\n";
				}
				
				
				
		$return .= "
		<style type=\"text/css\">";
			if ( ( '1' != $this->_options['enable_fade'] ) || ( count( $files ) == 1 ) ) {
				$return .= "#rotating-images-rotator_".$this->_instanceCount." {";

			if ( !empty($_SERVER['HTTPS'] ) ) {
				if ($_SERVER['HTTPS'] == 'on') { // If SSL in use, use https.
					$files[0]['image'] = str_replace('http://', 'https://', $files[0]['image']);
				}
			}
				//$return .= "background: url('".$files[0]['image']."');";
				if ( ! empty( $this->_options['enable_responsive'] ) ) {
					$return .= 'background-repeat: no-repeat;' . "\n";
					$return .= 'background-size: contain;' . "\n";
					$return .= 'background-position:center;' . "\n";
				}
				$return .= 'margin-top: 0px;' . "\n";
				$return .= 'margin-bottom: 0px;' . "\n";
				$return .= "}";
			}
			$return .= "#rotating-images-rotator_".$this->_instanceCount.",";
			$return .= "#rotating-images-rotator-wrapper_".$this->_instanceCount." {";
			$return .= "	width: ".$this->_options['width']."px;";
			$return .= "	height: ".$this->_options['height']."px;";
		
			if ( ! empty( $this->_options['enable_responsive'] ) ) {
				$return .= "    max-width: 100%;";
			}
			
			if ( $this->_options['widget_align'] == 'center' ) {
				$return .= 'margin-left: auto;';
				$return .= 'margin-right: auto;';
			}
			$return .= "}";
		
			if ( ! empty( $this->_options['enable_responsive'] ) ) { 
				$return .= "#rotating-images-rotator-container_".$this->_instanceCount." {" . "\n";
				$return .= "	max-width: 100%;" . "\n";	
				$return .= "}" . "\n";
			}
			$return .= "#rotating-images-rotator-wrapper_".$this->_instanceCount." img {";
			
			if ( ! empty( $this->_options['enable_responsive'] ) ) { 
				$return .= "	max-width: 100%;";
			}
			$return .= "margin-top:	10px;";
			$return .= "	padding: 0px;";
			$return .= "}";
			$return .= "#rotating-images-rotator-container_".$this->_instanceCount." .rotating-images-link-overlay_".$this->_instanceCount." {";
			$return .= "	height: ".$this->_options['height']."px;";
			$return .= "	width: ".$this->_options['width']."px;";
			$return .= "	position: absolute;";
			$return .= "	top: 0;";
			$return .= "	display: block;";
			$return .= " }";
			$return .= " #rotating-images-rotator-container_".$this->_instanceCount." .rotating-images-link_".$this->_instanceCount." {";
			$return .= "	text-decoration: none;";
			$return .= "}";
			$return .= "#rotating-images-rotator-container_".$this->_instanceCount." .rotating-images-title-overlay_".$this->_instanceCount." {";
			$return .= " 	width: ".($this->_options['width'] - ( $this->_options['overlay_text_padding'] * 2 ) )."px;";
			$return .= "	position: absolute;";
			$return .= "	".$title_overlay_vertical.";";
			$return .= "	text-align: ".$this->_options['overlay_text_alignment'].";";
			$return .= "	padding: ".$this->_options['overlay_text_padding']."px;";
			$return .= "	display: block;";
			$return .= "}";
			$return .= "#rotating-images-rotator-container_".$this->_instanceCount." .rotating-images-title-overlay-header_".$this->_instanceCount.",";
			$return .= "#rotating-images-rotator-container_".$this->_instanceCount." .rotating-images-title-overlay-subheader_".$this->_instanceCount." {";
			$return .= "width: 100%;";
			$return .= "display: block;";
				if ( ! empty( $this->_options['enable_responsive'] ) ) {
					$return .= "max-width: 100%;";
				}
			$return .= " top:50%;";
			$return .= "left:0;";
			$return .= "right:0;";
			$return .= "}";
			$return .= "#rotating-images-rotator-container_".$this->_instanceCount." .rotating-images-title-overlay-header_".$this->_instanceCount." {";
			$return .= "	padding-bottom: ".$this->_options['overlay_text_padding']."px;";
			$return .= "}";
			$return .= "#rotating-images-rotator-container_".$this->_instanceCount." .rotating-images-title-overlay-header_".$this->_instanceCount." a {";
			$return .= "	color: ".$this->_options['overlay_header_color'].";";
			$return .= "	font-size: ".$this->_options['overlay_header_size']."px;";
				if ((isset($this->_options['overlay_header_font'])) && ($this->_options['overlay_header_font'] != '')) {
					$return .= "	font-family: \"". $this->_options['overlay_header_font']."\";";
				}
			$return .= "	line-height: 1;";
			$return .= "}";
			$return .= "#rotating-images-rotator-container_".$this->_instanceCount." .rotating-images-title-overlay-subheader_".$this->_instanceCount." a {";
			$return .= "	color: ".$this->_options['overlay_subheader_color'].";";
			$return .= "	font-size: ".$this->_options['overlay_subheader_size']."px;";
				if ((isset($this->_options['overlay_subheader_font'])) && ($this->_options['overlay_subheader_font'] != '')) {
					$return .= "	font-family: \"".$this->_options['overlay_subheader_font']."\";";
				}
			$return .= "	line-height: 1;";
			$return .= "}";
			$return .= "</style>";
			
		
		$return .= '<div id="rotating-images-rotator-wrapper_'.$this->_instanceCount.'" style="position:relative;">';
		$return .= '	<div id="rotating-images-rotator-container_'.$this->_instanceCount.'" style="position:relative;">';
		$return .= '		<div id="rotating-images-rotator_'.$this->_instanceCount.'">' . "\n";
		if ( ( '1' != $this->_options['enable_fade'] ) || ( count( $files ) == 1 ) ) {
			$return .= '			<img src="' . $files[0]['image'] . '" style="max-width: 100%;" alt="image" />' . "\n";
		}
		$return .= ' 		<!-- placeholder --></div>' . "\n";
				 if ( ( false === $this->_defaults['force_disable_overlay'] ) && ! empty( $this->_options['enable_overlay'] ) ) : 
					
		$return .= '			<div class="rotating-images-title-overlay_'.$this->_instanceCount.'">';
						 if ( 'middle' === $this->_options['overlay_text_vertical_position'] ) :
							
		$return .= '					<div class="jq-update-responsive-size_' . $this->_instanceCount . '" style="display: table; height: '. ( $this->_options['height'] - ( $this->_options['overlay_text_padding'] * 2 ) ).'px; width: '.( $this->_options['width'] - ( $this->_options['overlay_text_padding'] * 2 ) ).'px; #position: relative; overflow: hidden;">';
		$return .= '						<div class="jq-update-responsive-size_' . $this->_instanceCount . '" style=" display: table-cell; vertical-align: middle; left: 0; #position: absolute; #top: 50%; display: table-cell;">';
		$return .= '							<div style="margin-right: auto; margin-left:auto; #position: relative; #top: -50%; width: '.'px; display:block;">';
		$return .= 									$overlay_text;
		$return .= '						</div>';
		$return .= '					</div>';
		$return .= ' 					</div>';
						 else : 
							
							$return .= $overlay_text;
						endif;
						
		$return .= '			</div>';
				endif;
				
				if ( ! empty( $files[0]['url'] ) ) :
					
					$target = ( ! empty ( $this->_options['open_new_window'] ) ) ? ' target="_blank"' : '';
					
		$return .= '			<a href="'.$files[0]['url'].'" class="rotating-images-link_'.$this->_instanceCount;

		if ( ( $this->_options['enable_fade'] != 1 ) || (count( $files )==1) ) {
			$return .=  ' rotating-images-link-overlay_'.$this->_instanceCount;
		}
		$return .= '" '. $target.'>';
		
		$return .= '				<!-- filler content -->';
		$return .= '			</a>';
				endif;
				
		$return .= 	'</div>';
		$return .= '</div>';
		
			if ( ! empty( $this->_options['enable_responsive'] ) ) { 
				$return .= '<script type="text/javascript">' . "\n";
				$return .= ' var it_images = jQuery("#rotating-images-rotator_' . $this->_instanceCount . ' img");' . "\n";
				$return .= 'jQuery(window).on("load", function() {' . "\n";
				$return .= '	it_images.imagesLoaded(function() {' . "\n";  
			//	if ( ( $this->_options['enable_fade'] = 1 ) && (count( $files ) > 1) ) {
					$return .= '		it_update_size_' . $this->_instanceCount . '();' . "\n";
			//	}
				$return .= '		it_update_font_load_' . $this->_instanceCount . '();' . "\n";
			 	$return .= '	});' . "\n";
				$return .= ' });' . "\n";
				$return .= '	jQuery(window).resize(function() {' . "\n";
			//	if ( ( $this->_options['enable_fade'] = 1 ) && (count( $files ) > 1) ) {
					$return .= '		it_update_size_' . $this->_instanceCount . '();' . "\n";
			//	}
				$return .= '        	it_update_font_load_' . $this->_instanceCount . '();' . "\n";
				$return .= '	});' . "\n";
				$return .= ' function it_update_size_' . $this->_instanceCount . '() {' . "\n";
				if ( ( $this->_options['enable_fade'] = 1 ) && (count( $files ) > 1) ) {
					$return .= ' 	var containerHeight = jQuery( "#rotating-images-rotator_' . $this->_instanceCount . ' img" ).height();' . "\n";
				} else { 
					$return .= ' 	var containerHeight = jQuery( "#rotating-images-rotator-wrapper_' . $this->_instanceCount . ' img" ).height();' . "\n";
				}
				$return .= '		jQuery("#rotating-images-rotator-wrapper_' . $this->_instanceCount . ', #rotating-images-rotator_' . $this->_instanceCount . ', #rotating-images-rotator-container_'.$this->_instanceCount.'").css({' . "\n";
				$return .= '			height: containerHeight + "px"' . "\n";
				$return	.= '		});' . "\n";
				$return .= '}' . "\n";
				$return .= ' function it_update_font_load_' . $this->_instanceCount . '() {' . "\n";
				$return .= ' 	var containerWidth = jQuery( "#rotating-images-rotator_' . $this->_instanceCount . '" ).width();' . "\n";
				$return .= ' 	var containerHeight = jQuery( "#rotating-images-rotator_' . $this->_instanceCount . '" ).height();' . "\n";
				$return .= '    var updatedcontainerHeight = containerHeight - 20;' . "\n";
				$return .= '    var updatedcontainerWidth = containerWidth - 20;' . "\n";
				if ( 'bottom' != $this->_options['overlay_text_vertical_position'] ) { 
				$return .= '	jQuery(".jq-update-responsive-size_' . $this->_instanceCount . ', .rotating-images-title-overlay_' . $this->_instanceCount . '").height(updatedcontainerHeight);' . "\n";
				}
				$return .= '	jQuery(".jq-update-responsive-size_' . $this->_instanceCount . ', .rotating-images-title-overlay_' . $this->_instanceCount . '").width(updatedcontainerWidth);' . "\n";
				$return .= ' 	var fixed_width = ' . $this->_options['width'] . ';' . "\n";
				$return .= ' 	var font_header_size = '.$this->_options['overlay_header_size'].';' . "\n";
				$return .= ' 	var font_subheader_size = '.$this->_options['overlay_subheader_size'].';' . "\n";
				$return .= ' 	var percentage = fixed_width / containerWidth;' . "\n"; 
				$return .= '    var newfontheaderSize = Math.floor(font_header_size / percentage);' . "\n";
				$return .= '    var newfontsubheaderSize = Math.floor(font_subheader_size / percentage);' . "\n";				
				$return .= ' 	var hearderElm = jQuery(".rotating-images-header-text_'.$this->_instanceCount.'");' . "\n";
				$return .= ' 	var subhearderElm = jQuery(".rotating-images-subheader-text_'.$this->_instanceCount.'");' . "\n";
				$return .= ' 	hearderElm.attr("style", "font-size: " + newfontheaderSize + "px;" );' . "\n";
				$return .= ' 	subhearderElm.attr("style", "font-size: " + newfontsubheaderSize + "px;" );' . "\n";
				$return .= '		}' . "\n";
				$return .= '</script>' . "\n";
			}
		
		

			} // End error if.
			
			if ($widget == true) {
				echo $return;
			} else {
				return $return;
			}
		} // End fadeimages.
		
		function _showStatusMessage( $message ) {
			
?>
	<div id="message" class="updated fade"><p><strong><?php echo $message; ?></strong></p></div>
<?php
			
		}
		
		function _showErrorMessage( $message ) {
			
?>
	<div id="message" class="error"><p><strong><?php echo $message; ?></strong></p></div>
<?php
			
		}
		
		function _merge_defaults( $values, $defaults, $force = false ) {
			if ( ! $this->_is_associative_array( $defaults ) ) {
				if ( ! isset( $values ) )
					return $defaults;
				
				if ( false === $force )
					return $values;
				
				if ( isset( $values ) || is_array( $values ) )
					return $values;
				return $defaults;
			}
			
			foreach ( (array) $defaults as $key => $val ) {
				if ( ! isset( $values[$key] ) )
					$values[$key] = null;
				
				$values[$key] = $this->_merge_defaults($values[$key], $val, $force );
			}
			
			return $values;
		}
		
		function _is_associative_array( &$array ) {
			if ( ! is_array( $array ) || empty( $array ) )
				return false;
			
			$next = 0;
			
			foreach ( $array as $k => $v )
				if ( $k !== $next++ )
					return true;
			
			return false;
		}
		
		
		// Utility Functions //////////////////////////
		
		function _sortImages() {
			if (is_null($this->_options['image_ids'])) $this->_load(); // Fix missing header
			if ( 'ordered' === $this->_options['fade_sort'] )
				uksort( $this->_options['image_ids'], array( &$this, '_orderedSort' ) );
			else if ( 'alpha' === $this->_options['fade_sort'] )
				uksort( $this->_options['image_ids'], array( &$this, '_alphaSort' ) );
			else
				uksort( $this->_options['image_ids'], array( &$this, '_randomSort' ) );
		}
		
		function _orderedSort( $a, $b ) {
			$a = $this->_options['image_ids'][$a];
			$b = $this->_options['image_ids'][$b];
			
			if ( $a['order'] < $b['order'] )
				return -1;
			
			return 1;
		}
		
		function _alphaSort( $a, $b ) {
			$a = basename( get_attached_file( (array) $this->_options['image_ids'][$a]['attachment_id'] ) );
			$b = basename( get_attached_file( (array) $this->_options['image_ids'][$b]['attachment_id'] ) );
			
			return strnatcasecmp( $a, $b );
		}
		
		function _randomSort( $a, $b ) {
			if ( mt_rand( 0, 1 ) === 1 )
				return -1;
			
			return 1;
		}
		
		function _sortGroupsByName( $a, $b ) {
			if ( $this->_options['groups'][$a]['name'] < $this->_options['groups'][$b]['name'] )
				return -1;
			
			return 1;
		}

		function tip( $message, $title = '', $echo_tip = true ) {
			$tip = ' <a class="pluginbuddy_tip" title="' . $title . ' - ' . $message . '"><img src="' . $this->_pluginURL . '/images/it_tip.png" alt="(?)" /></a>';
			if ( $echo_tip === true ) {
				echo $tip;
			} else {
				return $tip;
			}
		}
		
		function _initializeImages() {
			if ( $dir = @opendir( $this->_pluginPath . '/images/random/' ) ) {
				require_once( $this->_pluginPath . '/lib/file-utility/file-utility.php' );
				
				if ( ! ( ( $uploads = wp_upload_dir() ) && false === $uploads['error'] ) )
					return new WP_Error( 'upload_dir_failure', 'Unable to load images into the uploads directory: ' . $uploads['error'] );
				
				
				$this->_options['image_ids'] = array();
				
				$order = 1;
				
				while ( ( $file = readdir( $dir ) ) !== false ) {
					if ( is_file( $this->_pluginPath . '/images/random/' . $file ) && ( preg_match( '/gif$|jpg$|jpeg$|png$/i', $file ) ) ) {
						$filename = wp_unique_filename( $uploads['path'], basename( $file ) );
						
						// Move the file to the uploads dir
						$new_file = $uploads['path'] . "/$filename";
						if ( false === copy( $this->_pluginPath . '/images/random/' . $file, $new_file ) ) {
							closedir( $dir );
							return new WP_Error( 'copy_file_failure', 'The theme images were unable to be loaded into the uploads directory' );
						}
						
						// Set correct file permissions
						$stat = stat( dirname( $new_file ));
						$perms = $stat['mode'] & 0000666;
						@chmod( $new_file, $perms );
						
						// Compute the URL
						$url = $uploads['url'] . "/$filename";
						
						
						$wp_filetype = wp_check_filetype( $file );
						$type = $wp_filetype['type'];
						
						
						$file_obj['url'] = $url;
						$file_obj['type'] = $type;
						$file_obj['file'] = $new_file;
						
						
						$title = preg_replace( '/\.[^.]+$/', '', basename( $file ) );
						$content = '';
						
						require_once( ABSPATH . 'wp-admin/includes/image.php' );
						
						// use image exif/iptc data for title and caption defaults if possible
						if ( $image_meta = @wp_read_image_metadata( $new_file ) ) {
							if ( trim( $image_meta['title'] ) )
								$title = $image_meta['title'];
							if ( trim( $image_meta['caption'] ) )
								$content = $image_meta['caption'];
						}
						
						// Construct the attachment array
						$attachment = array(
							'post_mime_type' => $type,
							'guid' => $url,
							'post_title' => $title,
							'post_content' => $content
						);
						
						// Save the data
						$id = wp_insert_attachment( $attachment, $new_file );
						if ( ! is_wp_error( $id ) ) {
							wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $new_file ) );
						}
						
						
						$entry = array();
						$entry['attachment_id'] = $id;
						$entry['order'] = $order;
						$entry['url'] = '';
						
						$this->_options['image_ids'][] = $entry;
						
						
						$order++;
					}
				}
				
				closedir( $dir );
				
				
				$this->save();
			}
		}
		
	}
	
	$iThemesRotatingImages = new iThemesRotatingImages();
}


// Widget Functionality //////////////////////////////////////


/**
 * widget_iThemesRotatingImages Class
 *
 * Adds widget capabilities to Rotating Images.
 *
 * Author:	Dustin Bolton
 * Date:	January 2010
 *
 */

class widget_iThemesRotatingImages extends WP_Widget 
{
	var $_widget_control_width = 300;
	var $_widget_control_height = 300;
	
	/**
	 * widget_iThemesRotatingImages::widget_iThemesRotatingImages()
	 * 
	 * Default constructor ran by WP_Widget class.
	 * 
	 * @return void
	 */
	function __construct() {
		$widget_ops = array('description' => __('Display Rotating Images as a widget.', 'iThemesRotatingImages'));
		parent::__construct('iThemesRotatingImages', __('Rotating Images'), $widget_ops);
	}
	
	/**
	 * widget_iThemesRotatingImages::widget()
	 *
	 * Display public widget.
	 *
	 * @param	array	$args		Widget arguments -- currently not in use.
	 * @param	array	$instance	Instance data including title, group id, etc.
	 * @return	void
	 */
	function widget($args, $instance) {
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );
		echo $before_widget;
		
		if ( $title )
			echo $before_title . $title . $after_title;
		
		$group = intval( $instance['group'] );
		do_action( 'ithemes_rotating_images_fade_images', $group, true);
		
		echo $after_widget;
	}
	
	/**
	 * widget_iThemesRotatingImages::update()
	 *
	 * Save widget form settings.
	 *
	 * @param	array	$new_instance	NEW instance data including title, group id, etc.
	 * @param	array	$old_instance	PREVIOUS instance data including title, group id, etc.
	 * @return	void
	 */
	function update($new_instance, $old_instance) {
		if (!isset($new_instance['submit'])) {
			return false;
		}
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['group'] = intval($new_instance['group']);
		return $instance;
	}
		
	/**
	 * widget_iThemesRotatingImages::form()
	 *
	 * Display widget control panel.
	 *
	 * @param	array	$instance	Instance data including title, group id, etc.
	 * @return	void
	 */
	function form($instance) {
		//global $wpdb, $ithemes_theme_options;
		
		// Group indicates rotating images group for this instance.
		$group = ( isset( $instance['group'] ) ) ? $instance['group'] : '';
		$title = ( isset( $instance['title'] ) ) ? $instance['title'] : '';
		
		$instance = wp_parse_args( (array) $instance, array( 'title' => __( 'Rotating Images', 'iThemesRotatingImages' ), 'group' => $group ) );
		$title = esc_attr( $title );
		$group = intval( $group );
		
		$temp_options = get_option('ithemes-rotating-images');
		
?>
		<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'iThemesRotatingImages'); ?>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
		</label>
		
		<label for="<?php echo $this->get_field_id('group'); ?>"><?php _e('Image Group:', 'iThemesRotatingImages'); ?>
			<select class="widefat" id="<?php echo $this->get_field_id('group'); ?>" name="<?php echo $this->get_field_name('group'); ?>">
				<?php
					foreach ( (array)$temp_options['groups'] as $id => $grouploop ) {
						$selected = '';
						if ( $group == $id ) { $selected = ' selected '; }
						echo '<option value="' . $id . '"' . $selected . '>' . $grouploop['name'] . '</option>';
					}
				?>
			</select>
		</label>
		
		<input type="hidden" id="<?php echo $this->get_field_id('submit'); ?>" name="<?php echo $this->get_field_name('submit'); ?>" value="1" />
<?php

	}
		

} // End widget_iThemesRotatingImages class.

// Register function to create widget.
add_action('widgets_init', 'widget_iThemesRotatingImages_init');

/**
 * widget_iThemesRotatingImages_init()
 *
 * Instantiate widget via WP registration.
 *
 * @return	void
 */
function widget_iThemesRotatingImages_init() {
	register_widget('widget_iThemesRotatingImages');
}

//iThemes updater instantiation
function ithemes_rotating_images_updater_register( $updater ) { 
    $updater->register( 'rotating-images', __FILE__ );
}

add_action( 'ithemes_updater_register', 'ithemes_rotating_images_updater_register' );

require( dirname( __FILE__ ) . '/lib/updater/load.php' );
?>
