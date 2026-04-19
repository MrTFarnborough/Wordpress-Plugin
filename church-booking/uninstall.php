<?php
/**
 * Uninstall handler — removes plugin data when the user deletes the plugin.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

delete_option( 'cb_settings' );
delete_option( 'cb_db_version' );

$bookings = $wpdb->prefix . 'cb_bookings';
$rooms    = $wpdb->prefix . 'cb_rooms';
$wpdb->query( "DROP TABLE IF EXISTS {$bookings}" );
$wpdb->query( "DROP TABLE IF EXISTS {$rooms}" );
