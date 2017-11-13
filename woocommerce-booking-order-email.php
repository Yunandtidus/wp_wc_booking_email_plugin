<?php
/**
 * Plugin Name: WooCommerce Pinpoint Booking Order Email
 * Description: A plugin to create different emails for different pinpoint booking calendar.
 * Author: Yunandtidus
 * Version: 1.0
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 *  Add the email to the list of emails WooCommerce should load
 *
 * @since 1.0
 * @param array $email_classes available email classes
 * @return array filtered available email classes
 */
function add_booking_order_woocommerce_email( $email_classes ) {

	require_once( 'includes/class-wc-booking-order-email.php' );

	$email_classes['WC_Booking_Order_Email'] = new WC_Booking_Order_Email();

	return $email_classes;

}
add_filter( 'woocommerce_email_classes', 'add_booking_order_woocommerce_email' );
