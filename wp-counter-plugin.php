<?php
/**
 * Plugin Name:          WP Counter Plugin
 * Plugin URI:           https://github.com/your-user-name/wp-counter-plugin
 * Description:          A lightweight counter with an admin settings page and a [counter] shortcode. Increments over REST/AJAX without page reloads, supports shortcode attributes, custom colours and a one-click reset.
 * Version:              1.0.0
 * Requires at least:    5.8
 * Requires PHP:         7.4
 * Author:               Your Name
 * License:              GPL-2.0-or-later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          wp-counter-plugin
 * Domain Path:          /languages
 *
 * @package WP_Counter_Plugin
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WCP_VERSION', '1.0.0' );
define( 'WCP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WCP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/** Option name that stores the admin configured settings (array). */
define( 'WCP_SETTINGS_OPTION', 'wcp_counter_settings' );

/** Option name that stores the current counter value (integer). */
define( 'WCP_VALUE_OPTION', 'wcp_counter_value' );

require_once WCP_PLUGIN_DIR . 'includes/class-admin.php';
require_once WCP_PLUGIN_DIR . 'includes/class-shortcode.php';
require_once WCP_PLUGIN_DIR . 'includes/class-rest.php';

/**
 * Default settings used on a fresh install and as a safety net for bad data.
 *
 * @return array<string, mixed>
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
 * Read the saved settings and always return a clean, safe array.
 *
 * The stored option is treated as untrusted data: every key is re-sanitized
 * here so a corrupted or manually edited option can never reach the screen.
 *
 * @return array<string, mixed>
 */
function wcp_get_settings() {
	$saved = get_option( WCP_SETTINGS_OPTION, array() );
	$saved = is_array( $saved ) ? $saved : array();

	$settings = wp_parse_args( $saved, wcp_get_default_settings() );
	$defaults = wcp_get_default_settings();

	$title = is_string( $settings['title'] ) ? sanitize_text_field( $settings['title'] ) : '';
	$settings['title'] = '' !== $title ? $title : $defaults['title'];

	$settings['starting_value'] = max( -999999, min( 999999, intval( $settings['starting_value'] ) ) );

	$step = intval( $settings['step'] );
	$settings['step'] = $step > 0 ? $step : $defaults['step'];

	$color = is_string( $settings['color'] ) ? sanitize_hex_color( $settings['color'] ) : null;
	$settings['color'] = $color ? $color : $defaults['color'];

	$settings['show_button'] = filter_var( $settings['show_button'], FILTER_VALIDATE_BOOLEAN );

	return $settings;
}

/**
 * Current counter value. Created from the starting value on first access.
 *
 * @return int
 */
function wcp_get_counter_value() {
	$value = get_option( WCP_VALUE_OPTION, null );

	if ( null === $value ) {
		$value = wcp_get_settings()['starting_value'];
		update_option( WCP_VALUE_OPTION, $value, false );
	}

	return intval( $value );
}

/**
 * Store a new counter value.
 *
 * @param int $value Value to save.
 * @return int The stored value.
 */
function wcp_set_counter_value( $value ) {
	$value = intval( $value );
	update_option( WCP_VALUE_OPTION, $value, false );

	return $value;
}

/**
 * Plugin activation: seed the options so the shortcode works immediately.
 *
 * @return void
 */
function wcp_activate() {
	if ( false === get_option( WCP_SETTINGS_OPTION ) ) {
		add_option( WCP_SETTINGS_OPTION, wcp_get_default_settings() );
	}

	if ( false === get_option( WCP_VALUE_OPTION ) ) {
		add_option( WCP_VALUE_OPTION, wcp_get_settings()['starting_value'] );
	}
}
register_activation_hook( __FILE__, 'wcp_activate' );

/**
 * Load the translation files.
 *
 * @return void
 */
function wcp_load_textdomain() {
	load_plugin_textdomain(
		'wp-counter-plugin',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
}
add_action( 'init', 'wcp_load_textdomain' );

// Boot the plugin.
new WCP_Admin();
new WCP_Shortcode();
new WCP_Rest();
