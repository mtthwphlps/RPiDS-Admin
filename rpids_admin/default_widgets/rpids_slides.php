<?php
// Add custom post types
function rpids_codex_custom_init_slides() {
	$labels = array(
		'name'               => 'Slides',
		'singular_name'      => 'Slide',
		'add_new'            => 'Add Slide',
		'add_new_item'       => 'Add New Slide',
		'edit_item'          => 'Edit Slide',
		'new_item'           => 'New Slide',
		'all_items'          => 'All Slides',
		'view_item'          => 'View Slide',
		'search_items'       => 'Search Slides',
		'not_found'          => 'No slide found',
		'not_found_in_trash' => 'No slide found in Trash',
		'parent_item_colon'  => '',
		'menu_name'          => 'Slides'
	);
	$args = array(
		'labels'             => $labels,
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'slides' ),
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => 5,
		'menu_icon'			 => 'dashicons-images-alt2',
		'supports'           => array( 'title', 'author', 'thumbnail', 'revisions' ),
		'taxonomies' => array('post_tag')
	);
	register_post_type( 'slides', $args );
}
add_action( 'init', 'rpids_codex_custom_init_slides' );

// Add meta boxes for slides
function rpids_slide_add_box() {
	add_meta_box('rpids-slide-content', 'Slide Text', 'rpids_slide_show_box', 'slides', 'normal', 'high');
	add_meta_box('rpids-slide-group', 'Group', 'rpids_slide_group_show_box', 'slides', 'normal', 'high');
}
add_action('admin_menu', 'rpids_slide_add_box');

// Function to show fields in the slides text meta box
function rpids_slide_show_box() {
	global $post;
	$slide_info = get_post_meta($post->ID, 'slide_text', true); ?>
	<input type="hidden" name="rpids_slide_meta_box" value="<?php echo wp_create_nonce(basename(__FILE__)); ?>" />
	<table width="100%" border="0" cellspacing="0" cellpadding="0">
		<tr>
			<td width="10%"><strong>Line 1</strong></td>
			<td width="90%"><input name="rpids_slide_h1" value="<?php echo @$slide_info['h1']; ?>" type="text" style="width: 100%;" /></td>
		</tr>
		<tr>
			<td><strong>Line 2</strong></td>
			<td><input name="rpids_slide_h2" value="<?php echo @$slide_info['h2']; ?>" type="text" style="width: 100%;" /></td>
		</tr>
		<tr>
			<td><strong>Line 3</strong></td>
			<td><input name="rpids_slide_p" value="<?php echo @$slide_info['p']; ?>" type="text" style="width: 100%;" /></td>
		</tr>
	</table>
<?php }

// Function to show fields in the group meta box
function rpids_slide_group_show_box() {
	global $post;
	$groupsdata = get_option("rpids_groups", array(array("name"=>"all","description"=>"Displays all content. Useful for lobbys and general gathering areas.")));
	$slide_group = get_post_meta($post->ID, 'group', true);
	if( $slide_group = '' ) {
		$slide_group = get_post_meta($post->ID, 'slide_group', true);
	} ?>
	<input type="hidden" name="rpids_slide_meta_box" value="<?php echo wp_create_nonce(basename(__FILE__)); ?>" />
	<table width="100%" border="0" cellspacing="0" cellpadding="0">
		<tr>
			<td width="10%"><strong>Group</strong></td>
			<td width="90%">
				<select name="rpids_slide_group">
					<option value="">Choose...</option>
					<?php if(count($groupsdata) > 0 && $groupsdata != 'none') {
						foreach($groupsdata as $group) {
							if($slide_group == $group['name']) {
								echo '<option value="'.$group['name'].'" selected="selected">'.$group['name'].'</option>';
							} else {
								echo '<option value="'.$group['name'].'">'.$group['name'].'</option>';
							}
						}
					} ?>
				</select>
			</td>
		</tr>
		<tr>
			<td width="100%" colspan="2">
				&bull; <em>Choosing the "all" group will display this slide in all locations.</em><br />
				&bull; <em>Choosing a group other than "all" will display this slide in only the location configured to use the selected group.</em>
			</td>
		</tr>
	</table>
<?php }
add_action('save_post', 'rpids_slide_save_data');

// Save data
function rpids_slide_save_data($post_id) {
	// Verify nonce
	if (!wp_verify_nonce(@$_POST['rpids_slide_meta_box'], basename(__FILE__))) {
		return $post_id;
	}
	// Check autosave
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return $post_id;
	}
	// Check permissions
	if(!current_user_can('edit_post', $post_id)) {
		return $post_id;
	}
	// Get the form data
	$slide_h1 = filter_input(INPUT_POST, 'rpids_slide_h1', FILTER_SANITIZE_STRING);
	$slide_h2 = filter_input(INPUT_POST, 'rpids_slide_h2', FILTER_SANITIZE_STRING);
	$slide_p = filter_input(INPUT_POST, 'rpids_slide_p', FILTER_SANITIZE_STRING);
	$slide_group = filter_input(INPUT_POST, 'rpids_slide_group', FILTER_SANITIZE_STRING);
	// Create the array
	$slide_text = array(
		"h1" => $slide_h1,
		"h2" => $slide_h2,
		"p" => $slide_p
	);
	// Update the post
	update_post_meta($post_id, 'slide_text', $slide_text);
	update_post_meta( $post_id, 'group', $slide_group );
	update_option('rpids_slides_timestamp', time());
}

// Add slide graphic to admin post listing
function rpids_slide_cpt_columns($columns) {
	$new_columns = array(
		'thumbnail' => 'Thumbnail'
	);
    return array_merge($columns, $new_columns);
}
add_filter('manage_slide_posts_columns' , 'rpids_slide_cpt_columns');

function rpids_slide_action_columns( $column, $post_id ) {
	$meta = get_post_meta( $post_id );
	switch ( $column ) {
		case 'thumbnail' :
			echo get_the_post_thumbnail( $post_id, 'thumbnail' );
		break;
	}
}
add_action( 'manage_slide_posts_custom_column' , 'rpids_slide_action_columns', 10, 2 );

// Remove the tags column
function rpids_slides_columns_filter( $columns ) {
	unset($columns['tags']);
	return $columns;
}
add_filter( 'manage_edit-slide_columns', 'rpids_slides_columns_filter',10, 1 );

// Slides image
add_action('do_meta_boxes', 'rpids_slides_image_box');
function rpids_slides_image_box() {
	remove_meta_box( 'postimagediv', 'slides', 'side' );
	add_meta_box('postimagediv', 'Slide Image', 'post_thumbnail_meta_box', 'slides', 'normal', 'high');
}
?>