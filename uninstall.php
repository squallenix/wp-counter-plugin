<?php
/**
 * Uninstall cleanup.
 *
 * Removes all plugin options and named counter values.
 *
 * @package WP_Counter_Plugin
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Remove plugin data from the current site.
 *
 * @return void
 */
function wcp_uninstall_site() {

	/*
	 * Get named counter IDs before deleting the ID list.
	 */
	$ids = get_option(
		'wcp_counter_ids',
		array()
	);

	if ( is_array( $ids ) ) {

		foreach ( $ids as $id ) {

			$id = sanitize_key(
				(string) $id
			);

			if ( '' === $id ) {
				continue;
			}

			delete_option(
				'wcp_counter_value_' . $id
			);
		}
	}

	/*
	 * Delete main plugin options.
	 */
	delete_option(
		'wcp_counter_settings'
	);

	delete_option(
		'wcp_counter_value'
	);

	delete_option(
		'wcp_counter_ids'
	);
}

/*
 * Single site.
 */
wcp_uninstall_site();

/*
 * Multisite.
 */
if ( is_multisite() ) {

	$site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $site_ids as $site_id ) {

		switch_to_blog(
			$site_id
		);

		wcp_uninstall_site();

		restore_current_blog();
	}
}