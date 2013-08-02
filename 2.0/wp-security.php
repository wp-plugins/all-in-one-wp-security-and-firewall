<?php
/*
Plugin Name: All In One WP Security
Version: v2.0
Plugin URI: http://www.tipsandtricks-hq.com/
Author: Tips and Tricks HQ, Peter, Ruhul Amin
Author URI: http://www.tipsandtricks-hq.com/
Description: All round best WordPress security plugin!
License: GPL2
*/

if(!defined('ABSPATH'))exit; //Exit if accessed directly

include_once('wp-security-core.php');
register_activation_hook(__FILE__,array('AIO_WP_Security','activate_handler'));//activation hook
register_deactivation_hook(__FILE__,array('AIO_WP_Security','deactivate_handler'));//deactivation hook

function aiowps_show_plugin_settings_link($links, $file) 
{
    if ($file == plugin_basename(__FILE__)){
            $settings_link = '<a href="admin.php?page=aiowpsec_settings">Settings</a>';
            array_unshift($links, $settings_link);
    }
    return $links;
}
add_filter('plugin_action_links', 'aiowps_show_plugin_settings_link', 10, 2 );