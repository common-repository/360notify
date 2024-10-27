<?php
/* This file is forked from the original author Micro Ocean Technologies's MoceanAPI Order SMS Notification plugin on 7/1/2024 */

namespace NotifyAPI_WC;

use NotifyAPI_WC\Forms\Handlers\ContactForm7;
use NotifyAPI_WC\Migrations\MigrateSendSMSPlugin;
use NotifyAPI_WC\Migrations\MigrateWoocommercePlugin;

class Loader {

    public static function load()
    {
        new ContactForm7();

        // load Migrations
        MigrateWoocommercePlugin::migrate();
        MigrateSendSMSPlugin::migrate();
    }
}
