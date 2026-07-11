<?php declare( strict_types=1 );

namespace A8C\SpecialProjects\Template\Integrations;

use A8C\SpecialProjects\Template\Framework\Component;

defined( 'ABSPATH' ) || exit;

/**
 * Provides the full-tier WooCommerce extension. It gates on WooCommerce core and the
 * header-declared `WC requires at least` floor, then adds a real section to WooCommerce → Settings
 * → Advanced.
 *
 * This folder is the deletable WooCommerce tier; see the README's "Watering down to plain
 * WordPress" sequence.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @see uninstall.php
 */
class WC_Settings_Section implements Component {
	// region FIELDS AND CONSTANTS

	/**
	 * The persisted WooCommerce example option. It is mirrored in the `uninstall.php` footprint
	 * under this component's owner group; the footprint line goes with this folder when the
	 * WooCommerce tier is deleted.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @var     string
	 */
	private const OPTION_KEY = 'a8csp_template_wc_example_option';

	// endregion

	// region METHODS

	/**
	 * Returns true if WooCommerce core is active on a version that meets the `WC requires at least`
	 * floor declared in the plugin header. To require a specific WooCommerce extension instead, add
	 * its own `class_exists()` check here, such as `class_exists( 'WC_Subscriptions' )` for
	 * Subscriptions.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  bool
	 */
	public function is_needed(): bool {
		if ( ! \class_exists( 'WooCommerce' ) || ! \defined( 'WC_VERSION' ) ) {
			return false;
		}

		return self::meets_minimum_wc_version( WC_VERSION, a8csp_template_get_plugin_metadata( 'WC requires at least' ) );
	}

	/**
	 * Compares an installed WooCommerce version against the header-declared floor. A pure value
	 * comparison with no WordPress or WooCommerce calls, so the Unit suite can exercise the
	 * branching directly instead of stubbing globals.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @param   string      $installed_version The installed WooCommerce version.
	 * @param   string|null $minimum_version    The minimum WooCommerce version declared in the plugin
	 *                                          header, or null/empty if the header doesn't declare one.
	 *
	 * @return  bool
	 */
	public static function meets_minimum_wc_version( string $installed_version, ?string $minimum_version ): bool {
		if ( null === $minimum_version || '' === $minimum_version ) {
			return true;
		}

		return \version_compare( $installed_version, $minimum_version, '>=' );
	}

	/**
	 * Wires the section into WooCommerce's Advanced settings tab. WooCommerce renders and saves the
	 * declared fields itself; this component owns only the declaration.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function initialize(): void {
		\add_filter( 'woocommerce_get_sections_advanced', array( $this, 'add_section' ) );
		\add_filter( 'woocommerce_get_settings_advanced', array( $this, 'get_settings' ), 10, 2 );
	}

	// endregion

	// region HOOKS

	/**
	 * Adds the plugin's section to the Advanced settings tab. The section slug carries the prefix
	 * token so generation rewrites it, and the label doubles as the plugin title.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @param   array<string, string> $sections The registered Advanced settings sections.
	 *
	 * @return  array<string, string>
	 */
	public function add_section( array $sections ): array {
		$sections['a8csp_template'] = \__( 'A8CSP Template Plugin', 'a8csp-plugin-template' );

		return $sections;
	}

	/**
	 * Declares this section's WooCommerce settings rows. WooCommerce persists the field through its
	 * own settings save, so declaring the field is the whole persistence story and the option key
	 * still appears in the uninstall footprint.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @param   array<int, array<string, mixed>> $settings        The settings rows for the current
	 *                                                            Advanced section.
	 * @param   string                           $current_section The current Advanced section slug.
	 *
	 * @return  array<int, array<string, mixed>>
	 */
	public function get_settings( array $settings, string $current_section ): array {
		if ( 'a8csp_template' !== $current_section ) {
			return $settings;
		}

		return array(
			array(
				'title' => \__( 'A8CSP Template Plugin', 'a8csp-plugin-template' ),
				'type'  => 'title',
				'id'    => 'a8csp_template_wc_example_section',
			),
			array(
				'title' => \__( 'Example option', 'a8csp-plugin-template' ),
				'desc'  => \__( 'A persisted example setting owned by the WooCommerce integration.', 'a8csp-plugin-template' ),
				'id'    => self::OPTION_KEY,
				'type'  => 'text',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'a8csp_template_wc_example_section',
			),
		);
	}

	// endregion
}
