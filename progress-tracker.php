<?php
/*
Plugin Name: Progress Tracker
Plugin URI: https://wordpress.org/plugins/progress-tracker/
Description: Keep track of your progress through a site, allowing you to mark pages as 'read'. 
Version: 0.9.3
Author: Alex Furr and Simon Ward
Author URI: https://wordpress.org/plugins/progress-tracker/
License: GPL
*/
define( 'PTRACKER_PLUGIN_URL', plugins_url('progress-tracker' , dirname( __FILE__ )) );
define( 'PTRACKER_PATH', plugin_dir_path(__FILE__) );
include_once( PTRACKER_PATH . '/class-progress-tracker.php' );
include_once( PTRACKER_PATH . '/class-draw.php' );
include_once( PTRACKER_PATH . '/ajax.php' );
include_once( PTRACKER_PATH . '/widget.php' );
include_once( PTRACKER_PATH . '/class-utils.php' );

if ( is_admin() ) {
	include_once( PTRACKER_PATH . '/settings-tabs.php' );
	include_once( PTRACKER_PATH . '/settings-user.php' );
}
$P_TRACKER = new progressTracker();


?>