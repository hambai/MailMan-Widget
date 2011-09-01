<?php
/*
Plugin Name: MailMan Widget
Plugin URI: http://labs.phurix.net/projects/wp-mailman-widget
Description: Uses php-mailman to integrate the GNU Mailman mailing list manager with Wordpress.
Author: James Wade
Version: 2.0
Author URI: http://labs.phurix.net/
License: GPL
Donate: http://tinyurl.com/hm2kpaypal

Copyright 2011 James Wade
*/
/**
 * Set up the autoloader.
 */
set_include_path(get_include_path() . PATH_SEPARATOR . realpath(dirname(__FILE__) . '/lib/'));
spl_autoload_extensions('.class.php');
if (! function_exists('buffered_autoloader')) {
	function buffered_autoloader ($c) {
		try {
			spl_autoload($c);
		} catch (Exception $e) {
			$message = $e->getMessage();
			return $message;
		}
	}
}
spl_autoload_register('buffered_autoloader');
/**
 * Get the plugin object. All the bookkeeping and other setup stuff happens here.
 */
$ns_mm_plugin = NS_MM_Plugin::get_instance();
register_deactivation_hook(__FILE__, array(&$ns_mm_plugin, 'remove_options'));
//eof