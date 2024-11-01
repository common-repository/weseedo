<?php
/*
Plugin Name: WebRTC plugin for realtime video contact
Plugin URI: http://www.weseedo.nl
Description: Plugin for WeSeeDo realtime online communication
Author: WeSeeDo
Version: 1.1.1
Author URI: https://www.weseedo.nl/wordpress/
*/

// require site part
require_once('inc/site.php');

// require api part
require_once('inc/api.php');

// require admin part
require_once('inc/admin.php');

$weSeeDoApi = new WeSeeDoApi();

$weSeeDoSite = new WeSeeDoSite();
$weSeeDoSite->setApi($weSeeDoApi);

$weSeeDoAdmin = new WeSeeDoAdmin();
$weSeeDoAdmin->setApi($weSeeDoApi);


function weseedo_create_db()
{
    // Create DB Here
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'weseedo_screens';

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255),
        account  varchar(32),
        page bigint(20),
        screen_type varchar(32),
        screen_width smallint(4),
        screen_height smallint(4),
		UNIQUE KEY id (id)
	) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    // insert empty record with id 1
    $wpdb->insert(
        $table_name,
        array(
            'id' => 1,
        ));


}
register_activation_hook( __FILE__, 'weseedo_create_db' );

/**
 * Register style sheet.
 */
function weseedo_register_plugin_styles() {
    wp_register_style( 'weseedo_css', plugins_url( 'css/client.css', __FILE__ ) );
    wp_enqueue_style( 'weseedo_css' );
}

function weseedo_register_plugin_admin_style() {
    wp_register_style( 'weseedo_admin_css', plugins_url( 'css/admin.css', __FILE__ ) );
    wp_enqueue_style( 'weseedo_admin_css' );
}

/**
 * Register javascript
 */
function weseedo_register_plugin_scripts() {
    wp_enqueue_script( 'jquery' );
    wp_register_script( 'weseedo_socketio_js', plugins_url( 'js/socket.io.js', __FILE__ ) );
    wp_register_script( 'weseedo_site_js', plugins_url( 'js/site.js', __FILE__ ) );
    wp_register_script( 'weseedo_video_js', plugins_url( 'js/video.js', __FILE__ ) );
}
