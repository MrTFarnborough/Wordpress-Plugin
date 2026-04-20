<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ChurchSuite importer.
 *
 * Fetches a bookings feed URL and converts each entry into a stored booking
 * plus the rooms it uses. Supports:
 *
 *   - ChurchSuite JSON (the shape exposed by /embed/bookings/json) — the
 *     native format. Each event has a primary `location` (hire room) and
 *     optional `additional_locations` (additional rooms attached to it).
 *   - ICS feeds — LOCATION becomes the hire room; additional rooms can't
 *     be expressed in ICS so they must be set manually.
 *   - A public HTML page that embeds a ChurchSuite widget — the client
 *     scrapes the embedded JSON URL and follows it.
 *
 * The class keeps all ChurchSuite-specific logic in one place so the admin
 * layer just calls `import( $url )`.
 */
class CB_ChurchSuite {

    /**
     * @param string $url
     * @return array|WP_Error {bookings:int, rooms:int, additional_rooms:int}
     */
    public static function import( $url ) {
        $url = esc_url_raw( (string) $url );
        if ( empty( $url ) ) {
            return new WP_Error( 'no_url', __( 'Please provide a ChurchSuite feed URL.', 'church-booking' ) );
        }

        $body_info = self::fetch( $url );
        if ( is_wp_error( $body_info ) ) {
            return $body_info;
        }

        list( $body, $content_type ) = $body_info;
        $format = self::detect_format( $body, $content_type );

        if ( 'html' === $format ) {
            $embedded = self::find_embedded_feed( $body );
            if ( ! $embedded ) {
                return new WP_Error( 'no_feed', __( 'The URL returned HTML but no ChurchSuite JSON/ICS feed was found inside it. Please provide the direct feed URL.', 'church-booking' ) );
            }
            $body_info = self::fetch( $embedded );
            if ( is_wp_error( $body_info ) ) {
                return $body_info;
            }
            list( $body, $content_type ) = $body_info;
            $format = self::detect_format( $body, $content_type );
        }

        if ( 'json' === $format ) {
            return self::import_json( $body );
        }

        if ( 'ics' === $format ) {
            return self::import_ics( $body );
        }

        return new WP_Error( 'unknown_format', __( 'Could not recognise the feed format (expected ChurchSuite JSON or ICS).', 'church-booking' ) );
    }

    private static function fetch( $url ) {
        $response = wp_safe_remote_get( $url, array(
            'timeout' => 20,
            'headers' => array( 'Accept' => 'application/json, text/calendar;q=0.9, */*;q=0.1' ),
        ) );
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        $code = (int) wp_remote_retrieve_response_code( $response );
        if ( 200 !== $code ) {
            return new WP_Error( 'http_error', sprintf( __( 'Feed responded with HTTP %d.', 'church-booking' ), $code ) );
        }
        return array(
            (string) wp_remote_retrieve_body( $response ),
            (string) wp_remote_retrieve_header( $response, 'content-type' ),
        );
    }

    private static function detect_format( $body, $content_type ) {
        $content_type = strtolower( (string) $content_type );
        if ( false !== strpos( $content_type, 'application/json' ) ) {
            return 'json';
        }
        if ( false !== strpos( $content_type, 'text/calendar' ) ) {
            return 'ics';
        }
        $trimmed = ltrim( (string) $body );
        if ( '' === $trimmed ) {
            return 'unknown';
        }
        $first = $trimmed[0];
        if ( '{' === $first || '[' === $first ) {
            return 'json';
        }
        if ( 0 === strncmp( $trimmed, 'BEGIN:VCALENDAR', 15 ) ) {
            return 'ics';
        }
        if ( false !== strpos( $content_type, 'text/html' ) || '<' === $first ) {
            return 'html';
        }
        return 'unknown';
    }

    /**
     * Look for a ChurchSuite embed JSON URL inside an HTML page.
     */
    private static function find_embedded_feed( $html ) {
        if ( preg_match( '#https?://[a-z0-9\-]+\.churchsuite\.(?:com|co\.uk)/embed/bookings/json[^"\'\s<>]*#i', $html, $m ) ) {
            return html_entity_decode( $m[0], ENT_QUOTES | ENT_HTML5 );
        }
        if ( preg_match( '#https?://[a-z0-9\-]+\.churchsuite\.(?:com|co\.uk)/embed/calendar/[^"\'\s<>]*\.ics[^"\'\s<>]*#i', $html, $m ) ) {
            return html_entity_decode( $m[0], ENT_QUOTES | ENT_HTML5 );
        }
        return '';
    }

