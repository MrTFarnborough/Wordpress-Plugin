<?php
/**
 * Plugin Name: Church Booking
 * Plugin URI:  https://example.com/church-booking
 * Description: Extracts existing church bookings and exposes the remaining time as available slots that visitors can book from the frontend.
 * Version:     1.0.0
 * Author:      Church Booking
 * License:     GPL-2.0-or-later
 * Text Domain: church-booking
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CHURCH_BOOKING_VERSION', '1.0.0' );
define( 'CHURCH_BOOKING_FILE', __FILE__ );
define( 'CHURCH_BOOKING_PATH', plugin_dir_path( __FILE__ ) );
define( 'CHURCH_BOOKING_URL', plugin_dir_url( __FILE__ ) );

require_once CHURCH_BOOKING_PATH . 'includes/class-cb-install.php';
require_once CHURCH_BOOKING_PATH . 'includes/class-cb-settings.php';
require_once CHURCH_BOOKING_PATH . 'includes/class-cb-rooms.php';
require_once CHURCH_BOOKING_PATH . 'includes/class-cb-bookings.php';
require_once CHURCH_BOOKING_PATH . 'includes/class-cb-slots.php';
require_once CHURCH_BOOKING_PATH . 'includes/class-cb-admin.php';
require_once CHURCH_BOOKING_PATH . 'includes/class-cb-frontend.php';

register_activation_hook( __FILE__, array( 'CB_Install', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'CB_Install', 'deactivate' ) );

add_action( 'plugins_loaded', 'church_booking_bootstrap' );

function church_booking_bootstrap() {
    load_plugin_textdomain( 'church-booking', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

    CB_Install::maybe_upgrade();

    if ( is_admin() ) {
        new CB_Admin();
    }

    new CB_Frontend();
}
