<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap cb-wrap">
    <h1><?php esc_html_e( 'Import from ChurchSuite', 'church-booking' ); ?></h1>
    <?php settings_errors( 'church-booking' ); ?>

    <p><?php esc_html_e( 'Paste the ChurchSuite bookings feed URL. The plugin prefers the ChurchSuite JSON feed (which lets it distinguish a booking\'s main hire room from additional rooms automatically), but it also accepts an ICS URL or a public page that embeds a ChurchSuite widget.', 'church-booking' ); ?></p>

    <form method="post">
        <?php wp_nonce_field( 'cb_import_feed' ); ?>
        <input type="hidden" name="cb_action" value="import_feed" />
        <table class="form-table" role="presentation">
            <tr>
                <th><label for="cb-feed"><?php esc_html_e( 'ChurchSuite feed URL', 'church-booking' ); ?></label></th>
                <td>
                    <input id="cb-feed" type="url" class="regular-text" name="feed_url"
                           value="<?php echo esc_attr( $settings['church_feed_url'] ); ?>"
                           placeholder="https://kingshope.church/bookings" required />
                    <p class="description">
                        <?php esc_html_e( 'Examples: https://kingshope.church/bookings, https://yourchurch.churchsuite.com/embed/bookings/json, or any ChurchSuite ICS URL.', 'church-booking' ); ?>
                    </p>
                </td>
            </tr>
        </table>
        <p><button type="submit" class="button button-primary"><?php esc_html_e( 'Import now', 'church-booking' ); ?></button></p>
    </form>
</div>
