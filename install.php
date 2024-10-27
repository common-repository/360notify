<?php
/* This file is forked from the original author Micro Ocean Technologies's MoceanAPI Order SMS Notification plugin on 7/1/2024 */

if ( ! defined( 'ABSPATH' ) ) exit;

$create_sms_send = ( "CREATE TABLE IF NOT EXISTS 360notify_wc_send_whatsapp_outbox(
	ID int(10) NOT NULL auto_increment,
	date DATETIME DEFAULT CURRENT_TIMESTAMP,
	sender VARCHAR(20) NOT NULL,
	message TEXT NOT NULL,
	recipient TEXT NOT NULL,
    status VARCHAR(255) NOT NULL,
	PRIMARY KEY(ID)) CHARSET=utf8
" );