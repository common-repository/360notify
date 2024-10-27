<?php
/* This file is forked from the original author Micro Ocean Technologies's MoceanAPI Order SMS Notification plugin on 7/1/2024 */

namespace NotifyAPI_WC\Forms\Handlers;

use NotifyAPI_WC\Helpers\Sanitization;
use \Notify_WooCoommerce_Logger;

class ContactForm7 {

    private $_option_prefix;
    private $log;
    public function __construct() {
        $this->_option_prefix = 'notifysms_wpcf7_';
        $this->log = new Notify_WooCoommerce_Logger();
        // add_filter( 'wpcf7_validate_text*', array( $this, 'validateFormPost' ), 1, 2 );
		add_filter( 'wpcf7_validate_tel', array( $this, 'validateFormPost' ), 1, 2 );
		add_filter( 'wpcf7_validate_tel*', array( $this, 'validateFormPost' ), 1, 2 );
		add_filter( 'wpcf7_validate_notify_phone', array( $this, 'validateFormPost' ), 10, 2 );
		add_filter( 'wpcf7_validate_notify_phone*', array( $this, 'validateFormPost' ), 10, 2 );
		add_filter( 'wpcf7_messages', array( $this, 'wpcf7_notify_phone_messages' ), 10, 1 );

		add_filter( 'wpcf7_editor_panels', array( $this, 'new_menu_notify' ), 10, 1 );

		add_action( 'wpcf7_admin_init', array( $this, 'add_notifyapi_phone_tag' ), 20, 0 );
        add_action( 'wpcf7_after_save', array( &$this, 'save_form' ) );
		add_action( 'wpcf7_before_send_mail', array( $this, 'sendsms_c7' ) );

		add_action( 'wpcf7_init', array( $this, 'notifyapi_wpcf7_add_shortcode_phonefield_frontend' ) );
		add_action( 'wpcf7_admin_notices', array( $this,'notifyapi_wpcf7_show_warnings'), 10, 3 );

    }

    public function get_contact_form_id($form)
    {
        return method_exists( $form, 'id' ) ? $form->id() : $form->id;
    }

    public function save_form($form) {
        // identifier = notifyapi_sms_wpcf7_{id}
        /* array (
            visitor_notification,
            visitor_mobile_field,
            visitor_sms_template,
            admin_notification,
            admin_mobile_numbers,
            admin_sms_template
        )
        */
        $wpcf7notifyapi_settings = ( ! empty( $_POST['wpcf7notifyapi-settings'] ) ) ? wp_unslash( $_POST['wpcf7notifyapi-settings'] ) : '';
		update_option( $this->_option_prefix . $this->get_contact_form_id($form), Sanitization::notifyapi_sanitize_array( $wpcf7notifyapi_settings ) );
    }

    /**
	 * Send WhatsApp if cf7 form submitted successfully.
	 *
	 * @param object $form form object.
	 *
	 * @return void
	 */

	public function sendsms_c7( $form ) {
        $this->log->add("360MessengerWhatsApp", "Initiating Send WhatsApp c7");
		$options         = get_option( $this->_option_prefix . $this->get_contact_form_id($form) );
		$send_to_admin   = false;
		$send_to_visitor  = false;
		$admin_numbers    = [];
		$admin_message   = '';
		$visitor_number  = '';
		$visitor_message = '';

		if ( !empty( $options['admin_notification'] ) && 'on' === $options['admin_notification'] && ! empty( $options['admin_mobile_numbers'] ) && ! empty( $options['admin_sms_template'] ) ) {

			$admin_numbers_comma_sep  = $this->convert_cf7_tags_to_value( $options['admin_mobile_numbers'], $form );
            $admin_numbers = explode(",", $admin_numbers_comma_sep);
			$admin_message = $this->convert_cf7_tags_to_value( $options['admin_sms_template'], $form );
			$send_to_admin = true;
		}

		$visitor_number = $this->convert_cf7_tags_to_value( "[{$options['visitor_mobile_field']}]", $form );

		if ( !empty( $options['visitor_notification'] ) && 'on' === $options['visitor_notification'] && !empty( $options['visitor_mobile_field'] ) && !empty( $options['visitor_sms_template'] ) ) {
			$visitor_message = $this->convert_cf7_tags_to_value( $options['visitor_sms_template'], $form );
			$send_to_visitor  = true;
		}

		if ( $send_to_admin ) {
            $this->log->add("360MessengerWhatsApp", "Sending WhatsApp c7 to admin");

            foreach ($admin_numbers as $admin_number) {
                $validated_admin_number = \Notify_SendSMS_Sms::get_formatted_number($admin_number);
                \Notify_SendSMS_Sms::send_sms('', $validated_admin_number, $admin_message);
            }
		}

		if ( $send_to_visitor ) {
            $this->log->add("360MessengerWhatsApp", "Sending WhatsApp c7 to visitor");
            $validated_visitor_number = \Notify_SendSMS_Sms::get_formatted_number($visitor_number);
            \Notify_SendSMS_Sms::send_sms('', $validated_visitor_number, $visitor_message);
		}

	}

