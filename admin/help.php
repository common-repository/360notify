<?php
/* This file is forked from the original author Micro Ocean Technologies's MoceanAPI Order SMS Notification plugin on 7/1/2024 */

class Notify_Help_View implements Notify_Register_Interface {

	private $settings_api;

	function __construct() {
		$this->settings_api = new WeDevs_Settings_API;
	}

	public function register() {
        add_filter( 'notifysms_setting_section',     array($this, 'set_help_setting_section' ) );
		add_filter( 'notifysms_setting_fields',      array($this, 'set_help_setting_field' ) );
        add_action( 'notifysms_setting_fields_custom_html', array($this, 'display_help_page'), 10, 1);
	}

	public function set_help_setting_section( $sections ) {
		$sections[] = array(
            'id'               => 'notifysms_help_setting',
            'title'            => __( 'Help', NOTIFY_TEXT_DOMAIN ),
            'submit_button'    => '',
		);

		return $sections;
	}

	/**
	 * Returns all the settings fields
	 *
	 * @return array settings fields
	 */
	public function set_help_setting_field( $setting_fields ) {
		return $setting_fields;
	}

    public function display_help_page($form_id) {
        if($form_id !== 'notifysms_help_setting') { return; }
    ?>
        <br>
        <h4><?php _e( 'How to create an API key?', NOTIFY_TEXT_DOMAIN ); ?></h4>
        <p><?php _e( 'If you want to use the plugin for whatsapp notification, you need to create an API key. You can do this by creating an account <a href="https://app.360messenger.com"><strong>here</strong></a>.  The account creation is 7days free.', NOTIFY_TEXT_DOMAIN ); ?></p>
        <h4><?php _e( 'Have questions?', NOTIFY_TEXT_DOMAIN ); ?></h4>
        <p><?php _e( 'If you have any questions or feedbacks, you can send a message to our support team and we will get back to you as soon as possible at our <a href="https://app.360messenger.com" target="_blank">page</a>.', NOTIFY_TEXT_DOMAIN ); ?></p>
    <?php
    }


}

?>
