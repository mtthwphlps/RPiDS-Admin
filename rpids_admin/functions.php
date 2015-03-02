<?php
/*
	RPiDS functions.php file
	Here we load any initial files we need as well handle initial setup and config
*/

// Set the timezone (after getting it from the WP option)
// We default to the America/New_York timezone (Eastern w/ daylight savings)
$rpids_timezone = get_option( 'rpids_timezone', 'America/New_York' );
date_default_timezone_set( $rpids_timezone );

// Set the version variables
$version_str = 'RPiDS v2.0';
$version_num = '2.0';

// Start a session
session_start();

// WP table prefix function
if( !function_exists( 'rpids_tableprefix' ) ) {
	function rpids_tableprefix() {
		global $wpdb;
		if(is_multisite()) {
			$table_prefix = $wpdb->base_prefix.''.get_current_blog_id().'';
		} else {
			$table_prefix = $wpdb->prefix.'';
		}
		return $table_prefix;
	}
}

// The RPiDS unserialize function
if( !function_exists( 'rpids_unserialize' ) ) {
	function rpids_unserialize( $string ) {
		if( !is_array( $string ) ) {
			return unserialize( $string );
		} else {
			return $string;
		}
	}
}

// Load these now
require_once( 'inc/rpids_log.class.php' );

// Near-real-time sync
function post_updated_sync( $post_id ) {
	/*
		Currently we only check for updates every 30 seconds (using the heartbeat API call). In the future we'll update immediately when a post is published.
	*/
	// Load globals (WPDB and RPiDS API)
	global $wpdb;
	global $rpids_api;
	
	// Get the post for the post id
	$post = get_post( $post_id );
	
	if( !is_null( $post ) ) { // Make sure we're getting something back
		/*
			Note: we don't really care about the post status, the API script will sort that out. We just need to let the API script know there was a change using the db.
		*/
		// Get the post group
		$group = get_post_meta( $post->ID, 'group', true );
	
		// Make sure we have a group. If not, set it to "all"
		if( $group == '' ) {
			$group == 'all';
		}
		
		// Figure out the locations that are part of this group
		$locations = $wpdb->get_results( "SELECT * FROM `" . rpids_tableprefix() . "rpids_locations` WHERE `groups` LIKE \'%" . $group . "%\'" );
		
		// Now figure out what screens are in those locations
		$sql = "SELECT * FROM `" . rpids_tableprefix() . "rpids_screens` WHERE ";
		$multi_screen = false;
		foreach( $locations as $location ) {
			if( $multi_screen ) {
				$sql .= " OR ";
			}
			$sql .= "`location`='" . $location->location . "'";
			$multi_screen = true;
		}
		$screens = $wpdb->get_results( $sql, ARRAY_A );
		
		// Make sure we have at least one screen
		if( $wpdb->num_rows > 0 ) {
			$updated_screens = array(); // We'll need this to store the updated screens for later
			foreach( $screens as $screen ) {
				// Store the screen ID
				$updated_screens[] = $screen['id'];
				
				// Pull out the existing update data
				$screen_update_data = $screen['update_data'];
				
				// Make sure the update data is an array (new screens may have this field empty)
				if( !is_array( $screen_update_data ) ) {
					$screen_update_data = array();
				}
				
				// Update the last_modified value for this post type
				$screen_update_data['posts'][$post->post_type]['last_modified'] = $post->post_modified_gmt;
				
				// Update the sql record for this screen
					$wpdb->update( rpids_tableprefix() . "rpids_screens", array( "update_data" => $screen_update_data ), array( "id" => $screen['id'] ) );
					
				// NEXT!
			}
		} else {
			// No screens to update
		}
	}
}
add_action( 'save_post', 'post_updated_sync' );

// Remove unused admin pages
define( 'DISALLOW_FILE_EDIT', true );
function rpids_remove_unused_admin_pages() {
	remove_menu_page( 'edit.php' );
	remove_menu_page( 'edit.php?post_type=page' );
}
add_action( 'admin_menu', 'rpids_remove_unused_admin_pages' );

