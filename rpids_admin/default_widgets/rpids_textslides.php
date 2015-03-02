<?php
// Add custom post types
function rpids_codex_custom_init_text_slides() {
	$labels = array(
		'name'               => 'Text Slides',
		'singular_name'      => 'Text Slide',
		'add_new'            => 'Add Text Slide',
		'add_new_item'       => 'Add New Text Slide',
		'edit_item'          => 'Edit Text Slide',
		'new_item'           => 'New Text Slide',
		'all_items'          => 'All Text Slides',
		'view_item'          => 'View Text Slide',
		'search_items'       => 'Search Text Slides',
		'not_found'          => 'No text slide found',
		'not_found_in_trash' => 'No text slide found in Trash',
		'parent_item_colon'  => '',
		'menu_name'          => 'Text Slides'
	);
	$args = array(
		'labels'             => $labels,
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'text_slides' ),
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => 5,
		'menu_icon'			 => 'dashicons-format-quote',
		'supports'           => array( 'title', 'editor', 'author', 'revisions' ),
		'taxonomies' => array('post_tag')
	);
	register_post_type( 'text_slides', $args );
}
add_action( 'init', 'rpids_codex_custom_init_text_slides' );

// Remove the tags column
function rpids_text_slides_columns_filter( $columns ) {
	unset($columns['tags']);
	return $columns;
}
add_filter( 'manage_edit-text_slide_columns', 'rpids_text_slides_columns_filter',10, 1 );

?>