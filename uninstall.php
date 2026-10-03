<?php
/**
 * Uninstall cleanup: removes every option the plugin creates.
 *
 * @package WP_Counter_Plugin
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'wcp_counter_settings' );
delete_option( 'wcp_counter_value' );

// Multisite: clean up every site in the network.
if ( is_multisite() ) {
	$site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $site_ids as $site_id ) {
		switch_to_blog( $site_id );
		delete_option( 'wcp_counter_settings' );
		delete_option( 'wcp_counter_value' );
		restore_current_blog();
	}
}
