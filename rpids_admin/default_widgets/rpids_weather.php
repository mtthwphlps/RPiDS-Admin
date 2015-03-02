<?php
// Add weather page
function rpids_weather_add_page() {
	add_menu_page( 'Weather', 'Weather', 'edit_posts', 'rpids-weather', 'rpids_weather_display', 'dashicons-format-image', 10);
}
function rpids_weather_display() {
	global $wpdb;
	global $table_prefix;
?>
	<div class="wrap">
		<h2>RPiDS Weather</h2>
		<p>This is a read only view of the current weather for each location.</p>
		<p>
		<?php if(@$_GET['rpids-weather'] == 'update') {
			include 'weather.php';
		} else {
			$locations = rpids_get_setting('locations');
			if(count($locations) > 0) {
				foreach ($locations as $location) {
					$sql = "SELECT * FROM `".$table_prefix."rpids_weather` WHERE `location`='".$location['location']."';";
					$weather = $wpdb->get_results($sql, ARRAY_A); ?>
					<strong><?php echo $location['location']; ?></strong><br />
					Current: <?php $weather['current_temp']; ?>&deg;F <?php $weather['current_weather']; ?> <?php $weather['current_img']; ?><br />
					Today: High: <?php $weather['today_maxtemp']; ?>&deg;F Low: <?php $weather['today_mintemp']; ?>&deg;F <?php $weather['today_weather']; ?> <?php $weather['today_img']; ?><br />
					Tomorrow: High: <?php $weather['tomorrow_maxtemp']; ?>&deg;F Low: <?php $weather['tomorrow_mintemp']; ?>&deg;F <?php $weather['tomorrow_weather']; ?> <?php $weather['tomorrow_img']; ?>
					<hr />
				<?php }
			}
		} ?>
		</p>
		<p><a href="admin.php?page=rpids-weather&rpids-weather=update" class="button button-primary">Update Now</a></p>
	</div>
<?php 
}
add_action( 'admin_menu', 'rpids_weather_add_page' );
?>