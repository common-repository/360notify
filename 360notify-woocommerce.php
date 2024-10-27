<?php

/*
Plugin Name: 360Notify
Plugin URI:  https://360messenger.com
Description: WooCommerce Order WhatsApp Notification
Version:     4.3
Author:      360messenger
Author URI:  https://360messenger.com
License:     GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: 360notify

This plugin is forked from the original author Micro Ocean Technologies's MoceanAPI Order SMS Notification plugin on 7/1/2024
*/

use NotifyAPI_WC\Loader;

if ( ! defined( 'WPINC' ) ) {
	die;
}


define("NOTIFY_PLUGIN_URL", plugin_dir_url(__FILE__));
define("NOTIFY_PLUGIN_DIR", plugin_dir_path(__FILE__));
define("NOTIFY_INC_DIR", NOTIFY_PLUGIN_DIR . "includes/");
define("NOTIFY_ADMIN_VIEW", NOTIFY_PLUGIN_DIR . "admin/");
define("NOTIFY_TEXT_DOMAIN", "360notify");
define("NOTIFY_DB_TABLE_NAME", "360notify_wc_send_whatsapp_outbox");

require_once NOTIFY_PLUGIN_DIR . 'lib/action-scheduler/action-scheduler.php';

add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'wk_plugin_settings_link' );
//add_action('plugins_loaded', array($this,'plugin_init')); 


add_action( 'plugins_loaded', 'notify_woocommerce_init', PHP_INT_MAX );

function notify_install() {

    include_once NOTIFY_PLUGIN_DIR . '/install.php';
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $create_sms_send );
}

register_activation_hook(__FILE__, 'notify_install');

function notify_cleanup() {
    delete_option("notify_plugin_version");
    delete_option("notify_domain_reachable");
}

register_deactivation_hook(__FILE__, 'notify_cleanup');

function notify_woocommerce_init() {
    //require_once(plugin_dir_path(__FILE__) . '/vendor/autoload.php');
	require_once ABSPATH . '/wp-admin/includes/plugin.php';
	require_once ABSPATH . '/wp-includes/pluggable.php';
	require_once NOTIFY_PLUGIN_DIR . 'interfaces/Notify_PluginInterface.php';
	require_once NOTIFY_PLUGIN_DIR . 'includes/contracts/class-notify-register-interface.php';
	require_once NOTIFY_PLUGIN_DIR . 'includes/class-notify-helper.php';
	require_once NOTIFY_PLUGIN_DIR . 'includes/class-notify-woocommerce-frontend-scripts.php';
	require_once NOTIFY_PLUGIN_DIR . 'includes/class-notify-woocommerce-hook.php';
	require_once NOTIFY_PLUGIN_DIR . 'includes/class-notify-woocommerce-register.php';
	require_once NOTIFY_PLUGIN_DIR . 'includes/class-notify-woocommerce-logger.php';
	require_once NOTIFY_PLUGIN_DIR . 'includes/class-notify-woocommerce-notification.php';
	require_once NOTIFY_PLUGIN_DIR . 'includes/class-notify-woocommerce-widget.php';
	require_once NOTIFY_PLUGIN_DIR . 'includes/class-notify-download-log.php';
	require_once NOTIFY_PLUGIN_DIR . 'includes/class-notify-sendsms.php';
	require_once NOTIFY_PLUGIN_DIR . 'includes/multivendor/class-notify-multivendor.php';
	require_once NOTIFY_PLUGIN_DIR . 'lib/NotifySMS.php';
	require_once NOTIFY_PLUGIN_DIR . 'lib/class.settings-api.php';
	require_once NOTIFY_PLUGIN_DIR . 'admin/class-notify-woocommerce-setting.php';
	require_once NOTIFY_PLUGIN_DIR . 'admin/sendsms.php';
	require_once NOTIFY_PLUGIN_DIR . 'admin/smsoutbox.php';
	require_once NOTIFY_PLUGIN_DIR . 'admin/automation.php';
	require_once NOTIFY_PLUGIN_DIR . 'admin/logs.php';
	require_once NOTIFY_PLUGIN_DIR . 'admin/help.php';
	require_once NOTIFY_PLUGIN_DIR . 'admin/pluginlist.php';
    require_once NOTIFY_PLUGIN_DIR . 'includes/plugins/NotifyS2Member.php';
    require_once NOTIFY_PLUGIN_DIR . 'includes/plugins/NotifyARMemberLite.php';
    require_once NOTIFY_PLUGIN_DIR . 'includes/plugins/NotifyARMemberPremium.php';
    require_once NOTIFY_PLUGIN_DIR . 'includes/plugins/NotifyMemberPress.php';
    require_once NOTIFY_PLUGIN_DIR . 'includes/plugins/NotifyMemberMouse.php';
    require_once NOTIFY_PLUGIN_DIR . 'includes/plugins/NotifySimpleMembership.php';
    require_once NOTIFY_PLUGIN_DIR . 'includes/plugins/NotifyRestaurantReservation.php';
    require_once NOTIFY_PLUGIN_DIR . 'includes/plugins/NotifyQuickRestaurantReservation.php';
    require_once NOTIFY_PLUGIN_DIR . 'includes/plugins/NotifyBookIt.php';
    require_once NOTIFY_PLUGIN_DIR . 'includes/plugins/NotifyLatePoint.php';
    require_once NOTIFY_PLUGIN_DIR . 'includes/plugins/NotifyFATService.php';
    require_once NOTIFY_PLUGIN_DIR . 'includes/plugins/NotifyWpERP.php';
    require_once NOTIFY_PLUGIN_DIR . 'includes/plugins/NotifyJetpackCRM.php';
    require_once NOTIFY_PLUGIN_DIR . 'includes/plugins/NotifyFluentCRM.php';
    require_once NOTIFY_PLUGIN_DIR . 'includes/plugins/NotifyGroundhoggCRM.php';
    require_once NOTIFY_PLUGIN_DIR . 'includes/plugins/NotifySupportedPlugin.php';
	require_once NOTIFY_PLUGIN_DIR . 'src/Migrations/MigrateSendSMSPlugin.php';
	require_once NOTIFY_PLUGIN_DIR . 'src/Migrations/MigrateWoocommercePlugin.php';

    // load all Forms integrations
    //Loader::load();

	//create notification instance
	$notifysms_notification = new Notify_WooCommerce_Notification();

	//register hooks and settings
	$registerInstance = new Notify_WooCommerce_Register();
	$registerInstance->add( new Notify_WooCommerce_Hook( $notifysms_notification ) )
	                 ->add( new Notify_WooCommerce_Setting() )
	                 ->add( new Notify_WooCommerce_Widget() )
	                 ->add( new Notify_WooCommerce_Frontend_Scripts() )
	                 ->add( new Notify_Multivendor() )
	                 ->add( new Notify_Download_log() )
	                 ->add( new Notify_SendSMS_View() )
	                 ->add( new Notify_Automation_View() )
	                 ->add( new Notify_SMSOutbox_View() )
	                 ->add( new Notify_Logs_View() )
	                 ->add( new Notify_Help_View() )
	                 ->load();
}
