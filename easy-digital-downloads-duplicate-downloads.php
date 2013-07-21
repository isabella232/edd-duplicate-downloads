<?php
/*
Plugin Name: Easy Digital Downloads Duplicate Downloads
Plugin URI: http://shop.chriscct7.com/plugins/easy-digital-downloads-duplicate-downloads/
Description: Duplicates EDD Downloads
Version: 1.0
Author: Chris Christoff
Author URI: http://www.chriscct7.com
Text Domain: duplicate_downloads
 */

function edd_dd_wp(){
if ( class_exists( 'Easy_Digital_Downloads' ) ) {
    require_once( dirname( __FILE__ ) . '/duplicate.php' );
    require_once( dirname( __FILE__ ) . '/admin.php' );
   
if( ! class_exists( 'EDD_License' ) )
	include( dirname( __FILE__ ) . '/EDD_License_Handler.php' );

$license = new EDD_License( __FILE__, 'Duplicate Downloads', '1.0', 'Chris Christoff' );
}
}
add_action('plugins_loaded','edd_dd_wp');