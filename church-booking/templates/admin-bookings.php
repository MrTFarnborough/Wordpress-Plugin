<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap cb-wrap">
    <h1><?php esc_html_e( 'Church Booking — Bookings', 'church-booking' ); ?></h1>

    <?php settings_errors( 'church-booking' ); ?>

    <h2><?php esc_html_e( 'Add an existing church booking', 'church-booking' ); ?></h2>
    <p class="description">
        <?php esc_html_e( 'Block out time already reserved by the church. These periods will be subtracted from the available slots shown to visitors.', 'church-booking' ); ?>
    </p>
    <form method="post" class="cb-add-form">
        <?php wp_nonce_field( 'cb_add_booking' ); ?>
        <input type="hidden" name="cb_action" value="add_booking" />
        <table class="form-table" role="presentation">
            <tr>
                <th><label for="cb-title"><?php esc_html_e( 'Title', 'church-booking' ); ?></label></th>
                <td><input id="cb-title" name="title" type="text" class="regular-text" required /></td>
            </tr>
            <tr>
                <th><label for="cb-start"><?php esc_html_e( 'Start', 'church-booking' ); ?></label></th>
                <td><input id="cb-start" name="start_time" type="datetime-local" required /></td>
            </tr>
            <tr>
                <th><label for="cb-end"><?php esc_html_e( 'End', 'church-booking' ); ?></label></th>
                <td><input id="cb-end" name="end_time" type="datetime-local" required /></td>
            </tr>
        </table>
        <p><button class="button button-primary" type="submit"><?php esc_html_e( 'Add booking', 'church-booking' ); ?></button></p>
    </form>

    <h2><?php esc_html_e( 'Current bookings', 'church-booking' ); ?></h2>
    <table class="widefat striped">
        <thead>
            <tr>
                <th><?php esc_html_e( 'When', 'church-booking' ); ?></th>
                <th><?php esc_html_e( 'Title', 'church-booking' ); ?></th>
                <th><?php esc_html_e( 'Source', 'church-booking' ); ?></th>
                <th><?php esc_html_e( 'Status', 'church-booking' ); ?></th>
                <th><?php esc_html_e( 'Customer', 'church-booking' ); ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if ( empty( $bookings ) ) : ?>
            <tr><td colspan="6"><?php esc_html_e( 'No bookings yet.', 'church-booking' ); ?></td></tr>
        <?php else : foreach ( $bookings as $b ) :
            $delete_url = wp_nonce_url(
                admin_url( 'admin.php?page=' . CB_Admin::MENU_SLUG . '&cb_action=delete_booking&booking=' . (int) $b->id ),
                'cb_delete_booking_' . (int) $b->id
            );
        ?>
            <tr>
                <td>
                    <?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $b->start_time ) ); ?>
                    &nbsp;&rarr;&nbsp;
                    <?php echo esc_html( mysql2date( get_option( 'time_format' ), $b->end_time ) ); ?>
                </td>
                <td><?php echo esc_html( $b->title ); ?></td>
                <td><?php echo esc_html( $b->source ); ?></td>
                <td><?php echo esc_html( $b->status ); ?></td>
                <td>
                    <?php echo esc_html( $b->customer_name ); ?>
                    <?php if ( $b->customer_email ) : ?>
                        <br /><small><?php echo esc_html( $b->customer_email ); ?></small>
                    <?php endif; ?>
                </td>
                <td>
                    <a class="button button-small" href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this booking?', 'church-booking' ) ); ?>');">
                        <?php esc_html_e( 'Delete', 'church-booking' ); ?>
                    </a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
