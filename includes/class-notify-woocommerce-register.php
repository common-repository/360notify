<?php
/* This file is forked from the original author Micro Ocean Technologies's MoceanAPI Order SMS Notification plugin on 7/1/2024 */


class Notify_WooCommerce_Register {
	/* @var Notify_Register_Interface[] $instances_to_be_register */
	protected $instances_to_be_register = array();

	public function add( Notify_Register_Interface $instance ) {
		$this->instances_to_be_register[] = $instance;
		return $this;
	}

	public function load() {
		foreach ( $this->instances_to_be_register as $instance ) {
			$instance->register();
		}
	}
}

?>
