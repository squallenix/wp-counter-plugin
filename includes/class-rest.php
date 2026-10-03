<?php
/**
 * REST API for the counter.
 *
 * @package WP_Counter_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles counter REST requests.
 */
class WCP_Rest {

	/**
	 * REST API namespace.
	 */
	const REST_NAMESPACE = 'wp-counter-plugin/v1';

	/**
	 * Maximum updates per visitor per minute.
	 */
	const MAX_UPDATES = 30;

	/**
	 * Register REST API.
	 */
	public function __construct() {
		add_action(
			'rest_api_init',
			array(
				$this,
				'register_routes',
			)
		);
	}

	/**
	 * Register counter routes.
	 *
	 * GET:
	 *
	 * /wp-json/wp-counter-plugin/v1/counter
	 *
	 * POST:
	 *
	 * /wp-json/wp-counter-plugin/v1/counter
	 *
	 * @return void
	 */
	public function register_routes() {

		/*
		 * GET counter.
		 */
		register_rest_route(
			self::REST_NAMESPACE,
			'/counter',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array(
					$this,
					'get_counter',
				),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'wcp_sanitize_counter_id',
					),
				),
			)
		);

		/*
		 * POST counter update.
		 */
		register_rest_route(
			self::REST_NAMESPACE,
			'/counter',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array(
					$this,
					'update_counter',
				),
				'permission_callback' => '__return_true',
				'args'                => array(

					'id' => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'wcp_sanitize_counter_id',
					),

					'amount' => array(
						'type'     => 'integer',
						'required' => true,
					),

					'token' => array(
						'type'     => 'string',
						'required' => true,
					),
				),
			)
		);
	}

	/**
	 * Get the current counter value.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_counter( WP_REST_Request $request ) {

		$id = wcp_sanitize_counter_id(
			$request->get_param( 'id' )
		);

		/*
		 * Do not let REST GET create random named counters.
		 */
		if ( ! wcp_counter_exists( $id ) ) {

			return new WP_Error(
				'wcp_counter_not_found',
				__(
					'Counter not found.',
					'wp-counter-plugin'
				),
				array(
					'status' => 404,
				)
			);
		}

		$value = wcp_get_counter_value(
			$id
		);

		return rest_ensure_response(
			array(
				'value'     => $value,
				'formatted' => number_format_i18n(
					$value
				),
				'id'        => $id,
			)
		);
	}

	/**
	 * Increase a counter.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_counter( WP_REST_Request $request ) {

		/*
		 * Counter ID.
		 */
		$id = wcp_sanitize_counter_id(
			$request->get_param( 'id' )
		);

		/*
		 * REST requests may only update counters that already exist.
		 *
		 * A valid shortcode creates the counter first.
		 */
		if ( ! wcp_counter_exists( $id ) ) {

			return new WP_Error(
				'wcp_counter_not_found',
				__(
					'Counter not found.',
					'wp-counter-plugin'
				),
				array(
					'status' => 404,
				)
			);
		}

		/*
		 * Validate amount.
		 */
		$amount = filter_var(
			$request->get_param( 'amount' ),
			FILTER_VALIDATE_INT
		);

		if (
			false === $amount ||
			$amount < 1 ||
			$amount > WCP_MAX_STEP
		) {

			return new WP_Error(
				'wcp_invalid_amount',
				sprintf(
					/* translators: %d: maximum step. */
					__(
						'Amount must be between 1 and %d.',
						'wp-counter-plugin'
					),
					WCP_MAX_STEP
				),
				array(
					'status' => 400,
				)
			);
		}

		/*
		 * Get token.
		 */
		$token = $request->get_param(
			'token'
		);

		$token = is_string( $token )
			? trim( $token )
			: '';

		/*
		 * Check token.
		 *
		 * The token is connected to both:
		 *
		 * counter ID
		 * +
		 * step amount
		 */
		if (
			! wcp_verify_token(
				$token,
				$id,
				$amount
			)
		) {

			return new WP_Error(
				'wcp_invalid_token',
				__(
					'Invalid counter request.',
					'wp-counter-plugin'
				),
				array(
					'status' => 403,
				)
			);
		}

		/*
		 * Rate limit.
		 */
		if ( wcp_is_rate_limited( $id ) ) {

			return new WP_Error(
				'wcp_rate_limited',
				__(
					'Too many updates. Please wait a moment.',
					'wp-counter-plugin'
				),
				array(
					'status' => 429,
				)
			);
		}

		/*
		 * Increase counter.
		 */
		$value = wcp_increment_counter(
			$id,
			$amount
		);

		/*
		 * Return updated value.
		 */
		return rest_ensure_response(
			array(
				'value'     => $value,
				'formatted' => number_format_i18n(
					$value
				),
				'id'        => $id,
			)
		);
	}
}