<?php
/**
 * Uninstall handler — removes plugin data when the user deletes the plugin.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

delete_option( 'cb_settings' );

$table = $wpdb->prefix . 'cb_bookings';
$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
