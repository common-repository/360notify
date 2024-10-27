<?php
/* This file is forked from the original author Micro Ocean Technologies's MoceanAPI Order SMS Notification plugin on 7/1/2024 */

interface Notify_Multivendor_Interface {
	public function get_vendor_data_list_from_order( $order_id );
}
