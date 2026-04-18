<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap cb-wrap">
    <h1><?php esc_html_e( 'Import church bookings', 'church-booking' ); ?></h1>
    <?php settings_errors( 'church-booking' ); ?>

    <p><?php esc_html_e( 'Paste the URL of the church calendar feed (ICS/iCal). Events in the feed will be stored as blocked times so visitors cannot book during them.', 'church-booking' ); ?></p>

    <form method="post">
        <?php wp_nonce_field( 'cb_import_feed' ); ?>
        <input type="hidden" name="cb_action" value="import_feed" />
        <table class="form-table" role="presentation">
            <tr>
                <th><label for="cb-feed"><?php esc_html_e( 'ICS feed URL', 'church-booking' ); ?></label></th>
                <td>
                    <input id="cb-feed" type="url" class="regular-text" name="feed_url"
                           value="<?php echo esc_attr( $settings['church_feed_url'] ); ?>"
                           placeholder="https://example.com/church.ics" required />
                </td>
            </tr>
        </table>
        <p><button type="submit" class="button button-primary"><?php esc_html_e( 'Import now', 'church-booking' ); ?></button></p>
    </form>
</div>
