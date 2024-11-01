<?php

/*
  Plugin Name: wp-seatingchart
  Plugin URI:
  Description: Create a seating layout using tables, chairs, restroom locations and trash receptacles via a drag-n-drop interface.  Allow users to reserve a table or seat.
  Version: 1.0.6
  Author: oneprince
  Author URI:
  License: GPL2
 */

/*  Copyright 2013  oneprince  (email : oneprince@gmail.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

if (!function_exists('add_action')) {
    require_once("../../../wp-config.php");
}

if (!class_exists("WPSeatingChartPluginSeries")) {

    class WPSeatingChartPluginSeries {

        var $adminOptionsName = "plugin_wpsc_settings";
        var $shortcode = "[seating chart]";

        function WPSeatingChartPluginSeries() { //constructor
        }

        function registerIncludes() {
            wp_register_script('JSON2', WP_PLUGIN_URL . '/wp-seatingchart/js/json2.js');

            wp_register_script('wpscPluginScript', WP_PLUGIN_URL . '/wp-seatingchart/js/wp-seatingchart.js.php');
            wp_register_style('wpscPluginCSS', WP_PLUGIN_URL . '/wp-seatingchart/css/wp-seatingchart.css');

            wp_register_script('jquery-ui-slider', WP_PLUGIN_URL . '/wp-seatingchart/js/jquery-ui-1.7.2.custom.min.js');
            wp_register_style('jquery-ui-slider', WP_PLUGIN_URL . '/wp-seatingchart/css/vader/jquery-ui-1.7.2.custom.css');
        }

        function adminStyles() {
            wp_enqueue_style('wpscPluginCSS');
            wp_enqueue_style('jquery-ui-slider');
        }

        function adminScripts() {
            wp_enqueue_script('JSON2');
            wp_enqueue_script('jquery');
            wp_enqueue_script('jquery-ui-core');
            wp_enqueue_script('jquery-ui-draggable');
            wp_enqueue_script('jquery-ui-slider');
            wp_enqueue_script('wpscPluginScript');
        }

        function installDatabase() {
            global $wpdb;

            $table = $wpdb->prefix . "wpsc_seatingchartitems";
            $structure = "CREATE TABLE $table (
		        wpsc_ItemTypeID INTEGER NOT NULL AUTO_INCREMENT,
		        wpsc_ItemName VARCHAR(80) NOT NULL,
		        wpsc_ImageN VARCHAR(80) NOT NULL,
		        wpsc_ImageW VARCHAR(80) NOT NULL,
		        wpsc_ImageE VARCHAR(80) NOT NULL,
		        wpsc_ImageS VARCHAR(80) NOT NULL,
		        wpsc_Claimable BOOLEAN NOT NULL,
				PRIMARY KEY (wpsc_ItemTypeID)
		    );";
            $wpdb->query($structure);

            // Populate table
            //TABLE

            $hasItem = $wpdb->get_var("SELECT count(*) FROM $table WHERE wpsc_ItemName = 'Table';") > 0;

            if (!$hasItem)
                $wpdb->query("INSERT INTO $table(wpsc_ItemName, wpsc_ImageN, wpsc_ImageW, wpsc_ImageE, wpsc_ImageS, wpsc_Claimable)
                            VALUES('Table', 'table_vertical.png', 'table_horizontal.png', 'table_horizontal.png', 'table_vertical.png', 1 )");
            else
                $wpdb->query("UPDATE $table set wpsc_ImageN = 'table_vertical.png',
                                            wpsc_ImageW = 'table_vertical.png', 
                                            wpsc_ImageE = 'table_horizontal.png', 
                                            wpsc_ImageS = 'table_horizontal.png', 
                                            wpsc_Claimable = 1
                                            WHERE wpsc_itemName = 'Table'");

            //TRASH

            $hasItem = $wpdb->get_var("SELECT count(*) FROM $table WHERE wpsc_ItemName = 'Trash';") > 0;

            if (!$hasItem)
                $wpdb->query("INSERT INTO $table(wpsc_ItemName, wpsc_ImageN, wpsc_ImageW, wpsc_ImageE, wpsc_ImageS, wpsc_Claimable)
                            VALUES('Trash', 'trash_all.png', 'trash_all.png', 'trash_all.png', 'trash_all.png', 0)");
            else
                $wpdb->query("UPDATE $table set wpsc_ImageN = 'trash_all.png',
                                            wpsc_ImageW = 'trash_all.png', 
                                            wpsc_ImageE = 'trash_all.png', 
                                            wpsc_ImageS = 'trash_all.png', 
                                            wpsc_Claimable = 0
                                            WHERE wpsc_itemName = 'Trash'");

            //CHAIR

            $hasItem = $wpdb->get_var("SELECT count(*) FROM $table WHERE wpsc_ItemName = 'Chair';") > 0;

            if (!$hasItem)
                $wpdb->query("INSERT INTO $table(wpsc_ItemName, wpsc_ImageN, wpsc_ImageW, wpsc_ImageE, wpsc_ImageS, wpsc_Claimable)
                            VALUES('Chair', 'chair_n.png', 'chair_w.png', 'chair_e.png', 'chair_s.png', 1)");
            else
                $wpdb->query("UPDATE $table set wpsc_ImageN = 'chair_n.png',
                                            wpsc_ImageW = 'chair_w.png', 
                                            wpsc_ImageE = 'chair_e.png', 
                                            wpsc_ImageS = 'chair_s.png', 
                                            wpsc_Claimable = 1
                                            WHERE wpsc_itemName = 'Chair'");

            //RESTROOM

            $hasItem = $wpdb->get_var("SELECT count(*) FROM $table WHERE wpsc_ItemName = 'Restroom';") > 0;

            if (!$hasItem)
                $wpdb->query("INSERT INTO $table(wpsc_ItemName, wpsc_ImageN, wpsc_ImageW, wpsc_ImageE, wpsc_ImageS, wpsc_Claimable)
                            VALUES('Restroom', 'restroom_all.png', 'restroom_all.png', 'restroom_all.png', 'restroom_all.png', 0)");
            else
                $wpdb->query("UPDATE $table set wpsc_ImageN = 'restroom_all.png',
                                            wpsc_ImageW = 'restroom_all.png', 
                                            wpsc_ImageE = 'restroom_all.png', 
                                            wpsc_ImageS = 'restroom_all.png', 
                                            wpsc_Claimable = 0 
                                            WHERE wpsc_itemName = 'Restroom'");



            $table = $wpdb->prefix . "wpsc_seatingchart";
            $structure = "CREATE TABLE $table (
		        wpsc_SeatingChartID INTEGER NOT NULL AUTO_INCREMENT,
		        wpsc_ItemTypeID INTEGER NOT NULL,
		        wpsc_Claimable BOOLEAN NOT NULL,
		        wpsc_ClaimedBy INTEGER NOT NULL,
		        wpsc_X INTEGER NOT NULL,
		        wpsc_Y INTEGER NOT NULL,
		        wpsc_Direction ENUM('N','E','S','W') NOT NULL,
		        wpsc_ZIndex INTEGER NOT NULL,
		        PRIMARY KEY (wpsc_SeatingChartID)
		    );";
            $wpdb->query($structure);
        }

        function printAdminPage() {
            global $wpdb;
            $wpscOptions = $this->getAdminOptions();

            include 'include/wp-seatingchart-admin.php';
        }

        function getAdminOptions() {
            $wpscAdminOptions = array('wpsc_room_size_width' => '200',
                'wpsc_room_size_height' => '200',
                'wpsc_room_size_item_zoom' => '100',
                'wpsc_reservation_limit' => '1'
            );
            $wpscOptions = get_option($this->adminOptionsName);
            if (!empty($wpscOptions)) {
                foreach ($wpscOptions as $key => $option)
                    $wpscAdminOptions[$key] = $option;
            }
            update_option($this->adminOptionsName, $wpscAdminOptions);
            return $wpscAdminOptions;
        }

        function init() {
            $this->getAdminOptions();
            $this->installDatabase();
        }

        function addContent($content = '') {
            global $wpdb;
            $table = $wpdb->prefix . "wpsc_seatingchartitems";
            $wpsc_seatingchart_table = $wpdb->prefix . "wpsc_seatingchart";
            $seatingchartitems = $wpdb->get_results("SELECT * FROM $table", OBJECT);
            $wpusers = $wpdb->get_results("SELECT ID, user_nicename, md5(trim(lower(user_email))) as gravatar_hash FROM $wpdb->users ORDER BY user_nicename ASC");
            $seatingchart = $wpdb->get_results("SELECT * FROM $wpsc_seatingchart_table", OBJECT);
            $wpscOptions = get_option($this->adminOptionsName);

            foreach ($seatingchartitems as $seatingchartitem) {
                //
                //	add image dimensions to images
                //
				
				list($width, $height, $type, $attr) = getimagesize(WP_PLUGIN_DIR . "/wp-seatingchart/images/" . $seatingchartitem->wpsc_ImageN);
                $seatingchartitem->wpsc_ImageNWidth = $width;
                $seatingchartitem->wpsc_ImageNHeight = $height;

                list($width, $height, $type, $attr) = getimagesize(WP_PLUGIN_DIR . "/wp-seatingchart/images/" . $seatingchartitem->wpsc_ImageE);
                $seatingchartitem->wpsc_ImageEWidth = $width;
                $seatingchartitem->wpsc_ImageEHeight = $height;

                list($width, $height, $type, $attr) = getimagesize(WP_PLUGIN_DIR . "/wp-seatingchart/images/" . $seatingchartitem->wpsc_ImageS);
                $seatingchartitem->wpsc_ImageSWidth = $width;
                $seatingchartitem->wpsc_ImageSHeight = $height;

                list($width, $height, $type, $attr) = getimagesize(WP_PLUGIN_DIR . "/wp-seatingchart/images/" . $seatingchartitem->wpsc_ImageW);
                $seatingchartitem->wpsc_ImageWWidth = $width;
                $seatingchartitem->wpsc_ImageWHeight = $height;
            }

            $output = "<script type='text/javascript'>
			\$j = jQuery.noConflict();
			
			var roomWidth = parseInt('" . $wpscOptions['wpsc_room_size_width'] . "');
			var roomHeight = parseInt('" . $wpscOptions['wpsc_room_size_height'] . "');
			var roomZoom = parseInt('" . $wpscOptions['wpsc_room_size_item_zoom'] . "');
			var reservationLimit = parseInt('" . $wpscOptions['wpsc_reservation_limit'] . "');
                        var room = null;
			
			var itemDetails = JSON.parse('" . json_encode($seatingchartitems) . "', null);
			var items = JSON.parse('" . json_encode($seatingchart) . "', null);
			var users = JSON.parse('" . json_encode($wpusers) . "', null);
			var selectedItem = null;
			\$j(document).ready(function()
			{LoadPublicTable()});
			</script>
			<div id='divRoomLive'><div id='divAjaxLoader'></div></div>";

            $content = str_replace($this->shortcode, $output, $content);
            return $content;
        }

        function addScriptsAndStyles($posts) {
            if (empty($posts))
                return $posts;

            $shortcode_found = false; // use this flag to see if styles and scripts need to be enqueued

            foreach ($posts as $post) {
                $pos1 = stripos($post->post_content, $this->shortcode);

                if ($pos1 !== false) {
                    $shortcode_found = true; // bingo!
                    break;
                }
            }

            if ($shortcode_found) {
                // enqueue here
                $this->addScripts();
                $this->addStyles();
            }

            return $posts;
        }

        function addScripts() {
            wp_enqueue_script("JSON2", WP_PLUGIN_URL . '/wp-seatingchart/js/json2.js', array('jquery'));
            wp_enqueue_script("wpscPluginJS", WP_PLUGIN_URL . "/wp-seatingchart/js/wp-seatingchart.js.php", array('jquery'));

            wp_enqueue_script("wpscPluginJS", WP_PLUGIN_URL . "/wp-seatingchart/js/wp-seatingchart.js.php", array('jquery'));
            wp_enqueue_script("wpscJQToolTipJS", WP_PLUGIN_URL . "/wp-seatingchart/js/jquery.tooltip.js", array('jquery'));
            wp_enqueue_script("wpscJQDimensionsJS", WP_PLUGIN_URL . "/wp-seatingchart/js/jquery.dimensions.js", array('jquery'));
        }

        function addStyles() {
            wp_enqueue_style('wpscPluginCSS', WP_PLUGIN_URL . '/wp-seatingchart/css/wp-seatingchart.css');
            wp_enqueue_style('wpscJQToolTipCSS', WP_PLUGIN_URL . '/wp-seatingchart/css/jquery.tooltip.css');
        }

        function userSitDown() {
            global $wpdb;
            global $current_user;
            $wpsc_seatingchart_table = $wpdb->prefix . "wpsc_seatingchart";
            get_currentuserinfo();

            $wpscOptions = get_option($this->adminOptionsName);
            
            //  get limit
            $reservation_limit = intval($wpscOptions["wpsc_reservation_limit"]);
            
            //
            //	make sure the user hasn't reserved more than the limit
            //
            
            $claimedByYouAlready = $wpdb->get_results($wpdb->prepare("SELECT count(*) as cnt FROM $wpsc_seatingchart_table WHERE wpsc_ClaimedBy = %d", $current_user->ID), OBJECT);
            
            echo $reservation_limit . ' ' . $claimedByYouAlready[0]->cnt;
            
            $claimedAlready = $wpdb->get_results($wpdb->prepare("SELECT count(*) as cnt FROM $wpsc_seatingchart_table WHERE wpsc_SeatingChartID = %d and wpsc_ClaimedBy <> 0", $_POST["wpsc_SeatingChartID"]), OBJECT);
            
            if (!empty($claimedByYouAlready) && $claimedByYouAlready[0]->cnt >= $reservation_limit) {
                echo "You may only reserve " . $reservation_limit . " spots.";
            } else if (!empty($claimedAlready) && $claimedAlready[0]->cnt > 0) {
                echo "Someone snagged the spot before you, please refresh the page.";
            } else {
                $wpdb->query($wpdb->prepare("UPDATE $wpsc_seatingchart_table SET wpsc_ClaimedBy = %s WHERE wpsc_SeatingChartID = %d", $current_user->ID, $_POST["wpsc_SeatingChartID"]));
                echo "OK";
            }
        }

        function userStandUp() {
            global $wpdb;
            global $current_user;
            $wpsc_seatingchart_table = $wpdb->prefix . "wpsc_seatingchart";
            get_currentuserinfo();

            $wpdb->query($wpdb->prepare("UPDATE $wpsc_seatingchart_table SET wpsc_ClaimedBy = 0 WHERE wpsc_ClaimedBy = %d and wpsc_SeatingChartID = %d", $current_user->ID, $_POST["wpsc_SeatingChartID"]));

            echo "OK";
        }

    }

}

if (class_exists("WPSeatingChartPluginSeries")) {
    $wpsc_pluginSeries = new WPSeatingChartPluginSeries();
}

//Initialize the admin panel
if (!function_exists("WPSeatingChartPluginSeries_ap")) {

    function WPSeatingChartPluginSeries_ap() {
        global $wpsc_pluginSeries;
        if (!isset($wpsc_pluginSeries)) {
            return;
        }
        if (function_exists('add_options_page')) {
            $page = add_options_page('Seating Chart', 'Seating Chart', 9, basename(__FILE__), array(&$wpsc_pluginSeries, 'printAdminPage'));
            add_action('admin_print_scripts-' . $page, array(&$wpsc_pluginSeries, 'adminScripts'));
            add_action('admin_print_styles-' . $page, array(&$wpsc_pluginSeries, 'adminStyles'));
        }
    }

}

//Actions and Filters   
if (isset($wpsc_pluginSeries)) {
    //Actions

    add_action('activate_wp-seatingchart/wp-seatingchart.php', array(&$wpsc_pluginSeries, 'init'));
    add_action('admin_init', array(&$wpsc_pluginSeries, 'registerIncludes'));
    add_action('admin_menu', 'WPSeatingChartPluginSeries_ap');

    //THIS OR******** (Includes the public js and css on every page of the site - no overhead)
    //add_action('wp_print_scripts', array(&$wpsc_pluginSeries,'addScripts'));
    //add_action('wp_print_styles', array(&$wpsc_pluginSeries,'addStyles'));
    //Filters
    //THIS******** (Includes the public js and css on only the page of the site that has the shortcode - has overhead)
    add_filter('the_posts', array(&$wpsc_pluginSeries, 'addScriptsAndStyles'));

    add_filter('the_content', array(&$wpsc_pluginSeries, 'addContent'));
}

//
//	ajax handler
//

if (!empty($_POST["ajax"])) {

    switch ($_POST["command"]) {
        case "SitDown":
            echo $wpsc_pluginSeries->userSitDown();
            break;
        case "StandUp":
            echo $wpsc_pluginSeries->userStandUp();
            break;
    }
}
?>