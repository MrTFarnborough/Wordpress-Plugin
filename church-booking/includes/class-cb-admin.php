<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CB_Admin {

    const MENU_SLUG = 'church-booking';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_init', array( $this, 'handle_actions' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
    }

    public function enqueue( $hook ) {
        if ( false === strpos( (string) $hook, self::MENU_SLUG ) ) {
            return;
        }
        wp_enqueue_style(
            'church-booking-admin',
            CHURCH_BOOKING_URL . 'assets/css/admin.css',
            array(),
            CHURCH_BOOKING_VERSION
        );
    }

    public function register_menu() {
        add_menu_page(
            __( 'Church Booking', 'church-booking' ),
            __( 'Church Booking', 'church-booking' ),
            'manage_options',
            self::MENU_SLUG,
            array( $this, 'render_bookings_page' ),
            'dashicons-calendar-alt',
            26
        );

        add_submenu_page(
            self::MENU_SLUG,
            __( 'Bookings', 'church-booking' ),
            __( 'Bookings', 'church-booking' ),
            'manage_options',
            self::MENU_SLUG,
            array( $this, 'render_bookings_page' )
        );

        add_submenu_page(
            self::MENU_SLUG,
            __( 'Rooms', 'church-booking' ),
            __( 'Rooms', 'church-booking' ),
            'manage_options',
            self::MENU_SLUG . '-rooms',
            array( $this, 'render_rooms_page' )
        );

        add_submenu_page(
            self::MENU_SLUG,
            __( 'Import from Church', 'church-booking' ),
            __( 'Import', 'church-booking' ),
            'manage_options',
            self::MENU_SLUG . '-import',
            array( $this, 'render_import_page' )
        );

        add_submenu_page(
            self::MENU_SLUG,
            __( 'Settings', 'church-booking' ),
            __( 'Settings', 'church-booking' ),
            'manage_options',
            self::MENU_SLUG . '-settings',
            array( $this, 'render_settings_page' )
        );
    }

    public function handle_actions() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( isset( $_POST['cb_action'] ) && 'save_settings' === $_POST['cb_action'] ) {
            check_admin_referer( 'cb_save_settings' );
            $clean = CB_Settings::sanitize( wp_unslash( $_POST['cb_settings'] ?? array() ) );
            update_option( CB_Settings::OPTION, $clean );
            add_settings_error( 'church-booking', 'saved', __( 'Settings saved.', 'church-booking' ), 'updated' );
        }

        if ( isset( $_POST['cb_action'] ) && 'add_booking' === $_POST['cb_action'] ) {
            check_admin_referer( 'cb_add_booking' );
            $result = CB_Bookings::insert( array(
                'start_time'    => wp_unslash( $_POST['start_time'] ?? '' ),
                'end_time'      => wp_unslash( $_POST['end_time'] ?? '' ),
                'title'         => wp_unslash( $_POST['title'] ?? '' ),
                'source'        => 'church',
                'status'        => 'confirmed',
                'customer_name' => wp_unslash( $_POST['customer_name'] ?? '' ),
            ) );
            if ( is_wp_error( $result ) ) {
                add_settings_error( 'church-booking', 'booking_err', $result->get_error_message() );
            } else {
                add_settings_error( 'church-booking', 'booking_ok', __( 'Booking added.', 'church-booking' ), 'updated' );
            }
        }

        if ( isset( $_GET['cb_action'], $_GET['booking'] ) && 'delete_booking' === $_GET['cb_action'] ) {
            check_admin_referer( 'cb_delete_booking_' . (int) $_GET['booking'] );
            CB_Bookings::delete( (int) $_GET['booking'] );
            wp_safe_redirect( admin_url( 'admin.php?page=' . self::MENU_SLUG ) );
            exit;
        }

        if ( isset( $_POST['cb_action'] ) && 'import_feed' === $_POST['cb_action'] ) {
            check_admin_referer( 'cb_import_feed' );
            $url     = esc_url_raw( wp_unslash( $_POST['feed_url'] ?? '' ) );
            $summary = CB_ChurchSuite::import( $url );
            if ( is_wp_error( $summary ) ) {
                add_settings_error( 'church-booking', 'import_err', $summary->get_error_message() );
            } else {
                if ( $url && $url !== CB_Settings::get( 'church_feed_url' ) ) {
                    CB_Settings::update( array( 'church_feed_url' => $url ) );
                }
                add_settings_error(
                    'church-booking',
                    'import_ok',
                    sprintf(
                        /* translators: 1: bookings imported, 2: hire rooms, 3: additional rooms */
                        __( 'Imported %1$d bookings, %2$d hire rooms and %3$d additional rooms.', 'church-booking' ),
                        $summary['bookings'],
                        $summary['rooms'],
                        $summary['additional_rooms']
                    ),
                    'updated'
                );
            }
        }

        if ( isset( $_POST['cb_action'] ) && 'save_rooms' === $_POST['cb_action'] ) {
            check_admin_referer( 'cb_save_rooms' );
            $rooms = isset( $_POST['rooms'] ) && is_array( $_POST['rooms'] ) ? wp_unslash( $_POST['rooms'] ) : array();
            foreach ( $rooms as $id => $fields ) {
                CB_Rooms::update(
                    (int) $id,
                    (string) ( $fields['room_type'] ?? '' ),
                    (int) ( $fields['parent_room_id'] ?? 0 ),
                    (float) ( $fields['price_per_hour'] ?? 0 )
                );
            }
            add_settings_error( 'church-booking', 'rooms_ok', __( 'Rooms saved.', 'church-booking' ), 'updated' );
        }
    }

    public function render_bookings_page() {
        $bookings = CB_Bookings::all( 200 );
        include CHURCH_BOOKING_PATH . 'templates/admin-bookings.php';
    }

    public function render_settings_page() {
        $settings = CB_Settings::get();
        include CHURCH_BOOKING_PATH . 'templates/admin-settings.php';
    }

    public function render_import_page() {
        $settings = CB_Settings::get();
        include CHURCH_BOOKING_PATH . 'templates/admin-import.php';
    }

    public function render_rooms_page() {
        $rooms       = CB_Rooms::all();
        $hire_rooms  = CB_Rooms::hire_rooms();
        include CHURCH_BOOKING_PATH . 'templates/admin-rooms.php';
    }

}
