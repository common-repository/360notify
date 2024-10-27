<?php
/* This file is forked from the original author Micro Ocean Technologies's MoceanAPI Order SMS Notification plugin on 7/1/2024 */

namespace NotifyAPI_WC\Migrations;

class MigrateWoocommercePlugin {
    public static function migrate()
    {
        $setting_ids_to_iterate = ["notifysms_setting", "notifysms_admin_setting", "notifysms_customer_setting", "notifysms_multivendor_setting"];

        foreach($setting_ids_to_iterate as $setting_id) {
            // check if order notifciation plugin setting is set
            $setting = get_option($setting_id);
            
            if(empty($setting)) {
                // check notifyapi-sendsms
                $sendsms_setting_id = preg_replace("/notifysms_/", "moceansms_", $setting_id, 1);
                $sendsms_setting = get_option($sendsms_setting_id);
                if(!empty($sendsms_setting)) {
                    // if user have notifyapi-sendsms setting, we overwrite it to order notification
                    $new_option = [];
                    foreach($sendsms_setting as $key => $value) {
                        $new_key = preg_replace("/moceansms_/", "notifysms_", $key, 1);
                        $new_option[$new_key] = $value;

                    }
                    update_option($setting_id, $new_option);
                }
            }
        }


    }
}