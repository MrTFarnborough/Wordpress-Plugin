<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$days = array(
    0 => __( 'Sunday', 'church-booking' ),
    1 => __( 'Monday', 'church-booking' ),
    2 => __( 'Tuesday', 'church-booking' ),
    3 => __( 'Wednesday', 'church-booking' ),
    4 => __( 'Thursday', 'church-booking' ),
    5 => __( 'Friday', 'church-booking' ),
    6 => __( 'Saturday', 'church-booking' ),
);
?>
<div class="wrap cb-wrap">
    <h1><?php esc_html_e( 'Church Booking — Settings', 'church-booking' ); ?></h1>
    <?php settings_errors( 'church-booking' ); ?>

    <form method="post">
        <?php wp_nonce_field( 'cb_save_settings' ); ?>
        <input type="hidden" name="cb_action" value="save_settings" />

        <h2><?php esc_html_e( 'Opening hours', 'church-booking' ); ?></h2>
        <p class="description"><?php esc_html_e( 'Leave both fields blank for days the church is closed.', 'church-booking' ); ?></p>
        <table class="form-table" role="presentation">
            <?php foreach ( $days as $index => $label ) : ?>
                <tr>
                    <th scope="row"><?php echo esc_html( $label ); ?></th>
                    <td>
                        <input type="time" name="cb_settings[hours][<?php echo (int) $index; ?>][open]"
                               value="<?php echo esc_attr( $settings['hours'][ $index ]['open'] ?? '' ); ?>" />
                        &nbsp;&rarr;&nbsp;
                        <input type="time" name="cb_settings[hours][<?php echo (int) $index; ?>][close]"
                               value="<?php echo esc_attr( $settings['hours'][ $index ]['close'] ?? '' ); ?>" />
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <h2><?php esc_html_e( 'Slot configuration', 'church-booking' ); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th><label for="cb-slot-length"><?php esc_html_e( 'Slot length (minutes)', 'church-booking' ); ?></label></th>
                <td><input id="cb-slot-length" type="number" min="5" step="5" name="cb_settings[slot_length]" value="<?php echo esc_attr( $settings['slot_length'] ); ?>" /></td>
            </tr>
            <tr>
                <th><label for="cb-lead-time"><?php esc_html_e( 'Minimum lead time (hours)', 'church-booking' ); ?></label></th>
                <td><input id="cb-lead-time" type="number" min="0" name="cb_settings[lead_time_hours]" value="<?php echo esc_attr( $settings['lead_time_hours'] ); ?>" /></td>
            </tr>
            <tr>
                <th><label for="cb-advance"><?php esc_html_e( 'How many days ahead can visitors book?', 'church-booking' ); ?></label></th>
                <td><input id="cb-advance" type="number" min="1" name="cb_settings[advance_days]" value="<?php echo esc_attr( $settings['advance_days'] ); ?>" /></td>
            </tr>
            <tr>
                <th><label for="cb-require-approval"><?php esc_html_e( 'Require admin approval?', 'church-booking' ); ?></label></th>
                <td>
                    <label>
                        <input id="cb-require-approval" type="checkbox" name="cb_settings[require_approval]" value="1" <?php checked( 1, (int) $settings['require_approval'] ); ?> />
                        <?php esc_html_e( 'New bookings arrive as pending until confirmed.', 'church-booking' ); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="cb-notify"><?php esc_html_e( 'Notification email', 'church-booking' ); ?></label></th>
                <td><input id="cb-notify" type="email" class="regular-text" name="cb_settings[notification_email]" value="<?php echo esc_attr( $settings['notification_email'] ); ?>" /></td>
            </tr>
        </table>

        <p><button type="submit" class="button button-primary"><?php esc_html_e( 'Save changes', 'church-booking' ); ?></button></p>
    </form>

    <hr />
    <h2><?php esc_html_e( 'Shortcode', 'church-booking' ); ?></h2>
    <p><?php esc_html_e( 'Place the following shortcode on any page to display the booking form:', 'church-booking' ); ?></p>
    <p><code>[church_booking]</code></p>
</div>
