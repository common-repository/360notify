<?php
/* This file is forked from the original author Micro Ocean Technologies's MoceanAPI Order SMS Notification plugin on 7/1/2024 */

/**
 * Created by PhpStorm.
 * User: Neoson Lam
 * Date: 4/10/2019
 * Time: 2:04 PM.
 */

class Notify_Multivendor_Hook extends Notify_WooCommerce_Hook {
	public function register() {
		notify_add_actions( $this->get_multivendor_actions() );
	}

	protected function get_multivendor_actions() {
		$hook_actions   = array();
		$hook_actions[] = array(
			'hook'                  => 'woocommerce_order_status_pending',
			'function_to_be_called' => function ( $order_id ) {
				$this->notification_ins->send_to_vendors( $order_id, 'pending' );
			}
		);
		$hook_actions[] = array(
			'hook'                  => 'woocommerce_order_status_failed',
			'function_to_be_called' => function ( $order_id ) {
				$this->notification_ins->send_to_vendors( $order_id, 'failed' );
			}
		);
		$hook_actions[] = array(
			'hook'                  => 'woocommerce_order_status_on-hold',
			'function_to_be_called' => function ( $order_id ) {
				$this->notification_ins->send_to_vendors( $order_id, 'on-hold' );
			}
		);
		$hook_actions[] = array(
			'hook'                  => 'woocommerce_order_status_processing',
			'function_to_be_called' => function ( $order_id ) {
				$this->notification_ins->send_to_vendors( $order_id, 'processing' );
			}
		);
		$hook_actions[] = array(
			'hook'                  => 'woocommerce_order_status_completed',
			'function_to_be_called' => function ( $order_id ) {
				$this->notification_ins->send_to_vendors( $order_id, 'completed' );
			}
		);
		$hook_actions[] = array(
			'hook'                  => 'woocommerce_order_status_refunded',
			'function_to_be_called' => function ( $order_id ) {
				$this->notification_ins->send_to_vendors( $order_id, 'refunded' );
			}
		);
		$hook_actions[] = array(
			'hook'                  => 'woocommerce_order_status_cancelled',
			'function_to_be_called' => function ( $order_id ) {
				$this->notification_ins->send_to_vendors( $order_id, 'cancelled' );
			}
		);
		$hook_actions[] = array(
			'hook'                  => 'woocommerce_order_status_changed',
			'function_to_be_called' => array( $this->notification_ins, 'send_sms_woocommerce_vendor_custom_order_status' ),
            'priority'              => 10,
            'args_count'            => 3,
		);


		return $hook_actions;
	}
}
