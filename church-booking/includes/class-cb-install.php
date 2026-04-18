<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CB_Install {

    const TABLE_BOOKINGS = 'cb_bookings';

    public static function activate() {
        global $wpdb;

        $table           = $wpdb->prefix . self::TABLE_BOOKINGS;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            start_time DATETIME NOT NULL,
            end_time DATETIME NOT NULL,
            source VARCHAR(32) NOT NULL DEFAULT 'church',
            status VARCHAR(16) NOT NULL DEFAULT 'confirmed',
            title VARCHAR(255) NOT NULL DEFAULT '',
            customer_name VARCHAR(191) NOT NULL DEFAULT '',
            customer_email VARCHAR(191) NOT NULL DEFAULT '',
            customer_phone VARCHAR(64) NOT NULL DEFAULT '',
            notes TEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY start_time (start_time),
            KEY end_time (end_time),
            KEY status (status)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        if ( false === get_option( 'cb_settings' ) ) {
            add_option( 'cb_settings', CB_Settings::defaults() );
        }
    }

    public static function deactivate() {
        // Intentionally keep data on deactivation.
    }

    public static function table() {
        global $wpdb;
        return $wpdb->prefix . self::TABLE_BOOKINGS;
    }
}
