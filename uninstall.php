<?php

if( !defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') )
    exit();

delete_option('plugin_wpsc_settings');

global $wpdb;

$table = $wpdb->prefix."wpsc_seatingchartitems";
$wpdb->query("DROP TABLE IF EXISTS $table");

$table = $wpdb->prefix."wpsc_seatingchart";
$wpdb->query("DROP TABLE IF EXISTS $table");

?>