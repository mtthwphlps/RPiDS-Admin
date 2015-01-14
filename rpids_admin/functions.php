<?php
/*
	RPiDS functions.php file
	Here we load any initial files we need as well handle initial setup and config
*/

// Set the timezone (after getting it from the WP option)
// We default to the America/New_York timezone (Eastern w/ daylight savings)
$rpids_timezone = get_option( 'rpids_timezone', 'America/New_York' );
date_default_timezone_set( $rpids_timezone );

// Start a session
session_start();

// WP table prefix function
function rpids_tableprefix() {
	global $wpdb;
	if(is_multisite()) {
		$table_prefix = $wpdb->base_prefix.''.get_current_blog_id().'';
	} else {
		$table_prefix = $wpdb->prefix.'';
	}
	return $table_prefix;
}

// Load this now
require_once( 'inc/rpids_cron.php' );

// Add the every 10 minutes WP Cron
function cron_add_10min( $schedules ) {
	$schedules['10min'] = array(
		'interval' => 600,
		'display' => __( 'Every 10 minutes' )
	);
	return $schedules;
}
add_filter( 'cron_schedules', 'cron_add_10min' );

// Run this on install
function rpids_theme_install() {
	global $wpdb; // We're doing DB work
	
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	
	// Add the various tables we need
	$sql = "CREATE TABLE IF NOT EXISTS `" . rpids_tableprefix() . "rpids_status` (
		`id` int(10) NOT NULL AUTO_INCREMENT,
		`timestamp` varchar(255) NOT NULL,
		`title` text NOT NULL,
		`h1` text NOT NULL,
		`h2` text NOT NULL,
		`p` text NOT NULL,
		`img` text NOT NULL,
		PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;
	";
	dbDelta($sql);
	$sql = "CREATE TABLE IF NOT EXISTS `" . rpids_tableprefix() . "rpids_control` (
		`id` int(10) NOT NULL AUTO_INCREMENT,
		`timestamp` varchar(255) NOT NULL,
		`screen` varchar(255) NOT NULL,
		`location` varchar(255) NOT NULL,
		`nextpoweron` varchar(255) NOT NULL,
		`nextpoweroff` varchar(255) NOT NULL,
		`nextinputchange` varchar(255) NOT NULL,
		`nextinput` varchar(255) NOT NULL,
		`nextchannelchange` varchar(255) NOT NULL,
		`nextchannel` varchar(255) NOT NULL,
		PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;
	";
	dbDelta($sql);
	$sql = "CREATE TABLE IF NOT EXISTS `" . rpids_tableprefix() . "rpids_log` (
		`id` int(10) NOT NULL AUTO_INCREMENT,
		`timestamp` varchar(255) NOT NULL,
		`by` varchar(255) NOT NULL,
		`page` varchar(255) NOT NULL,
		`ip` varchar(255) NOT NULL,
		`data` varchar(255) NOT NULL,
		PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;
	";
	dbDelta($sql);
	$sql = "CREATE TABLE IF NOT EXISTS `" . rpids_tableprefix() . "rpids_rate_limit` (
		`id` int(10) NOT NULL AUTO_INCREMENT,
		`timestamp` varchar(255) NOT NULL,
		`sid` varchar(255) NOT NULL,
		`ip` varchar(255) NOT NULL,
		PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;
	";
	dbDelta($sql);
	$sql = "CREATE TABLE IF NOT EXISTS `" . rpids_tableprefix() . "rpids_events` (
		`id` int(10) NOT NULL AUTO_INCREMENT,
		`uniqueid` varchar(255) NOT NULL,
		`eventid` varchar(255) NOT NULL,
		`ccb_id` int(10) NOT NULL,
		`timestamp` varchar(255) NOT NULL,
		`date` varchar(255) NOT NULL,
		`event_name` varchar(255) NOT NULL,
		`event_description` varchar(255) NOT NULL,
		`start_time` varchar(255) NOT NULL,
		`end_time` varchar(255) NOT NULL,
		`event_duration` varchar(255) NOT NULL,
		`event_type` varchar(255) NOT NULL,
		`location` varchar(255) NOT NULL,
		`group_name` varchar(255) NOT NULL,
		`group_id` int(10) NOT NULL,
		`group_type` varchar(255) NOT NULL,
		`grouping_name` varchar(255) NOT NULL,
		`leader_name` varchar(255) NOT NULL,
		`leader_id` varchar(255) NOT NULL,
		`leader_phone` varchar(255) NOT NULL,
		`leader_email` varchar(255) NOT NULL,
		PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;
	";
	dbDelta($sql);
	$sql = "CREATE TABLE IF NOT EXISTS `" . rpids_tableprefix() . "rpids_weather` (
		`id` int(10) NOT NULL AUTO_INCREMENT,
		`location` varchar(255) NOT NULL,
		`timestamp` varchar(255) NOT NULL,
		`current_temp` varchar(255) NOT NULL,
		`current_weather` varchar(255) NOT NULL,
		`current_img` varchar(255) NOT NULL,
		`today_maxtemp` varchar(255) NOT NULL,
		`today_mintemp` varchar(255) NOT NULL,
		`today_weather` varchar(255) NOT NULL,
		`today_img` varchar(255) NOT NULL,
		`tomorrow_maxtemp` varchar(255) NOT NULL,
		`tomorrow_mintemp` varchar(255) NOT NULL,
		`tomorrow_weather` varchar(255) NOT NULL,
		`tomorrow_img` varchar(255) NOT NULL,
		`updated` varchar(255) NOT NULL,
		PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;
	";
	dbDelta($sql);
	$sql = "CREATE TABLE IF NOT EXISTS `" . rpids_tableprefix() . "rpids_locations` (
		`id` int(10) NOT NULL AUTO_INCREMENT,
		`location` varchar(255) NOT NULL,
		`groups` text NOT NULL,
		`weathercode` varchar(255) NOT NULL,
		`weathermeasure` varchar(1) NOT NULL,
		PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;
	";
	dbDelta($sql);
	$sql = "CREATE TABLE IF NOT EXISTS `" . rpids_tableprefix() . "rpids_screens` (
		`id` int(10) NOT NULL AUTO_INCREMENT,
		`screen` varchar(255) NOT NULL,
		`location` varchar(255) NOT NULL,
		`did` int(100) NOT NULL,
		`update_data` text NOT NULL,
		PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;
	";
	dbDelta($sql);
	$sql = "CREATE TABLE IF NOT EXISTS `" . rpids_tableprefix() . "rpids_devices` (
		`id` int(10) NOT NULL AUTO_INCREMENT,
		`did` int(10) NOT NULL,
		`model` varchar(255) NOT NULL,
		`hversion` varchar(255) NOT NULL,
		`sversion` varchar(255) NOT NULL,
		`type` varchar(255) NOT NULL,
		`builder` varchar(255) NOT NULL,
		PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;
	";
	dbDelta($sql);
	$sql = "CREATE TABLE IF NOT EXISTS `" . rpids_tableprefix() . "rpids_layouts` (
		`id` int(10) NOT NULL AUTO_INCREMENT,
		`width` int(10) NOT NULL,
		`height` int(10) NOT NULL,
		`bgimage` text NOT NULL,
		`bgimagetype` varchar(255) NOT NULL,
		`bgcolor` varchar(255) NOT NULL,
		`startimage` text NOT NULL,
		`items` text NOT NULL,
		`lastmodified` varchar(255) NOT NULL,
		PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;
	";
	dbDelta($sql);
	$sql = "CREATE TABLE IF NOT EXISTS `" . rpids_tableprefix() . "rpids_group_links` (
		`id` int(10) NOT NULL AUTO_INCREMENT,
		`rpids_group` varchar(255) NOT NULL,
		`source_group` varchar(255) NOT NULL,
		PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;
	";
	dbDelta($sql);
	$sql = "CREATE TABLE IF NOT EXISTS `" . rpids_tableprefix() . "rpids_errors` (
		`id` int(10) NOT NULL AUTO_INCREMENT,
		`type` varchar(255) NOT NULL,
		`data` text NOT NULL,
		PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;
	";
	dbDelta($sql);
	add_option( "rpids_db_version", "2.0" );
	
	// Insert the default layouts
	$items = serialize( array(
		"type" => 'slides',
		"id" => '1',
		"top" => '0',
		"left" => '0',
		"width" => '100',
		"height" => '100'
	) );
	$wpdb->insert( rpids_tableprefix() . "rpids_layouts", array(
		"name" => 'Graphic Only, Full Screen Scaled',
		"type" => 'scale', 
		"width" => '100',
		"height" => '100',
		"bgimage" => '',
		"bgimagetype" => '',
		"bgcolor" => '#000000',
		"startimage" => '/display/img/rpids_full_screen.jpg',
		"items" => $items,
		"lastmodified" => '1420839974',
		"locked" => '1'
	));
	$items = serialize( array(
		array(
			"type" => 'slides',
			"id" => '1',
			"top" => '0.46',
			"left" => '0.26',
			"width" => '66.66',
			"height" => '66.66'
		),
		array(
			"type" => 'text',
			"id" => '1',
			"top" => '67.59',
			"left" => '0.26',
			"width" => '40.31',
			"height" => '31.94'
		),
		array(
			"type" => 'schedule',
			"id" => '1',
			"top" => '0.46',
			"left" => '67.18',
			"width" => '32.55',
			"height" => '81.01'
		),
		array(
			"type" => 'clock',
			"id" => '1',
			"top" => '81.94',
			"left" => '67.18',
			"width" => '32.55',
			"height" => '17.59'
		),
		array(
			"type" => 'weather',
			"id" => '1',
			"top" => '67.59',
			"left" => '40.88',
			"width" => '26.04',
			"height" => '31.94'
		)
	) );
	$wpdb->insert( rpids_tableprefix() . "rpids_layouts", array(
		"name" => 'Graphic, Text, Schedule, Weather and Clock. Scaled.',
		"type" => 'scale', 
		"width" => '100',
		"height" => '100',
		"bgimage" => '',
		"bgimagetype" => '',
		"bgcolor" => '#000000',
		"startimage" => '/display/img/rpids_full_screen.jpg',
		"items" => $items,
		"lastmodified" => '1420839974',
		"locked" => '1'
	));
	
	// Add the RPiDS event hook (that we use for all cron jobs)
	wp_schedule_event( time(), '10min', 'rpids_10min_event_hook' );
}
add_action( 'after_switch_theme', 'rpids_theme_install' );

// Connect the cron hook to the function
add_action( 'rpids_10min_event_hook', 'rpids_every_10_mins' );

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

// This lets us display notices using sessions (useful when we're redirected after a notice is set)
function rpids_admin_notice() {
	if( @$_SESSION["rpids_notice"] != '' ) {
		$return = urldecode( $_SESSION["rpids_notice"] );
		$_SESSION["rpids_notice"] = '';
		echo $return;
	}
}
add_action('admin_notices', 'rpids_admin_notice');

// Include other files
require_once( 'inc/rpids_global.php' ); // Global stuff
require_once( 'inc/rpids_api.php' ); // API (outbound stuff)
require_once( 'inc/rpids_ajax.php' ); // AJAX (inbound API calls)
require_once( 'inc/rpids_setting.php' ); // Settings
require_once( 'inc/rpids_setting.class.php' ); // The setting class (used almost everywhere)
require_once( 'inc/rpids_slides.php' ); // The slides custom post
require_once( 'inc/rpids_textslides.php' ); // The text slides custom post
require_once( 'inc/rpids_events.php' ); // The events/schedule custom post

?>