<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

// WP_List_Table is not loaded automatically so we need to load it in our application
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


/**
 * Create a new table class that will extend the WP_List_Table
 */
class WMFO_Logs_Table extends WP_List_Table {
	/**
	 * Prepare the items for the table to process
	 *
	 * @return Void
	 */
	protected $table = 'wmfo_logs';

	/**
	 *
	 * @since  2.0.0
	 * @access public
	 */
	public function no_items() {
		_e( 'No Log records available.', 'woo-manage-fraud-orders' );
	}

	/**
	 * Get log items
	 *
	 * @since  2.0.0
	 * @access public
	 */
	public function prepare_items() {
		global $wpdb;

		$this->process_bulk_action();
		$columns  = $this->get_columns();
		$hidden   = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();


		$sql = "SELECT * FROM {$wpdb->prefix}{$this->table} ORDER BY id desc";

		$data = $wpdb->get_results( $sql, 'ARRAY_A' );

		$perPage     = 10;
		$currentPage = $this->get_pagenum();
		$totalItems  = count( $data );

		$this->set_pagination_args( array(
			'total_items' => $totalItems,
			'per_page'    => $perPage
		) );

		$data = array_slice( $data, ( ( $currentPage - 1 ) * $perPage ), $perPage );

		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items           = $data;
	}


	/**
	 * @return string|
	 *
	 * @since  2.0.0
	 * @access public
	 */
	public function record_count() {
		global $wpdb;

		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}{$this->table}";

		return $wpdb->get_var( $sql );
	}

	/**
	 * @param object $item
	 *
	 * @return string|void
	 *
	 * @since  2.0.0
	 * @access public
	 */
	function column_cb( $item ) {
		if ( $this->record_count() ) {
			return sprintf(
				'<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['id']
			);
		}

	}

	/**
	 * @param $item
	 *
	 * @return string
	 *
	 * @since  2.0.0
	 * @access public
	 */
	function column_full_name( $item ) {
		$actions = array(
			'delete' => sprintf( '<a href="?page=%1$s&action=%2$s&log=%3$s">%4$s</a>', $_REQUEST['page'], 'delete', $item['id'], __( 'Delete', 'woo-manage-fraud-orders' ) ),
		);

		return sprintf( '%1$s %2$s', $item['full_name'], $this->row_actions( $actions ) );
	}

	/**
	 * @return array|string[]
	 *
	 * @since  2.0.0
	 * @access public
	 */
	function get_bulk_actions() {
		$actions = array(
			'bulk-delete' => __( 'Delete', 'woo-manage-fraud-orders' )
		);

		return $actions;
	}

	/**
	 * Handle bulk actions
	 *
	 * @since  2.0.0
	 * @access public
	 */
	public function process_bulk_action() {
		if ( 'delete' === $this->current_action() ) {
			$this->delete_log( absint( $_GET['log'] ) );
			wp_redirect( admin_url( 'admin.php?page=wmfo' ) );
		}

		// If the delete bulk action is triggered
		if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
		     || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
		) {

			$delete_ids = esc_sql( $_POST['bulk-delete'] );

			// loop over the array of record IDs and delete them
			foreach ( $delete_ids as $id ) {
				$this->delete_log( $id );

			}

			wp_redirect( esc_url_raw( remove_query_arg( 'paged' ) ) );
			exit;
		}
	}

	/**
	 * Delete a log record.
	 *
	 * @param int $id ID
	 *
	 * @since  2.0.0
	 * @access public
	 */
	public function delete_log( $id ) {
		$log_handler = new WMFO_Logs_Handler();
		$log_handler->delete_log( $id );
	}

	/**
	 * Override the parent columns method. Defines the columns to use in your listing table
	 *
	 * @return Array
	 *
	 * @since  2.0.0
	 * @access public
	 */
	public function get_columns() {
		$cb = array();
		if ( $this->record_count() ) {
			$cb['cb'] = '<input type="checkbox" />';
		}

		$columns = array(
			'full_name'         => __( 'Full Name', 'woo-manage-fraud-orders' ),
			'phone' => __( 'Phone', 'woo-manage-fraud-orders' ),
			'ip'      => __( 'IP', 'woo-manage-fraud-orders' ),
			'email'    => __( 'Email', 'woo-manage-fraud-orders' ),
			'billing_address'        => __( 'Billing Address', 'woo-manage-fraud-orders' ),
			'shipping_address'              => __( 'Shipping Address', 'woo-manage-fraud-orders' ),
			'blacklisted_reason'              => __( 'Blacklisted Reason', 'woo-manage-fraud-orders' ),
			'timestamp'         => __( 'Date', 'woo-manage-fraud-orders' ),
		);

		$columns = array_merge( $cb, $columns );


		return $columns;
	}

	/**
	 * Define which columns are hidden
	 *
	 * @return Array
	 *
	 * @since  2.0.0
	 * @access public
	 */
	public function get_hidden_columns() {
		return array();
	}

	/**
	 * Define what data to show on each column of the table
	 *
	 * @param Array $item Data
	 * @param String $column_name - Current column name
	 *
	 * @return Mixed
	 *
	 * @since  2.0.0
	 * @access public
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'full_name':
			case 'phone':
			case 'ip':
			case 'email':
			case 'billing_address':
			case 'shipping_address':
			case 'blacklisted_reason':
			case 'timestamp':
				return $item[ $column_name ];

			default:
				return null;
		}
	}

}