<?php declare( strict_types=1 );
/**
 * The A8CSP Template Plugin bootstrap file.
 *
 * This file must remain parsable on PHP versions below the plugin's declared floor, since it
 * runs before the requirements check can report a friendly error; a dedicated CI job lints it
 * directly against the older PHP versions.
 *
 * @since       1.0.0
 * @version     1.0.0
 * @package     A8C\SpecialProjects\PluginTemplate
 * @author      A8C Special Projects
 * @license     GPL-2.0-or-later
 *
 * @noinspection    ALL
 *
 * @wordpress-plugin
 * Plugin Name:             A8CSP Template Plugin
 * Plugin URI:              https://specialprojects.automattic.com
 * Update URI:              https://github.com/a8cteam51/a8csp-plugin-template
 * Description:             A template for A8C Special Projects plugins.
 * Version:                 1.0.0
 * Requires at least:       7.0
 * Tested up to:            7.0
 * Requires PHP:            8.5
 * Author:                  A8C Special Projects
 * Author URI:              https://specialprojects.automattic.com
 * License:                 GPL v2 or later
 * License URI:             https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:             a8csp-plugin-template
 * Domain Path:             /languages
 * WC requires at least:    10.0
 * WC tested up to:         10.9
 */

\defined( 'ABSPATH' ) || exit;

// Define plugin constants.
\define( 'A8CSP_TEMPLATE_BASENAME', plugin_basename( __FILE__ ) );
\define( 'A8CSP_TEMPLATE_DIR_PATH', plugin_dir_path( __FILE__ ) );
\define( 'A8CSP_TEMPLATE_DIR_URL', plugin_dir_url( __FILE__ ) );

// The gate's helper functions live in functions-bootstrap.php, which shares this file's
// below-floor parse constraint; they must exist before the compatibility hook and the
// requirements gate below can call them.
require_once A8CSP_TEMPLATE_DIR_PATH . '/functions-bootstrap.php';

add_filter(
	'update_plugins_github.com',
	static function ( $update, $plugin_data, $plugin_file ) {
		if ( A8CSP_TEMPLATE_BASENAME !== $plugin_file || false !== $update ) {
			return $update;
		}

		$latest_release_info = get_transient( 'a8csp_template_github_latest_release' );
		if ( false === $latest_release_info ) {
			// A prerelease installation follows every published release; a stable installation follows only the
			// stable channel, which the latest-release endpoint provides by definition.
			$release_url_path    = \str_contains( (string) ( $plugin_data['Version'] ?? '' ), '-' ) ? 'releases?per_page=10' : 'releases/latest';
			$response            = wp_remote_get( 'https://api.github.com/repos/a8cteam51/a8csp-plugin-template/' . $release_url_path );
			$latest_release_info = is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ? array() : \json_decode( wp_remote_retrieve_body( $response ), true );
		}

		if ( ! \is_array( $latest_release_info ) ) {
			$latest_release_info = array();
		}

		if ( array() !== $latest_release_info && \array_is_list( $latest_release_info ) ) {
			// The release list arrives newest first; the first non-draft entry is the channel's latest.
			$channel_latest = null;
			foreach ( $latest_release_info as $release_candidate ) {
				if ( \is_array( $release_candidate ) && true !== ( $release_candidate['draft'] ?? null ) ) {
					$channel_latest = $release_candidate;
					break;
				}
			}

			$latest_release_info = \is_array( $channel_latest ) ? $channel_latest : array();
		}

		$release_tag    = $latest_release_info['tag_name'] ?? null;
		$release_url    = $latest_release_info['html_url'] ?? null;
		$release_assets = $latest_release_info['assets'] ?? null;

		$release_asset = null;
		foreach ( \is_array( $release_assets ) ? $release_assets : array() as $asset ) {
			if ( ! \is_array( $asset ) ) {
				continue;
			}

			$asset_name = $asset['name'] ?? null;
			if ( 'a8csp-plugin-template.zip' !== $asset_name ) {
				continue;
			}

			$release_asset = $asset['browser_download_url'] ?? null;
			break;
		}

		$release_is_usable = \is_string( $release_tag ) && \is_string( $release_url ) && \is_string( $release_asset );
		if ( isset( $response ) ) {
			set_transient( 'a8csp_template_github_latest_release', $release_is_usable ? $latest_release_info : array(), $release_is_usable ? HOUR_IN_SECONDS : 5 * MINUTE_IN_SECONDS );
		}

		if ( ! $release_is_usable ) {
			return false;
		}

		$latest_release_version = \ltrim( $release_tag, 'v' );
		if ( \version_compare( $plugin_data['Version'], $latest_release_version, '<' ) ) {
			$update = array(
				'slug'    => $plugin_data['TextDomain'],
				'version' => $latest_release_version,
				'url'     => $release_url,
				'package' => $release_asset,
			);
		} else {
			$update = false;
		}

		return $update;
	},
	10,
	3
);

// Registration-only since WP 6.7, so include time is safe — and required: core registers the
// header path only for site-active plugins, so network-activated copies lose their bundled
// translations without this line. Gettext calls still wait for `init` (JIT).
load_plugin_textdomain( 'a8csp-plugin-template', false, dirname( A8CSP_TEMPLATE_BASENAME ) . '/languages' );

// Declare compatibility with WC features.
add_action(
	'before_woocommerce_init',
	static function () {
		if ( \class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

// Load the autoloader.
if ( ! \is_file( A8CSP_TEMPLATE_DIR_PATH . '/vendor/autoload.php' ) ) {
	a8csp_template_output_requirements_error( new WP_Error( 'missing_autoloader' ) );
	return;
}
require_once A8CSP_TEMPLATE_DIR_PATH . '/vendor/autoload.php';

// Bootstrap the plugin (maybe)!
\define( 'A8CSP_TEMPLATE_REQUIREMENTS', a8csp_template_validate_requirements() );
if ( is_wp_error( A8CSP_TEMPLATE_REQUIREMENTS ) ) {
	a8csp_template_output_requirements_error( A8CSP_TEMPLATE_REQUIREMENTS );
} else {
	require_once A8CSP_TEMPLATE_DIR_PATH . '/functions.php';
	add_action( 'plugins_loaded', array( a8csp_template_plugin(), 'boot' ) );
}