// Customize the At A Glance box
function rpids_at_a_glance_customize() {
	// Globals
	global $rpids_settings;
	global $wpdb;
	global $rpids_layout_widgets;
	
	// First the custom posts...
    $args = array(
        'public' => true,
        '_builtin' => false
    );
    $output = 'object';
    $operator = 'and';
    $post_types = get_post_types( $args, $output, $operator );
    foreach ( $post_types as $post_type ) {
        $num_posts = wp_count_posts( $post_type->name );
        $num = number_format_i18n( $num_posts->publish );
        $text = _n( $post_type->labels->singular_name, $post_type->labels->name, intval( $num_posts->publish ) );
        if ( current_user_can( 'edit_posts' ) ) {
            $output = '<a href="edit.php?post_type=' . $post_type->name . '">' . $num . ' ' . $text . '</a>';
            echo '<li class="post-count ' . $post_type->name . '-count">' . $output . '</li>';
        }
    }
	
	// Now the locations
	$locations = $rpids_settings->locations();
	$location_count = count( $locations );
	unset( $locations );
	echo '<li class="post-count location-count"><a href="admin.php?page=rpids_settings_locations">' . $location_count . ' Locations</a></li>';
	unset( $location_count );
	
	// Now the screens
	$screens = $rpids_settings->all_screens();
	$screen_count = count( $screens );
	unset( $screens );
	echo '<li class="post-count screen-count"><a href="admin.php?page=rpids_settings_locations">' . $screen_count . ' Screens</a></li>';
	unset( $screen_count );
	
	// And the layouts
	$sql = "SELECT * FROM `" . rpids_tableprefix() . "rpids_layouts`;";
	$layouts = $wpdb->get_results( $sql, ARRAY_A );
	$layout_count = $wpdb->num_rows;
	unset( $sql );
	unset( $layouts );
	echo '<li class="post-count layout-count"><a href="admin.php?page=rpids_settings_layouts">' . $layout_count . ' Layouts</a></li>';
	unset( $layout_count );
	
	// The devices
	$device_count = count( $rpids_settings->listDevices() );
	echo '<li class="post-count device-count"><a href="admin.php?page=rpids_settings_devices">' . $device_count . ' Devices</a></li>';
	
	// The widgets
	$widget_count = 0;
	foreach( $rpids_layout_widgets as $widget ) {
		$widget_count++;
	}
	echo '<li class="post-count widget-count"><a href="#">' . $widget_count . ' Widgets</a></li>';
	unset( $widget_count );
	unset( $widget );
}
add_action( 'dashboard_glance_items', 'rpids_at_a_glance_customize' );

// Add the dasboard box
function rpids_dashboard_box() {
	wp_add_dashboard_widget('rpids_dashboard_box', 'RPiDS', 'rpids_dashboard_content');
}
add_action('wp_dashboard_setup', 'rpids_dashboard_box' );

function rpids_dashboard_content() { ?>
	<strong>Hi!</strong><br />
	This box will be used for something, don't know what for yet.
<?php }

// This lets us display notices using sessions (useful when we're redirected after a notice is set)
function rpids_admin_notice() {
	if( @$_SESSION["rpids_notice"] != '' ) {
		$return = urldecode( $_SESSION["rpids_notice"] );
		$_SESSION["rpids_notice"] = '';
		echo $return;
	}
}
add_action('admin_notices', 'rpids_admin_notice');

// Let's make a curl function
function rpids_curl( $url, $format = 'object', $timeout = 60, $ssl = 1 ) {
	$user_agent = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)";
	$process = curl_init( $url );
	curl_setopt( $process, CURLOPT_USERAGENT, $user_agent );
	curl_setopt( $process, CURLOPT_TIMEOUT, $timeout );
	if( $ssl == 1 ) {
		curl_setopt( $process, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt( $process, CURLOPT_SSL_VERIFYHOST, 0 );
	} else {
		curl_setopt( $process, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt( $process, CURLOPT_SSL_VERIFYHOST, 0 );
	}
	curl_setopt( $process, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $process, CURLOPT_FOLLOWLOCATION, 1 );
	curl_setopt( $process, CURLOPT_CAINFO, dirname(__FILE__) . '/cacert.pem' );
	$data = curl_exec( $process );
	curl_close( $process );
	if( $data === false ) {
		throw new Exception( "Curl error: " . curl_error( $process ) );
	} else {
		if( $format == 'object' ) {
			return json_decode( $data );
		} else {
			return $data;
		}
	}
}

// Include other files
require_once( 'inc/rpids_install.php' ); // Global stuff
require_once( 'inc/rpids_global.php' ); // Global stuff
require_once( 'inc/rpids_widgets.php' ); // The widgets
require_once( 'inc/rpids_api.php' ); // API (outbound stuff)
require_once( 'inc/rpids_ajax.php' ); // AJAX (inbound API calls)
require_once( 'inc/rpids_setting.class.php' ); // The setting class (used almost everywhere)
require_once( 'inc/pages/rpids_settings.php' ); // Settings
require_once( 'inc/rpids_config.php' ); // Config
require_once( 'inc/rpids_cron.php' ); // Cron

// Now include the default widgets
require_once( 'default_widgets/rpids_slides.php' ); // The slides custom post
require_once( 'default_widgets/rpids_textslides.php' ); // The text slides custom post
require_once( 'default_widgets/rpids_time.php' ); // The date/time widget
require_once( 'default_widgets/rpids_weather.php' ); // The weather widget

?>