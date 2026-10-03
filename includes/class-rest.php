<?php
/**
 * REST endpoint used by the frontend "+" button (AJAX without page reload).
 *
 * @package WP_Counter_Plugin
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers /wp-json/wp-counter-plugin/v1/counter.
 */
class WCP_Rest {

	/** REST namespace. */
	const REST_NAMESPACE = 'wp-counter-plugin/v1';

	/** Maximum amount a single request may add. */
	const MAX_AMOUNT = 100;

	/** Maximum number of updates allowed per visitor per minute. */
	const MAX_UPDATES = 30;

	/**
	 * Hook the REST API.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register the counter route: GET reads it, POST increments it.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			'/counter',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_counter' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/counter',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_counter' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'amount' => array(
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Writes require a valid REST nonce (works for logged-out visitors too,
	 * because the script localises one per page view).
	 *
	 * @param WP_REST_Request $request Current request.
	 * @return bool|WP_Error
	 */
	public function check_permission( WP_REST_Request $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );

		if ( $nonce && wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return true;
		}

		return new WP_Error(
			'wcp_rest_forbidden',
			__( 'Your session could not be verified. Please reload the page and try again.', 'wp-counter-plugin' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}

	/**
	 * Read the current counter.
	 *
	 * @return WP_REST_Response
	 */
	public function get_counter() {
		$settings = wcp_get_settings();

		return rest_ensure_response(
			array(
				'value'     => wcp_get_counter_value(),
				'formatted' => number_format_i18n( wcp_get_counter_value() ),
				'title'     => $settings['title'],
				'step'      => $settings['step'],
			)
		);
	}

	/**
	 * Increase the counter and return the new value.
	 *
	 * @param WP_REST_Request $request Current request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_counter( WP_REST_Request $request ) {
		if ( $this->is_rate_limited() ) {
			return new WP_Error(
				'wcp_rest_rate_limited',
				__( 'Too many updates in a row. Please wait a moment and try again.', 'wp-counter-plugin' ),
				array( 'status' => 429 )
			);
		}

		$settings = wcp_get_settings();

		$amount = intval( $request->get_param( 'amount' ) );
		if ( $amount < 1 ) {
			$amount = $settings['step'];
		}
		$amount = min( $amount, self::MAX_AMOUNT );

		$value = wcp_set_counter_value( wcp_get_counter_value() + $amount );

		return rest_ensure_response(
			array(
				'value'     => $value,
				'formatted' => number_format_i18n( $value ),
				'title'     => $settings['title'],
				'step'      => $settings['step'],
			)
		);
	}

	/**
	 * Light throttle so a script cannot inflate the counter forever.
	 *
	 * @return bool True when this visitor has hit the limit.
	 */
	private function is_rate_limited() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$key = 'wcp_rate_' . md5( $ip );

		$count = (int) get_transient( $key );
		if ( $count >= self::MAX_UPDATES ) {
			return true;
		}

		set_transient( $key, $count + 1, MINUTE_IN_SECONDS );

		return false;
	}
}
