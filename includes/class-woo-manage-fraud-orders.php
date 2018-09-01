<?php
/**
 * Main class
 * Handles everything from here, includes  file for the backend settings and
 * blacklisting funcitonalities Inlcudes the frontend handlers as well
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( ! class_exists( 'Woo_Manage_Fraud_Orders' ) ) {
	class Woo_Manage_Fraud_Orders {

		public $version = '1.3.0';
		public static $_instance;

		public function __construct() {
			$this->define_constants();
			$this->includes();
			$this->init_hooks();
		}

		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		private function define_constants() {
			$upload_dir = wp_upload_dir( NULL, FALSE );

			$this->define( 'WMFO_ABSPATH', dirname( WMFO_PLUGIN_FILE ) . '/' );
			$this->define( 'WMFO_PLUGIN_BASENAME', plugin_basename( WMFO_PLUGIN_FILE ) );
			$this->define( 'WMFO_VERSION', $this->version );
			$this->define( 'WMFO_LOG_DIR', $upload_dir['basedir'] . '/wmfo-logs/' );
		}

		private function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		private function init_hooks() {
			register_activation_hook( WMFO_PLUGIN_FILE, [ $this, 'install' ] );

			add_filter( 'plugin_action_links_' . plugin_basename( WMFO_PLUGIN_FILE ), [
				$this,
				'action_links',
			] );
			add_action( 'plugins_loaded', [ $this, 'load_text_domain' ] );
			add_action( 'init', array( $this, 'db_update_handler'));
			add_action('admin_notices', array( $this, 'force_db_update_notice' )); 
		}

		public static function install() {
			if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

				// Deactivate the plugin
				deactivate_plugins( __FILE__ );

				// Throw an error in the wordpress admin console
				$error_message = __( sprintf( 'This plugin requires <a href="%s">WooCommerce plugins to be active!</a>', 'http://wordpress.org/extend/plugins/woocommerce/' ), 'woo-manage-fraud-orders' );
				die( $error_message );

			}
		}

		public static function action_links( $links ) {
			$links[] = '<a href="' . esc_url( get_admin_url( NULL, 'admin.php?page=wc-settings&tab=settings_tab_blacklists' ) ) . '">Settings</a>';

			return $links;
		}

		public function load_text_domain() {
			load_plugin_textdomain( 'woo-manage-fraud-orders', WMFO_PLUGIN_FILE, plugin_basename( dirname( __FILE__ ) ) . '/languages/' );
		}

		public function includes() {
			include_once WMFO_ABSPATH . 'includes/wmfo-functions.php';
			include_once WMFO_ABSPATH . 'includes/class-wmfo-blacklist-handler.php';
			include_once WMFO_ABSPATH . 'includes/class-wmfo-track-fraud-attempts.php';
			if ( is_admin() ) {
				include_once WMFO_ABSPATH . 'includes/admin/class-wmfo-settings.php';
				include_once WMFO_ABSPATH . 'includes/admin/class-wmfo-blacklist-action.php';
				require_once WMFO_ABSPATH . 'includes/admin/class-wmfo-bulk-blacklist.php';
			}
		}

		public function force_db_update_notice(){
			if( get_option('wmfo_fb_update' != 'updated') && ! isset( $_GET['wmfo_action'] ) ){
				$class = 'notice notice-error';
			    $message = __( 'Please update the database to be compabtible with latest version of Woo Manage Fraud Order plugin.<a href="'.add_query_arg('wmfo_action', 'update_db', admin_url()).'">Update</a>', 'woo-manage-fraud-orders' );
			 
			    printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message  ); 
			}
			
		}

		public static function db_update_handler(){
			//check for $_GET values 
			if( isset( $_GET['wmfo_action'] ) && $_GET['wmfo_action'] == 'update_db' ){
				$wmfo_settings = array(
					'wmfo_black_list_ips', 
					'wmfo_black_list_phones',
					'wmfo_black_list_emails',
				);
				foreach ($wmfo_settings as $key => $setting_key ) {

					$setting_value = WMFO_Blacklist_Handler::get_setting( $setting_key );
					$setting_value_array = explode(',', $setting_value ); 
					if( count( $setting_value_array ) > 1 ){
						$new_setting_value = implode( PHP_EOL, array_map('trim', $setting_value_array ) ); 

						update_option( $setting_key, $new_setting_value ); 
					}
				}
			}
		}
	}
}
