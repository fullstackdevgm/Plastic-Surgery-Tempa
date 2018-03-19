<?php
/**
 * Pulls posts from a specified posttype.
 *
 * @package	Carousel Anything for VC
 */

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly.
}
// Initializes plugin class.
if ( ! class_exists( 'GambitCarouselPosts' ) ) {

	/**
	 * Does all the heavy lifting to pull posts.
	 */
	class GambitCarouselPosts {

		/**
		 * Sets a unique identifier of each carousel posts.
		 *
		 * @var id
		 */
	    private static $id = 0;

		/**
		 * Hook into WordPress.
		 *
		 * @return	void
		 * @since	1.0
		 */
		function __construct() {

			// Initializes VC shortcode.
			add_filter( 'init', array( $this, 'create_cp_shortcodes' ), 999 );

			// Render shortcode for the plugin.
			add_shortcode( 'carousel_posts', array( $this, 'render_cp_shortcode' ) );
		}


		/**
		 * Pulls a list of options with dependencies.
		 *
		 * @return	$posttypes
		 * @since	1.5
		 */
		function generate_options() {
			// Initialize option.
			$tax_dependency = array();
			$term_dependency = array();
			$the_terms = array();
			$taxonomy_names = array();
			$all_taxonomies = array();
			$taxonomy_label = array();
			$all_terms = array();

			// First, pull all post types.
			$post_types = $this->get_post_type( 'array' );

			// And make an output here. This generates an option in VC.
			$output[] = array(
				'type' => 'dropdown',
				'heading' => __( 'Select Post Type we will be using', GAMBIT_CAROUSEL_ANYTHING ),
				'param_name' => 'posttype',
				'value' => $this->get_post_type(),
				'description' => __( 'Choose the Post Type to use to populate the Carousel.<br />If your post type has a taxonomy like Category, and its terms has posts associated with it, a new pulldown below will appear.', GAMBIT_CAROUSEL_ANYTHING ),
				'group' => __( 'Contents', GAMBIT_CAROUSEL_ANYTHING ),
				'dependency' => array(
					'element' => 'force_posttype',
					'value' => 'false',
				),
			);

			// Now pull their taxonomies, using the array generated.
			foreach ( $post_types['slug'] as $post_type ) {

				// This does the dirty work of getting the taxonomies, it returns as an array object.
				$taxonomy_names[ $post_type ] = get_object_taxonomies( $post_type );

				// No taxonomy found? Terminate.
				if ( ! is_array( $taxonomy_names[ $post_type ] ) ) {
					break;
				}

				// Now parse the taxonomy contents.
				if ( ! empty( $taxonomy_names[ $post_type ] ) ) {
					foreach ( $taxonomy_names[ $post_type ] as $taxonomy_name ) {
						$all_taxonomies[] = $taxonomy_name;
						$tax = get_taxonomy( $taxonomy_name );

						// Store the Taxonomy name so we have a human-readable name for the VC option later.
						$taxonomy_label[ $taxonomy_name ] = $tax->labels->name;

						// Populate dependency data.
						$tax_dependency[ $taxonomy_name ][] = $post_type;
					}
				}

				// Iterate through the collected taxonomy and terms and now print them all.
				if ( count( $taxonomy_names[ $post_type ] ) > 0 ) {
					// Initialize the array for collecting the terms selectable.
					$all_terms[ $post_type ] = array();
					foreach ( $taxonomy_names[ $post_type ] as $taxonomy_name ) {
						// Does the heavy lifting of getting all the terms in a given taxonomy.
						$the_term_names[ $post_type ] = $this->get_terms( $taxonomy_name );
						// The temporary placeholder for collected terms.
						$names_now[ $post_type ] = array();
						if ( count( $the_term_names[ $post_type ] ) > 0 ) {
							// Apply the default selection ONLY if there's a term to print.
							$names_now[ $post_type ] = array( __( 'All Categories', GAMBIT_CAROUSEL_ANYTHING ) => 'all' );
							foreach ( $the_term_names[ $post_type ] as $key => $value ) {
							    $key .= ' (' . $taxonomy_label[ $taxonomy_name ] . ')';
								$value = $taxonomy_name . '|' . $value;
								$names_now[ $post_type ][ $key ] = $value;
							}
							// This collects all the terms from separate taxonomies into a unified array identified by post type.
							$all_terms[ $post_type ] = array_merge( $all_terms[ $post_type ], $names_now[ $post_type ] );
						}
					}

					// Makes sure no duplicate terms get printed.
					$all_terms[ $post_type ] = array_unique( $all_terms[ $post_type ] );

					// Now print out the option ONLY if there's terms to use.
					if ( count( $all_terms[ $post_type ] ) > 0 ) {

						$caption = __( 'Select Category / Taxonomy for %s', GAMBIT_CAROUSEL_ANYTHING );
						$caption = str_replace( '%s', $post_types['name'][ $post_type ], $caption );

						$output[] = array(
							'type' => 'dropdown',
							'heading' => $caption,
							'param_name' => 'taxonomy_' . $post_type,
							'value' => $all_terms[ $post_type ],
		                    'description' => __( 'Choose the category terms for this post type to use.', GAMBIT_CAROUSEL_ANYTHING ),
							'dependency' => array(
								'element' => 'posttype',
								'value' => $tax_dependency[ $taxonomy_name ],
							),
							'group' => __( 'Contents', GAMBIT_CAROUSEL_ANYTHING ),
						);
					}
				}
			}
			return $output;
		}


		/**
		 * Limits wordcount.
		 *
		 * @param string $string is the target of limiting.
		 * @param number $offset details where to start. Needed by array_splice.
		 * @param number $word_limit tells how many words should be retained.
		 * @return string of subtracted words
		 * @since 1.6
		 */
		function limit_words( $string, $offset, $word_limit ) {
		    $words = explode( ' ',$string );
		    $out = implode( ' ', array_splice( $words, $offset, $word_limit ) );
			return $out;
		}


		/**
		 * Counts words.
		 *
		 * @param string $string is the subject of counting.
		 * @return number of words given.
		 * @since 1.6
		 */
		function count_words( $string ) {
		    $words = explode( ' ',$string );
		    return count( $words );
		}


		/**
		 * Pulls a list of terms with their Taxonomies
		 *
		 * @param string $taxonomy a needed argument for getting the terms by get_terms.
		 * @return array $posttypes, parsable in Visual Composer.
		 * @since 1.5
		 */
		function get_terms( $taxonomy ) {
			$output = array();

			$terms = get_terms( $taxonomy, array(
				'parent' => 0,
				'hide_empty' => false,
			) );

			if ( is_wp_error( $terms ) ) {
				return $output;
			}

			foreach ( $terms as $term ) {

				$output[ $term->name ] = $term->slug;
				$term_children = get_term_children( $term->term_id, $taxonomy );

				if ( is_wp_error( $term_children ) ) {
					continue;
				}

				// If the term has a child, this disambiguates the entry.
				foreach ( $term_children as $term_child_id ) {

					$term_child = get_term_by( 'id', $term_child_id, $taxonomy );

					if ( is_wp_error( $term_child ) ) {
						continue;
					}

					$output[ $term->name . ' - ' . $term_child->name ] = $term_child->slug;
				}
			}

			return $output;
		}


		/**
		 * Pulls a list of post types and their slugs.
		 *
		 * @param string $type - defines whether a simple array or multidimensional array will be returned as a list.
		 * @return $posttypes
		 * @since 1.5
		 */
		public function get_post_type( $type = 'list' ) {
			if ( 'list' == $type ) {
				$posttypes = array( 'Posts' => 'post', 'Pages' => 'page' );
			} else {
				$posttypes['slug'][] = 'post';
				$posttypes['slug'][] = 'page';
				$posttypes['name']['post'] = 'Posts';
				$posttypes['name']['page'] = 'Pages';
			}
			$args = array(
			   'public' => true,
			   '_builtin' => false,
			);
			$post_types = get_post_types( $args, 'objects' );

			foreach ( $post_types  as $post_type ) {
				$slugname = ( empty( $post_type->query_var ) ? $post_type->rewrite->slug : $post_type->query_var );
				if ( 'list' == $type ) {
					$posttypes[ $post_type->labels->name ] = $slugname;
				} else {

					$posttypes['slug'][] = $slugname;
					$posttypes['name'][ $slugname ] = $post_type->labels->name;
				}
			}

			return $posttypes;
		}


		/**
		 * Retrieves post terms and places them as classes in a post loop.
		 *
		 * @param int    $the_id - The post ID in a loop.
		 * @param string $the_taxonomy - The taxonomy, required by the query.
		 * @param string $output - Selects the kind of output. Choose from array or class.
		 * @return either a string or array, dependent on $output value.
		 * @since 1.5
		 */
		public function get_post_terms( $the_id, $the_taxonomy, $output = 'class' ) {
			$terms = get_the_terms( $the_id, $the_taxonomy );

			if ( is_wp_error( $terms ) ) {
				return false;
			}

			if ( ! empty( $terms ) ) {
			    foreach ( $terms as $term ) {
			    	$classes[] = $term->slug;
			    }
				$classes = array_map( function( $value ) { return 'gcp-term-'.$value;
				}, $classes );
			    if ( 'class' == $output ) {
			    	$out = implode( ' ', $classes );
			    } else {
					$out = $classes;
				}
				return $out;
			} else {
				return false;
			}
		}

		/**
		 * Creates the carousel element inside VC, for posts.
		 *
		 * @return	void
		 * @since	1.5
		 */
		public function create_cp_shortcodes() {
			if ( ! function_exists( 'vc_map' ) ) {
				return;
			}

			// Set up VC Element Array here since we use dynamically generated stuff.
			$vc_element = array(
			    'name' => __( 'Carousel Posts', GAMBIT_CAROUSEL_ANYTHING ),
			    'base' => 'carousel_posts',
				'icon' => plugins_url( 'carousel-anything/images/vc-icon.png', __FILE__ ),
				'description' => __( 'A modern and responsive posts carousel system.', GAMBIT_CAROUSEL_ANYTHING ),
				'as_parent' => array( 'only' => 'vc_row,vc_row_inner' ),
				'content_element' => true,
				'is_container' => true,
				'container_not_allowed' => false,
			);

			// Make the options here.
			$vc_element['params'] = array(
				array(
					'type' => 'textfield',
					'heading' => __( 'Items to display on screen', GAMBIT_CAROUSEL_ANYTHING ),
					'param_name' => 'items',
					'value' => '3',
					'group' => __( 'General Options', GAMBIT_CAROUSEL_ANYTHING ),
					'description' => __( 'Maximum items to display at a time.', GAMBIT_CAROUSEL_ANYTHING ),
				),
				/**
				 * Removed due to tablet issues.
				array(
					'type' => 'textfield',
					'heading' => __( 'Items to display on small desktops', GAMBIT_CAROUSEL_ANYTHING ),
					'param_name' => 'items_desktop_small',
					'value' => '2',
					'group' => __( 'General Options', GAMBIT_CAROUSEL_ANYTHING ),
					'description' => __( 'Maximum items to display at a time for smaller screened desktops.', GAMBIT_CAROUSEL_ANYTHING ),
				),
				 */
				array(
					'type' => 'textfield',
					'heading' => __( 'Items to display on tablets', GAMBIT_CAROUSEL_ANYTHING ),
					'param_name' => 'items_tablet',
					'value' => '2',
					'group' => __( 'General Options', GAMBIT_CAROUSEL_ANYTHING ),
		                    'description' => __( 'Maximum items to display at a time for tablet devices.', GAMBIT_CAROUSEL_ANYTHING ),
				),
				array(
					'type' => 'textfield',
					'heading' => __( 'Items to display on mobile phones', GAMBIT_CAROUSEL_ANYTHING ),
					'param_name' => 'items_mobile',
					'value' => '1',
					'group' => __( 'General Options', GAMBIT_CAROUSEL_ANYTHING ),
					'description' => __( 'Maximum items to display at a time for mobile devices.', GAMBIT_CAROUSEL_ANYTHING ),
				),
				array(
					'type' => 'dropdown',
					'heading' => __( 'Select Post Type source', GAMBIT_CAROUSEL_ANYTHING ),
					'param_name' => 'force_posttype',
					'value' => array(
						__( 'From Selection', GAMBIT_CAROUSEL_ANYTHING ) => 'false',
						__( 'From custom input', GAMBIT_CAROUSEL_ANYTHING ) => 'true',
					),
					'group' => __( 'Contents', GAMBIT_CAROUSEL_ANYTHING ),
				),
			);
			// Insert options generated by a function.
			$dynamic_options = $this->generate_options();
			foreach ( $dynamic_options as $dynamic_option ) {
				$vc_element['params'][] = $dynamic_option;
			}

			// Continue with the rest of the options.
			$other_options = array(
				array(
					'type' => 'textfield',
					'heading' => __( 'Specified post type entry', GAMBIT_CAROUSEL_ANYTHING ),
					'param_name' => 'specified_posttype',
					'value' => '',
					'description' => __( 'If selections do not work for you, you can specify manually the slug of the post type that you want to use instead. This can be seen by observing the URL and its GET variables in your browser window when you browse posts of a particular post type.', GAMBIT_CAROUSEL_ANYTHING ),
					'group' => __( 'Contents', GAMBIT_CAROUSEL_ANYTHING ),
					'dependency' => array(
						'element' => 'force_posttype',
						'value' => 'true',
					),
				),
				array(
					'type' => 'textfield',
					'heading' => __( 'Number of Total Posts', GAMBIT_CAROUSEL_ANYTHING ),
					'param_name' => 'numofallposts',
					'value' => '9',
					'description' => __( 'Specify how many posts to pull all in all. When this amount is reached, all other posts will be ignored. Zero or blank values will pull all posts regardless of post types.', GAMBIT_CAROUSEL_ANYTHING ),
					'group' => __( 'Contents', GAMBIT_CAROUSEL_ANYTHING ),
				),
				array(
					'type' => 'dropdown',
					'heading' => __( 'Post ordering', GAMBIT_CAROUSEL_ANYTHING ),
					'param_name' => 'orderby',
					'value' => array(
						__( 'By Date', GAMBIT_CAROUSEL_ANYTHING ) => 'date',
						__( 'By Post Title', GAMBIT_CAROUSEL_ANYTHING ) => 'title',
						__( 'By Comment count', GAMBIT_CAROUSEL_ANYTHING ) => 'comment_count',
						__( 'Random', GAMBIT_CAROUSEL_ANYTHING ) => 'rand',
					),
					'description' => __( 'Select the order of posting to pull.', GAMBIT_CAROUSEL_ANYTHING ),
					'group' => __( 'Contents', GAMBIT_CAROUSEL_ANYTHING ),
				),
				array(
					'type' => 'dropdown',
					'heading' => __( 'Post direction', GAMBIT_CAROUSEL_ANYTHING ),
					'param_name' => 'order_direction',
					'value' => array(
						__( 'Descending', GAMBIT_CAROUSEL_ANYTHING ) => 'DESC',
						__( 'Ascending', GAMBIT_CAROUSEL_ANYTHING ) => 'ASC',
					),
					'description' => __( 'Choose sorting order of the post.', GAMBIT_CAROUSEL_ANYTHING ),
					'group' => __( 'Contents', GAMBIT_CAROUSEL_ANYTHING ),
				),
				array(
					'type' => 'checkbox',
					'heading' => 'Post Details to Display',
					'param_name' => 'show_details',
					'value' => array(
						__( 'Featured Image', GAMBIT_CAROUSEL_ANYTHING ) => 'featured_image',
						__( 'Title', GAMBIT_CAROUSEL_ANYTHING ) => 'title',
						__( 'Author', GAMBIT_CAROUSEL_ANYTHING ) => 'author',
						__( 'Excerpt', GAMBIT_CAROUSEL_ANYTHING ) => 'excerpt',
					),
					'description' => '',
					'std' => 'featured_image,title,excerpt',
					'group' => __( 'Contents', GAMBIT_CAROUSEL_ANYTHING ),
				),
				array(
					'type' => 'dropdown',
					'heading' => 'Post Design',
					'param_name' => 'featured',
					'value' => array(
						__( 'Plain image', GAMBIT_CAROUSEL_ANYTHING ) => 'image',
						__( 'Use as background image', GAMBIT_CAROUSEL_ANYTHING ) => 'bg',
					),
					'description' => __( 'The selection done here will affect all posts pulled. If a post does not have a Featured Image, it will not be rendered for that post.', GAMBIT_CAROUSEL_ANYTHING ),
					'group' => __( 'Design', GAMBIT_CAROUSEL_ANYTHING ),
				),
				array(
					'type' => 'dropdown',
					'heading' => 'Alignment',
					'param_name' => 'alignment',
					'value' => array(
						__( 'No alignment', GAMBIT_CAROUSEL_ANYTHING ) => '',
						__( 'Align left', GAMBIT_CAROUSEL_ANYTHING ) => ' gcp-alignleft',
						__( 'Align center', GAMBIT_CAROUSEL_ANYTHING ) => ' gcp-aligncenter',
						__( 'Align right', GAMBIT_CAROUSEL_ANYTHING ) => ' gcp-alignright',
					),
					'description' => __( 'If desired, you can force content alignment of the particular pulled post.', GAMBIT_CAROUSEL_ANYTHING ),
					'group' => __( 'Design', GAMBIT_CAROUSEL_ANYTHING ),
				),
				array(
					'type' => 'textfield',
					'heading' => __( 'Excerpt word count', GAMBIT_CAROUSEL_ANYTHING ),
					'param_name' => 'excerpt_count',
					'value' => '25',
					'description' => __( 'If your post excerpt is too long, you can limit the amount of words printed here.', GAMBIT_CAROUSEL_ANYTHING ),
					'group' => __( 'Design', GAMBIT_CAROUSEL_ANYTHING ),
				),
				array(
					'type' => 'checkbox',
					'heading' => 'Excerpt ellipsis',
					'param_name' => 'ellipsis',
					'value' => array(
						__( 'Check to add elipsis on the end part of the excerpt. If the wordcount is shorter than the limit, the ellipsis will not be added. Setting it to false will make use of the default excerpt options of WordPress.', GAMBIT_CAROUSEL_ANYTHING ) => 'true',
					),
					'description' => '',
					'std' => 'true',
					'group' => __( 'Design', GAMBIT_CAROUSEL_ANYTHING ),
				),
				array(
					'type' => 'textfield',
					'heading' => __( 'Custom ellipsis', GAMBIT_CAROUSEL_ANYTHING ),
					'param_name' => 'custom_ellipsis',
					'value' => '...',
					'description' => __( 'If you want to customize your ellipsis character, you can do so here.', GAMBIT_CAROUSEL_ANYTHING ),
					'group' => __( 'Design', GAMBIT_CAROUSEL_ANYTHING ),
				),
				array(
					'type' => 'textfield',
					'heading' => __( 'Read More tag', GAMBIT_CAROUSEL_ANYTHING ),
					'param_name' => 'read_more',
					'value' => '',
					'description' => __( 'If you want a "Read More" link for all entries, populate this entry.', GAMBIT_CAROUSEL_ANYTHING ),
					'group' => __( 'Design', GAMBIT_CAROUSEL_ANYTHING ),
				),
				array(
					'type' => 'textfield',
					'heading' => __( 'Image Height', GAMBIT_CAROUSEL_ANYTHING ),
					'param_name' => 'image_height',
					'value' => '200',
					'description' => __( 'Specify the height of the image inside the carousel content for each post.', GAMBIT_CAROUSEL_ANYTHING ),
					'group' => __( 'Design', GAMBIT_CAROUSEL_ANYTHING ),
					'dependency' => array(
						'element' => 'featured',
						'value' => 'image',
					),
				),
				array(
					'type' => 'textfield',
					'heading' => __( 'Total Content Height', GAMBIT_CAROUSEL_ANYTHING ),
					'param_name' => 'content_height',
					'value' => '400',
					'description' => __( 'Specify the height of the carousel content for each post.', GAMBIT_CAROUSEL_ANYTHING ),
					'group' => __( 'Design', GAMBIT_CAROUSEL_ANYTHING ),
					'dependency' => array(
						'element' => 'featured',
						'value' => 'bg',
					),
				),
				array(
					'type' => 'colorpicker',
					'heading' => __( 'Title Text Color', GAMBIT_CAROUSEL_ANYTHING ),
					'param_name' => 'title_color',
					'value' => '#000',
					'description' => __( 'The color of the title for each pulled post.', GAMBIT_CAROUSEL_ANYTHING ),
					'group' => __( 'Design', GAMBIT_CAROUSEL_ANYTHING ),
					'dependency' => array(
						'element' => 'featured',
						'value' => 'bg',
					),
				),
				array(
					'type' => 'colorpicker',
					'heading' => __( 'Author Text Color', GAMBIT_CAROUSEL_ANYTHING ),
					'param_name' => 'author_color',
					'value' => '#000',
					'description' => __( 'The color of the text of the author of each pulled post.', GAMBIT_CAROUSEL_ANYTHING ),
					'group' => __( 'Design', GAMBIT_CAROUSEL_ANYTHING ),
					'dependency' => array(
						'element' => 'featured',
						'value' => 'bg',
					),
				),
				array(
					'type' => 'colorpicker',
					'heading' => __( 'Body Text Color', GAMBIT_CAROUSEL_ANYTHING ),
					'param_name' => 'body_color',
					'value' => '#000',
					'description' => __( 'The color of the body text for each pulled post.', GAMBIT_CAROUSEL_ANYTHING ),
					'group' => __( 'Design', GAMBIT_CAROUSEL_ANYTHING ),
					'dependency' => array(
						'element' => 'featured',
						'value' => 'bg',
					),
				),
				array(
					'type' => 'colorpicker',
					'heading' => __( 'Body Background Color', GAMBIT_CAROUSEL_ANYTHING ),
					'param_name' => 'body_bg_color',
					'value' => '',
					'description' => __( 'The background color of the pulled post.', GAMBIT_CAROUSEL_ANYTHING ),
					'group' => __( 'Design', GAMBIT_CAROUSEL_ANYTHING ),
					'dependency' => array(
						'element' => 'featured',
						'value' => 'bg',
					),
				),
				array(
					'type' => 'dropdown',
					'heading' => __( 'Navigation Thumbnails', GAMBIT_CAROUSEL_ANYTHING ),
					'param_name' => 'thumbnails',
					'value' => array(
						__( 'Circle', GAMBIT_CAROUSEL_ANYTHING ) => 'circle',
						__( 'Square', GAMBIT_CAROUSEL_ANYTHING ) => 'square',
						__( 'Arrows', GAMBIT_CAROUSEL_ANYTHING ) => 'arrows',
						__( 'None', GAMBIT_CAROUSEL_ANYTHING ) => 'none',
					),
					'description' => __( 'Select whether to display thumbnails below your carousel for navigation.<br>Selecting Arrows will display navigation arrows at each side.', GAMBIT_CAROUSEL_ANYTHING ),
					'group' => __( 'Thumbnails', GAMBIT_CAROUSEL_ANYTHING ),
				),
				array(
					'type' => 'colorpicker',
					'heading' => __( 'Thumbnail Default Color', GAMBIT_CAROUSEL_ANYTHING ),
					'param_name' => 'thumbnail_color',
					'value' => '#c3cbc8',
					'description' => __( 'The color of the non-active thumbnail. Not applicable to Arrows type of navigation.', GAMBIT_CAROUSEL_ANYTHING ),
	                'dependency' => array(
	                    'element' => 'thumbnails',
	                    'value' => array( 'circle', 'square' ),
	                ),
					'group' => __( 'Thumbnails', GAMBIT_CAROUSEL_ANYTHING ),
				),
				array(
					'type' => 'colorpicker',
					'heading' => __( 'Thumbnail Active Color', GAMBIT_CAROUSEL_ANYTHING ),
					'param_name' => 'thumbnail_active_color',
					'value' => '#869791',
					'description' => __( 'The color of the active / current thumbnail. Not applicable to Arrows type of navigation.', GAMBIT_CAROUSEL_ANYTHING ),
	                'dependency' => array(
	                    'element' => 'thumbnails',
	                    'value' => array( 'circle', 'square' ),
	                ),
					'group' => __( 'Thumbnails', GAMBIT_CAROUSEL_ANYTHING ),
				),
				array(
					'type' => 'checkbox',
					'heading' => '',
					'param_name' => 'thumbnail_numbers',
					'value' => array( __( 'Check to display page numbers inside the thumbnails. Not applicable to Arrows type of navigation.', GAMBIT_CAROUSEL_ANYTHING ) => 'true' ),
					'description' => '',
	                'dependency' => array(
	                    'element' => 'thumbnails',
	                    'value' => array( 'circle', 'square' ),
	                ),
					'group' => __( 'Thumbnails', GAMBIT_CAROUSEL_ANYTHING ),
				),
				array(
					'type' => 'colorpicker',
					'heading' => __( 'Thumbnail Default Page Number Color', GAMBIT_CAROUSEL_ANYTHING ),
					'param_name' => 'thumbnail_number_color',
					'value' => '#ffffff',
					'description' => __( 'The color of the page numbers inside non-active thumbnails', GAMBIT_CAROUSEL_ANYTHING ),
	                'dependency' => array(
	                    'element' => 'thumbnail_numbers',
	                    'value' => array( 'true' ),
	                ),
					'group' => __( 'Thumbnails', GAMBIT_CAROUSEL_ANYTHING ),
				),
				array(
					'type' => 'colorpicker',
					'heading' => __( 'Thumbnail Active Page Number Color', GAMBIT_CAROUSEL_ANYTHING ),
					'param_name' => 'thumbnail_number_active_color',
					'value' => '#ffffff',
					'description' => __( 'The color of the page numbers inside active / current thumbnails', GAMBIT_CAROUSEL_ANYTHING ),
	                'dependency' => array(
	                    'element' => 'thumbnail_numbers',
	                    'value' => array( 'true' ),
	                ),
					'group' => __( 'Thumbnails', GAMBIT_CAROUSEL_ANYTHING ),
				),
				array(
					'type' => 'colorpicker',
					'heading' => __( 'Arrows Default Color', GAMBIT_CAROUSEL_ANYTHING ),
					'param_name' => 'arrows_color',
					'value' => '#c3cbc8',
					'description' => __( 'The default color of the navigation arrow.', GAMBIT_CAROUSEL_ANYTHING ),
					'dependency' => array(
						'element' => 'thumbnails',
						'value' => array( 'arrows' ),
					),
					'group' => __( 'Thumbnails', GAMBIT_CAROUSEL_ANYTHING ),
				),
				array(
					'type' => 'colorpicker',
					'heading' => __( 'Arrows Active Color', GAMBIT_CAROUSEL_ANYTHING ),
					'param_name' => 'arrows_active_color',
					'value' => '#869791',
					'description' => __( 'The color of the active / current arrows when highlighted.', GAMBIT_CAROUSEL_ANYTHING ),
					'dependency' => array(
						'element' => 'thumbnails',
						'value' => array( 'arrows' ),
					),
					'group' => __( 'Thumbnails', GAMBIT_CAROUSEL_ANYTHING ),
				),
				array(
					'type' => 'textfield',
					'heading' => __( 'Arrows Size', GAMBIT_CAROUSEL_ANYTHING ),
					'param_name' => 'arrows_size',
					'value' => '20px',
					'description' => __( 'The size of the arrows can be customized here.', GAMBIT_CAROUSEL_ANYTHING ),
					'dependency' => array(
						'element' => 'thumbnails',
						'value' => array( 'arrows' ),
					),
					'group' => __( 'Thumbnails', GAMBIT_CAROUSEL_ANYTHING ),
				),
				array(
					'type' => 'textfield',
					'heading' => __( 'Starting Position', GAMBIT_CAROUSEL_ANYTHING ),
					'param_name' => 'start',
					'value' => '',
					'description' => __( 'Enter the starting position of the carousel, by slide number. Leave blank to disable this function. (eg. To start the carousel at the 4th slide, enter "4" as value.)', GAMBIT_CAROUSEL_ANYTHING ),
					'group' => __( 'Advanced', GAMBIT_CAROUSEL_ANYTHING ),
				),
				array(
					'type' => 'textfield',
					'heading' => __( 'Autoplay', GAMBIT_CAROUSEL_ANYTHING ),
					'param_name' => 'autoplay',
					'value' => '5000',
					'description' => __( 'Enter an amount in milliseconds for the carousel to move. Leave blank to disable autoplay', GAMBIT_CAROUSEL_ANYTHING ),
					'group' => __( 'Advanced', GAMBIT_CAROUSEL_ANYTHING ),
				),
				array(
					'type' => 'checkbox',
					'heading' => '',
					'param_name' => 'stop_on_hover',
					'value' => array( __( 'Pause the carousel when the mouse is hovered onto it.', GAMBIT_CAROUSEL_ANYTHING ) => 'true' ),
					'description' => '',
	                'dependency' => array(
	                    'element' => 'autoplay',
	                    'not_empty' => true,
	                ),
					'group' => __( 'Advanced', GAMBIT_CAROUSEL_ANYTHING ),
				),
				array(
					'type' => 'textfield',
					'heading' => __( 'Scroll Speed', GAMBIT_CAROUSEL_ANYTHING ),
					'param_name' => 'speed_scroll',
					'value' => '800',
					'description' => __( 'The speed the carousel scrolls in milliseconds', GAMBIT_CAROUSEL_ANYTHING ),
					'group' => __( 'Advanced', GAMBIT_CAROUSEL_ANYTHING ),
				),
				array(
					'type' => 'textfield',
					'heading' => __( 'Rewind Speed', GAMBIT_CAROUSEL_ANYTHING ),
					'param_name' => 'speed_rewind',
					'value' => '1000',
					'description' => __( 'The speed the carousel scrolls back to the beginning after it reaches the end in milliseconds', GAMBIT_CAROUSEL_ANYTHING ),
					'group' => __( 'Advanced', GAMBIT_CAROUSEL_ANYTHING ),
				),
				array(
					'type' => 'checkbox',
					'heading' => '',
					'param_name' => 'touchdrag',
					'value' => array( __( 'Check this box to disable touch dragging of the carousel. (Normally enabled by default)', GAMBIT_CAROUSEL_ANYTHING ) => 'true' ),
					'description' => '',
					'group' => __( 'Advanced', GAMBIT_CAROUSEL_ANYTHING ),
				),
				array(
					'type' => 'dropdown',
					'heading' => __( 'Keyboard Navigation', GAMBIT_CAROUSEL_ANYTHING ),
					'param_name' => 'keyboard',
					'value' => array(
						__( 'Disabled', GAMBIT_CAROUSEL_ANYTHING ) => 'false',
						__( 'Cursor keys', GAMBIT_CAROUSEL_ANYTHING ) => 'cursor',
						__( 'A and D keys', GAMBIT_CAROUSEL_ANYTHING ) => 'fps',
					),
					'description' => __( 'Select whether to enable carousel manipulation through cursor keys. Enabling this on a page with multiple carousels may give unpredictable results! Use it on a page with a single Carousel Posts element, or when there are no other scripts binding cursor or other keys present that may conflict.', GAMBIT_CAROUSEL_ANYTHING ),
					'group' => __( 'Advanced', GAMBIT_CAROUSEL_ANYTHING ),
				),
				array(
					'type' => 'textfield',
					'heading' => __( 'Custom Class', GAMBIT_CAROUSEL_ANYTHING ),
					'param_name' => 'class',
					'value' => '',
					'description' => __( 'Add a custom class name for the carousel here.', GAMBIT_CAROUSEL_ANYTHING ),
					'group' => __( 'Advanced', GAMBIT_CAROUSEL_ANYTHING ),
				),
				array(
					'type' => 'checkbox',
					'heading' => __( 'Rendering direction', GAMBIT_CAROUSEL_ANYTHING ),
					'param_name' => 'rtl',
					'value' => array( __( 'Check this box to reverse the rendering direction of the carousel, and even the icons. This is commonly known as RTL (Right-To-Left), and essential for Arabic / Middle-eastern languages.', GAMBIT_CAROUSEL_ANYTHING ) => 'true' ),
					'description' => '',
					'group' => __( 'Advanced', GAMBIT_CAROUSEL_ANYTHING ),
				),
			);
			foreach ( $other_options as $other_option ) {
				$vc_element['params'][] = $other_option;
			}

			// Put everything together and make it a whole array of options.
			vc_map( $vc_element );
		}

		/**
		 * Shortcode logic.
		 *
		 * @param array  $atts - WordPress shortcode attributes, defined by Visual Composer.
		 * @param string $content - Not needed in this plugin.
		 * @return string - The rendered html.
		 * @since 1.0
		 */
		public function render_cp_shortcode( $atts, $content = null ) {
	        $defaults = array(
				'start' => '0',
				'force_posttype' => 'false',
				'posttype' => 'post',
				'specified_posttype' => 'post',
				'taxonomy_posts' => 'category',
				'numofallposts' => '9',
				'orderby' => 'date',
				'order_direction' => 'DESC',
				'items' => '3',
				// 'items_desktop_small' => '2',
				'items_tablet' => '2',
				'items_mobile' => '1',
				'autoplay' => '5000',
				'stop_on_hover' => false,
				'scroll_per_page' => false,
				'speed_scroll' => '800',
				'speed_rewind' => '1000',
				'show_details' => 'featured_image,title,excerpt',
				'featured' => 'image',
				'alignment'	=> '',
				'thumbnails' => 'circle',
				'thumbnail_color' => '#c3cbc8',
				'thumbnail_active_color' => '#869791',
				'thumbnail_numbers' => false,
				'thumbnail_number_color' => '#ffffff',
				'thumbnail_number_active_color' => '#ffffff',
				'arrows_color' => '#c3cbc8',
				'arrows_active_color' => '#869791',
				'arrows_inactive_color' => '#ffffff',
				'arrows_size' => '20px',
				'title_color' => '#000',
				'author_color' => '#000',
				'body_color' => '#000',
				'body_bg_color' => '',
				'touchdrag' => 'false',
				'keyboard' => 'false',
				'image_height' => '200',
				'content_height' => '400',
				'class' => '',
				'excerpt_count'	=> '25',
				'ellipsis' => 'true',
				'custom_ellipsis' => '...',
				'read_more' => '',
				'rtl' => 'false',
	        );
			if ( empty( $atts ) ) {
				$atts = array();
			}
			$atts = array_merge( $defaults, $atts );

			self::$id++;
			$id = 'carousel-posts-' . esc_attr( self::$id );

			// Parse what to show.
			$postdata = explode( ',', $atts['show_details'] );

			// Initialize necessary arrays and defaults.
			$title_styles = array();
			$author_styles = array();
			$content_styles = array();
			$title_other_styles = array();
			$author_other_styles = array();
			$content_other_styles = array();
			$styles = '';
			$carousel_class = '';
			$navigation_buttons = false;
	        $ret = '';
			$title_entry = '';
			$author_entry = '';
			$content_entry = '';

			// Post number checker.
			if ( empty( $atts['numofallposts'] ) || $atts['numofallposts'] < 1 ) {
				$numposts = -1;
			} else {
				$numposts = $atts['numofallposts'];
			}

			// Determine the post type used.
			$the_post_type = ( 'true' == $atts['force_posttype'] ? $atts['specified_posttype'] : $atts['posttype'] );

			// Pull posts.
			$querypost = array(
				'posts_per_page' => $numposts,
				'orderby' => $atts['orderby'],
				'order' => $atts['order_direction'],
				'post_status' => 'publish',
				'post_type' => $the_post_type,
				'ignore_sticky_posts' => 1,
			);

			// Check if term entry exists, or if set to all.
			if ( ! empty( $atts[ 'taxonomy_' . $the_post_type ] ) ) {
				$term_of_post	= $atts[ 'taxonomy_' . $the_post_type ];
				if ( 'all' != $term_of_post ) {
					$cat_in = explode( '|', $term_of_post );
					if ( is_array( $cat_in ) ) {
						$key = $cat_in[0];
						if ( 'category' == $key ) {
							$key = 'category_name';
						}
						$querypost[ $key ] = $cat_in[1];
					}
				}
			}

			// Using filters, arguments for get_posts can be manipulated on the fly. Use the gambit_cp_query function within an included PHP file if you want to do so.
			$querypost = apply_filters( 'gca_carousel_posts_parameters', $querypost );
			$posts = new WP_Query( $querypost );
			$postentries = '';

			if ( $posts->have_posts() ) :

				// Thumbnail styles.
				if ( ! empty( $atts['thumbnails'] ) ) {
					if ( 'square' == $atts['thumbnails'] ) {
						$styles .= "#{$id}.owl-ca-theme .owl-ca-controls .owl-ca-page span { border-radius: 0 }";
					}
					if ( 'arrows' == $atts['thumbnails'] ) {
						$navigation_buttons = true;
						$carousel_class = ' has-arrows';
						$styles .= '.owl-ca-prev, .owl-ca-next { width: '.$atts['arrows_size'].'!important; }';
						$styles .= '.owl-ca-prev::before, .owl-ca-next::before { color: '.$atts['arrows_color'].' !important; font-size: '. $atts['arrows_size'] .'!important; }';
						$styles .= '.owl-ca-prev:hover::before, .owl-ca-next:hover::before { color: '.$atts['arrows_active_color'].'!important; }';
					}
					if ( 'none' != $atts['thumbnails'] && 'arrows' != $atts['thumbnails'] ) {
						$styles .= "#{$id}.owl-ca-theme .owl-ca-controls .owl-ca-page span { opacity: 1; background: " . esc_attr( $atts['thumbnail_color'] ) . ' }'
								 . "#{$id}.owl-ca-theme .owl-ca-controls .owl-ca-page.active span { background: " . esc_attr( $atts['thumbnail_active_color'] ) . ' }';
					}
					if ( false != $atts['thumbnail_numbers'] && 'arrows' != $atts['thumbnails'] ) {
						$styles .= "#{$id}.owl-ca-theme .owl-ca-controls .owl-ca-page span.owl-ca-numbers { color: " . esc_attr( $atts['thumbnail_number_color'] ) . ' }'
							 	 . "#{$id}.owl-ca-theme .owl-ca-controls .owl-ca-page.active span.owl-ca-numbers { color: " . esc_attr( $atts['thumbnail_number_active_color'] ) . ' }';
					}
				}

				if ( 'bg' == $atts['featured'] ) {
					$title_styles[] = 'color: ' . $atts['title_color'] . '; ';
					$author_styles[] = 'color: ' . $atts['author_color'] . '; ';
					$content_styles[] = 'color: ' . $atts['body_color'] . '; ';
				}

				// Style initialization based on display preference.
				if ( in_array( 'featured_image', $postdata ) && in_array( 'title', $postdata ) && 2 == count( $postdata ) && 'bg' == $atts['featured'] ) {
					$title_other_styles[] = 'padding: 5px;';
				} elseif ( in_array( 'featured_image', $postdata ) && in_array( 'author', $postdata ) && 2 == count( $postdata ) && 'bg' == $atts['featured'] ) {
					$author_other_styles[] = 'padding: 5px;';
				} elseif ( in_array( 'featured_image', $postdata ) && in_array( 'excerpt', $postdata ) && 2 == count( $postdata ) && 'bg' == $atts['featured'] ) {
					$content_other_styles[] = 'padding: 5px; margin-bottom: 0;';
				} elseif ( 4 == count( $postdata ) && 'bg' == $atts['featured'] ) {
					$title_other_styles[] = 'padding: 5px;';
				}

				// Explode all individual styles for Title into the main style.
				if ( count( $title_styles ) > 0 ) {
					$styles .= '.gcp-post-title, .gcp-post-title a, .gcp-post-title a:link, .gcp-post-title a:visited, .gcp-post-title a:hover { ' . implode( ' ', $title_styles ) . ' }';
				}
				if ( count( $title_other_styles ) > 0 ) {
					$styles .= '.gcp-post-title { ' . implode( ' ', $title_other_styles ) . ' }';
				}

				// Explode all individual styles for Author into the main style.
				if ( count( $author_styles ) > 0 ) {
					$styles .= '.gcp-post-author, .gcp-post-author a, .gcp-post-author a:link, .gcp-post-author a:visited, .gcp-post-author a:hover { ' . implode( ' ', $author_styles ) . ' }';
				}
				if ( count( $author_other_styles ) > 0 ) {
					$styles .= '.gcp-post-author { ' . implode( ' ', $author_other_styles ) . ' }';
				}

				// Explode all individual styles for Content Excerpt into the main style.
				if ( count( $content_styles ) > 0 ) {
					$styles .= '.gcp-post-content, .gcp-post-content a, .gcp-post-content a:link, .gcp-post-content a:hover, .gcp-post-content a:visited { ' . implode( ' ', $content_styles ) . ' }';
				}
				if ( count( $content_other_styles ) > 0 ) {
					$styles .= '.gcp-post-content { ' . implode( ' ', $content_other_styles ) . ' }';
				}

				// Apply the classes to the main carousel div.
				// if ( ! empty( $atts['class'] ) ) {
				// $carousel_class .= ' ' . esc_attr( $atts['class'] );
				// }
				// Print out an inline stylesheet.
				if ( $styles ) {
					$ret .= "<style>{$styles}</style>";
				}

				if ( $navigation_buttons ) {
					wp_enqueue_style( 'dashicons' );
				}
				wp_enqueue_style( 'gcp-owl-carousel-css', plugins_url( 'carousel-anything/css/style.css', __FILE__ ), array(), VERSION_GAMBIT_CAROUSEL_ANYTHING );
				wp_enqueue_style( 'carousel-anything-owl', plugins_url( 'carousel-anything/css/owl.carousel.theme.style.css', __FILE__ ), array(), VERSION_GAMBIT_CAROUSEL_ANYTHING );
				wp_enqueue_script( 'carousel-anything-owl', plugins_url( 'carousel-anything/js/min/owl.carousel-min.js', __FILE__ ), array( 'jquery' ), '1.3.3', true );
				wp_enqueue_script( 'carousel-anything', plugins_url( 'carousel-anything/js/min/script-min.js', __FILE__ ), array( 'jquery', 'carousel-anything-owl' ), VERSION_GAMBIT_CAROUSEL_ANYTHING, true );
				wp_enqueue_style( 'carousel-anything-single-post', plugins_url( 'carousel-anything/css/single-post.css', __FILE__ ), array(), VERSION_GAMBIT_CAROUSEL_ANYTHING );

				$the_post_count = $posts->post_count;

				while ( $posts->have_posts() ) : $posts->the_post();

					$overridden_slide = apply_filters( 'gca_carousel_posts_slide_override', '', get_the_ID() );
					if ( $overridden_slide ) {
						$postentries .= '<div>' . $overridden_slide . '</div>';
						continue;
					}

					// Process the featured image.
					$postclasses = '';
					$thumbnail = '';
					$content_entry = '';
					$css = array();

					if ( $atts['content_height'] > 0 && 'image' != $atts['featured'] ) {
						$css[] = 'height: ' . $atts['content_height'] . 'px;';
					}

					if ( in_array( 'featured_image', $postdata ) ) {
						$post_thumbnail_id = get_post_thumbnail_id();
						// Jetpack issue, Photon is not giving us the image dimensions.
						// This snippet gets the dimensions for us.
						add_filter( 'jetpack_photon_override_image_downsize', '__return_true' );
						$image_info = wp_get_attachment_image_src( $post_thumbnail_id, 'full' );
						remove_filter( 'jetpack_photon_override_image_downsize', '__return_true' );

						$attachment_image = wp_get_attachment_image_src( $post_thumbnail_id, 'full' );
						$bg_image_width = $image_info[1];
						$bg_image_height = $image_info[2];
						$bg_image = $attachment_image[0];

						if ( $post_thumbnail_id ) {
							if ( 'bg' == $atts['featured'] ) {
								$css[] = 'background-repeat: no-repeat; background-size: cover; background-position: center;  background-image: url(' . $attachment_image[0] . ');';
							} elseif ( 'image' == $atts['featured'] ) {
								// $thumbnail = '<a href="' . get_permalink() . '"><div id="post-image" style="height: ' . $atts['image_height'] . 'px; background-repeat: no-repeat; background-size: cover; background-position: center;  background-image: url(' . $attachment_image[0] . ');"></div></a>';
								$thumbnail = '<div id="post-image" style="height: ' . $atts['image_height'] . 'px; background-repeat: no-repeat; background-size: cover; background-position: center;  background-image: url(' . $attachment_image[0] . ');"></div>';
							}
						}
					}

					// Render background color image, if a color is defined. Do not add if none.
					if ( ! empty( $atts['body_bg_color'] ) ) {
						$css[] = 'background-color: ' . $atts['body_bg_color'] . ';';
					}

					// Render the entries.
					if ( in_array( 'title', $postdata ) ) {
						$title_entry = '<h4 class="gcp-post-title"><a href="' . get_permalink() . '">' . get_the_title() . '</a></h2>';
					}

					if ( in_array( 'author', $postdata ) ) {
						$author_entry = '<p class="gcp-post-author"><a href="' . get_author_posts_url( get_the_author_meta( 'ID' ) ) . '">' . get_the_author() . '</a></p>';
					}

					if ( in_array( 'excerpt', $postdata ) ) {
						$the_excerpt = $this->limit_words( get_the_excerpt(), 0, $atts['excerpt_count'] );
						$the_excerpt_count = $this->count_words( $the_excerpt );

						if ( '' != $the_excerpt && $the_excerpt_count <= $atts['excerpt_count'] && 'true' == $atts['ellipsis'] ) {
							$the_excerpt .= $atts['custom_ellipsis'];
						}
						$content_entry .= '<p class="gcp-post-content">' . $the_excerpt . '</p>';
					}

					if ( '' != $atts['read_more'] ) {
						$content_entry .= '<p class="gcp-post-readmore"><a href="' . get_permalink() . '">' . $atts['read_more'] . '</a></p>';
					}

					// Assemble all css parameters into a single array.
					$main_styling = ' style="' . implode( ' ', $css ) . ' "';

					/** Deleted for now due to buggy implementation

					// Get the terms and taxonomy and add them as classes.
					$itsTheTaxonomy = get_post_taxonomies( get_the_ID() );
					// var_dump( $itsTheTaxonomy );
					if ( array_key_exists( 'taxonomy', $posts->query_vars ) ) {
						$thisTaxonomy = $posts->query_vars['taxonomy'];
						if ( '' != $thisTaxonomy ) {
							$postclasses .= ' gcp-taxonomy-' . $thisTaxonomy;
							if ( $this->get_post_terms( get_the_ID(), $thisTaxonomy, 'class' ) ) {
								$postclasses .= ' ' . $this->get_post_terms( get_the_ID(), $thisTaxonomy, 'class' );
							}
						} else {
							$the_taxonomy = get_post_taxonomies( get_the_ID() );
							// var_dump( $the_taxonomy );
						}
					} elseif ( array_key_exists( 'category_name', $posts->query_vars ) ) {
						$thisTaxonomy = $posts->query_vars['category_name'];
						if ( '' != $thisTaxonomy ) {
							$postclasses .= ' gcp-taxonomy-' . $thisTaxonomy;
						}
					}
					*/

					// If there's an excerpt, add to the classes.
					$captionclass = in_array( 'excerpt', $postdata ) ? ' gcp-caption-wrapper' : '';

					// Assemble the container of each pulled post.
					$postentries .= '<div class="gcp-post ' . esc_attr( $atts['alignment'] ) . ' gcp-design-' . esc_attr( $atts['featured'] ) . $postclasses . ' "' . $main_styling . '>';

					if ( 'image' == $atts['featured'] ) {
						$postentries .= $thumbnail;
					}

					$div_classes = $postdata;
					array_splice( $div_classes , array_search( 'featured_image', $div_classes ), 1 );

					$postentries .= '<div class="gcp-caption-wrapper gcp-' . implode( '-', $div_classes ) . esc_attr( $atts['alignment'] ) . $captionclass . '">';
					$postentries .= in_array( 'title', $postdata ) ? $title_entry : '';
					$postentries .= in_array( 'author', $postdata ) ? $author_entry : '';
					$postentries .= in_array( 'excerpt', $postdata ) ? $content_entry : '';
					$postentries .= '</div>';

					$postentries .= '</div>';

				endwhile;

				$slide_number = ( $atts['start'] > 0 ? $atts['start'] - 1 : $atts['start'] );

				// Carousel html.
				$ret .= '<div id="' . esc_attr( $id ) . '" class="carousel-anything-container owl-ca-carousel' . $carousel_class . '"  data-items="' . esc_attr( $atts['items'] ) . '"  data-totalitems="' . esc_attr( $the_post_count ) . '" data-scroll_per_page="' . esc_attr( $atts['scroll_per_page'] ) . '" data-autoplay="' . esc_attr( empty( $atts['autoplay'] ) || '0' == $atts['autoplay'] ? 'false' : $atts['autoplay'] ) . '" data-items-tablet="' . esc_attr( $atts['items_tablet'] ) . '" data-items-mobile="' . esc_attr( $atts['items_mobile'] ) . '" data-stop-on-hover="' . esc_attr( $atts['stop_on_hover'] ) . '" data-speed-scroll="' . esc_attr( $atts['speed_scroll'] ) . '" data-speed-rewind="' . esc_attr( $atts['speed_rewind'] ) . '" data-thumbnails="' . esc_attr( $atts['thumbnails'] ) . '" data-thumbnail-numbers="' . esc_attr( $atts['thumbnail_numbers'] ) . '" data-navigation="' . esc_attr( $navigation_buttons ? 'true' : 'false' ) . '" data-touchdrag="' . esc_attr( $atts['touchdrag'] ) . '" data-keyboard="' . esc_attr( $atts['keyboard'] ) . '" data-rtl="' . esc_attr( $atts['rtl'] ) . '" data-start="' . esc_attr( $slide_number ) . '">';
				$ret .= $postentries . '</div>';

				endif;
			wp_reset_query();

			$ret = apply_filters( 'gambit_cp_output', $ret );

			return $ret;
		}
	}
	new GambitCarouselPosts();
}