    /**
	 * Get CF7 tags to string.
	 *
	 * @param string $value value.
	 * @param object $form form object.
	 *
	 * @return bool
	 */
    public function convert_cf7_tags_to_value( $value, $form ) {
        $result = '';
		if ( function_exists( 'wpcf7_mail_replace_tags' ) ) {
			$result = wpcf7_mail_replace_tags( $value );
		} elseif ( method_exists( $form, 'replace_mail_tags' ) ) {
			$result = $form->replace_mail_tags( $value );
		} else {
			return;
		}
		return $result;
	}

    public function new_menu_notify( $panels ) {
		$panels['notify-sms-panel'] = array(
			'title'    => __( '360Notify' , NOTIFY_TEXT_DOMAIN ),
			'callback' => array( $this, 'add_panel_notify' ),
		);
		return $panels;
	}

    /**
	 * Add phonefield to backend cf7 form builder section.
	 *
	 * @return void
	 */
	public function notifyapi_wpcf7_add_shortcode_phonefield_frontend() {
		wpcf7_add_form_tag(
			array( 'notify_phone', 'notify_phone*'),
			array( $this, 'notifyapi_wpcf7_shortcode_handler' ),
			true
		);
	}

	/**
	 * Add tab panel to contact form 7 form
	 *
	 * @param object $form form object.
	 *
	 * @return void
	 */
	public function add_panel_notify( $form ) {
		if ( wpcf7_admin_has_edit_cap() ) {
			$options = get_option( $this->_option_prefix . $this->get_contact_form_id($form) );
			if ( empty( $options ) || ! is_array( $options ) ) {
                // default options
                $default_visitor_sms_template = "Hi [your-name], we've received your submission. We'll get back to you soon";
                $default_admin_sms_template = "Hi Admin, you've received a form submission from [your-name].";

				$options = array(
                    'visitor_notification'  => 'off',
                    'visitor_mobile_field'  => '',
                    'visitor_sms_template'  => $default_visitor_sms_template,
                    'admin_notification'    => 'off',
                    'admin_mobile_numbers'  => '',
                    'admin_sms_template'    => $default_admin_sms_template,
				);
			}
			$options['_form'] = $form;
			$data            = $options;
			include NOTIFY_PLUGIN_DIR . '/src/Forms/Views/ContactForm7View.php';
		}
	}

    public function add_notifyapi_phone_tag()
    {
        if ( class_exists( 'WPCF7_TagGenerator' ) ) {
			$tag_generator = \WPCF7_TagGenerator::get_instance();
			$tag_generator->add( 'notify_phone', __( '360Notify PHONE', 'contact-form-7' ), array( $this, 'notifyapi_wpcf7_tag_generator_text' ) );
		}
    }

