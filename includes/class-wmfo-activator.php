<?php

/**
 * Fired during plugin activation.
 *
 */
class WMFO_Activator {

	public static function create_db_table() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		global $wpdb;

		// WnFO logs table
		$wmfo_logs_table = $wpdb->prefix . 'wmfo_logs';
		if ( ! ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $wmfo_logs_table ) ) === $wmfo_logs_table ) ) {
			$charset_collate = $wpdb->get_charset_collate();
			$sql             = "CREATE TABLE $wmfo_logs_table (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				full_name varchar(255) DEFAULT '' NOT NULL,
				phone varchar(255) DEFAULT '' NOT NULL,
				ip varchar(255) DEFAULT '' NOT NULL,
				email varchar(255) DEFAULT '' NOT NULL,
				billing_address varchar(255) DEFAULT '' NOT NULL,
				shipping_address varchar(255) DEFAULT '' NOT NULL,
				blacklisted_reason varchar(255) DEFAULT '' NOT NULL,
				timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				PRIMARY KEY  (id)
			) $charset_collate;";

			dbDelta( $sql );

			flush_rewrite_rules();
		}

		//WMFO fraud attempts table
		$wmfo_fraud_attempts_table = $wpdb->prefix . 'wmfo_fraud_attempts';
		if ( ! ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $wmfo_fraud_attempts_table ) ) === $wmfo_fraud_attempts_table ) ) {
			$charset_collate = $wpdb->get_charset_collate();
			$sql             = "CREATE TABLE $wmfo_fraud_attempts_table (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				full_name varchar(255) DEFAULT '' NOT NULL,
				billing_phone varchar(255) DEFAULT '' NOT NULL,
				ip varchar(255) DEFAULT '' NOT NULL,
				billing_email varchar(255) DEFAULT '' NOT NULL,
				billing_address varchar(255) DEFAULT '' NOT NULL,
				shipping_address varchar(255) DEFAULT '' NOT NULL,
				payment_method varchar(255) DEFAULT '' NOT NULL,
				timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				PRIMARY KEY  (id)
			) $charset_collate;";

			dbDelta( $sql );

			flush_rewrite_rules();
		}

	}

	public static function create_upload_dir() {
		if ( ! is_dir( WMFO_LOG_DIR ) ) {
			mkdir( WMFO_LOG_DIR, 0700 );
		}
	}

}
