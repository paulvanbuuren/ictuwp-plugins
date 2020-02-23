<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class DLM_EN_Download_Option {

	/**
	 * Setup the class
	 */
	public function setup() {

		// Add email notification download option
		add_action( 'dlm_options_end', array( $this, 'add_download_option' ), 10, 1 );

		// Save download options
		add_action( 'dlm_save_metabox', array( $this, 'save_download_option' ), 10, 1 );

	}

	/**
	 * Add email notification to download options
	 *
	 * @param $post_id
	 */
	public function add_download_option( $post_id ) {
		echo '<p class="form-field form-field-checkbox">
			<input type="checkbox" name="' . DLM_EN_Constants::META_NOTIFICATION_ENABLED . '" id="' . DLM_EN_Constants::META_NOTIFICATION_ENABLED . '" ' . checked( get_post_meta( $post_id, DLM_EN_Constants::META_NOTIFICATION_ENABLED, true ), '1', false ) . ' />
			<label for="' . DLM_EN_Constants::META_NOTIFICATION_ENABLED . '">' . __( 'Email Notification', 'dlm-email-notification' ) . '</label>
			<span class="dlm-description">' . __( 'Checking this will enable email notifications for this download.', 'dlm-email-notification' ) . '</span>
		</p>';
	}

	/**
	 * Save download option
	 *
	 * @param $post_id
	 */
	public function save_download_option( $post_id ) {
		$notification_enabled = ( isset( $_POST[ DLM_EN_Constants::META_NOTIFICATION_ENABLED ] ) ) ? '1' : '0';
		if( '1' === $notification_enabled ) {
			update_post_meta( $post_id, DLM_EN_Constants::META_NOTIFICATION_ENABLED, $notification_enabled );
		}else {
			delete_post_meta( $post_id, DLM_EN_Constants::META_NOTIFICATION_ENABLED );
		}
	}

}