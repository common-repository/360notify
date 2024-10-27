<?php
/* This file is forked from the original author Micro Ocean Technologies's MoceanAPI Order SMS Notification plugin on 7/1/2024 */

class NotifyGroundhoggCRM implements Notify_PluginInterface, Notify_Register_Interface {
    /*
    Plugin Name: WordPress CRM, Email & Marketing Automation for WordPress | Award Winner — Groundhogg
    Plugin Link: https://wordpress.org/plugins/groundhogg/
    */

    public static $plugin_identifier = 'groundhogg';
    private $plugin_name;
    private $plugin_medium;
    private $log;
    private $option_id;

    public function __construct() {
        $this->log = new Notify_WooCoommerce_Logger();
        $this->option_id = "notifysms_{$this::$plugin_identifier}";
        $this->plugin_name = 'Groundhogg CRM';
        $this->plugin_medium = 'wp_' . str_replace( ' ', '_', strtolower($this->plugin_name));
    }

    public static function plugin_activated()
    {
        $log = new Notify_WooCoommerce_Logger();
        if( ! is_plugin_active(sprintf('%1$s/%1$s.php', self::$plugin_identifier ))) { return false; }
        return true;
    }

    public function register()
    {
        add_action( 'groundhogg/contact/preferences/updated'  , array( $this, 'send_sms_on'), 10, 4);
    }

    public function get_option_id()
    {
        return $this->option_id;
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
        foreach($this->get_sms_template_fields() as $sms_templates) {
            $setting_fields[] = $sms_templates;
        }
        return $setting_fields;
    }

