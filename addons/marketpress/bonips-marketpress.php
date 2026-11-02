<?php
/**
 * MarketPress integration for boniPS.
 *
 * @since 1.0
 */

if ( ! class_exists( 'boniPS_MarketPress_Plugin' ) ) :
	final class boniPS_MarketPress_Plugin {

		public $version             = '1.0';

		// Instnace
		protected static $_instance = NULL;

		// Current session
		public $session             = NULL;

		/**
		 * Setup Instance
		 * @since 1.0
		 * @version 1.0
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Not allowed
		 * @since 1.0
		 * @version 1.0
		 */
		public function __clone() { _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.0' ); }

		/**
		 * Not allowed
		 * @since 1.0
		 * @version 1.0
		 */
		public function __wakeup() { _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.0' ); }

		/**
		 * Define
		 * @since 1.0
		 * @version 1.0
		 */
		private function define( $name, $value, $definable = true ) {
			if ( ! defined( $name ) )
				define( $name, $value );
		}

		/**
		 * Require File
		 * @since 1.0
		 * @version 1.0
		 */
		public function file( $required_file ) {
			if ( file_exists( $required_file ) )
				require_once $required_file;
		}

		/**
		 * Construct
		 * @since 1.0
		 * @version 1.0
		 */
		public function __construct() {

			$this->define_constants();
			$this->includes();

			add_action( 'bonips_all_references',                      array( $this, 'add_badge_support' ) );

			add_action( 'marketpress/load_plugins/mp_include',        array( $this, 'load_gateway_class' ) );
			add_filter( 'mp_gateway_api/get_gateways',                array( $this, 'load_gateway' ) );

			add_filter( 'mp_format_currency',                         array( $this, 'adjust_currency_format' ), 10, 4 );

			add_filter( 'bonips_parse_log_entry_marketpress_payment', 'bonips_marketpress_parse_log', 90, 2 );
			add_filter( 'bonips_email_before_send',                   'bonips_marketpress_parse_email', 20 );

			add_action( 'bonips_init',                                'bonips_marketpress_load_rewards', 20 );

		}

		/**
		 * Define Constants
		 * @since 1.0
		 * @version 1.0.1
		 */
		public function define_constants() {

			$this->define( 'BONIPS_MARKET_VERSION',    $this->version );
			$this->define( 'BONIPS_SLUG',              'bonips' );
			$this->define( 'BONIPS_DEFAULT_LABEL',     'BoniPress' );

			$this->define( 'BONIPS_MARKETPRESS',        __FILE__ );
			$this->define( 'BONIPS_MARKET_ROOT_DIR',    plugin_dir_path( BONIPS_MARKETPRESS ) );
			$this->define( 'BONIPS_MARKET_CLASSES_DIR', BONIPS_MARKET_ROOT_DIR . 'classes/' );
			$this->define( 'BONIPS_MARKET_INC_DIR',     BONIPS_MARKET_ROOT_DIR . 'includes/' );

		}

		/**
		 * Includes
		 * @since 1.0
		 * @version 1.0
		 */
		public function includes() {

			$this->file( BONIPS_MARKET_INC_DIR . 'marketpress-gateway.php' );
			$this->file( BONIPS_MARKET_INC_DIR . 'marketpress-rewards.php' );

		}

		/**
		 * Includes
		 * @since 1.0.1
		 * @version 1.0
		 */
		public function load_gateway( $list ) {

			if ( ! array_key_exists( BONIPS_SLUG, $list ) && class_exists( 'MP_Gateway_boniPS_MarketPress' ) ) {

				$global = ( is_multisite() && bonips_centralize_log() ) ? true : false;
				$list[ BONIPS_SLUG ] = array( 'MP_Gateway_boniPS_MarketPress', BONIPS_DEFAULT_LABEL, $global, false );

			}

			return $list;

		}

		/**
		 * Load the gateway implementation after MarketPress has loaded its API.
		 *
		 * @since 1.0
		 */
		public function load_gateway_class() {

			if ( ! class_exists( 'Marketpress' ) || ! class_exists( 'MP_Gateway_API' ) ) {
				return;
			}

			$this->file( BONIPS_MARKET_CLASSES_DIR . 'class.boniPS-MarketPress-Gateway.php' );

		}

		/**
		 * Adjust Currency Format
		 * @since 1.1
		 * @version 1.0
		 */
		public function adjust_currency_format( $formatted, $currency, $symbol, $amount ) {

			if ( $currency == 'POINTS' || ( function_exists( 'bonips_point_type_exists' ) && bonips_point_type_exists( $currency ) ) ) {

				$point_type = mp_get_setting( "gateways->" . BONIPS_SLUG . "->{type}", BONIPS_DEFAULT_TYPE_KEY );
				$bonips     = bonips( $point_type );

				return $bonips->format_creds( $amount );

			}

			return $formatted;

		}

		/**
		 * Add Badge Support
		 * @since 1.0
		 * @version 1.0
		 */
		public function add_badge_support( $references ) {

			if ( ! class_exists( 'Marketpress' ) ) return $references;

			$references['marketpress_payment'] = __( 'Shop-Zahlung (MarketPress)', 'bonips' );
			$references['marketpress_sale']    = __( 'Shop-Verkauf (MarketPress)', 'bonips' );
			$references['marketpress_reward']  = __( 'Shop-Belohnung (MarketPress)', 'bonips' );

			return $references;

		}

	}
endif;

function bonips_for_marketpress_plugin() {
	return boniPS_MarketPress_Plugin::instance();
}
bonips_for_marketpress_plugin();