        /**
	 * Handle notify wpcf7 shortcode.
	 *
	 * @param  object $tag get tag objects.
	 *
	 * @return string
	 */
	public function notifyapi_wpcf7_shortcode_handler( $tag ) {
		$wpcf7    = wpcf7_get_current_contact_form();
		$unit_tag = $wpcf7->unit_tag();

		$tag = new \WPCF7_FormTag( $tag );
		if ( empty( $tag->name ) ) {
			return '';
		}

		$validation_error = wpcf7_get_validation_error( $tag->name );

		$class = wpcf7_form_controls_class( $tag->type, 'wpcf7-notifyapi' );
		if ( $validation_error ) {
			$class .= ' wpcf7-not-valid';
		}

		$atts = array();

		$atts['size']      = $tag->get_size_option( '40' );
		$atts['maxlength'] = $tag->get_maxlength_option();
		$atts['minlength'] = $tag->get_minlength_option();

		if ( $atts['maxlength'] && $atts['minlength'] && $atts['maxlength'] < $atts['minlength'] ) {
			unset( $atts['maxlength'], $atts['minlength'] );
		}
		$atts['class']    = $tag->get_class_option( $class );
		$atts['id']       = $tag->get_id_option();
		$atts['tabindex'] = $tag->get_option( 'tabindex', 'int', true );

		if ( $tag->has_option( 'readonly' ) ) {
			$atts['readonly'] = 'readonly';
		}

		if ( $tag->is_required() ) {
			$atts['aria-required'] = 'true';
		}

		$atts['aria-invalid'] = $validation_error ? 'true' : 'false';

		$value       = (string) reset( $tag->values );
		if ( $tag->has_option( 'placeholder' ) || $tag->has_option( 'watermark' ) ) {
			$atts['placeholder'] = $value;
			$value               = '';
		}
		$value = $tag->get_default_option( $value );
		$value = wpcf7_get_hangover( $tag->name, $value );
		$scval = do_shortcode( '[' . $value . ']' );

		if ( '[' . $value . ']' !== $scval ) {
			$value = esc_attr( $scval );
		}

		$atts['value'] = $value;
		$atts['type']  = 'tel';
		$atts['name']  = $tag->name;
		$atts          = wpcf7_format_atts( $atts );

		$html = sprintf(
			'<span class="wpcf7-form-control-wrap" data-name="%1$s"><input %2$s />%3$s</span>',
			sanitize_html_class( $tag->name ),
			$atts,
			$validation_error
		);

		return $html;
	}

