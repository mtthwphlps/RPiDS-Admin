<?php wp_enqueue_script('jquery'); ?>
<?php require 'header.php'; ?>
<?php
if( get_option( 'rpids_ccs_active' ) == 'ccs_off' || get_option( 'rpids_ccs_active' ) == '' ) {
	require_once( 'display/index.php' );
} else { ?>
	This site is the admin side of our RPiDS system. No content is displayed on this site.
<?php } ?>
<?php require 'footer.php'; ?>