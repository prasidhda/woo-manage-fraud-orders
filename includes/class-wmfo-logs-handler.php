<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

class WMFO_Logs_Handler {
	public $table = 'wmfo_logs';

	/**
	 * Function to add the log in table
	 *
	 * @param array $data
	 *
	 */
	public function add_log( $data = array() ) {

		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . $this->table,
			$data
		);

	}

	/**
	 * Delete a log record.
	 *
	 * @param int $id ID
	 *
	 */
	public function delete_log( $id ) {
		global $wpdb;
		$wpdb->delete( "{$wpdb->prefix}{$this->table}", array( 'id' => $id ), array( '%d' ) );
	}
}