    	/**
	 * Tag generator form for notifyapi phone tag in cf7 backend
	 *
	 * @param object $contact_form cf7 form object.
	 * @param array  $args cf7 form arguments.
	 *
	 * @return void
	 */
	public function notifyapi_wpcf7_tag_generator_text( $contact_form, $args = '' ) {
		$args = wp_parse_args( $args, array() );
		$type = $args['id'];
        $field_name = 'notify_phone';
		?>
        <div class="control-box">
        <fieldset>

        <table class="form-table">
        <tbody>
            <tr>
            <th scope="row"><?php esc_html_e( 'Field type', 'contact-form-7' ); ?></th>
            <td>
                <fieldset>
                <legend class="screen-reader-text"><?php esc_html_e( 'Field type', 'contact-form-7' ); ?></legend>
                <label><input type="checkbox" name="required" checked="checked"/> <?php esc_html_e( 'Required field', 'contact-form-7' ); ?></label>
                </fieldset>
            </td>
            </tr>

            <tr>
                <th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php esc_html_e( 'Name', 'contact-form-7' ); ?></label></th>

                <td>
                    <input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" value="<?php echo esc_attr( $field_name ); ?>" />
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-values' ); ?>"><?php esc_html_e( 'Default value', 'contact-form-7' ); ?></label></th>
                <td><input type="text" name="values" class="oneline" id="<?php echo esc_attr( $args['content'] . '-values' ); ?>" /><br />
                <label><input type="checkbox" name="placeholder" class="option" /> <?php esc_html_e( 'Use this text as the placeholder of the field', 'contact-form-7' ); ?></label></td>
            </tr>
            <tr>
                <th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-id' ); ?>"><?php esc_html_e( 'Id attribute', 'contact-form-7' ); ?></label></th>
                <td><input type="text" name="id" class="idvalue oneline option" id="<?php echo $args['content'] . '-id'; ?>" /></td>
            </tr>

            <tr>
                <th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-class' ); ?>"><?php esc_html_e( 'Class attribute', 'contact-form-7' ); ?></label></th>
                <td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-class' ); ?>" /></td>
            </tr>
        </tbody>
        </table>
        </fieldset>
        </div>

        <div class="insert-box">
            <input type="text" name="<?php echo esc_attr( $type ); ?>" class="tag code" readonly="readonly" onfocus="this.select()" />

            <div class="submitbox">
            <input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'contact-form-7' ) ); ?>" />
            </div>

            <br class="clear" />

            <p class="description mail-tag"><label for="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>"><?php echo wp_kses_post( sprintf( __( 'To use the value input through this field in a mail field, you need to insert the corresponding mail-tag (%s) into the field on the Mail tab.', 'sms-alert' ), '<strong><span class="mail-tag"></span></strong>' ) ); ?><input type="text" class="mail-tag code hidden" readonly="readonly" id="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>" /></label></p>
        </div>
		<?php
	}

    /**
	 * Validate form post for notify_phone field at frontend.
	 *
	 * @param object $result result from cf7 object.
	 * @param object $tag tag object.
	 *
	 * @return object
	 */
	public function validateFormPost( $result, $tag ) {
		$tag  = new \WPCF7_FormTag( $tag );
		$name = $tag->name;
		// $value = ( ! empty( $_POST[ $name ] ) ) ? trim( sanitize_text_field( wp_unslash( strtr( (string) $_POST[ $name ] ), "\n", ' ' ) ) ) : '';
		$value = ( ! empty( $_POST[ $name ] ) ) ? trim( sanitize_text_field( wp_unslash( $_POST[ $name ] ) ) ) : '';

		if ( in_array( $tag->basetype, array( 'notify_phone' ), true ) ) {
            if($tag->is_required()) {
                if(empty( $value )) {
                    $result->invalidate( $tag, wpcf7_get_message( 'invalid_required' ) );
                } else {
                    if ( ! \Notify_SendSMS_Sms::get_formatted_number( $value ) ) {
                        $result->invalidate( $tag, wpcf7_get_message( 'notify_invalid_number' ) );
                    }
                }
            } else  {
                if(!empty( $value ) && ! \Notify_SendSMS_Sms::get_formatted_number( $value ) ) {
                    $result->invalidate( $tag, wpcf7_get_message( 'notify_invalid_number' ) );
                }
            }

            if( ! empty( $value ) && ! wpcf7_is_tel( $value ) ) {
                $result->invalidate( $tag, wpcf7_get_message( 'invalid_tel' ) );
            }

			$maxlength = $tag->get_maxlength_option();
			$minlength = $tag->get_minlength_option();

			if ( $maxlength && $minlength
			&& $maxlength < $minlength ) {
				$maxlength = null;
				$minlength = null;
			}

			$code_units = wpcf7_count_code_units( stripslashes( $value ) );

			if ( false !== $code_units ) {
				if ( $maxlength && $maxlength < $code_units ) {
					$result->invalidate( $tag, wpcf7_get_message( 'invalid_too_long' ) );
				} elseif ( $minlength && $code_units < $minlength ) {
					$result->invalidate( $tag, wpcf7_get_message( 'invalid_too_short' ) );
				}
			}

		}

		return $result;
	}

    /**
	 * Set validation error for billing phone for frontend form.
	 *
	 * @param array $messages error messages.
	 *
	 * @return object
	 */
	public function wpcf7_notify_phone_messages( $messages ) {
		return array_merge(
			$messages,
			array(
				'notify_invalid_number' => array(
					'description' => __( 'Invalid number', NOTIFY_TEXT_DOMAIN ),
					'default'     => __( 'Invalid number', NOTIFY_TEXT_DOMAIN ),
				),
			)
		);
	}

    /**
	 * Show warning if notify_phone phone field not selected.
	 *
	 * @param  object $page get page objects.
	 * @param  object $action get action objects.
	 * @param  object $object get object objects.
	 *
	 * @return void
	 */
	function notifyapi_wpcf7_show_warnings($page,$action,$object)
	{
		if ( ! in_array( $page, array( 'wpcf7', 'wpcf7-new' ) ) )
		{
			return;
		}
		if(!empty($_REQUEST['post'])){
			$options         = get_option( $this->_option_prefix . $_REQUEST['post'] );
			if ( empty($options['visitor_mobile_field']) )
			{
				echo sprintf(
					'<div id="message" class="notice notice-warning"><p>%s</p></div>',
					esc_html__( "Please choose mobile number field in 360Notify tab", NOTIFY_TEXT_DOMAIN)
				);
			}
		}
	}
}