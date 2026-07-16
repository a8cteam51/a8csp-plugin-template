<?php declare( strict_types=1 );

namespace A8C\SpecialProjects\PluginTemplate\Settings;

use A8C\SpecialProjects\PluginTemplate\AbstractComponent;

\defined( 'ABSPATH' ) || exit;

/**
 * Composes the Settings feature: it owns both of the plugin's settings surfaces — one option
 * registered and persisted through the WordPress Settings API on the General options page, and
 * one section with a persisted option in WooCommerce → Settings → Advanced. Core functionality
 * behind the plugin-wide host gate: WooCommerce is guaranteed, no surface needs a gate of its
 * own, and the defaults-only base fits. Both surfaces double as the worked example for the
 * uninstall footprint.
 *
 * Imitate this feature for any option your plugin owns. Delete the surface — or the whole
 * feature folder — your plugin does not need.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @see uninstall.php
 */
final class Component extends AbstractComponent {
	// region METHODS

	/**
	 * {@inheritDoc}
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function register_hooks(): void {
		// The Settings API is only loaded in the admin, so registration stages onto admin_init.
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		add_filter( 'woocommerce_get_sections_advanced', array( $this, 'add_section' ) );
		add_filter( 'woocommerce_get_settings_advanced', array( $this, 'get_settings' ), 10, 2 );
	}

	// endregion

	// region HOOKS

	/**
	 * Hangs the demo field on the core General options page to avoid inventing a whole admin page
	 * for a one-field demo. Imitate the `register_setting()` shape and retarget the page as needed.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function register_settings(): void {
		// Every persisted key is mirrored in the `uninstall.php` footprint manifest in the same
		// change that introduces the write.
		register_setting(
			'general',
			'a8csp_template_example_option',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		add_settings_section(
			'a8csp_template_example_section',
			__( 'A8CSP Template Plugin', 'a8csp-plugin-template' ),
			'__return_empty_string',
			'general'
		);

		add_settings_field(
			'a8csp_template_example_field',
			__( 'Example option', 'a8csp-plugin-template' ),
			array( $this, 'render_field' ),
			'general',
			'a8csp_template_example_section'
		);
	}

	/**
	 * Renders the persisted value as a text field. Escaping on output is the point this example
	 * models, even though the value is also sanitized before WordPress persists it.
	 * The value comes through the typed reader in `includes/settings.php`, the worked example of
	 * reading an option this component registers.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function render_field(): void {
		\printf(
			'<input type="text" id="%1$s" name="%1$s" value="%2$s" />',
			esc_attr( 'a8csp_template_example_option' ),
			esc_attr( a8csp_template_get_example_option() )
		);
	}

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
		$sections['a8csp_template'] = __( 'A8CSP Template Plugin', 'a8csp-plugin-template' );

		return $sections;
	}

	/**
	 * Declares this section's WooCommerce settings rows. WooCommerce persists the field through its
	 * own settings save, so declaring the field is the whole persistence story and the option key
	 * still appears in the uninstallation footprint.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @param   array<int, array<string, mixed>> $settings        The settings rows for the current Advanced section.
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
				'title' => __( 'A8CSP Template Plugin', 'a8csp-plugin-template' ),
				'type'  => 'title',
				'id'    => 'a8csp_template_wc_example_section',
			),
			array(
				'title' => __( 'Example option', 'a8csp-plugin-template' ),
				'desc'  => __( 'A persisted example setting owned by the WooCommerce settings surface.', 'a8csp-plugin-template' ),
				// Mirrored in the `uninstall.php` footprint; the line there goes with this surface when it is deleted.
				'id'    => 'a8csp_template_wc_example_option',
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
