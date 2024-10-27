<?php
/* This file is forked from the original author Micro Ocean Technologies's MoceanAPI Order SMS Notification plugin on 7/1/2024 */

class NotifyBookIt implements Notify_PluginInterface, Notify_Register_Interface {
    /*
    Plugin Name: Quick Restaurant Reservations - WordPress Booking Plugin
    Plugin Link: https://wordpress.org/plugins/bookit
    */

    // private $section_id;
    public static $plugin_identifier = 'bookit';
    private $plugin_name;
    private $plugin_medium;
    private $hook_action;
    private $log;
    private $option_id;

    public function __construct() {
        $this->log = new Notify_WooCoommerce_Logger();
        $this->option_id = "notifysms_{$this::$plugin_identifier}";
        $this->plugin_name = 'BookIt';
        $this->plugin_medium = 'wp_' . str_replace( ' ', '_', strtolower($this->plugin_name));
        $this->hook_action = "notifysms_send_reminder_{$this::$plugin_identifier}";
    }

    public function register()
    {
        add_action( 'bookit_appointment_status_changed', array($this, 'send_sms_on_appointment_status_changed'), 10, 1 );
        add_action( $this->hook_action,                  array($this, 'send_sms_reminder'), 10, 2);
    }

    public function get_option_id()
    {
        return $this->option_id;
    }

    public static function plugin_activated()
    {
        if( ! is_plugin_active(sprintf("%s/%s.php", self::$plugin_identifier, self::$plugin_identifier))) {
            return false;
        }

        try {
            require_once BOOKIT_CLASSES_PATH . "database/Appointments.php";
            return true;
        } catch (Exception $e) {
            $log = new Notify_WooCoommerce_Logger();
            $log->add("360MessengerWhatsApp", "Failed to import database/Appointments.php from BookIt");
            $log->add("360MessengerWhatsApp", "Aborting...");
            $log->add("360MessengerWhatsApp", print_r($e, true));
            return false;
        }
    }

    public function get_setting_section_data()
    {
        return array(
            'id'    => $this->get_option_id(),
            'title' => __( $this->plugin_name, NOTIFY_TEXT_DOMAIN ),
        );
    }

    public function get_setting_field_data()
    {
        $setting_fields = array(
			$this->get_enable_notification_fields(),
			//$this->get_send_from_fields(),
			$this->get_send_on_fields(),
		);
        foreach($this->get_reminder_fields() as $reminder) {
            $setting_fields[] = $reminder;
        }
        foreach($this->get_sms_reminder_template_fields() as $sms_reminder) {
            $setting_fields[] = $sms_reminder;
        }
        foreach($this->get_sms_template_fields() as $sms_templates) {
            $setting_fields[] = $sms_templates;
        }
        return $setting_fields;
    }

    private function get_enable_notification_fields() {
        return array(
            'name'    => 'notifysms_automation_enable_notification',
            'label'   => __( 'Enable WhatsApp notifications', NOTIFY_TEXT_DOMAIN ),
            'desc'    => ' ' . __( 'Enable', NOTIFY_TEXT_DOMAIN ),
            'type'    => 'checkbox',
            'default' => 'off'
        );
    }

    private function get_send_from_fields() {
        return array(
            'name'  => 'notifysms_automation_send_from',
            'label' => __( 'Send from', NOTIFY_TEXT_DOMAIN ),
            'desc'  => __( 'Sender of the WhatsApp when a message is received at a mobile phone', NOTIFY_TEXT_DOMAIN ),
            'type'  => 'text',
        );
    }

    private function get_send_on_fields() {
        return array(
            'name'    => 'notifysms_automation_send_on',
            'label'   => __( 'Send notification on', NOTIFY_TEXT_DOMAIN ),
            'desc'    => __( 'Choose when to send a WhatsApp notification message to your customer', NOTIFY_TEXT_DOMAIN ),
            'type'    => 'multicheck',
            'options' => array(
                'appointment_pending'      => 'Appointment pending',
                'appointment_approved'     => 'Appointment approved',
                'appointment_cancelled'    => 'Appointment cancelled',
            )
        );
    }

