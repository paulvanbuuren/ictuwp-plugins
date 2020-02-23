<?php

class DLM_EN_Fields {

	/** @var DLM_Log_Item */
	private $log_item;

	/** @var DLM_Download */
	private $download;

	/** @var DLM_Download_Version */
	private $version;

	/**
	 * Get available fields
	 *
	 * @return array
	 */
	public static function get_available_fields() {
		return apply_filters( 'dlm_en_available_fields', array(
			'download_id',
			'download_name',
			'version',
			'user',
			'user_email',
			'ip_address',
			'user_agent'
		) );
	}

	/**
	 * Get default fields. These will be used if user didn't define any fields.
	 *
	 * @return array
	 */
	public static function get_default_fields() {
		return apply_filters( 'dlm_en_default_fields', array( 'download_name', 'version', 'user', 'ip_address' ) );
	}

	/**
	 * Get user selected fields. These are set in options.
	 * If fields is empty, default fields are returned.
	 *
	 * @return array
	 */
	public static function get_selected_fields() {
		$options = get_option( 'dlm_en_fields', array() );

		if ( ! empty( $options ) ) {
			$options = explode( ',', $options );
		} else {
			$options = self::get_default_fields();
		}

		return apply_filters( 'dlm_en_selected_fields', $options );
	}

	public function __construct( $log_item, $download, $version ) {
		$this->log_item = $log_item;
		$this->download = $download;
		$this->version  = $version;
	}

	/**
	 * @param string $field_name
	 *
	 * @return array('label' => '', 'value' => '')
	 */
	public function get_field_email_data( $field_name ) {

		// trim the field name
		$field_name = trim( $field_name );

		// default values
		$field = array( 'label' => '', 'value' => '' );

		switch ( $field_name ) {
			case 'download_id':
				$field['label'] = __( 'Download ID', 'dlm-email-notification' );
				$field['value'] = $this->download->get_id();
				break;
			case 'download_name':
				$field['label'] = __( 'Download Name', 'dlm-email-notification' );
				$field['value'] = $this->download->get_title();
				break;
			case 'version':
				$field['label'] = __( 'Version', 'dlm-email-notification' );
				$field['value'] = $this->download->get_version()->get_version();
				break;
			case 'user':
				$field['label'] = __( 'User', 'dlm-email-notification' );
				$field['value'] = "-";

				// get user by user id
				$user = get_user_by( 'id', $this->log_item->get_user_id() );
				if ( $user instanceof WP_User && isset( $user->ID ) && $user->ID > 0 ) {
					$field['value'] = $user->user_login;
				}
				break;
			case 'user_email':
				$field['label'] = __( 'User Email', 'dlm-email-notification' );
				$field['value'] = "-";

				// get user by user id
				$user = get_user_by( 'id', $this->log_item->get_user_id() );
				if ( $user instanceof WP_User && isset( $user->ID ) && $user->ID > 0 ) {
					$field['value'] = $user->user_email;
				}
				break;
			case 'ip_address':
				$field['label'] = __( 'IP Address', 'dlm-email-notification' );
				$field['value'] = $this->log_item->get_user_ip();
				break;
			case 'user_agent':
				if ( ! class_exists( 'UAParser' ) ) {
					require_once( download_monitor()->get_plugin_path() . "/includes/admin/uaparser/uaparser.php" );
				}

				// parse user agent
				$uaparser = new UAParser();
				$ua       = $uaparser->parse( $this->log_item->get_user_agent() );

				$field['label'] = __( 'User Agent', 'dlm-email-notification' );
				$field['value'] = $ua->toFullString;
				break;
			default:
				/**
				 * When the key is not found in our buildin set, it might be added by third party software.
				 * This is where we allow them to filter the field and add their own field data.
				 */
				$field = apply_filters( 'dlm_en_field_' . $field_name, $field, $this->log_item, $this->download, $this->version );
				break;
		}

		return apply_filters( 'dlm_en_field_email_data', $field, $field_name, $this->log_item, $this->download, $this->version );
	}


}