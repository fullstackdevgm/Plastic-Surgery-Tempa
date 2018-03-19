<?php
/**
 * Plugin Name: 	  Julian Before & After
 * Plugin URI:        http://julianplasticsurgery.com
 * Description:       Adds a simple Before & After photo system for easily uploading and displaying Before & After shots on the Julian Plastic Surgery website.
 * Version:           1.0.0
 * Author:            Josh Mallard
 * Author URI:        http://limecuda.com
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'init', 'julian_beforeafter_cpt_tax' );

function julian_beforeafter_cpt_tax() {
	$labels = array(
		'name' 			=> 'Before / After Photos',
		'menu_name' 	=> 'Before / After',
		'add_new'		=> 'Add New Gallery',
		'add_new_item'	=> 'Add New Before & After Gallery',
		'edit_item'		=> 'Edit Gallery',
		'view_item' 	=> 'View Before & After Gallery',
		'all_items' 	=> 'All Before & After Galleries'
	);

	$args = array(
		'labels'             => $labels,
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'before-after-galleries' ),
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_icon'			 => 'dashicons-format-gallery',
		'menu_position'      => null,
		'supports'           => array( 'title', 'editor', 'revisions' )
	);

	register_post_type( 'before-after', $args );

	$labels = array(
		'name' 				=> 'Procedure',
		'singular_name' 	=> 'Procedure',
		'search_items' 		=> 'Search Procedures',
		'all_items'			=> 'All Procedures',
		'edit_item'			=> 'Edit Procedure',
		'update_item'		=> 'Update Procedure',
		'add_new_item'		=> 'Add New Procedure',
		'menu_name'			=> 'Procedures'
	);

	$args = array(
		'labels'			=> $labels,
		'hierarchical'		=> true,
		'show_ui'			=> true,
		'show_admin_column'	=> true,
		'query_var'			=> true,
		'rewrite'			=> array( 'slug' => 'before-after' )
	);

	register_taxonomy( 'procedure-types', array( 'before-after' ), $args );
}