<?php
/* This file is forked from the original author Micro Ocean Technologies's MoceanAPI Order SMS Notification plugin on 7/1/2024 */

/**
 * Created by PhpStorm.
 * User: Neoson Lam
 * Date: 4/10/2019
 * Time: 2:47 PM.
 */

class Notify_Multivendor implements Notify_Register_Interface {
	public function register() {
		$this->required_files();
		//create notification instance
		$notify_notification = new Notify_Multivendor_Notification( 'Wordpress-Woocommerce-Multivendor-Extension-' . Notify_Multivendor_Factory::$activatedPlugin );

		$registerInstance = new Notify_WooCommerce_Register();
		$registerInstance->add( new Notify_Multivendor_Hook( $notify_notification ) )
		                 ->add( new Notify_Multivendor_Setting() )
		                 ->load();
	}

	protected function required_files() {
		require_once __DIR__ . '/admin/class-notify-multivendor-setting.php';
		require_once __DIR__ . '/abstract/abstract-notify-multivendor.php';
		require_once __DIR__ . '/contracts/class-notify-multivendor-interface.php';
		require_once __DIR__ . '/class-notify-multivendor-factory.php';
		require_once __DIR__ . '/class-notify-multivendor-hook.php';
		require_once __DIR__ . '/class-notify-multivendor-notification.php';
	}
}
