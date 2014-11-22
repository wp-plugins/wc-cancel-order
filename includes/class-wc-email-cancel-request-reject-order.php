<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'Wc_Email_Cancel_Request_Reject_Order' ) ) :

/**
 * Customer Processing Order Email
 *
 * An email sent to the admin when a new order is received/paid for.
 *
 * @class 		WC_Email_Customer_Processing_Order
 * @version		2.0.0
 * @package		WooCommerce/Classes/Emails
 * @author 		WooThemes
 * @extends 	WC_Email
 */
class Wc_Email_Cancel_Request_Reject_Order extends WC_Email {

	/**
	 * Constructor
	 */
	function __construct() {

		$this->id 				= 'cancel_order_request_rejected';
		$this->title 			= __( 'Cancel Request Rejected', 'woocommerce' );
		$this->description		= __( 'This is an order notification sent to the admin and customer when cthe cancellation order request is rejected.', 'woocommerce' );

		$this->heading 			= __( 'Order Cancellation Request has been rejected', 'woocommerce' );
		$this->subject      	= __( 'Order NO: {order_number} Cancellation Request has been rejected', 'woocommerce' );

		$this->template_html 	= 'emails/cancell-request-rejecte-order.php';
		$this->template_plain 	= 'emails/plain-cancell-request-rejecte-order.php';

		// Triggers for this email
		add_action( 'woocommerce_order_status_cancel-request_to_processing_notification', array( $this, 'trigger' ) );
		// Call parent constructor
		parent::__construct();
	}

	/**
	 * trigger function.
	 *
	 * @access public
	 * @return void
	 */
	function trigger( $order_id ) {

		if ( $order_id ) {
			$this->object 		= wc_get_order( $order_id );
			$this->recipient	= $this->object->billing_email;

			$this->find['order-date']      = '{order_date}';
			$this->find['order-number']    = '{order_number}';

			$this->replace['order-date']   = date_i18n( wc_date_format(), strtotime( $this->object->order_date ) );
			$this->replace['order-number'] = $this->object->get_order_number();
		}

		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			return;
		}
		$rec = array($this->get_recipient(),get_option( 'admin_email' ));
		$this->send($rec, $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}

	/**
	 * get_content_html function.
	 *
	 * @access public
	 * @return string
	 */
	function get_content_html() {
		ob_start();
		wc_get_template( $this->template_html, array(
			'order' 		=> $this->object,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => false,
			'plain_text'    => false
		) );
		return ob_get_clean();
	}

	/**
	 * get_content_plain function.
	 *
	 * @access public
	 * @return string
	 */
	function get_content_plain() {
		ob_start();
		wc_get_template( $this->template_plain, array(
			'order'         => $this->object,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => false,
			'plain_text'    => true
		) );
		return ob_get_clean();
	}
}


endif;