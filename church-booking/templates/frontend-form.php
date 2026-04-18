<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="cb-booking" data-cb-root>
    <div class="cb-step cb-step--date">
        <h3><?php esc_html_e( 'Pick a date', 'church-booking' ); ?></h3>
        <?php if ( empty( $dates ) ) : ?>
            <p><?php esc_html_e( 'There are no available dates at the moment. Please check back later.', 'church-booking' ); ?></p>
        <?php else : ?>
            <div class="cb-date-list" role="listbox">
                <?php foreach ( $dates as $date ) :
                    $ts = strtotime( $date );
                ?>
                    <button type="button" class="cb-date" data-cb-date="<?php echo esc_attr( $date ); ?>">
                        <span class="cb-date__weekday"><?php echo esc_html( wp_date( 'D', $ts ) ); ?></span>
                        <span class="cb-date__day"><?php echo esc_html( wp_date( 'j', $ts ) ); ?></span>
                        <span class="cb-date__month"><?php echo esc_html( wp_date( 'M', $ts ) ); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="cb-step cb-step--slots" hidden>
        <h3><?php esc_html_e( 'Available times', 'church-booking' ); ?></h3>
        <p class="cb-slots__status" data-cb-slots-status></p>
        <div class="cb-slot-list" data-cb-slot-list></div>
    </div>

    <form class="cb-step cb-step--form" data-cb-form hidden>
        <h3><?php esc_html_e( 'Your details', 'church-booking' ); ?></h3>
        <p class="cb-chosen" data-cb-chosen></p>

        <label>
            <span><?php esc_html_e( 'Name', 'church-booking' ); ?></span>
            <input type="text" name="name" required />
        </label>
        <label>
            <span><?php esc_html_e( 'Email', 'church-booking' ); ?></span>
            <input type="email" name="email" required />
        </label>
        <label>
            <span><?php esc_html_e( 'Phone', 'church-booking' ); ?></span>
            <input type="tel" name="phone" />
        </label>
        <label>
            <span><?php esc_html_e( 'Notes (optional)', 'church-booking' ); ?></span>
            <textarea name="notes" rows="3"></textarea>
        </label>

        <input type="hidden" name="start" data-cb-start />
        <input type="hidden" name="end" data-cb-end />

        <p class="cb-actions">
            <button type="button" class="cb-back" data-cb-back><?php esc_html_e( 'Back', 'church-booking' ); ?></button>
            <button type="submit" class="cb-submit"><?php esc_html_e( 'Confirm booking', 'church-booking' ); ?></button>
        </p>

        <p class="cb-message" data-cb-message role="status"></p>
    </form>
</div>
