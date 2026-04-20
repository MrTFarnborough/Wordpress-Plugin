<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Derives available booking slots by subtracting existing bookings from the
 * church's operating hours.
 */
class CB_Slots {

    /**
     * Build available slots for a single calendar day.
     *
     * @param string $date Y-m-d.
     * @return array List of slots: [ ['start' => DateTimeImmutable, 'end' => DateTimeImmutable, 'label' => string], ... ]
     */
    public static function for_date( $date ) {
        $settings = CB_Settings::get();
        $tz       = wp_timezone();

        try {
            $day_start = new DateTimeImmutable( $date . ' 00:00:00', $tz );
        } catch ( Exception $e ) {
            return array();
        }

        $weekday = (int) $day_start->format( 'w' );
        $hours   = $settings['hours'][ (string) $weekday ] ?? array( 'open' => '', 'close' => '' );

        if ( empty( $hours['open'] ) || empty( $hours['close'] ) ) {
            return array();
        }

        $open  = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $date . ' ' . $hours['open'], $tz );
        $close = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $date . ' ' . $hours['close'], $tz );

        if ( ! $open || ! $close || $close <= $open ) {
            return array();
        }

        $slot_length = max( 5, (int) $settings['slot_length'] );
        $lead_cutoff = ( new DateTimeImmutable( 'now', $tz ) )->modify( '+' . max( 0, (int) $settings['lead_time_hours'] ) . ' hours' );

        $existing = CB_Bookings::in_range( $open, $close );
        $blocks   = array();
        foreach ( $existing as $row ) {
            $blocks[] = array(
                'start' => new DateTimeImmutable( $row->start_time, $tz ),
                'end'   => new DateTimeImmutable( $row->end_time, $tz ),
            );
        }

        $slots  = array();
        $cursor = $open;
        $step   = new DateInterval( 'PT' . $slot_length . 'M' );

        while ( true ) {
            $next = $cursor->add( $step );
            if ( $next > $close ) {
                break;
            }

            if ( $cursor < $lead_cutoff ) {
                $cursor = $next;
                continue;
            }

            if ( ! self::overlaps_any( $cursor, $next, $blocks ) ) {
                $slots[] = array(
                    'start' => $cursor,
                    'end'   => $next,
                    'label' => $cursor->format( get_option( 'time_format', 'g:i a' ) ),
                );
            }

            $cursor = $next;
        }

        return $slots;
    }

    /**
     * Return an ordered list of dates (Y-m-d) with at least one open slot,
     * looking ahead from today.
     */
    public static function upcoming_dates( $limit = null ) {
        $settings = CB_Settings::get();
        $limit    = null === $limit ? (int) $settings['advance_days'] : max( 1, (int) $limit );
        $tz       = wp_timezone();
        $today    = new DateTimeImmutable( 'today', $tz );

        $dates = array();
        for ( $i = 0; $i < $limit; $i++ ) {
            $day     = $today->modify( '+' . $i . ' day' );
            $weekday = (int) $day->format( 'w' );
            $hours   = $settings['hours'][ (string) $weekday ] ?? array( 'open' => '', 'close' => '' );
            if ( empty( $hours['open'] ) || empty( $hours['close'] ) ) {
                continue;
            }
            $dates[] = $day->format( 'Y-m-d' );
        }

        return $dates;
    }

    private static function overlaps_any( DateTimeInterface $start, DateTimeInterface $end, array $blocks ) {
        foreach ( $blocks as $block ) {
            if ( $start < $block['end'] && $end > $block['start'] ) {
                return true;
            }
        }
        return false;
    }
}
