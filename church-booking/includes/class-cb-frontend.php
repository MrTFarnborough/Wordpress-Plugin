<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CB_Frontend {

    const SHORTCODE = 'church_booking';

    public function __construct() {
        add_shortcode( self::SHORTCODE, array( $this, 'render_shortcode' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_ajax_cb_get_slots', array( $this, 'ajax_get_slots' ) );
        add_action( 'wp_ajax_nopriv_cb_get_slots', array( $this, 'ajax_get_slots' ) );
        add_action( 'wp_ajax_cb_book_slot', array( $this, 'ajax_book_slot' ) );
        add_action( 'wp_ajax_nopriv_cb_book_slot', array( $this, 'ajax_book_slot' ) );
    }

    public function enqueue_assets() {
        wp_register_style(
            'church-booking',
            CHURCH_BOOKING_URL . 'assets/css/frontend.css',
            array(),
            CHURCH_BOOKING_VERSION
        );

        wp_register_script(
            'church-booking',
            CHURCH_BOOKING_URL . 'assets/js/frontend.js',
            array(),
            CHURCH_BOOKING_VERSION,
            true
        );

        wp_localize_script( 'church-booking', 'CB_DATA', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'cb_public' ),
            'i18n'    => array(
                'loading'    => __( 'Loading available times…', 'church-booking' ),
                'noSlots'    => __( 'No slots available on this date.', 'church-booking' ),
                'chooseDate' => __( 'Select a date to see available times.', 'church-booking' ),
                'booking'    => __( 'Submitting your booking…', 'church-booking' ),
                'genericErr' => __( 'Something went wrong. Please try again.', 'church-booking' ),
            ),
        ) );
    }

    public function render_shortcode( $atts = array() ) {
        wp_enqueue_style( 'church-booking' );
        wp_enqueue_script( 'church-booking' );

        $dates = CB_Slots::upcoming_dates();

        ob_start();
        include CHURCH_BOOKING_PATH . 'templates/frontend-form.php';
        return ob_get_clean();
    }

    public function ajax_get_slots() {
        check_ajax_referer( 'cb_public', 'nonce' );

        $date = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';
        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid date.', 'church-booking' ) ), 400 );
        }

        $slots  = CB_Slots::for_date( $date );
        $format = get_option( 'time_format', 'g:i a' );
        $data   = array();
        foreach ( $slots as $slot ) {
            $data[] = array(
                'start' => $slot['start']->format( 'Y-m-d H:i:s' ),
                'end'   => $slot['end']->format( 'Y-m-d H:i:s' ),
                'label' => wp_date( $format, $slot['start']->getTimestamp() ),
            );
        }

        wp_send_json_success( array( 'slots' => $data ) );
    }

    public function ajax_book_slot() {
        check_ajax_referer( 'cb_public', 'nonce' );

        $start = isset( $_POST['start'] ) ? sanitize_text_field( wp_unslash( $_POST['start'] ) ) : '';
        $end   = isset( $_POST['end'] ) ? sanitize_text_field( wp_unslash( $_POST['end'] ) ) : '';
        $name  = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
        $notes = isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '';

        if ( empty( $name ) || ! is_email( $email ) ) {
            wp_send_json_error( array( 'message' => __( 'Please provide your name and a valid email.', 'church-booking' ) ), 400 );
        }

        try {
            $tz         = wp_timezone();
            $start_dt   = new DateTimeImmutable( $start, $tz );
            $end_dt     = new DateTimeImmutable( $end, $tz );
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'message' => __( 'Invalid time range.', 'church-booking' ) ), 400 );
        }

        if ( $end_dt <= $start_dt ) {
            wp_send_json_error( array( 'message' => __( 'Invalid time range.', 'church-booking' ) ), 400 );
        }

        if ( CB_Bookings::overlaps( $start_dt, $end_dt ) ) {
            wp_send_json_error( array( 'message' => __( 'That slot has just been taken. Please pick another.', 'church-booking' ) ), 409 );
        }

        $require_approval = (int) CB_Settings::get( 'require_approval' );
        $status           = $require_approval ? 'pending' : 'confirmed';

        $id = CB_Bookings::insert( array(
            'start_time'     => $start_dt->format( 'Y-m-d H:i:s' ),
            'end_time'       => $end_dt->format( 'Y-m-d H:i:s' ),
            'title'          => sprintf( /* translators: %s: customer name */ __( 'Booking — %s', 'church-booking' ), $name ),
            'source'         => 'user',
            'status'         => $status,
            'customer_name'  => $name,
            'customer_email' => $email,
            'customer_phone' => $phone,
            'notes'          => $notes,
        ) );

        if ( is_wp_error( $id ) ) {
            wp_send_json_error( array( 'message' => $id->get_error_message() ), 500 );
        }

        $this->send_notification( $id, $status );

        $message = $require_approval
            ? __( 'Thanks! Your booking request has been received and is awaiting confirmation.', 'church-booking' )
            : __( 'Thanks! Your booking is confirmed.', 'church-booking' );

        wp_send_json_success( array( 'message' => $message, 'id' => $id ) );
    }

    private function send_notification( $booking_id, $status ) {
        $booking = CB_Bookings::get( $booking_id );
        if ( ! $booking ) {
            return;
        }

        $to = CB_Settings::get( 'notification_email' );
        if ( empty( $to ) ) {
            return;
        }

        $subject = sprintf(
            /* translators: 1: status, 2: site name */
            __( '[%2$s] New %1$s booking', 'church-booking' ),
            $status,
            wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
        );

        $body = sprintf(
            "%s\n\n%s: %s\n%s: %s\n%s: %s\n%s: %s\n%s:\n%s\n",
            __( 'A new booking has been submitted.', 'church-booking' ),
            __( 'When', 'church-booking' ), $booking->start_time . ' — ' . $booking->end_time,
            __( 'Name', 'church-booking' ), $booking->customer_name,
            __( 'Email', 'church-booking' ), $booking->customer_email,
            __( 'Phone', 'church-booking' ), $booking->customer_phone,
            __( 'Notes', 'church-booking' ), $booking->notes
        );

        wp_mail( $to, $subject, $body );
    }
}
