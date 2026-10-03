<?php
/**
 * Plugin Name: WP Counter Plugin
 * Plugin URI: https://github.com/squallenix/wp-counter-plugin
 * Description: Simple WordPress counter with shortcode, AJAX updates, named counters and admin settings.
 * Version: 2.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Nizam Uddin
 * License: GPL-2.0-or-later
 * Text Domain: wp-counter-plugin
 *
 * @package WP_Counter_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
|--------------------------------------------------------------------------
| Constants
|--------------------------------------------------------------------------
*/

define( 'WCP_VERSION', '2.0.0' );
define( 'WCP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WCP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

define( 'WCP_SETTINGS_OPTION', 'wcp_counter_settings' );
define( 'WCP_VALUE_OPTION', 'wcp_counter_value' );
define( 'WCP_IDS_OPTION', 'wcp_counter_ids' );

define( 'WCP_MAX_STEP', 100 );
define( 'WCP_MIN_VALUE', -999999999 );
define( 'WCP_MAX_VALUE', 999999999 );

/*
|--------------------------------------------------------------------------
| Load plugin classes
|--------------------------------------------------------------------------
*/

require_once WCP_PLUGIN_DIR . 'includes/class-admin.php';
require_once WCP_PLUGIN_DIR . 'includes/class-shortcode.php';
require_once WCP_PLUGIN_DIR . 'includes/class-rest.php';

/*
|--------------------------------------------------------------------------
| Settings
|--------------------------------------------------------------------------
*/

/**
 * Default plugin settings.
 *
 * @return array
 */
function wcp_get_default_settings() {
	return array(
		'title'          => __( 'Visitor Counter', 'wp-counter-plugin' ),
		'starting_value' => 0,
		'step'           => 1,
		'color'          => '#2563eb',
		'show_button'    => true,
	);
}

/**
 * Get saved settings.
 *
 * @return array
 */
function wcp_get_settings() {
	$saved    = get_option( WCP_SETTINGS_OPTION, array() );
	$defaults = wcp_get_default_settings();

	if ( ! is_array( $saved ) ) {
		$saved = array();
	}

	$settings = wp_parse_args( $saved, $defaults );

	$settings['title'] = sanitize_text_field(
		$settings['title']
	);

	if ( '' === $settings['title'] ) {
		$settings['title'] = $defaults['title'];
	}

	$settings['starting_value'] = intval(
		$settings['starting_value']
	);

	$settings['starting_value'] = max(
		WCP_MIN_VALUE,
		min(
			WCP_MAX_VALUE,
			$settings['starting_value']
		)
	);

	$settings['step'] = intval(
		$settings['step']
	);

	if (
		$settings['step'] < 1 ||
		$settings['step'] > WCP_MAX_STEP
	) {
		$settings['step'] = 1;
	}

	$color = sanitize_hex_color(
		$settings['color']
	);

	$settings['color'] = $color
		? $color
		: $defaults['color'];

	$settings['show_button'] = ! empty(
		$settings['show_button']
	);

	return $settings;
}

/*
|--------------------------------------------------------------------------
| Counter IDs
|--------------------------------------------------------------------------
*/

/**
 * Clean a counter ID.
 *
 * Empty ID means the default counter.
 *
 * @param string $id Counter ID.
 * @return string
 */
function wcp_sanitize_counter_id( $id ) {
	$id = sanitize_key( (string) $id );

	return substr( $id, 0, 40 );
}

/**
 * Get the WordPress option name for a counter.
 *
 * @param string $id Counter ID.
 * @return string
 */
function wcp_counter_option_name( $id = '' ) {
	$id = wcp_sanitize_counter_id( $id );

	if ( '' === $id ) {
		return WCP_VALUE_OPTION;
	}

	return WCP_VALUE_OPTION . '_' . $id;
}

/**
 * Get all registered named counter IDs.
 *
 * @return array
 */
function wcp_get_counter_ids() {
	$ids = get_option(
		WCP_IDS_OPTION,
		array()
	);

	if ( ! is_array( $ids ) ) {
		return array();
	}

	return array_values(
		array_unique(
			array_map(
				'wcp_sanitize_counter_id',
				$ids
			)
		)
	);
}

/**
 * Remember a named counter.
 *
 * @param string $id Counter ID.
 * @return void
 */
function wcp_register_counter_id( $id ) {
	$id = wcp_sanitize_counter_id( $id );

	if ( '' === $id ) {
		return;
	}

	$ids = wcp_get_counter_ids();

	if ( in_array( $id, $ids, true ) ) {
		return;
	}

	$ids[] = $id;

	update_option(
		WCP_IDS_OPTION,
		$ids,
		false
	);
}

/*
|--------------------------------------------------------------------------
| Counter values
|--------------------------------------------------------------------------
*/

/**
 * Get a counter value.
 *
 * Creates the counter automatically on first use.
 *
 * @param string $id Counter ID.
 * @return int
 */
function wcp_get_counter_value( $id = '' ) {
	$id     = wcp_sanitize_counter_id( $id );
	$option = wcp_counter_option_name( $id );

	$value = get_option(
		$option,
		null
	);

	if ( null === $value ) {
		$value = wcp_get_settings()['starting_value'];

		add_option(
			$option,
			$value,
			'',
			false
		);

		wcp_register_counter_id( $id );
	}

	return intval( $value );
}

/**
 * Check whether a counter already exists.
 *
 * @param string $id Counter ID.
 * @return bool
 */
function wcp_counter_exists( $id = '' ) {
	$option = wcp_counter_option_name( $id );

	return false !== get_option(
		$option,
		false
	);
}

/**
 * Save a counter value.
 *
 * @param int    $value Counter value.
 * @param string $id    Counter ID.
 * @return int
 */
function wcp_set_counter_value( $value, $id = '' ) {
	$id = wcp_sanitize_counter_id( $id );

	$value = intval( $value );

	$value = max(
		WCP_MIN_VALUE,
		min(
			WCP_MAX_VALUE,
			$value
		)
	);

	update_option(
		wcp_counter_option_name( $id ),
		$value,
		false
	);

	wcp_register_counter_id( $id );

	return $value;
}

/**
 * Increase a counter.
 *
 * This simple version uses WordPress options.
 *
 * @param string $id     Counter ID.
 * @param int    $amount Amount to add.
 * @return int
 */
function wcp_increment_counter( $id, $amount ) {
	$id     = wcp_sanitize_counter_id( $id );
	$amount = intval( $amount );

	if (
		$amount < 1 ||
		$amount > WCP_MAX_STEP
	) {
		$amount = 1;
	}

	$current = wcp_get_counter_value( $id );

	$new_value = $current + $amount;

	return wcp_set_counter_value(
		$new_value,
		$id
	);
}

/**
 * Reset all counters.
 *
 * @param int|null $starting_value Starting value.
 * @return void
 */
function wcp_reset_all_counters( $starting_value = null ) {
	if ( null === $starting_value ) {
		$starting_value = wcp_get_settings()['starting_value'];
	}

	wcp_set_counter_value(
		$starting_value
	);

	foreach ( wcp_get_counter_ids() as $id ) {
		wcp_set_counter_value(
			$starting_value,
			$id
		);
	}
}

/*
|--------------------------------------------------------------------------
| Request token
|--------------------------------------------------------------------------
*/

/**
 * Create a token for a counter and step.
 *
 * The token prevents visitors from changing the step value manually.
 *
 * @param string $id   Counter ID.
 * @param int    $step Increment step.
 * @return string
 */
function wcp_make_token( $id, $step ) {
	$id   = wcp_sanitize_counter_id( $id );
	$step = intval( $step );

	$data = $id . '|' . $step;

	return hash_hmac(
		'sha256',
		$data,
		wp_salt( 'auth' )
	);
}

/**
 * Verify a counter token.
 *
 * @param string $token Token sent by browser.
 * @param string $id    Counter ID.
 * @param int    $step  Increment step.
 * @return bool
 */
function wcp_verify_token( $token, $id, $step ) {
	if ( ! is_string( $token ) || '' === $token ) {
		return false;
	}

	$expected = wcp_make_token(
		$id,
		$step
	);

	return hash_equals(
		$expected,
		$token
	);
}

/*
|--------------------------------------------------------------------------
| Simple rate limiting
|--------------------------------------------------------------------------
*/

/**
 * Check if this visitor is clicking too often.
 *
 * @param string $id Counter ID.
 * @return bool
 */
function wcp_is_rate_limited( $id = '' ) {
	$ip = isset( $_SERVER['REMOTE_ADDR'] )
		? sanitize_text_field(
			wp_unslash(
				$_SERVER['REMOTE_ADDR']
			)
		)
		: 'unknown';

	$key = 'wcp_rate_' . md5(
		$ip . '|' . wcp_sanitize_counter_id( $id )
	);

	$count = intval(
		get_transient( $key )
	);

	if ( $count >= 30 ) {
		return true;
	}

	set_transient(
		$key,
		$count + 1,
		MINUTE_IN_SECONDS
	);

	return false;
}

/*
|--------------------------------------------------------------------------
| Activation
|--------------------------------------------------------------------------
*/

/**
 * Create default plugin options.
 *
 * @return void
 */
function wcp_activate() {
	if ( false === get_option( WCP_SETTINGS_OPTION ) ) {
		add_option(
			WCP_SETTINGS_OPTION,
			wcp_get_default_settings()
		);
	}

	if ( false === get_option( WCP_VALUE_OPTION ) ) {
		add_option(
			WCP_VALUE_OPTION,
			wcp_get_settings()['starting_value'],
			'',
			false
		);
	}

	if ( false === get_option( WCP_IDS_OPTION ) ) {
		add_option(
			WCP_IDS_OPTION,
			array(),
			'',
			false
		);
	}
}

register_activation_hook(
	__FILE__,
	'wcp_activate'
);

/*
|--------------------------------------------------------------------------
| Translations
|--------------------------------------------------------------------------
*/

function wcp_load_textdomain() {
	load_plugin_textdomain(
		'wp-counter-plugin',
		false,
		dirname(
			plugin_basename( __FILE__ )
		) . '/languages'
	);
}

add_action(
	'init',
	'wcp_load_textdomain'
);

/*
|--------------------------------------------------------------------------
| Start plugin
|--------------------------------------------------------------------------
*/

new WCP_Admin();
new WCP_Shortcode();
new WCP_Rest();