    public function get_plugin_settings($with_identifier = false)
    {
        $settings = array(
            "notifysms_automation_enable_notification"        => notifysms_get_options("notifysms_automation_enable_notification", $this->get_option_id()),
            "notifysms_send_from"                             => notifysms_get_options('notifysms_automation_send_from', $this->get_option_id()),
            "notifysms_automation_send_on"                    => notifysms_get_options("notifysms_automation_send_on", $this->get_option_id()),
            "notifysms_automation_sms_template_confirmed"     => notifysms_get_options("notifysms_automation_sms_template_confirmed", $this->get_option_id()),
            "notifysms_automation_sms_template_unconfirmed"   => notifysms_get_options("notifysms_automation_sms_template_unconfirmed", $this->get_option_id()),
            "notifysms_automation_sms_template_unsubscribed"  => notifysms_get_options("notifysms_automation_sms_template_unsubscribed", $this->get_option_id()),
        );

        if ($with_identifier) {
            return array(
                self::$plugin_identifier => $settings,
            );
        }

        return $settings;
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
            'desc'    => __( 'Choose when to send a WhatsApp notification message', NOTIFY_TEXT_DOMAIN ),
            'type'    => 'multicheck',
            'options' => array(
                'confirmed'    => 'Confirmed',
                'unconfirmed'  => 'Unconfirmed',
                'unsubscribed' => 'Unsubscribed',
            )
        );
    }

    private function get_sms_template_fields() {
        return array(
            array(
                'name'    => 'notifysms_automation_sms_template_confirmed',
                'label'   => __( 'Confirmed status WhatsApp message', NOTIFY_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your WhatsApp with <button type="button" id="notifysms-open-keyword-%1$s-[dummy]" data-attr-type="customer" data-attr-target="%1$s[notifysms_automation_sms_template_confirmed]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], thank you for subscribing, trending contents will be delivered to you.', NOTIFY_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'notifysms_automation_sms_template_unconfirmed',
                'label'   => __( 'Unconfirmed status WhatsApp message', NOTIFY_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your WhatsApp with <button type="button" id="notifysms-open-keyword-%1$s-[dummy]" data-attr-type="lead" data-attr-target="%1$s[notifysms_automation_sms_template_unconfirmed]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], would you like to opt-in to our newsletter and get trending contents delivered to you ? We promise we will not spam you.', NOTIFY_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'notifysms_automation_sms_template_unsubscribed',
                'label'   => __( 'Unsubscribed status WhatsApp message', NOTIFY_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your WhatsApp with <button type="button" id="notifysms-open-keyword-%1$s-[dummy]" data-attr-type="refused" data-attr-target="%1$s[notifysms_automation_sms_template_unsubscribed]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], we are sorry to see you go, tell us how cna we improve to serve you better in the future ?', NOTIFY_TEXT_DOMAIN )
            ),
        );
    }

    public function get_keywords_field()
    {
        return array(
            'contacts' => array(
                'first_name',
                'last_name',
                'full_name',
                'email',
                'optin_status',
                'street_address_1',
                'street_address_2',
                'postal_zip',
                'city',
                'country',
                'primary_phone',
                'primary_phone_ext',
                'mobile_phone',
                'age',
                'company',
                'job_title',
                'date_of_birth',
            ),
        );

    }

    public function send_sms_on($pref_id, $new_pref, $old_pref, $contact)
    {
        $plugin_settings = $this->get_plugin_settings();
        $enable_notifications = $plugin_settings['notifysms_automation_enable_notification'];
        $send_on = $plugin_settings['notifysms_automation_send_on'];

        $status = $this->convert_optin_status($contact->get_optin_status());

        $this->log->add("360MessengerWhatsApp", "status: {$status}");

        if($enable_notifications === "on") {
            $this->log->add("360MessengerWhatsApp", "enable notifications: on");
            if(!empty($send_on) && is_array($send_on)) {
                if(array_key_exists($status, $send_on)) {
                    $this->log->add("360MessengerWhatsApp", "enable {$status} notifications: on");
                    $this->send_customer_notification($contact, $status);
                }
            }
        }

        return false;
    }

    public function send_customer_notification($contact, $status)
    {
        $this->log->add("360MessengerWhatsApp", "send_customer_notification status: {$status}");
        $settings = $this->get_plugin_settings();
        $sms_from = $settings['notifysms_automation_send_from'];

        // get number from args
        $phone_no = $contact->get_mobile_number();
        $phone_no = preg_replace('/[^0-9]/', '', $phone_no);
        if( !ctype_digit($phone_no) ) {
            $this->log->add("360MessengerWhatsApp", "phone_no is not a digit: {$phone_no}. Aborting...");
            return;
        }
        if( !empty($contact->country) ) {
            $country = $contact->country;
            $phone_no = Notify_SendSMS_Sms::get_formatted_number($phone_no, $country);
        }

        $this->log->add("360MessengerWhatsApp", "phone_no: {$phone_no}");

        // get message template from status
        $msg_template = $settings["notifysms_automation_sms_template_{$status}"];
        $message = $this->replace_keywords_with_value($contact, $msg_template);

        Notify_SendSMS_Sms::send_sms($sms_from, $phone_no, $message, $this->plugin_medium);
    }

    /*
        returns the message with keywords replaced to original value it points to
        eg: [name] => 'customer name here'
    */
    protected function replace_keywords_with_value($contact, $message)
    {
        // use regex to match all [stuff_inside]
        // return the message
        // preg_match_all('/\[(.*?)\]/', $message, $keywords);
        $address = $contact->get_address();

        $keywords = array(
            '[first_name]'          => $contact->get_first_name(),
            '[last_name]'           => $contact->get_last_name(),
            '[full_name]'           => $contact->get_full_name(),
            '[email]'               => $contact->get_email(),
            '[optin_status]'        => $this->convert_optin_status($contact->get_optin_status()),
            '[street_address_1]'    => !empty($address['street_address_1']) ? $address['street_address_1']  : '',
            '[street_address_2]'    => !empty($address['street_address_2']) ? $address['street_address_2']  : '',
            '[postal_zip]'          => !empty($address['postal_zip'])       ? $address['postal_zip ']       : '',
            '[city]'                => !empty($address['city'])             ? $address['city']              : '',
            '[country]'             => !empty($address['country'])          ? $address['country']           : '',
            '[primary_phone]'       => $contact->get_phone_number(),
            '[primary_phone_ext]'   => $contact->get_phone_extension(),
            '[mobile_phone]'        => $contact->get_mobile_number(),
            '[age]'                 => $contact->get_age(),
            '[company]'             => $contact->get_company(),
            '[job_title]'           => $contact->get_job_title(),
            '[date_of_birth]'       => $contact->get_meta("birthday") ? $contact->get_meta("birthday") : '',
        );

        return str_replace(array_keys($keywords), array_values($keywords), $message);
    }

    private function convert_optin_status($optin_status)
    {
        switch($optin_status) {
            // unconfirmed
            case 1:
                return "unconfirmed";
                break;
            case 2:
                return "confirmed";
                break;
            case 3:
                return "unsubscribed";
                break;
            default:
                return '';
                break;

        }
    }
}
