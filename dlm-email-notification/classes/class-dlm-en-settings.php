<?php

class DLM_EN_Settings {

	/**
	 * Add settings to DLM settings
	 *
	 * @param $settings
	 *
	 * @return mixed
	 */
	public static function add_settings( $settings ) {

		$fields_description = __( 'What fields should be included in the email. Separate multiple fields by comma(,).', 'dlm-email-notification' );
		$fields_description .= "<br/>";
		$fields_description .= __( 'Available fields', 'dlm-email-notification' );;
		$fields_description .= ": ";
		$fields_description .= "<code>" . implode( "</code>, <code>", DLM_EN_Fields::get_available_fields() ) . "</code>";

		$settings['email_notification'] = array(
			__( 'Email Notification', 'dlm-email-notification' ),
			array(
				array(
					'name'    => 'dlm_en_type',
					'std'     => 'all',
					'label'   => __( 'Send notifications for', 'dlm-email-notification' ),
					'desc'    => __( 'You can send notifications for every or just selected downloads files. When "Selected Downloads" is selected, turn on notifications per download in the download edit screen.', 'dlm-email-notification' ),
					'type'    => 'select',
					'options' => array(
						'all'      => __( 'All Downloads', 'dlm-email-notification' ),
						'selected' => __( 'Selected Downloads', 'dlm-email-notification' )
					)
				),
				array(
					'name'        => 'dlm_en_email_addresses',
					'type'        => 'text',
					'std'         => get_option( 'admin_email', '' ),
					'label'       => __( 'Email Addresses', 'dlm-email-notification' ),
					'placeholder' => get_option( 'admin_email', '' ),
					'desc'        => __( 'Define which email addresses will receive download notifications. Separate multiple addresses by comma(,). ', 'dlm-email-notification' )
				),
				array(
					'name'        => 'dlm_en_fields',
					'type'        => 'text',
					'std'         => implode( ',', DLM_EN_Fields::get_default_fields() ),
					'label'       => __( 'Email Fields', 'dlm-email-notification' ),
					'placeholder' => get_option( 'admin_email', '' ),
					'desc'        => $fields_description
				),
			),
		);

		return $settings;
	}

}