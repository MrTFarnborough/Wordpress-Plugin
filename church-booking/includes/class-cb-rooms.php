<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Repository for rooms extracted from ChurchSuite.
 *
 * Each row represents either a hire room (rentable primary space) or an
 * additional room attached to a hire room.
 */
class CB_Rooms {

    const TYPE_HIRE       = 'hire';
    const TYPE_ADDITIONAL = 'additional';

    /**
     * Create the room if it doesn't exist (by external id, then by name) and
     * return its row id.
     */
    public static function upsert( $name, $external_id = '' ) {
        global $wpdb;
        $name = trim( (string) $name );
        if ( '' === $name ) {
            return 0;
        }

        $table       = CB_Install::rooms_table();
        $external_id = sanitize_text_field( (string) $external_id );

        if ( '' !== $external_id ) {
            $existing = $wpdb->get_row(
                $wpdb->prepare( "SELECT id, name FROM {$table} WHERE external_id = %s", $external_id )
            );
            if ( $existing ) {
                if ( $existing->name !== $name ) {
                    $wpdb->update( $table, array( 'name' => $name ), array( 'id' => $existing->id ), array( '%s' ), array( '%d' ) );
                }
                return (int) $existing->id;
            }
        }

        $existing = $wpdb->get_row(
            $wpdb->prepare( "SELECT id, external_id FROM {$table} WHERE name = %s", $name )
        );
        if ( $existing ) {
            if ( '' !== $external_id && empty( $existing->external_id ) ) {
                $wpdb->update( $table, array( 'external_id' => $external_id ), array( 'id' => $existing->id ), array( '%s' ), array( '%d' ) );
            }
            return (int) $existing->id;
        }

        $wpdb->insert(
            $table,
            array(
                'external_id'    => $external_id,
                'name'           => $name,
                'room_type'      => self::TYPE_HIRE,
                'parent_room_id' => null,
                'price_per_hour' => 0,
                'created_at'     => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s', '%d', '%f', '%s' )
        );

        return (int) $wpdb->insert_id;
    }

    public static function all() {
        global $wpdb;
        $table = CB_Install::rooms_table();
        return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY room_type ASC, name ASC" );
    }

    public static function hire_rooms() {
        global $wpdb;
        $table = CB_Install::rooms_table();
        return $wpdb->get_results(
            $wpdb->prepare( "SELECT id, name FROM {$table} WHERE room_type = %s ORDER BY name ASC", self::TYPE_HIRE )
        );
    }

    public static function get( $id ) {
        global $wpdb;
        $table = CB_Install::rooms_table();
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $id ) );
    }

    /**
     * Update type, parent, and hourly price. Parent is only stored when
     * type is "additional"; otherwise it's cleared.
     */
    public static function update( $id, $room_type, $parent_room_id, $price_per_hour ) {
        global $wpdb;
        $id        = (int) $id;
        $room_type = in_array( $room_type, array( self::TYPE_HIRE, self::TYPE_ADDITIONAL ), true ) ? $room_type : self::TYPE_HIRE;
        $parent    = ( self::TYPE_ADDITIONAL === $room_type && (int) $parent_room_id > 0 ) ? (int) $parent_room_id : null;
        $price     = max( 0, (float) $price_per_hour );

        if ( $parent && $parent === $id ) {
            $parent = null;
        }

        return $wpdb->update(
            CB_Install::rooms_table(),
            array(
                'room_type'      => $room_type,
                'parent_room_id' => $parent,
                'price_per_hour' => $price,
            ),
            array( 'id' => $id ),
            array( '%s', '%d', '%f' ),
            array( '%d' )
        );
    }
}
