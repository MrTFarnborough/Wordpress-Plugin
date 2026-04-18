=== Church Booking ===
Contributors: churchbooking
Tags: booking, calendar, church, appointments, ics
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Extracts existing church bookings (manually or from an ICS feed) and exposes
the remaining opening hours as bookable slots that visitors can reserve.

== Description ==

Church Booking turns your church's existing calendar into a self-service
booking page:

* Define opening hours for each day of the week.
* Import existing bookings from a church ICS / iCal feed or add them manually.
* The plugin subtracts those bookings from your opening hours and presents the
  **remaining** time as available slots.
* Visitors pick a date, choose an open slot, and submit a booking — either
  auto-confirmed or pending admin approval.

Drop the shortcode `[church_booking]` on any page to show the booking form.

== Installation ==

1. Upload the `church-booking` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Visit **Church Booking → Settings** to configure opening hours and slot length.
4. Add existing church bookings under **Church Booking → Bookings**, or pull
   them from an ICS feed under **Church Booking → Import**.
5. Add the `[church_booking]` shortcode to any page.

== Frequently Asked Questions ==

= Does it double-book? =

No. Every booking attempt re-checks for overlaps before being saved.

= Where do the "available" times come from? =

Opening hours (from Settings) minus all stored bookings — whether imported
from the church calendar or submitted by visitors.

== Changelog ==

= 1.0.0 =
* Initial release.
