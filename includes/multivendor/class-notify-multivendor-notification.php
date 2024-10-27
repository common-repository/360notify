<?php
/* This file is forked from the original author Micro Ocean Technologies's MoceanAPI Order SMS Notification plugin on 7/1/2024 */

/**
 * Created by PhpStorm.
 * User: Neoson Lam
 * Date: 2/18/2019
 * Time: 3:25 PM.
 */

class Notify_Multivendor_Notification extends Notify_WooCommerce_Notification {
	/* @var Abstract_Notify_Multivendor $notifysms_multivendor */
	private $notifysms_multivendor;
	private $medium;
	private $defaultHooks = array(
		'pending',
		'on-hold',
		'processing',
		'completed',
		'cancelled',
		'refunded',
		'failed'
	);

	public function __construct( $medium = 'wordpress_woocommerce_multivendor', $notifysms_multivendor = null, Notify_WooCoommerce_Logger $log = null ) {
		parent::__construct( $log );
		if ( $notifysms_multivendor === null ) {
			$notifysms_multivendor = Notify_Multivendor_Factory::make();
		}
		$this->notifysms_multivendor = $notifysms_multivendor;
		$this->medium                = $medium;
	}

    public function send_sms_woocommerce_vendor_custom_order_status($order_id, $old_status, $new_status)
    {
        $default_statuses = [
            'pending',
            'processing',
            'on-hold',
            'completed',
            'cancelled',
            'refunded',
            'failed',
            'checkout-draft'
        ];

        if(in_array($new_status, $default_statuses)) { return; }
        $this->send_to_vendors( $order_id, $new_status );

    }

	public function send_to_vendors( $order_id, $status ) {
		if ( notifysms_get_options( 'notifysms_multivendor_vendor_send_sms', 'notifysms_multivendor_setting', 'off' ) === 'off' ) {
			return;
		}
		$send_sms_flag = true;

		//Checking if multivendor is "wc_marketplace" but do not have suborder
		if (Notify_Multivendor_Factory::$activatedPlugin == "wc_marketplace")
		{
			// if order id is not vendor order
			$is_suborder = (get_wcmp_suborders( $order_id, false, false) ? true : false);
			if( $is_suborder ) {
				//Do not send WhatsApp when it's sub order
				$send_sms_flag = false;
			}
		}

		if($send_sms_flag){
			// check for specific hook if WhatsApp should be send
			$activatedHooks = notifysms_get_options( 'notifysms_multivendor_vendor_send_sms_on', 'notifysms_multivendor_setting', $this->defaultHooks );
			if ( ! in_array( $status, $activatedHooks ) ) {
				$this->log->add( '360MessengerWhatsApp', 'not sending, current hook: ["' . $status . '"] activated hooks: ' . json_encode( $activatedHooks ) );
				return;
			}

			$this->log->add( '360MessengerWhatsApp', '3rd party plugin setting: ' . notifysms_get_options( 'notifysms_multivendor_selected_plugin', 'notifysms_multivendor_setting', 'auto' ) );
			if ( ! $this->notifysms_multivendor ) {
				$this->log->add( '360MessengerWhatsApp', 'error: no multivendor plugin detected' );
				return;
			}
			$this->log->add( '360MessengerWhatsApp', 'activated plugin: ' . Notify_Multivendor_Factory::$activatedPlugin );

			$order_details = wc_get_order( $order_id );
			$message       = notifysms_get_options( 'notifysms_multivendor_vendor_sms_template', 'notifysms_multivendor_setting', '' );
			//Get default country v1.1.17
			$default_country = notifysms_get_options('notifysms_woocommerce_country_code', 'notifysms_setting', '' );

			$vendor_data_list = $this->notifysms_multivendor->get_vendor_data_list_from_order( $order_id );
			if ( ! $vendor_data_list ) {
			    $this->log->add( '360MessengerWhatsApp', "Failed to retrieve vendor data list from order id. Exiting..." );
				return;
			}

			foreach ( $vendor_data_list as $phone_number => $vendor_datas ) {
				$phone_number = $this->phone_number_processing( $phone_number );
				$this->log->add( '360MessengerWhatsApp', 'Original template: ' . $message );
				$processed_msg = $this->replace_vendor_order_keyword( $message, $order_details, $vendor_datas );
				//Country Code v1.1.17
				$vendor_country = $this->notifysms_multivendor->get_vendor_country_from_vendor_data( $vendor_datas );
                $this->log->add( '360MessengerWhatsApp', "Vendor country: {$vendor_country}" );
                $this->log->add( '360MessengerWhatsApp', "Default country: {$default_country}" );
				if(empty($vendor_country)){
                    $vendor_country = $default_country;
                    $this->log->add( '360MessengerWhatsApp', "Country field being used: Default Country" );
				} else {
                    $this->log->add( '360MessengerWhatsApp', "Country field being used: Vendor Country" );
                }

				$phone_with_country_code = $this->check_and_get_phone_number($phone_number, $vendor_country);
				if ( $phone_with_country_code !== false ) {
					$this->log->add( '360MessengerWhatsApp', 'Vendor\'s phone number (' . $phone_number . ') in country (' . $vendor_country . ') converted to ' . $phone_with_country_code );
				}else {
					$phone_with_country_code = $phone_number;
				}
				Notify_SendSMS_Sms::send_sms('', $phone_with_country_code, $processed_msg);
			}
		}
	}

