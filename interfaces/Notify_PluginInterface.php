<?php
/* This file is forked from the original author Micro Ocean Technologies's MoceanAPI Order SMS Notification plugin on 7/1/2024 */

interface Notify_PluginInterface {
    public static function plugin_activated();
    public function get_option_id();
    public function get_plugin_settings($with_identifier = false);
}