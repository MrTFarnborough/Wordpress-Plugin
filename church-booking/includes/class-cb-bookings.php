<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Repository for stored bookings (church-imported and user-submitted).
 */
class CB_Bookings {

    /**
     * Fetch all confirmed bookings that overlap a date range.
     *
     * @param DateTimeInterface $from Inclusive start.
     * @param DateTimeInterface $to   Exclusive end.
     */
    public static function in_range( DateTimeInterface $from, DateTimeInterface $to ) {
        global $wpdb;
        $table = CB_Install::table();

        $sql = $wpdb->prepare(
            "SELECT id, start_time, end_time, source, status, title FROM {$table}
             WHERE status IN ('confirmed','pending')
               AND end_time > %s
               AND start_time < %s
             ORDER BY start_time ASC",
            $from->format( 'Y-m-d H:i:s' ),
            $to->format( 'Y-m-d H:i:s' )
        );

        return $wpdb->get_results( $sql );
    }

    public static function all( $limit = 100, $offset = 0 ) {
        global $wpdb;
        $table = CB_Install::table();
        $limit  = max( 1, (int) $limit );
        $offset = max( 0, (int) $offset );

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} ORDER BY start_time DESC LIMIT %d OFFSET %d",
                $limit,
                $offset
            )
        );
    }

    public static function count_all() {
        global $wpdb;
        $table = CB_Install::table();
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
    }

    public static function get( $id ) {
        global $wpdb;
        $table = CB_Install::table();
        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $id )
        );
    }

    public static function insert( array $data ) {
        global $wpdb;
        $table = CB_Install::table();

        $row = self::normalize( $data );
        if ( is_wp_error( $row ) ) {
            return $row;
        }

        $row['created_at'] = current_time( 'mysql' );

        $result = $wpdb->insert( $table, $row );
        if ( false === $result ) {
            return new WP_Error( 'db_error', __( 'Could not save booking.', 'church-booking' ) );
        }
        return (int) $wpdb->insert_id;
    }

    public static function delete( $id ) {
        global $wpdb;
        return $wpdb->delete( CB_Install::table(), array( 'id' => (int) $id ), array( '%d' ) );
    }

    public static function update_status( $id, $status ) {
        global $wpdb;
        $status = in_array( $status, array( 'pending', 'confirmed', 'cancelled' ), true ) ? $status : 'pending';
        return $wpdb->update(
            CB_Install::table(),
            array( 'status' => $status ),
            array( 'id' => (int) $id ),
            array( '%s' ),
            array( '%d' )
        );
    }

    /**
     * Check whether a candidate slot overlaps any stored booking.
     */
    public static function overlaps( DateTimeInterface $start, DateTimeInterface $end ) {
        global $wpdb;
        $table = CB_Install::table();
        $sql   = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table}
             WHERE status IN ('confirmed','pending')
               AND start_time < %s
               AND end_time > %s",
            $end->format( 'Y-m-d H:i:s' ),
            $start->format( 'Y-m-d H:i:s' )
        );
        return (int) $wpdb->get_var( $sql ) > 0;
    }

    private static function normalize( array $data ) {
        $start = isset( $data['start_time'] ) ? strtotime( $data['start_time'] ) : false;
        $end   = isset( $data['end_time'] ) ? strtotime( $data['end_time'] ) : false;

        if ( ! $start || ! $end || $end <= $start ) {
            return new WP_Error( 'invalid_range', __( 'Start time must be before end time.', 'church-booking' ) );
        }

        $source = isset( $data['source'] ) && in_array( $data['source'], array( 'church', 'user', 'import' ), true )
            ? $data['source']
            : 'church';

        $status = isset( $data['status'] ) && in_array( $data['status'], array( 'pending', 'confirmed', 'cancelled' ), true )
            ? $data['status']
            : 'confirmed';

        $room_id = isset( $data['room_id'] ) ? (int) $data['room_id'] : 0;

        return array(
            'room_id'        => $room_id > 0 ? $room_id : null,
            'start_time'     => gmdate( 'Y-m-d H:i:s', $start ),
            'end_time'       => gmdate( 'Y-m-d H:i:s', $end ),
            'source'         => $source,
            'status'         => $status,
            'title'          => sanitize_text_field( $data['title'] ?? '' ),
            'customer_name'  => sanitize_text_field( $data['customer_name'] ?? '' ),
            'customer_email' => sanitize_email( $data['customer_email'] ?? '' ),
            'customer_phone' => sanitize_text_field( $data['customer_phone'] ?? '' ),
            'notes'          => sanitize_textarea_field( $data['notes'] ?? '' ),
        );
    }
}
