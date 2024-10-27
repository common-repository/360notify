<?php
/* This file is forked from the original author Micro Ocean Technologies's MoceanAPI Order SMS Notification plugin on 7/1/2024 */

/**
 * Created by PhpStorm.
 * User: Neoson Lam
 * Date: 2/25/2019
 * Time: 9:59 AM.
 */

class Notify_WooCommerce_Widget implements Notify_Register_Interface {
	protected $log;

	public function __construct( Notify_WooCoommerce_Logger $log = null ) {
		if ( $log === null ) {
			$log = new Notify_WooCoommerce_Logger();
		}

		$this->log = $log;
	}

	public function register() {
		add_action( 'wp_dashboard_setup', array( $this, 'register_widget' ) );
	}

	public function register_widget() {
		wp_add_dashboard_widget( 'msmswc_dashboard_widget', 'NotifySMS', array( $this, 'display_widget' ) );
	}

	public function display_widget() {
		$api_key        = notifysms_get_options( 'notifysms_woocommerce_api_key', 'notifysms_setting', '' );
		$api_secret     = notifysms_get_options( 'notifysms_woocommerce_api_secret', 'notifysms_setting', '' );
		$notifysms_rest = new NotifySMS( $api_key, $api_secret );
		try {
			//$balance = json_decode( $notifysms_rest->accountBalance() );

			if ( $api_key && $api_secret ) {
				?>

                <!--<h3><?php echo $balance->status === 0 ? "Balance: $balance->value" : urldecode( $balance->err_msg ) ?></h3>-->

				<?php
			} else {
				?>

                <h3>
                    Please setup API Key and API Secret in
                    <a href="<?php echo admin_url( 'options-general.php?page=360notify-woocoommerce-setting' ) ?>">
                        360Notify settings
                    </a>
                </h3>

				<?php
			}
		} catch ( Exception $exception ) {
			//errors in curl
			$this->log->add( '360MessengerWhatsApp', 'Failed get balance: ' . $exception->getMessage() );
			?>

            <h3>
                There's some problem while showing balance, please refresh this page and try again.
            </h3>

			<?php
		}
	}
}
