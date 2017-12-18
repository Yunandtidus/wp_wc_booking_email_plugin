<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

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

		add_action('woocommerce_order_status_completed_notification', array( $this, 'trigger' ));
		parent::__construct();
	}

	/**
	 * Determine if the email should actually be sent and setup email merge variables
	 *
	 * @since 1.0
	 * @param int $order_id
	 */
	 public function trigger($order_id, $order = false) {

 		if ($order_id && !is_a( $order, 'WC_Order')) {
 			$order = wc_get_order( $order_id );
 		}

 		if (is_a( $order, 'WC_Order')) {
 			$this->object = $order;
 		}

		$reservation = $this->get_reservation();

		if (! $this->is_enabled() || 'no' === $this->get_option('enabled_'.$reservation->calendar_id))
			return;

		$headers = $this->get_headers();
		if ("yes" === $this->get_option('copieToAdmin')){
			$headers .= "Bcc: " . get_option('admin_email'). "\r\n";
		}

		$this->send(
			$this->object->get_billing_email(),
			$this->get_mail_subject($reservation),
			$this->get_mail_content($reservation),
			$headers,
			$this->get_attachments()
		);

	}

	/**
	 * get_mail_subject function.
	 * @since 1.0
	 * @return string the email content depending on calendar_id
	 */
	public function get_mail_subject($reservation) {
		return $this->get_option('subject_'.$reservation->calendar_id);
	}

	/**
	 * get_mail_content function.
	 * @since 1.0
	 * @return string the email content depending on calendar_id
	 */
	public function get_mail_content($reservation) {
		$date = DateTime::createFromFormat('Y-m-d', $reservation->check_in);

		$mail_content = $this->get_option( 'mail_template_'.$reservation->calendar_id);
		// replace placeholder {date_jeu}
		return str_replace("{date_jeu}", $date->format('d/m/Y').' à '.$reservation->start_hour, $mail_content);
	}

	/**
	 * get reservation (booking information)
	 * Note that only one product should be selected in the cart
	 * @since 1.0
	 * @return the reservation
	 */

	public function get_reservation(){
		global $wpdb;
		global $DOPBSP;

		foreach ($this->object->get_items() as $order_item_id => $order_item){
			$reservations_data = $wpdb->get_results($wpdb->prepare('SELECT * FROM '.$DOPBSP->tables->reservations.' WHERE transaction_id=%d', $this->object->get_id()));

			foreach($reservations_data as $r){
				return $r;
			}
		}
	}

	/**
	 * Initialize Settings Form Fields
	 *
	 * @since 1.0
	 */
	public function init_form_fields() {

		global $wpdb;
		global $DOPBSP;

		$calendars = $reservations_data = $wpdb->get_results('SELECT * FROM '.$DOPBSP->tables->calendars);

		$this->form_fields = array(
			'enabled'	  => array(
				'title'   => 'Activer/Désactiver',
				'type'    => 'checkbox',
				'label'   => 'Activer la notification par Email',
				'default' => 'yes'
			),
			'copieToAdmin'	  => array(
				'title'   => 'Envoyer une copie à l\'administrateur',
				'type'    => 'checkbox',
				'label'   => 'Activer la copie à l\'administrateur par Email',
				'default' => 'yes'
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

		// Form fields depending on calendars
		foreach ($calendars as $c){

			$this->form_fields['enabled_'.$c->id] = array(
				'title'   => 'Activer l\'envoi de mail pour le calendrier n°'.$c->id.' ('.$c->name.')',
				'type'    => 'checkbox',
				'label'   => 'Activer',
				'default' => 'no'
			);
			$this->form_fields['subject_'.$c->id] = array(
				'title'       => 'Sujet pour le calendrier n°'.$c->id.' ('.$c->name.')',
				'type'        => 'text',
				'description' => 'Le sujet des mails pour le calendrier n°'.$c->id.' ('.$c->name.')',
				'placeholder' => '',
				'default'     => 'Confirmation de réservation'
			);

			$this->form_fields['mail_template_'.$c->id] = array(
				'title'       => 'Sujet pour le calendrier n°'.$c->id.' ('.$c->name.')',
				'type'        => 'textarea',
				'description' => 'Le contenu des mails pour le calendrier n°'.$c->id.' ('.$c->name.')',
				'placeholder' => '',
				'default'     => 'Contenu du mail'
			);
		}
	}
}
endif;
