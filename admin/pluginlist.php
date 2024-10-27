<?php

function wk_plugin_settings_link( $links )
{
    $url = admin_url('options-general.php?page=360notify-woocoommerce-setting');
    $_link = '<a href="'.$url.'">' . __( 'Settings', NOTIFY_TEXT_DOMAIN ) . '</a>';
    $links[] = $_link;
    return $links;
}

add_filter( 'plugin_row_meta', 'custom_plugin_row_meta', 10, 2 );

function custom_plugin_row_meta( $links, $file ) {

	if ( strpos( $file, '360notify-woocommerce.php' ) !== false ) {
		$new_links = array(
			'<a href="https://360messenger.com/api" target="_blank">' . __( 'API docs', NOTIFY_TEXT_DOMAIN ) . '</a>'
			);
		
		$links = array_merge( $links, $new_links );
	}
	
	return $links;
}

function plugin_init() {

	load_plugin_textdomain( NOTIFY_TEXT_DOMAIN, false, dirname(plugin_basename(__FILE__)).'/languages/' );

}
