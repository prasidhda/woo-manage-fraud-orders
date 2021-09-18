<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

class WMFO_Fraud_Attempts_DB_Handler {
	public $table = 'wmfo_fraud_attempts';

	/**
	 * Function to add fraud in table
	 *
	 * @param array $data
	 *
	 */
	public function add_fraud_record( $data = array() ) {

		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . $this->table,
			$data
		);

	}

	/**
	 * Delete a fraud record.
	 *
	 * @param int $id ID
	 *
	 */
	public function delete_fraud_record( $id ) {
		global $wpdb;
		$wpdb->delete( "{$wpdb->prefix}{$this->table}", array( 'id' => $id ), array( '%d' ) );
	}
}