    // -------------------------------------------------------------------
    // JSON importer
    // -------------------------------------------------------------------

    private static function import_json( $body ) {
        $decoded = json_decode( $body, true );
        if ( null === $decoded && JSON_ERROR_NONE !== json_last_error() ) {
            return new WP_Error( 'bad_json', __( 'Could not parse JSON feed.', 'church-booking' ) );
        }

        $events          = self::flatten_json_events( $decoded );
        $booking_count   = 0;
        $hire_ids        = array();
        $additional_ids  = array();

        foreach ( $events as $event ) {
            $norm = self::normalize_event( $event );
            if ( ! $norm ) {
                continue;
            }

            $hire_room_id = 0;
            if ( ! empty( $norm['location_name'] ) ) {
                $hire_room_id = CB_Rooms::upsert(
                    $norm['location_name'],
                    $norm['location_ext_id'],
                    CB_Rooms::TYPE_HIRE
                );
                if ( $hire_room_id ) {
                    $hire_ids[ $hire_room_id ] = true;
                }
            }

            foreach ( $norm['additional'] as $extra ) {
                $extra_id = CB_Rooms::upsert(
                    $extra['name'],
                    $extra['ext_id'],
                    CB_Rooms::TYPE_ADDITIONAL,
                    $hire_room_id
                );
                if ( $extra_id ) {
                    $additional_ids[ $extra_id ] = true;
                }
            }

            $result = CB_Bookings::insert( array(
                'start_time' => $norm['start'],
                'end_time'   => $norm['end'],
                'title'      => $norm['title'],
                'room_id'    => $hire_room_id,
                'source'     => 'church',
                'status'     => 'confirmed',
            ) );
            if ( ! is_wp_error( $result ) ) {
                $booking_count++;
            }
        }

        return array(
            'bookings'         => $booking_count,
            'rooms'            => count( $hire_ids ),
            'additional_rooms' => count( $additional_ids ),
        );
    }

    /**
     * Accepts any of: a list of events, {events:[]}, {bookings:[]},
     * {dates:{"YYYY-MM-DD":[...]}}, or a {"YYYY-MM-DD":[...]} object.
     */
    private static function flatten_json_events( $decoded ) {
        if ( ! is_array( $decoded ) ) {
            return array();
        }
        if ( self::is_event_like( $decoded ) ) {
            return array( $decoded );
        }
        if ( self::is_list( $decoded ) ) {
            return array_filter( $decoded, array( __CLASS__, 'is_event_like' ) );
        }
        foreach ( array( 'events', 'bookings', 'data', 'items' ) as $key ) {
            if ( isset( $decoded[ $key ] ) && is_array( $decoded[ $key ] ) ) {
                return self::flatten_json_events( $decoded[ $key ] );
            }
        }
        if ( isset( $decoded['dates'] ) && is_array( $decoded['dates'] ) ) {
            $decoded = $decoded['dates'];
        }
        $out = array();
        foreach ( $decoded as $value ) {
            if ( is_array( $value ) ) {
                $out = array_merge( $out, self::flatten_json_events( $value ) );
            }
        }
        return $out;
    }

    private static function is_list( $arr ) {
        if ( ! is_array( $arr ) ) {
            return false;
        }
        return array_keys( $arr ) === range( 0, count( $arr ) - 1 );
    }

    private static function is_event_like( $value ) {
        if ( ! is_array( $value ) ) {
            return false;
        }
        $has_start = isset( $value['starts_at'] ) || isset( $value['start'] ) || isset( $value['dtstart'] ) || isset( $value['start_datetime'] );
        $has_end   = isset( $value['ends_at'] ) || isset( $value['end'] ) || isset( $value['dtend'] ) || isset( $value['end_datetime'] );
        return $has_start && $has_end;
    }

