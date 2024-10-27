<?php
/* This file is forked from the original author Micro Ocean Technologies's MoceanAPI Order SMS Notification plugin on 7/1/2024 */

class Notify_SMSOutbox_View implements Notify_Register_Interface {

	private $settings_api;

	function __construct() {
		$this->settings_api = new WeDevs_Settings_API;
	}

	public function register() {
        add_filter( 'notifysms_setting_section', array($this, 'set_smsoutbox_setting_section' ) );
		add_filter( 'notifysms_setting_fields',  array($this, 'set_smsoutbox_setting_field' ) );
        add_action( 'notifysms_setting_fields_custom_html', array($this, 'display_smsoutbox_page'), 10, 1);
	}

	public function set_smsoutbox_setting_section( $sections ) {
		$sections[] = array(
            'id'               => 'notifysms_smsoutbox_setting',
            'title'            => __( 'WhatsApp Outbox', NOTIFY_TEXT_DOMAIN ),
            'submit_button'    => '',
            // 'action'           => 'notifysms_sms_form',
            // 'action_url'       => admin_url('admin-post.php'),
		);

		return $sections;
	}

	/**
	 * Returns all the settings fields
	 *
	 * @return array settings fields
	 */
	public function set_smsoutbox_setting_field( $setting_fields ) {
		return $setting_fields;
	}

    public function display_smsoutbox_page($form_id) {
        if($form_id != 'notifysms_smsoutbox_setting') { return; }
        global $wpdb;
        $db_table_name = NOTIFY_DB_TABLE_NAME;
        $count_result = $wpdb->get_results ( "SELECT count(*) as count FROM {$db_table_name}" );

        $count = $count_result[0];
        $total = $count->count;
        $total_page = ceil($total / 10);
        $total_show_pages= 5;
        $middle_page_add_on_number = floor($total_show_pages/2);
        if(isset($_GET['pageno'])){
            $current_page = sanitize_text_field($_GET['pageno']);
        }else{
            $current_page = 1;
        }

        if($total_page < $total_show_pages)
        {
            $start_page = 1;
            $end_page = $total_page;
        }
        else
        {
            if(($current_page + $middle_page_add_on_number) > $total_page)
            {
                $start_page = $total_page - $total_show_pages + 1;
                $end_page = $total_page;
            }
        else if($current_page > $middle_page_add_on_number)
            {
                $start_page = $current_page - $middle_page_add_on_number;
                $end_page = $start_page + $total_show_pages - 1;
            }
            else
            {
                $start_page = 1;
                $end_page = $total_show_pages;
            }
        }

        $first_page = 1;
        $last_page = ($total_page > 0 ? $total_page : $first_page);
        $previous_page = ($current_page>1 ? $current_page -1 : 1);
        $next_page = ($current_page<$total_page ? $current_page +1 : $last_page);

        $admin_url = admin_url('options-general.php?page=360notify-woocoommerce-setting');
        $pageno = ($current_page - 1) * 10;
    ?>
        <br>
        <div class="bootstrap-wrapper">

            <nav aria-label="Page navigation example">
            <ul class="pagination">
                <li class="page-item"><a class="page-link" href="<?php echo $admin_url.'&pageno='.$first_page ?>"><?php _e( 'First', NOTIFY_TEXT_DOMAIN ); ?></a></li>
                <li class="page-item"><a class="page-link" href="<?php echo $admin_url.'&pageno='.$previous_page ?>"><?php _e( 'Previous', NOTIFY_TEXT_DOMAIN ); ?></a></li>
                <?php for($i=$start_page; $i<=$end_page; $i++) { ?>
                    <li class="page-item"><a class="page-link" href="<?php echo $admin_url.'&pageno='.$i ?>"><?php echo $i; ?></a></li>
                <?php } ?>
                <li class="page-item"><a class="page-link" href="<?php echo $admin_url.'&pageno='.$next_page ?>"><?php _e( 'Next', NOTIFY_TEXT_DOMAIN ); ?></a></li>
                <li class="page-item"><a class="page-link" href="<?php echo $admin_url.'&pageno='.$last_page ?>"><?php _e( 'Last', NOTIFY_TEXT_DOMAIN ); ?></a></li>
            </ul>
            </nav>


            <span><?php _e( 'Page : ', NOTIFY_TEXT_DOMAIN ); ?><?php echo $current_page; ?></span>
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col" id='date'><?php _e( 'Date', NOTIFY_TEXT_DOMAIN ); ?></th>
                        <th scope="col" id='sender'><?php _e( 'Sender', NOTIFY_TEXT_DOMAIN ); ?></th>
                        <th scope="col" id='recipient'><?php _e( 'Recipient', NOTIFY_TEXT_DOMAIN ); ?></th>
                        <th scope="col" id='message'><?php _e( 'Message', NOTIFY_TEXT_DOMAIN ); ?></th>
                        <th scope="col" id='message'><?php _e( 'Status', NOTIFY_TEXT_DOMAIN ); ?></th>
                    </tr>
                </thead>
            <tbody id="the-list" data-wp-lists='list:id'>
            <?php
                global $wpdb;
                $result = $wpdb->get_results ( "SELECT * FROM {$db_table_name} ORDER BY id DESC LIMIT ".$pageno.",10" );

                foreach ( $result as $print ) {
                ?>
                <tr>
                <td><?php echo esc_attr($print->date);?></td>
                <td><?php echo esc_attr($print->sender);?></td>
                <td><?php echo esc_attr($print->recipient);?></td>
                <td><?php echo esc_attr($print->message);?></td>
                <td><?php echo esc_attr($print->status);?></td>
                </tr>
                    <?php }
            ?>
            </tbody>

            </table>
        </div>

    <?php
    }


}

?>
