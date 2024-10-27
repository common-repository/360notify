<?php
/* This file is forked from the original author Micro Ocean Technologies's MoceanAPI Order SMS Notification plugin on 7/1/2024 */

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

class NotifySupportedPlugin {

    public function __construct() {}

    public static function get_activated_plugins()
    {
        $supported_plugins = array();
        if(NotifyS2Member::plugin_activated())
            $supported_plugins[] = NotifyS2Member::class;
        if(NotifyARMemberLite::plugin_activated())
            $supported_plugins[] = NotifyARMemberLite::class;
        if(NotifyARMemberPremium::plugin_activated())
            $supported_plugins[] = NotifyARMemberPremium::class;
        if(NotifyMemberPress::plugin_activated())
            $supported_plugins[] = NotifyMemberPress::class;
        if(NotifyMemberMouse::plugin_activated())
            $supported_plugins[] = NotifyMemberMouse::class;
        if(NotifySimpleMembership::plugin_activated())
            $supported_plugins[] = NotifySimpleMembership::class;

        if(NotifyRestaurantReservation::plugin_activated())
            $supported_plugins[] = NotifyRestaurantReservation::class;
        if(NotifyQuickRestaurantReservation::plugin_activated())
        $supported_plugins[] = NotifyQuickRestaurantReservation::class;
        if(NotifyBookIt::plugin_activated())
            $supported_plugins[] = NotifyBookIt::class;
        if(NotifyLatePoint::plugin_activated())
            $supported_plugins[] = NotifyLatePoint::class;
        if(NotifyFATService::plugin_activated())
            $supported_plugins[] = NotifyFATService::class;

        if(NotifyWpERP::plugin_activated())
            $supported_plugins[] = NotifyWpERP::class;
        if(NotifyJetpackCRM::plugin_activated())
            $supported_plugins[] = NotifyJetpackCRM::class;
        if(NotifyFluentCRM::plugin_activated())
            $supported_plugins[] = NotifyFluentCRM::class;
        if(NotifyGroundhoggCRM::plugin_activated())
            $supported_plugins[] = NotifyGroundhoggCRM::class;

        return $supported_plugins;
    }


}
