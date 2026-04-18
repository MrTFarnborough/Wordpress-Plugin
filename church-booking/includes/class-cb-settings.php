<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CB_Settings {

    const OPTION = 'cb_settings';

    public static function defaults() {
        return array(
            'slot_length'        => 30,
            'lead_time_hours'    => 2,
            'advance_days'       => 30,
            'hours'              => array(
                '0' => array( 'open' => '', 'close' => '' ),
                '1' => array( 'open' => '09:00', 'close' => '17:00' ),
                '2' => array( 'open' => '09:00', 'close' => '17:00' ),
                '3' => array( 'open' => '09:00', 'close' => '17:00' ),
                '4' => array( 'open' => '09:00', 'close' => '17:00' ),
                '5' => array( 'open' => '09:00', 'close' => '17:00' ),
                '6' => array( 'open' => '', 'close' => '' ),
            ),
            'notification_email' => get_option( 'admin_email' ),
            'require_approval'   => 0,
            'church_feed_url'    => '',
        );
    }

    public static function get( $key = null, $fallback = null ) {
        $settings = get_option( self::OPTION, array() );
        $settings = wp_parse_args( $settings, self::defaults() );

        if ( null === $key ) {
            return $settings;
        }

        return isset( $settings[ $key ] ) ? $settings[ $key ] : $fallback;
    }

    public static function update( array $values ) {
        $current = self::get();
        $merged  = array_replace_recursive( $current, $values );
        update_option( self::OPTION, $merged );
    }

    public static function sanitize( array $input ) {
        $defaults = self::defaults();
        $clean    = array();

        $clean['slot_length']        = max( 5, (int) ( $input['slot_length'] ?? $defaults['slot_length'] ) );
        $clean['lead_time_hours']    = max( 0, (int) ( $input['lead_time_hours'] ?? $defaults['lead_time_hours'] ) );
        $clean['advance_days']       = max( 1, (int) ( $input['advance_days'] ?? $defaults['advance_days'] ) );
        $clean['notification_email'] = sanitize_email( $input['notification_email'] ?? $defaults['notification_email'] );
        $clean['require_approval']   = ! empty( $input['require_approval'] ) ? 1 : 0;
        $clean['church_feed_url']    = esc_url_raw( $input['church_feed_url'] ?? '' );

        $hours = array();
        for ( $day = 0; $day <= 6; $day++ ) {
            $open  = $input['hours'][ $day ]['open'] ?? '';
            $close = $input['hours'][ $day ]['close'] ?? '';
            $hours[ (string) $day ] = array(
                'open'  => self::sanitize_time( $open ),
                'close' => self::sanitize_time( $close ),
            );
        }
        $clean['hours'] = $hours;

        return $clean;
    }

    public static function sanitize_time( $value ) {
        $value = trim( (string) $value );
        if ( '' === $value ) {
            return '';
        }
        if ( preg_match( '/^([01]?\d|2[0-3]):([0-5]\d)$/', $value, $m ) ) {
            return sprintf( '%02d:%02d', (int) $m[1], (int) $m[2] );
        }
        return '';
    }
}
