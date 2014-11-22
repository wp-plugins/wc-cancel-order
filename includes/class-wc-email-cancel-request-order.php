<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Email_Cancel_Request_Order' ) ) :

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
class WC_Email_Cancel_Request_Order extends WC_Email {

	/**
	 * Constructor
	 */
	function __construct() {
		
		$this->id 				= 'cancel_order_request';
		$this->title 			= __( 'Cancel Request', 'woocommerce' );
		$this->description		= __( 'This is an order notification sent to the admin and customer when customer raise the cancellation order request.', 'woocommerce' );

		$this->heading 			= __( 'Order Cancellation Request has been raised', 'woocommerce' );
		$this->subject      	= __( 'Order No: {order_number} Cancellation Request has been raised', 'woocommerce' );
		
		$this->template_html 	= 'emails/cancell-request-order.php';
		$this->template_plain 	= 'emails/emails/plain-cancell-request-order.php';

		// Triggers for this email
		add_action( 'woocommerce_order_status_pending_to_cancel-request_notification', array( $this, 'trigger' ) );
		add_action( 'woocommerce_order_status_on-hold_to_cancel-request_notification', array( $this, 'trigger' ) );
		add_action( 'woocommerce_order_status_processing_to_cancel-request_notification', array( $this, 'trigger' ) );
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
		$this->send($rec,$this->get_subject(),$this->get_content(),$this->get_headers(),$this->get_attachments() );
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


