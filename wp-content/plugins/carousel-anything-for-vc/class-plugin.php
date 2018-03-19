<?php
/**
Plugin Name: Carousel Anything for VC
Description: Adds a flexible touch-ready carousel system in Visual Composer.
Author: Gambit Technologies Inc.
Version: 1.9
Author URI: http://gambit.ph
Plugin URI: http://codecanyon.net/item/carousel-anything-for-visual-composer/8621711
Text Domain: carousel-anything
Domain Path: /languages
SKU: CAROUSEL
 *
 * The main plugin file
 *
 * @package Carousel Anything for VC
 **/

defined( 'VERSION_GAMBIT_CAROUSEL_ANYTHING' ) or define( 'VERSION_GAMBIT_CAROUSEL_ANYTHING', '1.9' );

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly.
}
defined( 'GAMBIT_CAROUSEL_ANYTHING' ) or define( 'GAMBIT_CAROUSEL_ANYTHING', 'carousel-anything' );

defined( 'GAMBIT_CAROUSEL_ANYTHING_FILE' ) or define( 'GAMBIT_CAROUSEL_ANYTHING_FILE', __FILE__ );

// Plugin automatic updates.
require_once( 'class-admin-license.php' );

// Load modules.
require_once( 'class-carousel-anything.php' );
require_once( 'class-carousel-posts.php' );
if ( ! class_exists( 'GambitCarouselShortcode' ) ) {

	/**
	 * Handles all pointers, autoupdates and others.
	 */
	class GambitCarouselShortcode {

		/**
		 * Hook into WordPress
		 *
		 * @return	void
		 * @since	1.0
		 */
		function __construct() {

			// Admin pointer reminders for automatic updates.
			require_once( 'class-admin-pointers.php' );
			if ( class_exists( 'GambitAdminPointers' ) ) {
				new GambitAdminPointers( array(
					'pointer_name' => 'gambitcarousel', // This should also be placed in uninstall.php.
					'header' => __( 'Automatic Updates', GAMBIT_CAROUSEL_ANYTHING ),
					'body' => __( 'Keep your Carousel Anything for VC plugin updated by entering your purchase code here.', GAMBIT_CAROUSEL_ANYTHING ),
				) );
			}

			// Our translations.
			add_action( 'plugins_loaded', array( $this, 'load_text_domain' ), 1 );

			// Gambit links.
			add_filter( 'plugin_row_meta', array( $this, 'plugin_links' ), 10, 2 );

		}


		/**
		 * Loads the translations.
		 *
		 * @return	void
		 * @since	1.0
		 */
		public function load_text_domain() {
			load_plugin_textdomain( GAMBIT_CAROUSEL_ANYTHING, false, basename( dirname( __FILE__ ) ) . '/languages/' );
		}


		/**
		 * Adds plugin links.
		 *
		 * @access	public
		 * @param	array  $plugin_meta The current array of links.
		 * @param	string $plugin_file The plugin file.
		 * @return	array The current array of links together with our additions.
		 * @since	1.0
		 **/
		public function plugin_links( $plugin_meta, $plugin_file ) {
			if ( plugin_basename( __FILE__ ) == $plugin_file ) {
				$plugin_data = get_plugin_data( __FILE__ );

				$plugin_meta[] = sprintf( "<a href='%s' target='_blank'>%s</a>",
					'http://support.gambit.ph?utm_source=' . urlencode( $plugin_data['Name'] ) . '&utm_medium=plugin_link',
					__( 'Get Customer Support', GAMBIT_CAROUSEL_ANYTHING )
				);
				$plugin_meta[] = sprintf( "<a href='%s' target='_blank'>%s</a>",
					'https://gambit.ph/plugins?utm_source=' . urlencode( $plugin_data['Name'] ) . '&utm_medium=plugin_link',
					__( 'Get More Plugins', GAMBIT_CAROUSEL_ANYTHING )
				);
			}
			return $plugin_meta;
		}
	}

	new GambitCarouselShortcode();
}
