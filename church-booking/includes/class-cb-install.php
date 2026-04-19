<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CB_Install {

    const TABLE_BOOKINGS = 'cb_bookings';
    const TABLE_ROOMS    = 'cb_rooms';
    const DB_VERSION     = '1.1.0';
    const DB_VERSION_OPT = 'cb_db_version';

    public static function activate() {
        self::run_schema();

        if ( false === get_option( 'cb_settings' ) ) {
            add_option( 'cb_settings', CB_Settings::defaults() );
        }
    }

    public static function deactivate() {
        // Intentionally keep data on deactivation.
    }

    /**
     * Called on every load; upgrades the schema when DB_VERSION changes.
     */
    public static function maybe_upgrade() {
        if ( get_option( self::DB_VERSION_OPT ) !== self::DB_VERSION ) {
            self::run_schema();
        }
    }

    private static function run_schema() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $bookings_table  = $wpdb->prefix . self::TABLE_BOOKINGS;
        $rooms_table     = $wpdb->prefix . self::TABLE_ROOMS;

        $bookings_sql = "CREATE TABLE {$bookings_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            room_id BIGINT(20) UNSIGNED NULL,
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
            KEY room_id (room_id),
            KEY start_time (start_time),
            KEY end_time (end_time),
            KEY status (status)
        ) {$charset_collate};";

        $rooms_sql = "CREATE TABLE {$rooms_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            external_id VARCHAR(64) NOT NULL DEFAULT '',
            name VARCHAR(191) NOT NULL,
            room_type VARCHAR(16) NOT NULL DEFAULT 'hire',
            parent_room_id BIGINT(20) UNSIGNED NULL,
            price_per_hour DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY name (name),
            KEY external_id (external_id),
            KEY parent_room_id (parent_room_id),
            KEY room_type (room_type)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $bookings_sql );
        dbDelta( $rooms_sql );

        update_option( self::DB_VERSION_OPT, self::DB_VERSION );
    }

    public static function table() {
        global $wpdb;
        return $wpdb->prefix . self::TABLE_BOOKINGS;
    }

    public static function rooms_table() {
        global $wpdb;
        return $wpdb->prefix . self::TABLE_ROOMS;
    }
}
