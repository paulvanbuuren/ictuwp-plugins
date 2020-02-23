<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class DLM_EN_Email_Handler {

	/**
	 * triggers on download
	 *
	 * @param DLM_Log_Item $log_item
	 * @param DLM_Download $download
	 * @param DLM_Download_Version $version
	 */
	public function trigger_notifications( $log_item, $download, $version ) {

		// check if notification must be send
		if ( 'all' === get_option( 'dlm_en_type', 'all' ) || '1' === get_post_meta( $download->get_id(), DLM_EN_Constants::META_NOTIFICATION_ENABLED, true ) ) {

			// send notifications
			$this->send_notifications( $log_item, $download, $version );
		}
	}

	/**
	 * Send notifications
	 *
	 * @param DLM_Log_Item $log_item
	 * @param DLM_Download $download
	 * @param DLM_Download_Version $version
	 */
	private function send_notifications( $log_item, $download, $version ) {

		// get email addresses
		$email_addresses = get_option( 'dlm_en_email_addresses', '' );

		// check if user entered email addresses
		if ( '' === $email_addresses ) {

			// use default admin email address
			$email_addresses = get_option( 'admin_email', '' );
		}

		// explode string on comma
		$email_addresses = explode( ',', $email_addresses );

		// loop
		if ( count( $email_addresses ) > 0 ) {

			// get site title
			$website_title = get_bloginfo( 'name' );
			if ( '' === $website_title ) {
				$website_title = 'Download Monitor';
			}

			// template handler
			$template_handler = new DLM_Template_Handler();

			$fields          = array();
			$selected_fields = DLM_EN_Fields::get_selected_fields();
			$fields_manager  = new DLM_EN_Fields( $log_item, $download, $version );
			if ( ! empty( $selected_fields ) ) {
				foreach ( $selected_fields as $field ) {
					$fields[] = $fields_manager->get_field_email_data( $field );
				}
			}

			// start buffer
			ob_start();

			// load template
			$template_handler->get_template_part( 'dlm-en-email-template', '', plugin_dir_path( DLM_Email_Notification::get_plugin_file() ) . 'templates/', array(
				'download' => $download,
				'fields'   => $fields
			) );

			// put template in var
			$email_body = ob_get_clean();

			// emails vars
			$email_body = str_ireplace( '%WEBSITE_URL%', $website_title, $email_body );

			/**
			 * legacy email vars. We keep these here for backwards compatibility
			 */
			// get current user
			$current_user = wp_get_current_user();
			$user_name    = 'Not logged in';
			$user_email   = 'Not logged in';
			if ( $current_user instanceof WP_User && isset( $current_user->ID ) && $current_user->ID > 0 ) {
				$user_name  = $current_user->user_login;
				$user_email = $current_user->user_email;
			}

			if ( ! class_exists( 'UAParser' ) ) {
				require_once( download_monitor()->get_plugin_path() . "/includes/admin/uaparser/uaparser.php" );
			}

			$uaparser = new UAParser();
			$ua       = $uaparser->parse( DLM_Utils::get_visitor_ua() );
			$email_body = str_ireplace( '%DOWNLOAD_NAME%', $download->get_title(), $email_body );
			$email_body = str_ireplace( '%USER%', $user_name, $email_body );
			$email_body = str_ireplace( '%USER_EMAIL%', $user_email, $email_body );
			$email_body = str_ireplace( '%IP_ADDRESS%', DLM_Utils::get_visitor_ip(), $email_body );
			$email_body = str_ireplace( '%USER_AGENT%', $ua->toFullString, $email_body );

			// add HTML filter
			add_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );

			// make email subject filterable
			$email_subject = apply_filters( 'dlm_en_email_subject', $website_title . ': New Download!' );

			// send email
			wp_mail( $email_addresses, $email_subject, $email_body );

			// remove HTML filter
			remove_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );

		}

	}

	/**
	 * HTML emails filter
	 *
	 * @return string
	 */
	public function set_html_content_type() {
		return 'text/html';
	}

}