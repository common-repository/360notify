<?php
/* This file is forked from the original author Micro Ocean Technologies's MoceanAPI Order SMS Notification plugin on 7/1/2024 */

class NotifyFATService implements Notify_PluginInterface, Notify_Register_Interface {
    /*
    Plugin Name: Five Star Restaurant Reservations - WordPress Booking Plugin
    Plugin Link: https://wordpress.org/plugins/restaurant-reservations/
    */

    // private $section_id;
    public static $plugin_identifier = 'fat-services-booking';
    private $log;
    private $plugin_name;
    private $plugin_medium;
    private $option_id;

    public function __construct() {
        $this->log = new Notify_WooCoommerce_Logger();
        $this->option_id = "notifysms_{$this::$plugin_identifier}";
        $this->plugin_name = 'FAT Services Booking';
        $this->plugin_medium = 'wp_' . str_replace( ' ', '_', strtolower($this->plugin_name));
    }

    public function register()
    {
        add_action( 'fat_after_update_booking_status', array($this, 'send_sms_on'), 10, 2 );
    }

    public function get_option_id()
    {
        return $this->option_id;
    }

    public static function plugin_activated()
    {
        return is_plugin_active(sprintf("%s/%s.php", self::$plugin_identifier, self::$plugin_identifier));
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
        foreach($this->get_sms_template_fields() as $sms_reminder_templates) {
            $setting_fields[] = $sms_reminder_templates;
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
                'cancel'   => 'Cancel',
                'approved' => 'Approved',
                'pending'  => 'Pending',
                'reject'   => 'Reject',
            )
        );
    }

    private function get_sms_template_fields() {
        return array(
            array(
                'name'    => 'notifysms_automation_sms_template_cancel',
                'label'   => __( 'Cancel WhatsApp message', NOTIFY_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your WhatsApp with <button type="button" id="notifysms-open-keyword-%1$s-[dummy]" data-attr-type="cancel" data-attr-target="%1$s[notifysms_automation_sms_template_cancel]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Greetings [c_first_name], your appointment for [s_name] on [b_date] [b_time] is [b_process_status]', NOTIFY_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'notifysms_automation_sms_template_approved',
                'label'   => __( 'Approved WhatsApp message', NOTIFY_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your WhatsApp with <button type="button" id="notifysms-open-keyword-%1$s-[dummy]" data-attr-type="approved" data-attr-target="%1$s[notifysms_automation_sms_template_approved]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Greetings [c_first_name], your appointment for [s_name] on [b_date] [b_time] is [b_process_status]', NOTIFY_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'notifysms_automation_sms_template_pending',
                'label'   => __( 'Pending WhatsApp message', NOTIFY_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your WhatsApp with <button type="button" id="notifysms-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[notifysms_automation_sms_template_pending]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Greetings [c_first_name], your appointment for [s_name] on [b_date] [b_time] is [b_process_status]', NOTIFY_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'notifysms_automation_sms_template_reject',
                'label'   => __( 'Rejected WhatsApp message', NOTIFY_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your WhatsApp with <button type="button" id="notifysms-open-keyword-%1$s-[dummy]" data-attr-type="approved" data-attr-target="%1$s[notifysms_automation_sms_template_reject]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Greetings [c_first_name], your appointment for [s_name] on [b_date] [b_time] is [b_process_status]', NOTIFY_TEXT_DOMAIN )
            ),
        );
    }

    public function get_plugin_settings($with_identifier = false)
    {
        $settings = array(
            "notifysms_automation_enable_notification"   => notifysms_get_options("notifysms_automation_enable_notification", $this->get_option_id()),
            "notifysms_send_from"                        => notifysms_get_options('notifysms_automation_send_from', $this->get_option_id()),
            "notifysms_automation_send_on"               => notifysms_get_options("notifysms_automation_send_on", $this->get_option_id()),
            "notifysms_automation_sms_template_cancel"   => notifysms_get_options("notifysms_automation_sms_template_cancel", $this->get_option_id()),
            "notifysms_automation_sms_template_approved" => notifysms_get_options("notifysms_automation_sms_template_approved", $this->get_option_id()),
            "notifysms_automation_sms_template_pending"  => notifysms_get_options("notifysms_automation_sms_template_pending", $this->get_option_id()),
            "notifysms_automation_sms_template_reject"   => notifysms_get_options("notifysms_automation_sms_template_reject", $this->get_option_id()),
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
            'booking' => array(
                'b_id',
                'b_date',
                'b_time',
                'b_total_pay',
                'b_description',
                'b_process_status',
                'b_coupon_code',
                'b_discount',
            ),
            'customer' => array(
                'c_id',
                'c_first_name',
                'c_last_name',
                'c_gender',
                'c_phone_code',
                'c_phone',
                'c_email',
                'c_dob',
                'c_description',
            ),
            'service' => array(
                's_id',
                's_name',
                's_description',
                's_price',
                's_tax	',
                's_duration',
                's_minimum_person',
                's_link',
            ),
            'employee' => array(
                'e_id',
                'e_first_name',
                'e_last_name',
                'e_phone',
                'e_email',
                'e_description',
            ),
            'location' => array(
                'loc_id',
                'loc_name',
                'loc_address',
                'loc_link',
                'loc_description',
            ),
        );

    }

    private function get_booking_by_id($booking_id) {
        // code
        global $wpdb;
        $sql = "SELECT *
                FROM {$wpdb->prefix}fat_sb_booking
                INNER JOIN {$wpdb->prefix}fat_sb_customers
                    ON {$wpdb->prefix}fat_sb_booking.b_customer_id = {$wpdb->prefix}fat_sb_customers.c_id
                    AND {$wpdb->prefix}fat_sb_booking.b_id=%d
                INNER JOIN {$wpdb->prefix}fat_sb_employees
                    ON {$wpdb->prefix}fat_sb_booking.b_employee_id = {$wpdb->prefix}fat_sb_employees.e_id
                INNER JOIN {$wpdb->prefix}fat_sb_locations
                    ON {$wpdb->prefix}fat_sb_booking.b_loc_id = {$wpdb->prefix}fat_sb_locations.loc_id
                INNER JOIN {$wpdb->prefix}fat_sb_services
                    ON {$wpdb->prefix}fat_sb_booking.b_service_id = {$wpdb->prefix}fat_sb_services.s_id
                ";
        $sql = $wpdb->prepare($sql, $booking_id);
        $booking = $wpdb->get_results($sql);
        if(count($booking) > 0) return $booking[0];
        else return array();
    }

    public function send_sms_on($b_id, $b_process_status)
    {
        if ($b_process_status == 0)      { $status = 'pending';  }
        else if ($b_process_status == 1) { $status = 'approved'; }
        else if ($b_process_status == 2) { $status = 'cancel';   }
        else if ($b_process_status == 3) { $status = 'reject';   }
        else                             { $status = 'none';     }
        $plugin_settings = $this->get_plugin_settings();
        $enable_notifications = $plugin_settings['notifysms_automation_enable_notification'];
        $send_on = $plugin_settings['notifysms_automation_send_on'];

        $this->log->add("360MessengerWhatsApp", "booking id: {$b_id}");
        $this->log->add("360MessengerWhatsApp", "booking status: {$status}");

        if($enable_notifications === "on"){
            $this->log->add("360MessengerWhatsApp", "Enable notifications: {$enable_notifications}");
            if(!empty($send_on) && is_array($send_on)) {
                if(array_key_exists($status, $send_on)) {
                    $this->log->add("360MessengerWhatsApp", "Enable {$status} notifications: true");
                    $booking = $this->get_booking_by_id($b_id);
                    // if booking is empty, nothing to do here
                    if(empty($booking)) {
                        $this->log->add("360MessengerWhatsApp", "booking is empty, nothing else to do. Aborting");
                        return;
                    }
                    $function_to_be_called = "send_sms_on_status_{$status}";
                    $this->$function_to_be_called($booking);
                }
            }
        }

        return $booking;
    }

    public function send_sms_on_status_pending($booking) {
		$this->send_customer_notification( $booking, "pending" );
	}

    public function send_sms_on_status_approved($booking) {
		$this->send_customer_notification( $booking, "approved" );
	}

    public function send_sms_on_status_reject($booking) {
		$this->send_customer_notification( $booking, "reject" );
	}

    public function send_sms_on_status_cancel($booking) {
		$this->send_customer_notification( $booking, "cancel" );
	}

    public function send_customer_notification($booking, $status)
    {
        $settings = $this->get_plugin_settings();

        $sms_from = $settings['notifysms_automation_send_from'];

        // get number from customer
        if(empty($booking->c_phone_code) && empty($booking->c_phone)) {
            $this->log->add("360MessengerWhatsApp", "customer country code and phone is empty");
            return;
        }

        $country_code = explode(',', $booking->c_phone_code)[1];
        $phone_no = Notify_SendSMS_Sms::get_formatted_number($booking->c_phone, $country_code);

        if(empty($phone_no)) {
            $this->log->add("360MessengerWhatsApp", "Number invalid format. Aborting");
            return;
        }

        // $phone_no = "{$country_code}{$booking->c_phone}";
        $this->log->add("360MessengerWhatsApp", "customer phone no: {$phone_no}");

        // get message template from status
        $msg_template = $settings["notifysms_automation_sms_template_{$status}"];

        $this->log->add("360MessengerWhatsApp", "Message template: {$msg_template}");

        $message = $this->replace_keywords_with_value($booking, $msg_template);
        Notify_SendSMS_Sms::send_sms($sms_from, $phone_no, $message, $this->plugin_medium);
    }

    /*
        returns the message with keywords replaced to original value it points to
        eg: [name] => 'customer name here'
    */
    protected function replace_keywords_with_value($booking, $message)
    {
        // use regex to match all [stuff_inside]
        // replace and match it with rtbBooking (booking) object
        // return the message
        preg_match_all('/\[(.*?)\]/', $message, $keywords);

        if($keywords) {
            foreach($keywords[1] as $keyword) {
                $replaced_keyword = $this->keyword_mapper($booking, $keyword);

                if(empty($replaced_keyword) && property_exists($booking, $keyword)) {
                    $message = str_replace("[{$keyword}]", $booking->$keyword, $message);
                }
                else if(!empty($replaced_keyword)) {
                    $message = str_replace("[{$keyword}]", $replaced_keyword, $message);
                }
                else {
                    $message = str_replace("[{$keyword}]", "", $message);
                }
            }
        }
        return $message;
    }

    private function keyword_mapper($booking, $key) {
        if ($booking->b_process_status == 0)      { $status = 'pending';  }
        else if ($booking->b_process_status == 1) { $status = 'approved'; }
        else if ($booking->b_process_status == 2) { $status = 'cancel';   }
        else if ($booking->b_process_status == 3) { $status = 'reject';   }
        $kw_mappers = array(
            'b_time' => date("H:i", mktime(0, $booking->b_time)),
            'b_process_status' => $status,
        );

        if(! array_key_exists($key, $kw_mappers)) { return ''; }
        return $kw_mappers[$key];
    }

}