    private static function normalize_event( array $event ) {
        $start = self::pick( $event, array( 'starts_at', 'start_datetime', 'start', 'dtstart' ) );
        $end   = self::pick( $event, array( 'ends_at', 'end_datetime', 'end', 'dtend' ) );
        $start = $start ? strtotime( $start ) : false;
        $end   = $end ? strtotime( $end ) : false;
        if ( ! $start || ! $end || $end <= $start ) {
            return null;
        }

        $title = (string) self::pick( $event, array( 'name', 'title', 'summary', 'event_name' ), '' );

        $location_name   = '';
        $location_ext_id = '';
        if ( isset( $event['location'] ) ) {
            if ( is_array( $event['location'] ) ) {
                $location_name   = (string) ( $event['location']['name'] ?? '' );
                $location_ext_id = (string) ( $event['location']['id'] ?? '' );
            } else {
                $location_name = (string) $event['location'];
            }
        } elseif ( isset( $event['site'] ) && is_array( $event['site'] ) ) {
            $location_name   = (string) ( $event['site']['name'] ?? '' );
            $location_ext_id = (string) ( $event['site']['id'] ?? '' );
        }

        $additional = array();
        $additional_source = $event['additional_locations'] ?? ( $event['additional_location'] ?? array() );
        if ( is_array( $additional_source ) ) {
            foreach ( $additional_source as $item ) {
                if ( is_array( $item ) ) {
                    $name = (string) ( $item['name'] ?? '' );
                    $eid  = (string) ( $item['id'] ?? '' );
                } else {
                    $name = (string) $item;
                    $eid  = '';
                }
                $name = trim( $name );
                if ( '' !== $name ) {
                    $additional[] = array( 'name' => $name, 'ext_id' => $eid );
                }
            }
        }

        return array(
            'title'           => $title,
            'start'           => gmdate( 'Y-m-d H:i:s', $start ),
            'end'             => gmdate( 'Y-m-d H:i:s', $end ),
            'location_name'   => trim( $location_name ),
            'location_ext_id' => $location_ext_id,
            'additional'      => $additional,
        );
    }

    private static function pick( array $arr, array $keys, $default = '' ) {
        foreach ( $keys as $k ) {
            if ( isset( $arr[ $k ] ) && '' !== $arr[ $k ] ) {
                return $arr[ $k ];
            }
        }
        return $default;
    }

    // -------------------------------------------------------------------
    // ICS fallback
    // -------------------------------------------------------------------

    private static function import_ics( $body ) {
        $events         = self::parse_ics( $body );
        $booking_count  = 0;
        $hire_ids       = array();

        foreach ( $events as $event ) {
            $room_id = 0;
            if ( ! empty( $event['location'] ) ) {
                $room_id = CB_Rooms::upsert( $event['location'], '', CB_Rooms::TYPE_HIRE );
                if ( $room_id ) {
                    $hire_ids[ $room_id ] = true;
                }
            }

            $result = CB_Bookings::insert( array(
                'start_time' => $event['start'],
                'end_time'   => $event['end'],
                'title'      => $event['summary'],
                'room_id'    => $room_id,
                'source'     => 'church',
                'status'     => 'confirmed',
            ) );
            if ( ! is_wp_error( $result ) ) {
                $booking_count++;
            }
        }

        return array(
            'bookings'         => $booking_count,
            'rooms'            => count( $hire_ids ),
            'additional_rooms' => 0,
        );
    }

    private static function parse_ics( $body ) {
        $lines   = preg_split( "/\r?\n/", (string) $body );
        $events  = array();
        $current = null;

        foreach ( $lines as $line ) {
            $line = trim( $line );
            if ( 'BEGIN:VEVENT' === $line ) {
                $current = array( 'start' => '', 'end' => '', 'summary' => '', 'location' => '' );
                continue;
            }
            if ( 'END:VEVENT' === $line ) {
                if ( $current && $current['start'] && $current['end'] ) {
                    $events[] = $current;
                }
                $current = null;
                continue;
            }
            if ( null === $current ) {
                continue;
            }
            if ( 0 === strpos( $line, 'DTSTART' ) ) {
                $current['start'] = self::ics_datetime( $line );
            } elseif ( 0 === strpos( $line, 'DTEND' ) ) {
                $current['end'] = self::ics_datetime( $line );
            } elseif ( 0 === strpos( $line, 'SUMMARY' ) ) {
                $current['summary'] = self::ics_unescape( substr( $line, strpos( $line, ':' ) + 1 ) );
            } elseif ( 0 === strpos( $line, 'LOCATION' ) ) {
                $current['location'] = self::ics_unescape( substr( $line, strpos( $line, ':' ) + 1 ) );
            }
        }
        return $events;
    }

    private static function ics_datetime( $line ) {
        $value = substr( $line, strpos( $line, ':' ) + 1 );
        $ts    = strtotime( $value );
        return $ts ? gmdate( 'Y-m-d H:i:s', $ts ) : '';
    }

    private static function ics_unescape( $value ) {
        return str_replace( array( '\\,', '\\;', '\\n', '\\N' ), array( ',', ';', "\n", "\n" ), (string) $value );
    }
}
