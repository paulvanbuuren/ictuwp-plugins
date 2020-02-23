<?php

/*
	Plugin Name: Download Monitor - Email Notification
	Plugin URI: https://www.download-monitor.com/extensions/email-notification/
	Description: The Email Notification extension for Download Monitor sends you an email whenever one of your files is downloaded.
	Version: 4.1.1
	Author: Never5
	Author URI: http://www.never5.com/
	License: GPL v3
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

class DLM_Email_Notification {

	const VERSION = '4.1.1';

	public function __construct() {

		// Admin only classes
		if ( is_admin() && 'selected' === get_option( 'dlm_en_type', '' ) ) {

			// Download Option
			$download_option = new DLM_EN_Download_Option();
			$download_option->setup();
		}

		// admin
		if ( is_admin() ) {
			// add settings
			add_filter( 'download_monitor_settings', array( 'DLM_EN_Settings', 'add_settings' ) );
		}

		// frontend
		if ( ! is_admin() ) {

			// the email handler
			$email_handler = new DLM_EN_Email_Handler();

			// hook into dlm_downloading_log_item_added.
			add_action( 'dlm_downloading_log_item_added', array( $email_handler, 'trigger_notifications' ), 10, 3 );
		}

		// Register Extension
		add_filter( 'dlm_extensions', array( $this, 'register_extension' ) );
	}

	/**
	 * Get the plugin file
	 *
	 * @static
	 *
	 * @return String
	 */
	public static function get_plugin_file() {
		return __FILE__;
	}

	/**
	 * Register this extension
	 *
	 * @param array $extensions
	 *
	 * @return array $extensions
	 */
	public function register_extension( $extensions ) {

		$extensions[] = array(
			'file'    => 'dlm-email-notification',
			'version' => self::VERSION,
			'name'    => 'Email Notification'
		);

		return $extensions;
	}

}

require_once dirname( __FILE__ ) . '/vendor/autoload_52.php';

function __dlm_email_notification() {
	new DLM_Email_Notification();
}

add_action( 'plugins_loaded', '__dlm_email_notification' );