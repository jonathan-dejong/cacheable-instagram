<?php
/*
Plugin Name: Shipyard Instagram
Version: 0.1
Description: Instagram Plugin for Barncancerfonden
Author: The Shipyard crew
Author URI: https://theshipyard.se
Text Domain: shipyard-instagram
Domain Path: /languages
*/


// load translations
load_plugin_textdomain( 'shipyard-instagram', false, dirname( plugin_basename(__FILE__) ) . '/languages/' );


/**
 * Register autoloader.
 */
function shipyard_instagram_autoloader( $classname ) {
    $classname = explode( '\\', $classname );
    $classfile = sprintf( '%sincludes/class-%s.php',
        plugin_dir_path( __FILE__ ),
        str_replace( '_', '-', strtolower( end( $classname ) ) )
    );
    if ( file_exists( $classfile ) ) {
        include_once( $classfile );
    }
}
spl_autoload_register( 'shipyard_instagram_autoloader' );


/**
 * Register a cron hook every fifteen minutes.
 *
 * @param array $schedules Array of cron schedules.
 *
 * @return array Modified array of cron schedules.
 */
function shipyard_instagram_add_cron_schedules( $schedules ) {
    $schedules['fifteen_minutes'] = array(
        'interval' => MINUTE_IN_SECONDS * 15,
        'display'  => __( 'Every Fifteen Minutes', 'shipyard-instagram' ),
    );

    return $schedules;
}
add_filter( 'cron_schedules', 'shipyard_instagram_add_cron_schedules' );


/**
 * Activate the cron hooks on plugin activation.
 */
function shipyard_instagram_activation() {
    wp_schedule_event( current_time( 'timestamp' ), 'fifteen_minutes', 'update_instagram_feed' );
    wp_schedule_event( current_time( 'timestamp' ), 'daily', 'delete_old_instragram_posts' );
}
register_activation_hook( __FILE__, 'shipyard_instagram_activation' );


/**
 * Remove the cron hooks on plugin deletion.
 */
function shipyard_instagram_deactivation() {
    wp_clear_scheduled_hook( 'update_instagram_feed' );
    wp_clear_scheduled_hook( 'delete_old_instragram_posts' );
}
register_deactivation_hook( __FILE__, 'shipyard_instagram_deactivation' );


Shipyard_Instagram_Post_Type::get();
Shipyard_Instagram_Import_Images::get();
Shipyard_Instagram_Options_Page::get();


// API
function shipyard_instagram_render_images( $num_images = 9 ) {
    Shipyard_Instagram_Display_Images::get()->render_images( $num_images );
}
