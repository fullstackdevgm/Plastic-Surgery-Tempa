<?php
/**
 * Pulls a singular post.
 *
 * @package	Carousel Anything for VC
 */

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly.
}
// Initializes plugin class.
if ( ! class_exists( 'GambitSinglePostPull' ) ) {

	/**
	 * The class that makes post pulling possible.
	 */
	class GambitSinglePostPull {

		/**
		 * Used for loading stuff only once during a page load.
		 *
		 * @var firstload
		 */
	    private static $first_load = 0;

		/**
		 * Hook into WordPress.
		 *
		 * @return	void
		 * @since	1.0
		 */
		function __construct() {

			// Initializes as a Visual Composer addon.
			add_action( 'init', array( $this, 'create_shortcode' ), 999 );

			// Makes the plugin function accessible as a shortcode.
			add_shortcode( 'single_post', array( $this, 'render_shortcode' ) );

		}


		/**
		 * Pulls a list of post types and their slugs.
		 *
		 * @return	$posttypes
		 * @since	1.5
		 */
		public function get_post_types() {
			$posttypes = array( 'Posts' => 'post', 'Pages' => 'page' );
			$args = array(
			   'public' => true,
			   '_builtin' => false,
			);
			$post_types = get_post_types( $args, 'objects' );
			foreach ( $post_types  as $post_type ) {
				$posttypes[] = $post_type->rewrite['slug'];
			}

			return $posttypes;
		}

		/**
		 * Pulls details of a post that will be available as a dropdown list.
		 *
		 * @return	array $posttypes
		 * @since	1.5
		 */
		public function get_post() {

			$allposttypes = $this->get_post_types();

			foreach ( $allposttypes as $posts ) {

				// The query arguments.
				$args = array(
					'post_type' => $posts,
				    'nopaging' => true,
				    'order' => 'DESC',
				    'orderby' => 'ID',
					'post_status' => 'publish',
				);

				// Create the related query.
				$query = new WP_Query( $args );

				// Check if there is any related posts.
				if ( $query->have_posts() ) {

					while ( $query->have_posts() ) : $query->the_post();
						$the_title = get_the_title();
						if ( empty( $the_title ) ) {
							$title = 'ID ' . get_the_ID();
						} else {
							$title = get_the_title();
						}

					    $postslist[ get_post_type( get_the_ID() ) . ' - ' . $title ] = get_the_ID();
					endwhile;

				}
			}

			return $postslist;

		}


		/**
		 * Creates our shortcode settings in Visual Composer
		 *
		 * @return	void
		 * @since	1.0
		 */
		public function create_shortcode() {
			if ( ! function_exists( 'vc_map' ) ) {
				return;
			}

			vc_map( array(
			    'name' => __( 'Single Post', GAMBIT_CAROUSEL_ANYTHING ),
			    'base' => 'single_post',
				'icon' => plugins_url( 'carousel-anything/images/vc-icon.png', GAMBIT_CAROUSEL_ANYTHING_FILE ),
				'description' => __( 'Pull up a single post.', GAMBIT_CAROUSEL_ANYTHING ),
			    'params' => array(
					array(
						'type' => 'dropdown',
						'heading' => __( 'Choose a Post', GAMBIT_CAROUSEL_ANYTHING ),
						'param_name' => 'post',
						'value' => $this->get_post(),
	                    'description' => '',
					),
					array(
						'type' => 'checkbox',
						'heading' => __( "Display the Post's...", GAMBIT_CAROUSEL_ANYTHING ),
						'param_name' => 'show_details',
						'value' => array(
							__( 'Author', GAMBIT_CAROUSEL_ANYTHING ) => 'author',
							__( 'Date Published', GAMBIT_CAROUSEL_ANYTHING ) => 'publish',
							// __( 'Category', GAMBIT_CAROUSEL_ANYTHING ) => 'category',
							// __( 'Tags', GAMBIT_CAROUSEL_ANYTHING ) => 'tags',
							__( 'Show excerpt instead of the full post.', GAMBIT_CAROUSEL_ANYTHING ) => 'excerpt',
						),
						'description' => __( 'If there are no excerpts in the post, the full post will be displayed but reduced. Categories will not be printed if the post does not belong to any category', GAMBIT_CAROUSEL_ANYTHING ),
					),
					array(
						'type' => 'colorpicker',
						'heading' => __( 'Posts Background Color', GAMBIT_CAROUSEL_ANYTHING ),
						'param_name' => 'post_bg_color',
						'value' => '',
						'description' => __( 'The background color of the post.', GAMBIT_CAROUSEL_ANYTHING ),
					),
					array(
						'type' => 'dropdown',
						'heading' => 'Featured Image Usage',
						'param_name' => 'featured',
						'value' => array(
	                        __( 'Do not use Featured Image at all', GAMBIT_CAROUSEL_ANYTHING ) => 'none',
	                        __( 'Use as background image', GAMBIT_CAROUSEL_ANYTHING ) => 'bg',
							__( 'Display at the top with no alignment', GAMBIT_CAROUSEL_ANYTHING ) => 'floattop',
	                        __( 'Display at the left', GAMBIT_CAROUSEL_ANYTHING ) => 'floatleft',
	                        __( 'Display at the center', GAMBIT_CAROUSEL_ANYTHING ) => 'floatcenter',
	                        __( 'Display at the right', GAMBIT_CAROUSEL_ANYTHING ) => 'floatright',
	                    ),
						'description' => __( 'If a post does not have a Featured Image, it will not be rendered.', GAMBIT_CAROUSEL_ANYTHING ),
					),
					array(
						'type' => 'dropdown',
						'heading' => 'Alignment',
						'param_name' => 'alignment',
						'value' => array(
	                        __( 'No alignment', GAMBIT_CAROUSEL_ANYTHING ) => '',
	                        __( 'Align left', GAMBIT_CAROUSEL_ANYTHING ) => 'alignleft',
	                        __( 'Align center', GAMBIT_CAROUSEL_ANYTHING ) => 'aligncenter',
	                        __( 'Align right', GAMBIT_CAROUSEL_ANYTHING ) => 'alignright',
	                    ),
						'description' => __( 'If desired, you can force content alignment of the particular pulled post.', GAMBIT_CAROUSEL_ANYTHING ),
					),
				),
			) );
		}


		/**
		 * Shortcode logic.
		 *
		 * @param array  $atts - The attributes of the shortcode.
		 * @param string $content - The content enclosed inside the shortcode if any.
		 * @return string The rendered html.
		 * @since 1.0
		 */
		public function render_shortcode( $atts, $content = null ) {
	        $defaults = array(
				'post' => '',
				'show_details' => '',
				'post_bg_color' => '',
				'featured' => 'none',
				'alignment' => '',
	        );
			if ( empty( $atts ) ) {
				$atts = array();
			}
			$atts = array_merge( $defaults, $atts );

			$css = '';

			wp_enqueue_style( 'carousel-anything-single-post', plugins_url( '/carousel-anything/css/single-post.css', __FILE__ ), array(), VERSION_GAMBIT_CAROUSEL_ANYTHING );

			// Set up alignment.
			$alignment = '';
			if ( ! empty( $atts['alignment'] ) ) {
				$alignment = ' ' . $atts['alignment'];
			}

			if ( 'none' != $atts['post'] ) {
				$thumbnail = '';
				$post_thumbnail_id = get_post_thumbnail_id( $atts['post'] );
				// Jetpack issue, Photon is not giving us the image dimensions.
				// This snippet gets the dimensions for us.
				add_filter( 'jetpack_photon_override_image_downsize', '__return_true' );
				$image_info = wp_get_attachment_image_src( $post_thumbnail_id, 'full' );
				remove_filter( 'jetpack_photon_override_image_downsize', '__return_true' );

				$attachment_image = wp_get_attachment_image_src( $post_thumbnail_id, 'full' );
				$bg_image_width = $image_info[1];
				$bg_image_height = $image_info[2];
				$bg_image = $attachment_image[0];

				if ( 'bg' == $atts['featured'] ) {
					$css .= 'background-image: url(' . $attachment_image[0] . ');';
				}
				if ( in_array( $atts['featured'], array( 'left', 'center', 'right' ) ) ) {
					$thumbnail .= '<img class="post-image' . $alignment . '" src="' . $attachment_image[0] . '">';
				}
			}

			$postdata = explode( ',', $atts['show_details'] );

			$post = get_post( $atts['post'] );

			$excerpt = ( empty( $post->post_excerpt ) ? substr( $post->post_content, 0, 50 ) . '...' : $post->post_excerpt );

			$content = '';

			if ( $thumbnail ) {
				$content .= $thumbnail;
			}

			$content .= '<h2 class="post-title' . $alignment . '">' . $post->post_title . '</h2>';

			if ( in_array( 'author', $postdata ) ) {
				$content .= '<p class="post-author' . $alignment . '">' . get_the_author_meta( 'display_name', $post->post_author ) . '</p>';
			}

			if ( in_array( 'publish', $postdata ) ) {
				$content .= '<p class="post-published' . $alignment . '">' . $post->post_date . '</p>';
			}

			/*
			Removed for now.
			if ( in_array( 'category', $postdata ) ) {
				$categories = get_the_category( $atts['post'] );
				$output = '';
				if ( $categories ) {
					foreach($categories as $category) {
						$output .= $category->cat_name . ' ';
					}
					$content .= '<p class="post-category">' . $output . '</p>';
				}
			}
			*/

			$content .= '<div class="post-content' . $alignment . '">' . ( in_array( 'excerpt', $postdata ) ? $excerpt : $post->post_content ) . '</div>';

			$ret = '';
			$ret .= "<div class='single_post_output'>" . do_shortcode( $content ) . '</div>';

			return $ret;
		}
	}

	new GambitSinglePostPull();
}