	public function replace_vendor_order_keyword( $message, WC_Order $order_details, $vendor_datas ) {
        $order_date = $order_details->get_date_created();
		$format = get_option("date_format");
        $order_date = date_i18n($format, $order_date);
        
        $order_id = $order_details->get_order_number();
		$tracking_items = get_post_meta( $order_id, '_wc_shipment_tracking_items', true );
		foreach ( $tracking_items as $tracking_item ){
			$shipment_tracking_number = $tracking_item['tracking_number'];
		}
	    
		$search  = array(
			'[shop_name]',
			'[shop_email]',
			'[shop_url]',
			'[vendor_shop_name]',
			'[order_id]',
			'[order_currency]',
			'[order_amount]',
			'[order_status]',
            '[order_latest_cust_note]',
            '[order_note]',
			'[order_product]',
			'[order_product_with_qty]',
            '[order_total_discount]',
			'[order_date_created]',
			'[order_total_tax]',
			'[order_subtotal]',
			'[billing_first_name]',
			'[billing_last_name]',
			'[billing_phone]',
			'[billing_email]',
			'[billing_company]',
			'[billing_address]',
			'[billing_country]',
			'[billing_city]',
			'[billing_state]',
			'[billing_postcode]',
			'[payment_method]',
			'[shipping_method]',
			'[transaction_id]',
			'[shipment_tracking_number]'
		);
		$replace = array(
			get_bloginfo( 'name' ),
			get_bloginfo( 'admin_email' ),
			get_bloginfo( 'url' ),
			$this->notifysms_multivendor->get_vendor_shop_name_from_vendor_data( $vendor_datas ),
			$order_details->get_order_number(),
			$order_details->get_currency(),
			$vendor_datas['total_amount_for_vendor'],
			ucfirst( $order_details->get_status() ),
            isset($order_details->get_customer_order_notes()[0]->comment_content) ? $order_details->get_customer_order_notes()[0]->comment_content : "",
            $order_details->get_customer_note(), // new
			$vendor_datas['item'],
			$vendor_datas['product_with_qty'],
            $order_details->get_total_discount(), // new
			$order_date, // new
			$order_details->get_total_tax(), // new
			$order_details->get_subtotal(), // new
			$order_details->get_billing_first_name(),
			$order_details->get_billing_last_name(),
			$order_details->get_billing_phone(),
			$order_details->get_billing_email(),
			$order_details->get_billing_company(),
			$order_details->get_billing_address_1(),
			$order_details->get_billing_country(),
			$order_details->get_billing_city(),
			$order_details->get_billing_state(),
			$order_details->get_billing_postcode(),
			$order_details->get_payment_method(),
            $order_details->get_shipping_method(), // new
			$order_details->get_transaction_id(), // new
			$shipment_tracking_number // new
		);
		$message = str_replace( $search, $replace, $message, $total_replaced );

		// 2020-07-04 - Support additional billing field for Multivendor
		$additional_billing_fields_array = $this->get_additional_billing_fields();
		foreach ( $additional_billing_fields_array as $field ) {
			$post_data = get_post_meta( $order_details->get_order_number(), $field, true );
			$message   = str_replace( '[' . $field . ']', $post_data, $message );
		}

		$this->log->add( '360MessengerWhatsApp', "Total replaced keyword: $total_replaced" );

		return $message;
	}

	// 2020-07-04 - Support additional billing field for Multivendor
	// Copied from class-notifysms-woocommerce-notification.php
	protected function get_additional_billing_fields() {
		$default_billing_fields = array(
			'billing_first_name',
			'billing_last_name',
			'billing_company',
			'billing_address', // added specially for Multivendor
			'billing_address_1',
			'billing_address_2',
			'billing_city',
			'billing_state',
			'billing_country',
			'billing_postcode',
			'billing_phone',
			'billing_email'
		);
		$additional_billing_field = array();
		$billing_fields           = array_filter( get_option( 'wc_fields_billing', array() ) );
		foreach ( $billing_fields as $field_key => $field_info ) {
			if ( ! in_array( $field_key, $default_billing_fields ) && $field_info['enabled'] ) {
				array_push( $additional_billing_field, $field_key );
			}
		}

		return $additional_billing_field;
	}
}