    private function get_sms_template_fields() {
        return array(
            array(
                'name'    => 'notifysms_automation_sms_template_appointment_pending',
                'label'   => __( 'Appointment pending WhatsApp message', NOTIFY_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your WhatsApp with <button type="button" id="notifysms-open-keyword-%1$s-[dummy]" data-attr-type="appointment_pending" data-attr-target="%1$s[notifysms_automation_sms_template_appointment_pending]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Greetings [customer_name], your appointment for [service_name] on [appointment_day] is [appointment_status]', NOTIFY_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'notifysms_automation_sms_template_appointment_approved',
                'label'   => __( 'Appointment approved WhatsApp message', NOTIFY_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your WhatsApp with <button type="button" id="notifysms-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[notifysms_automation_sms_template_appointment_approved]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Greetings [customer_name], your appointment for [service_name] on [appointment_day] is [appointment_status]', NOTIFY_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'notifysms_automation_sms_template_appointment_cancelled',
                'label'   => __( 'Appointment cancelled WhatsApp message', NOTIFY_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your WhatsApp with <button type="button" id="notifysms-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[notifysms_automation_sms_template_appointment_cancelled]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Greetings [customer_name], your appointment for [service_name] on [appointment_day] is [appointment_status]', NOTIFY_TEXT_DOMAIN )
            ),
        );
    }

    private function get_reminder_fields() {
        return array(
            array(
                'name'    => 'notifysms_automation_reminder',
                'label'   => __( 'Send reminder to customer reservation', NOTIFY_TEXT_DOMAIN ),
                'desc'    => __( '', NOTIFY_TEXT_DOMAIN ),
                'type'    => 'multicheck',
                'options' => array(
                    'rem_1'  => '15 minutes before reservation',
                    'rem_2'  => '30 minutes before reservation',
                    'rem_3'  => '60 minutes before reservation',
                    'custom' => 'Custom time before reservation',
                )
            ),
            array(
                'name'  => 'notifysms_automation_reminder_custom_time',
                'label' => __( '', NOTIFY_TEXT_DOMAIN ),
                'desc'  => __( 'Enter the custom time you want to remind your customer before reservation in (minutes) <br> Choose when to send a WhatsApp reminder message to your customer <br> Please set your timezone in <a href="' . admin_url('options-general.php') . '">settings</a> <br> You must setup cronjob <a href="https://github.com/360messenger/cronwordpress">here</a> ', NOTIFY_TEXT_DOMAIN ),
                'type'  => 'number',
            ),
        );
    }

    private function get_sms_reminder_template_fields() {
        return array(
            array(
                'name'    => 'notifysms_automation_sms_template_rem_1',
                'label'   => __( '15 minutes reminder WhatsApp message', NOTIFY_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your WhatsApp with <button type="button" id="notifysms-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[notifysms_automation_sms_template_rem_1]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Greetings [customer_name], your appointment for [service_name] on [appointment_day] is [appointment_status].', NOTIFY_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'notifysms_automation_sms_template_rem_2',
                'label'   => __( '30 minutes reminder WhatsApp message', NOTIFY_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your WhatsApp with <button type="button" id="notifysms-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[notifysms_automation_sms_template_rem_2]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Greetings [customer_name], your appointment for [service_name] on [appointment_day] is [appointment_status].', NOTIFY_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'notifysms_automation_sms_template_rem_3',
                'label'   => __( '60 minutes reminder WhatsApp message', NOTIFY_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your WhatsApp with <button type="button" id="notifysms-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[notifysms_automation_sms_template_rem_3]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Greetings [customer_name], your appointment for [service_name] on [appointment_day] is [appointment_status].', NOTIFY_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'notifysms_automation_sms_template_custom',
                'label'   => __( 'Custom time reminder WhatsApp Message', NOTIFY_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your WhatsApp with <button type="button" id="notifysms-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[notifysms_automation_sms_template_custom]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Greetings [customer_name], your appointment for [service_name] on [appointment_day] is [appointment_status]. - custom', NOTIFY_TEXT_DOMAIN )
            ),
        );
    }

    private function schedule_reminders($app_id, $status) {
        $send_sms_reminder_flag = true;
        $settings = $this->get_plugin_settings();
        $appointment = \Bookit\Classes\Database\Appointments::get_full_appointment_by_id($app_id);

        // do our reminder stuff
        $as_group = "{$this::$plugin_identifier}_{$appointment->id}";
        $format = get_option('date_format');
        // UTC booking date
        $appointment_date = DateTime::createFromFormat('U', $appointment->start_time)->format('Y-m-d H:i:s');

        // Direct convert to local timezone
        $local_appointment_date = DateTime::createFromFormat('Y-m-d H:i:s', $appointment_date, wp_timezone());
        $reminder_booking_date_15 = DateTime::createFromFormat('Y-m-d H:i:s', $appointment_date, wp_timezone());
        $reminder_booking_date_30 = DateTime::createFromFormat('Y-m-d H:i:s', $appointment_date, wp_timezone());
        $reminder_booking_date_60 = DateTime::createFromFormat('Y-m-d H:i:s', $appointment_date, wp_timezone());

        // current local time
        $current_time = date_i18n('Y-m-d H:i:s O');
        $now_date = DateTime::createFromFormat('Y-m-d H:i:s O', $current_time, wp_timezone())->format($format);
        $now_timestamp = DateTime::createFromFormat('Y-m-d H:i:s O', $current_time, wp_timezone())->getTimestamp();
        // $now_timestamp = strtotime("+1 minute", $now_timestamp);

        $this->log->add("360MessengerWhatsApp", "Booking date: {$appointment_date}");
        $this->log->add("360MessengerWhatsApp", "Current Local Date: {$now_date}");
        $this->log->add("360MessengerWhatsApp", "Current Local Timestamp: {$now_timestamp}");
        $this->log->add("360MessengerWhatsApp", "Booking date to Local time: {$local_appointment_date->format($format)}");

        $custom_reminder_time = $settings['notifysms_automation_reminder_custom_time'];
        if(!ctype_digit($custom_reminder_time)) {
            $this->log->add("360MessengerWhatsApp", "reminder time (in minutes) is not digit");
            $send_sms_reminder_flag = false;
        }

        $reminder_date_15 = $reminder_booking_date_15->modify("-15 minutes")->getTimestamp();
        $reminder_date_30 = $reminder_booking_date_30->modify("-30 minutes")->getTimestamp();
        $reminder_date_60 = $reminder_booking_date_60->modify("-60 minutes")->getTimestamp();

        $this->log->add("360MessengerWhatsApp", "15 mins Reminder timestamp: {$reminder_date_15}");
        $this->log->add("360MessengerWhatsApp", "30 mins Reminder timestamp: {$reminder_date_30}");
        $this->log->add("360MessengerWhatsApp", "60 mins Reminder timestamp: {$reminder_date_60}");

        $this->log->add("360MessengerWhatsApp", "Unscheduling all WhatsApp reminders for Group: {$as_group}");
        as_unschedule_all_actions('', array(), $as_group);
        $action_id_15 = as_schedule_single_action($reminder_date_15, $this->hook_action, array($appointment, 'rem_1'), $as_group );
        $action_id_30 = as_schedule_single_action($reminder_date_30, $this->hook_action, array($appointment, 'rem_2'), $as_group );
        $action_id_60 = as_schedule_single_action($reminder_date_60, $this->hook_action, array($appointment, 'rem_3'), $as_group );
        $this->log->add("360MessengerWhatsApp", "Send WhatsApp Reminder scheduled, action_id_15 = {$action_id_15}");
        $this->log->add("360MessengerWhatsApp", "Send WhatsApp Reminder scheduled, action_id_30 = {$action_id_30}");
        $this->log->add("360MessengerWhatsApp", "Send WhatsApp Reminder scheduled, action_id_60 = {$action_id_60}");

        if($send_sms_reminder_flag) {
            $reminder_date_custom = $local_appointment_date->modify("-{$custom_reminder_time} minutes")->getTimestamp();
            $this->log->add("360MessengerWhatsApp", "Custom Reminder timestamp: {$reminder_date_custom}");
            $action_id_custom = as_schedule_single_action($reminder_date_custom, $this->hook_action, array($appointment, 'custom'), $as_group );
            $this->log->add("360MessengerWhatsApp", "Send WhatsApp Reminder scheduled, action_id_custom = {$action_id_custom}");
        }

    }

    public function send_sms_reminder($appointment, $status)
    {
        $appointment = (object) $appointment;
        $this->log->add("360MessengerWhatsApp", "Appointment status: {$appointment->status}");
        $this->log->add("360MessengerWhatsApp", "Status: {$status}");

        if($appointment->status !== 'approved') {
            $this->log->add("360MessengerWhatsApp", "Appointment status is not approved, status: {$appointment->status}");
            $this->log->add("360MessengerWhatsApp", "not sending reminder.");
            return;
        }
        $appointment_date = DateTime::createFromFormat('U', $appointment->start_time)->format('Y-m-d H:i:s');
        $local_appointment_date = DateTime::createFromFormat('Y-m-d H:i:s', $appointment_date, wp_timezone());
        // Direct convert to local timezone
        $local_appointment_timestamp = $local_appointment_date->getTimestamp();
        $now_timestamp = current_datetime()->getTimestamp();
        $this->log->add("360MessengerWhatsApp", "appointment timestamp: {$local_appointment_timestamp}");
        $this->log->add("360MessengerWhatsApp", "now timestamp: {$now_timestamp}");

        // membership already expired
        if($now_timestamp >= $local_appointment_timestamp) {
            $this->log->add("360MessengerWhatsApp", "Appointment date is in the past");
            return;
        }

        $settings = $this->get_plugin_settings();

        $enable_notifications = $settings['notifysms_automation_enable_notification'];
        $reminder = $settings['notifysms_automation_reminder'];

        $this->log->add("360MessengerWhatsApp", "Successfully retrieved plugin settings");

        if($enable_notifications === "on"){
            $this->log->add("360MessengerWhatsApp", "enable_notifications: {$enable_notifications}");
            if(!empty($reminder) && is_array($reminder)) {
                if(array_key_exists($status, $reminder)) {
                    $this->log->add("360MessengerWhatsApp", "Sending reminder now");
                    $this->send_customer_notification($appointment, $status);
                }
            }
        }
    }

    public function get_plugin_settings($with_identifier = false)
    {
        $settings = array(
            "notifysms_automation_enable_notification"                => notifysms_get_options("notifysms_automation_enable_notification", $this->get_option_id()),
            "notifysms_send_from"                                     => notifysms_get_options('notifysms_automation_send_from', $this->get_option_id()),
            "notifysms_automation_send_on"                            => notifysms_get_options("notifysms_automation_send_on", $this->get_option_id()),
            "notifysms_automation_reminder"                           => notifysms_get_options("notifysms_automation_reminder", $this->get_option_id()),
            "notifysms_automation_reminder_custom_time"               => notifysms_get_options("notifysms_automation_reminder_custom_time", $this->get_option_id()),
            "notifysms_automation_sms_template_rem_1"                 => notifysms_get_options("notifysms_automation_sms_template_rem_1", $this->get_option_id()),
            "notifysms_automation_sms_template_rem_2"                 => notifysms_get_options("notifysms_automation_sms_template_rem_2", $this->get_option_id()),
            "notifysms_automation_sms_template_rem_3"                 => notifysms_get_options("notifysms_automation_sms_template_rem_3", $this->get_option_id()),
            "notifysms_automation_sms_template_custom"                => notifysms_get_options("notifysms_automation_sms_template_custom", $this->get_option_id()),
            "notifysms_automation_sms_template_appointment_pending"   => notifysms_get_options("notifysms_automation_sms_template_appointment_pending", $this->get_option_id()),
            "notifysms_automation_sms_template_appointment_approved"  => notifysms_get_options("notifysms_automation_sms_template_appointment_approved", $this->get_option_id()),
            "notifysms_automation_sms_template_appointment_cancelled" => notifysms_get_options("notifysms_automation_sms_template_appointment_cancelled", $this->get_option_id()),
        );

        if ($with_identifier) {
            return array(
                self::$plugin_identifier => $settings,
            );
        }

        return $settings;
    }

    public function get_keywords_field()
    {

        return array(
            'appointment' => array(
                'appointment_id',
                'appointment_day',
                'appointment_price',
                'appointment_total_price',
                'appointment_status',
                'appointment_start_time',
                'appointment_end_time',
            ),
            'service' => array(
                'service_name',
            ),
            'staff' => array(
                'staff_email',
                'staff_id',
                'staff_phone',
                'staff_name',
            ),
            'customer' => array(
                'customer_name',
                'customer_email',
                'customer_phone',
            ),
            'payment' => array(
                'payment_method',
                'payment_status',
            ),
            'notifysms' => array(
                'reminder_custom_time',
            ),
        );

    }

    public function send_sms_on_appointment_status_changed($app_id)
    {
        $appointment = \Bookit\Classes\Database\Appointments::get_full_appointment_by_id($app_id);
        $status_choices = ['pending', 'approved', 'cancelled'];

        if(!in_array($appointment->status, $status_choices)) { return; }
        $status = "appointment_{$appointment->status}";
        if($appointment->status == 'approved') {
            $this->log->add("360MessengerWhatsApp", "appointment status: {$appointment->status}");
            $this->log->add("360MessengerWhatsApp", "Scheduling reminder");
            $this->schedule_reminders($app_id, $status);
        }
        $this->send_sms_on($appointment, $status);
    }

    public function send_sms_on($appointment, $status)
    {
        $plugin_settings = $this->get_plugin_settings();
        $enable_notifications = $plugin_settings['notifysms_automation_enable_notification'];
        $send_on = $plugin_settings['notifysms_automation_send_on'];

        if($enable_notifications === "on"){
            if(!empty($send_on) && is_array($send_on)) {
                if(array_key_exists($status, $send_on)) {
                    $this->send_customer_notification($appointment, $status);
                }
            }
        }

        return;
    }

    public function send_customer_notification($appointment, $status)
    {
        $settings = $this->get_plugin_settings();

        $sms_from = $settings['notifysms_automation_send_from'];

        $phone_no = $appointment->customer_phone;

        // get message template from status
        $msg_template = $settings["notifysms_automation_sms_template_{$status}"];
        $message = $this->replace_keywords_with_value($appointment, $msg_template, $status);

        $validated_number = Notify_SendSMS_Sms::get_formatted_number($phone_no);

        Notify_SendSMS_Sms::send_sms($sms_from, $validated_number, $message, $this->plugin_medium);
    }

    /*
        returns the message with keywords replaced to original value it points to
        eg: [name] => 'customer name here'
    */
    protected function replace_keywords_with_value($appointment, $message, $status)
    {
        // use regex to match all [stuff_inside]
        // return the message
        preg_match_all('/\[(.*?)\]/', $message, $keywords);

        if($keywords) {
            foreach($keywords[1] as $keyword) {
                $keyword_value = $this->keyword_mapper($appointment, $keyword);
                if( !empty($keyword_value) ) {
                    $message = str_replace("[{$keyword}]", $keyword_value, $message);
                }
                else if($keyword == 'reminder_custom_time') {
                    $settings = $this->get_plugin_settings();
                    $reminder_time = $settings['notifysms_automation_reminder_custom_time'];
                    $message = str_replace("[{$keyword}]", $this->seconds_to_days($reminder_time), $message);
                }
                else {
                    $message = str_replace("[{$keyword}]", "", $message);
                }
            }
        }
        return $message;
    }

    protected function keyword_mapper($appointment, $keyword) {

        $keyword_mappers = array(
            'appointment_id'                => $appointment->id,
            'appointment_day'               => date( get_option('date_format'), $appointment->date_timestamp ),
            'appointment_price'             => $appointment->price,
            'appointment_total_price'       => bookit_price($appointment->price),
            'appointment_status'            => $appointment->status,
            'appointment_start_time'        => date( get_option('time_format'), $appointment->start_time ),
            'appointment_end_time'          => date( get_option('time_format'), $appointment->end_time ),
            'service_name'                  => $appointment->service_name,
            'staff_id'                      => $appointment->staff_id,
            'staff_name'                    => $appointment->staff_name,
            'staff_email'                   => $appointment->staff_email,
            'staff_phone'                   => $appointment->staff_phone,
            'customer_name'                 => $appointment->customer_name,
            'customer_email'                => $appointment->customer_email,
            'customer_phone'                => $appointment->customer_phone,
            'payment_method'                => $appointment->payment_method,
            'payment_status'                => $appointment->payment_status,
        );
        if(!array_key_exists($keyword, $keyword_mappers)) { return ''; }
        return $keyword_mappers[$keyword];

    }

    private function seconds_to_days($seconds) {

        if(!ctype_digit($seconds)) {
            $this->log->add("360MessengerWhatsApp", 'seconds_to_days: $seconds is not a valid digit');
            return '';
        }

        $ret = "";

        $days = intval(intval($seconds) / (3600*24));
        if($days> 0)
        {
            $ret .= "{$days}";
        }

        return $ret;
    }


}
