<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Define WC_PLUGIN_FILE.
if ( ! defined( 'CUSTOM_EMAIL_PLUGIN_FILE' ) ) {
	define( 'CUSTOM_EMAIL_PLUGIN_FILE', __FILE__ );
}

if ( ! class_exists( 'WC_Booking_Order_Email', false ) ) :
/**
 * A custom WooCommerce Email class with template corresponding to productId
 *
 * @since 1.0
 * @extends \WC_Email
 */
class WC_Booking_Order_Email extends WC_Email {


	/**
	 * Set email defaults
	 *
	 * @since 1.0
	 */
	public function __construct() {
		$this->id = 'wc_booking_order_email';
		$this->customer_email = true;

		// title in WooCommerce backend - Email settings
		$this->title = 'Mail Client Escape Game';

		$this->description = 'E-mail dépendant de l\'id du produit';

		$this->subject = 'Confirmation de réservation';

		$this->template_base = dirname(CUSTOM_EMAIL_PLUGIN_FILE) . '/../templates/';

		add_action( 'woocommerce_order_status_pending_to_on-hold_notification', array( $this, 'trigger' ) );

		parent::__construct();
  }

	/**
	 * Determine if the email should actually be sent and setup email merge variables
	 *
	 * @since 1.0
	 * @param int $order_id
	 */
	 public function trigger( $order_id, $order = false ) {

 		if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
 			$order = wc_get_order( $order_id );
 		}

 		if ( is_a( $order, 'WC_Order' ) ) {
 			$this->object                         = $order;
 			$this->recipient                      = $this->object->get_billing_email();
 			$this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
 			$this->placeholders['{order_number}'] = $this->object->get_order_number();
 		}

		// this sets the recipient to the client's email
		$this->recipient = $this->object->get_billing_email();

		if ( ! $this->is_enabled())
			return;

		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}


	/**
	 * get_content function.
	 * Note that only one product should be selected in the cart
	 * @since 1.0
	 * @return string the email content depending on calendar_id
	 */
	public function get_content() {
		global $wpdb;
		global $DOPBSPWooCommerce;

		foreach ($this->object->get_items() as $order_item_id => $order_item){
			$reservations_data = $wpdb->get_results($wpdb->prepare('SELECT * FROM '.$DOPBSPWooCommerce->tables->woocommerce.' WHERE order_item_id=%d', $order_item_id));

			foreach($reservations_data as $r){
				$rData = json_decode($reservations_data[0]->data, true);
				$date = DateTime::createFromFormat('Y-m-d', $rData['check_in']);

				$mail_content = wc_get_template_html(
					'email_calendar_'.$r->calendar_id.'.html',
					array(),
					$this->template_base,
					$this->template_base
				);
				// replace placeholder {date_jeu}
				return str_replace("{date_jeu}", $date->format('d/m/Y').' à '.$rData['start_hour'], $mail_content);
			}
		}
	}

	/**
	 * Initialize Settings Form Fields
	 *
	 * @since 1.0
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'    => array(
				'title'   => 'Enable/Disable',
				'type'    => 'checkbox',
				'label'   => 'Enable this email notification',
				'default' => 'yes'
			),
			'subject'    => array(
				'title'       => 'Subject',
				'type'        => 'text',
				'description' => sprintf( 'Le sujet du mail. Laisser vide pour utiliser la valeur par défaut: <code>%s</code>.', $this->subject ),
				'placeholder' => '',
				'default'     => ''
			),
			'email_type'      => array(
				'title'       => __( 'Email type', 'woocommerce' ),
				'type'        => 'select',
				'description' => __( 'Choose which format of email to send.', 'woocommerce' ),
				'default'     => 'html',
				'class'       => 'email_type wc-enhanced-select',
				'options'     => $this->get_email_type_options(),
				'desc_tip'    => true,
			)
		);
	}


}
endif;
