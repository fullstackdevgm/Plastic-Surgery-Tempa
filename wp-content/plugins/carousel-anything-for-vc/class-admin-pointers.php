<?php
/**
 * Initializes pointer for the admin license page.
 *
 * @package Carousel Anything for VC
 */

if ( ! class_exists( 'GambitAdminPointers' ) ) {

	/**
	 * Makes the WordPress pointers work for our product.
	 *
	 * @package	carousel-anything
	 * @class GambitAdminPointers
	 */
	class GambitAdminPointers {

		/**
		 * Holds the number of pointers currently active.
		 *
		 * @var $pointers_active
		 */
		public static $pointers_active = 0;

		/**
		 * Everything here will run immediately.
		 *
		 * @param array $settings - Takes up settings first defined in each plugin's class-plugin.php file.
		 */
		function __construct( $settings = array() ) {

			// Initialize default settings.
			$defaults = array(
				'pointer_name' => 'gambit',
				'header' => __( 'Automatic Updates', 'default' ),
				'body' => __( 'Keep your plugin updated by entering your purchase code here.', 'default' ),
			);
			$this->settings = array_merge( $defaults, $settings );

			// Pointers are only allowed to have names in small caps, a WP requirement.
			$this->settings['pointer_name'] = strtolower( $this->settings['pointer_name'] );

			// Initialize admin point headers.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_pointer_script' ) );
			add_action( 'admin_print_footer_scripts', array( $this, 'print_pointer_script' ) );
		}

		/**
		 * Enqueues our pointer scripts.
		 */
		public function enqueue_pointer_script() {
			if ( $this->form_admin_pointer() ) {
				wp_enqueue_script( 'wp-pointer' );
				wp_enqueue_style( 'wp-pointer' );
			}
		}

		/**
		 * Now print out the pointer script.
		 */
		public function print_pointer_script() {
			if ( ! $this->form_admin_pointer() ) {
				return;
			}

			// Get the pointer.
			$admin_pointer = $this->form_admin_pointer();

			// Only allow a single pointer to exist.
			// If another Gambit pointer has already been displayed, never show ours so we do not clutter the screen.
			// This might happen if multiple plugins are activated at the same time.
			if ( self::$pointers_active > 0 ) {
				$dismissed = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );

				if ( ! in_array( $admin_pointer['name'], $dismissed ) ) {
					$dismissed[] = $admin_pointer['name'];
					update_user_meta( get_current_user_id(), 'dismissed_wp_pointers', implode( ',', $dismissed ) );
				}

				return;
			}

			// Start the pointer.
			?>
			<script type="text/javascript">
				( function($) {
					var $a = $('#menu-plugins');
					if ( $('a[href="plugins.php?page=gambit_plugins"]').length > 0 ) {
						if ( $('a[href="plugins.php?page=gambit_plugins"]').offset().top > 0 ) {
							$a = $('a[href="plugins.php?page=gambit_plugins"]');
						}
					}
					$a.pointer( {
						content: '<?php echo wp_kses( $admin_pointer['content'], wp_kses_allowed_html( 'post' ) ) ?>',
						position: {
							edge: 'left',
							align: 'middle'
						},
						close: function() {
							$.post( ajaxurl, {
								pointer: '<?php echo wp_kses( $admin_pointer['name'], wp_kses_allowed_html( 'post' ) ) ?>',
								action: 'dismiss-wp-pointer'
							} );
						}
					} ).pointer( 'open' );
				} )(jQuery);
			</script>
			<?php

			// Admin pointers displayed + 1.
			self::$pointers_active++;
		}

		/**
		 * Prints out the pointer's content.
		 */
		public function form_admin_pointer() {
			$dismissed = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );

			if ( in_array( $this->settings['pointer_name'], $dismissed ) ) {
				return false;
			}

			$content = '<h3>' . esc_attr( $this->settings['header'] ) . '</h3>';
			$content .= '<p>' . esc_attr( $this->settings['body'] ) . '</p>';

			return array(
				'name' => $this->settings['pointer_name'],
				'content' => $content,
			);
		}
	}

}
