<?php declare( strict_types=1 );

namespace A8C\SpecialProjects\PluginTemplate\Tests\Integration;

use A8C\SpecialProjects\PluginTemplate\Blocks;
use A8C\SpecialProjects\PluginTemplate\Plugin;
use A8C\SpecialProjects\PluginTemplate\Settings;
use PHPUnit\Framework\TestCase;

/**
 * Verifies the plugin boots on a supported runtime inside wp-env: the requirements gate
 * passes, the component registry runs, and the demo components wire and register their
 * WordPress and WooCommerce functionality. The cached accessor and plugin boot are idempotent.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
final class PluginBootTest extends TestCase {
	/**
	 * On an at-floor runtime the requirements gate passes, `plugins_loaded` is wired to the
	 * memoized root's `boot()` (the accessor constructs eagerly at include time, so the wiring
	 * check below resolves against the same instance), and by request time the pipeline has run
	 * the demo components far enough to register the block, wire and register the base setting,
	 * and expose the WooCommerce section and its persisted field.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function test_plugin_boots_on_supported_runtime(): void {
		self::assertNotInstanceOf( \WP_Error::class, A8CSP_TEMPLATE_REQUIREMENTS_RESULT );
		self::assertTrue( \function_exists( 'a8csp_template_plugin' ) );
		self::assertNotFalse( has_action( 'plugins_loaded', array( a8csp_template_plugin(), 'boot' ) ) );
		self::assertInstanceOf( Plugin::class, a8csp_template_plugin() );
		self::assertTrue( a8csp_template_plugin()->is_booted() );

		$block_metadata = \json_decode(
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local filesystem read of a tracked build artifact, not a remote resource.
			(string) \file_get_contents( \constant( 'A8CSP_TEMPLATE_DIR_PATH' ) . 'blocks/build/example-notice/block.json' ),
			true,
			512,
			JSON_THROW_ON_ERROR
		);

		self::assertTrue( \WP_Block_Type_Registry::get_instance()->is_registered( $block_metadata['name'] ) );
		self::assertSame( 1, $this->count_settings_admin_init_registrations() );

		// register_settings() runs on `admin_init`, where WordPress has loaded the wp-admin
		// includes; loading the settings template functions here simulates that context for
		// the direct call below.
		require_once ABSPATH . 'wp-admin/includes/template.php';

		// A direct call verifies registration without firing every shared `admin_init` callback.
		( new Settings\Component() )->register_settings();
		self::assertTrue( \array_key_exists( 'a8csp_template_example_option', get_registered_settings() ) );

		$sections = apply_filters( 'woocommerce_get_sections_advanced', array() );
		self::assertArrayHasKey( 'a8csp_template', $sections );

		$rows = apply_filters( 'woocommerce_get_settings_advanced', array(), 'a8csp_template' );
		self::assertContains( 'a8csp_template_wc_example_option', \array_column( $rows, 'id' ) );
	}

	/**
	 * `Plugin::boot()` is idempotent: the `plugins_loaded` boot has already run, so a second
	 * call must not construct or wire the components again — the `Blocks` component's callback
	 * stays registered on `init` exactly once.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function test_second_boot_does_not_rewire_components(): void {
		self::assertSame( 1, $this->count_blocks_init_registrations() );

		$plugin = a8csp_template_plugin();
		self::assertInstanceOf( Plugin::class, $plugin );
		$plugin->boot();

		self::assertSame( 1, $this->count_blocks_init_registrations() );
	}

	/**
	 * Counts the `init` hook registrations that target a `Blocks\Component` instance's `register_blocks`
	 * method, across all priorities.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  int
	 */
	private function count_blocks_init_registrations(): int {
		$hook = $GLOBALS['wp_filter']['init'] ?? null;
		if ( ! $hook instanceof \WP_Hook ) {
			return 0;
		}

		$count = 0;
		foreach ( $hook->callbacks as $priority_callbacks ) {
			foreach ( $priority_callbacks as $registration ) {
				$callback = $registration['function'];
				if ( \is_array( $callback ) && ( $callback[0] ?? null ) instanceof Blocks\Component && 'register_blocks' === ( $callback[1] ?? null ) ) {
					++$count;
				}
			}
		}

		return $count;
	}

	/**
	 * Counts the `admin_init` hook registrations that target a `Settings\Component` instance's
	 * `register_settings` method, across all priorities.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  int
	 */
	private function count_settings_admin_init_registrations(): int {
		$hook = $GLOBALS['wp_filter']['admin_init'] ?? null;
		if ( ! $hook instanceof \WP_Hook ) {
			return 0;
		}

		$count = 0;
		foreach ( $hook->callbacks as $priority_callbacks ) {
			foreach ( $priority_callbacks as $registration ) {
				$callback = $registration['function'];
				if ( \is_array( $callback ) && ( $callback[0] ?? null ) instanceof Settings\Component && 'register_settings' === ( $callback[1] ?? null ) ) {
					++$count;
				}
			}
		}

		return $count;
	}
}
