<?php
/* This file is forked from the original author Micro Ocean Technologies's MoceanAPI Order SMS Notification plugin on 7/1/2024 */

class Notify_WooCoommerce_Logger {

	private $_handles;
	private $log_directory;

	public function __construct() {
		$upload_dir          = wp_upload_dir();
		$this->log_directory = $upload_dir['basedir'] . '/360notify-woocommerce-logs/';

		wp_mkdir_p( $this->log_directory );
		chmod( $this->log_directory, 0700 );
	}

	private function open( $handle ) {
		if ( isset( $this->_handles[ $handle ] ) ) {
			return true;
		}

		if ( $this->_handles[ $handle ] = @fopen( $this->log_directory . $handle . '.log', 'a' ) ) {
			return true;
		}

		return false;
	}

	public function add( $handle, $message ) {
        if ( notifysms_get_options( 'export_notifysms_log', 'notifysms_setting', 'off' ) == 'on' ) {
    		if ( $this->open( $handle ) ) {
    			$current_datetime = date( 'Y-m-d H:i:s' );
    			@fwrite( $this->_handles[ $handle ], "$current_datetime $message\n" );
    		}
        }
	}

    public function get_log_file($handle)
    {
        $log_file = $this->log_directory . "{$handle}.log"; //The log file.
        if(file_exists($log_file)){
            return file_get_contents($log_file);
        }
    }

    public function get_log_file_path($handle)
    {
        return $this->log_directory . "{$handle}.log";
    }
}

?>
