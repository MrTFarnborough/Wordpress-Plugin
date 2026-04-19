<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$currency = function_exists( 'get_woocommerce_currency_symbol' )
    ? get_woocommerce_currency_symbol()
    : apply_filters( 'cb_currency_symbol', '' );
?>
<div class="wrap cb-wrap">
    <h1><?php esc_html_e( 'Church Booking — Rooms', 'church-booking' ); ?></h1>
    <?php settings_errors( 'church-booking' ); ?>

    <p class="description">
        <?php esc_html_e( 'Rooms are populated automatically when you import bookings from ChurchSuite. Mark each room as a hire room or an additional room, and link additional rooms to their main hire room.', 'church-booking' ); ?>
    </p>

    <?php if ( empty( $rooms ) ) : ?>
        <p><?php esc_html_e( 'No rooms yet. Run an import from the Import menu to pull rooms from ChurchSuite.', 'church-booking' ); ?></p>
    <?php else : ?>
        <form method="post">
            <?php wp_nonce_field( 'cb_save_rooms' ); ?>
            <input type="hidden" name="cb_action" value="save_rooms" />

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Room', 'church-booking' ); ?></th>
                        <th><?php esc_html_e( 'Type', 'church-booking' ); ?></th>
                        <th><?php esc_html_e( 'Main hire room (for additional rooms)', 'church-booking' ); ?></th>
                        <th><?php esc_html_e( 'Price per hour', 'church-booking' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $rooms as $room ) :
                    $is_additional = ( CB_Rooms::TYPE_ADDITIONAL === $room->room_type );
                ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html( $room->name ); ?></strong>
                            <?php if ( ! empty( $room->external_id ) ) : ?>
                                <br /><small class="description">ChurchSuite ID: <?php echo esc_html( $room->external_id ); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <select name="rooms[<?php echo (int) $room->id; ?>][room_type]" class="cb-room-type">
                                <option value="<?php echo esc_attr( CB_Rooms::TYPE_HIRE ); ?>" <?php selected( $room->room_type, CB_Rooms::TYPE_HIRE ); ?>>
                                    <?php esc_html_e( 'Hire room', 'church-booking' ); ?>
                                </option>
                                <option value="<?php echo esc_attr( CB_Rooms::TYPE_ADDITIONAL ); ?>" <?php selected( $room->room_type, CB_Rooms::TYPE_ADDITIONAL ); ?>>
                                    <?php esc_html_e( 'Additional room', 'church-booking' ); ?>
                                </option>
                            </select>
                        </td>
                        <td>
                            <select name="rooms[<?php echo (int) $room->id; ?>][parent_room_id]" <?php disabled( ! $is_additional ); ?>>
                                <option value="0"><?php esc_html_e( '— none —', 'church-booking' ); ?></option>
                                <?php foreach ( $hire_rooms as $hire ) :
                                    if ( (int) $hire->id === (int) $room->id ) {
                                        continue;
                                    }
                                ?>
                                    <option value="<?php echo (int) $hire->id; ?>" <?php selected( (int) $room->parent_room_id, (int) $hire->id ); ?>>
                                        <?php echo esc_html( $hire->name ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <span><?php echo esc_html( $currency ); ?></span>
                            <input type="number" step="0.01" min="0"
                                   name="rooms[<?php echo (int) $room->id; ?>][price_per_hour]"
                                   value="<?php echo esc_attr( number_format( (float) $room->price_per_hour, 2, '.', '' ) ); ?>"
                                   class="small-text" />
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <p><button type="submit" class="button button-primary"><?php esc_html_e( 'Save rooms', 'church-booking' ); ?></button></p>
        </form>

        <script>
        (function () {
            document.querySelectorAll('.cb-room-type').forEach(function (select) {
                var row = select.closest('tr');
                var parent = row.querySelector('select[name$="[parent_room_id]"]');
                var sync = function () {
                    parent.disabled = (select.value !== '<?php echo esc_js( CB_Rooms::TYPE_ADDITIONAL ); ?>');
                    if (parent.disabled) { parent.value = '0'; }
                };
                select.addEventListener('change', sync);
            });
        }());
        </script>
    <?php endif; ?>
</div>
