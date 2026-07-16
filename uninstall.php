<?php declare( strict_types=1 );
/**
 * Uninstall handler. WordPress runs this file directly when the plugin is deleted, in a cold
 * bootstrap without the plugin loaded; the footprint comment below spells out what that means
 * for this file.
 *
 * @since       1.0.0
 * @version     1.0.0
 * @package     A8C\SpecialProjects\PluginTemplate
 */

\defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/*
 * The plugin's persisted footprint. Every option and user-meta key any component writes is
 * listed here, in the same change that introduces the write — grouped by owning component
 * so ownership stays reviewable. This file runs in WordPress's cold uninstall bootstrap
 * (no autoloader, no Plugin or Component classes), so the arrays stay inline: nothing here
 * may reference plugin code.
 */
$a8csp_template_footprint = array(
	'options'   => array(
		// Settings owns:
		'a8csp_template_example_option',
		'a8csp_template_wc_example_option',
		// Integrations\WooCommerceSubscriptions\Component owns:
		'a8csp_template_wcs_example_option',
	),
	'user_meta' => array(),
);

/*
 * Options are stored per site, and a multisite uninstall runs only once, network-wide — so the
 * options sweep visits every site of the network. `get_sites()` returns at most 100 sites by
 * default; `number => 0` lifts that cap so no site's options outlive the plugin.
 */
if ( is_multisite() ) {
	$a8csp_template_uninstall_site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);
	foreach ( $a8csp_template_uninstall_site_ids as $a8csp_template_uninstall_site_id ) {
		switch_to_blog( $a8csp_template_uninstall_site_id );

		foreach ( $a8csp_template_footprint['options'] as $a8csp_template_uninstall_option ) {
			delete_option( $a8csp_template_uninstall_option );
		}

		restore_current_blog();
	}
} else {
	foreach ( $a8csp_template_footprint['options'] as $a8csp_template_uninstall_option ) {
		delete_option( $a8csp_template_uninstall_option );
	}
}

// User meta is stored network-globally, so one pass covers every site.
// @phpstan-ignore foreach.emptyArray (The scaffold's user-meta footprint starts empty; the loop is live the day a consumer lists a key.)
foreach ( $a8csp_template_footprint['user_meta'] as $a8csp_template_uninstall_meta_key ) {
	delete_metadata( 'user', 0, $a8csp_template_uninstall_meta_key, '', true );
}
