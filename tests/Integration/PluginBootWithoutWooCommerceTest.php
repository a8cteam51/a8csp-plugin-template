<?php declare( strict_types=1 );

namespace A8C\SpecialProjects\PluginTemplate\Tests\Integration;

use A8C\SpecialProjects\PluginTemplate\Settings;
use PHPUnit\Framework\TestCase;

/**
 * Proves the plugin-wide host gate in Plugin::boot(): with WooCommerce inactive the boot latches
 * un-booted for the request — no component constructs, the block does not register, no settings
 * wiring runs — and the "requires WooCommerce" notice is staged instead. The test is meaningful
 * only with WooCommerce deactivated, so it self-skips in the standard `composer test:integration`
 * run (WooCommerce is active there) — see tests/README.md for how to exercise it against a
 * WooCommerce-less runtime.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
final class PluginBootWithoutWooCommerceTest extends TestCase {
	/**
	 * With WooCommerce inactive, the requirements gate still passes and the boot hook still runs,
	 * but the host gate latches the plugin un-booted: the block is not registered, Settings wires
	 * nothing, no WooCommerce filters exist, and the missing-host notice is staged on
	 * `all_admin_notices`.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function test_plugin_stays_unbooted_without_woocommerce(): void {
		if ( \class_exists( 'WooCommerce' ) ) {
			self::markTestSkipped( 'This proof only runs against a WooCommerce-less runtime; WooCommerce is active in this run.' );
		}

		self::assertNotInstanceOf( \WP_Error::class, A8CSP_TEMPLATE_REQUIREMENTS_RESULT );
		self::assertTrue( \function_exists( 'a8csp_template_plugin' ) );

		self::assertFalse( a8csp_template_plugin()->is_booted() );
		// The gate's notice callback is an inline closure, so the assertion can only check that
		// something is staged on the hook, not the specific target.
		self::assertTrue( has_action( 'all_admin_notices' ) );

		$block_metadata = \json_decode(
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local filesystem read of a tracked build artifact, not a remote resource.
			(string) \file_get_contents( \constant( 'A8CSP_TEMPLATE_DIR_PATH' ) . 'blocks/build/example-notice/block.json' ),
			true,
			512,
			JSON_THROW_ON_ERROR
		);

		self::assertFalse( \WP_Block_Type_Registry::get_instance()->is_registered( $block_metadata['name'] ) );
		self::assertSame( 0, $this->count_settings_admin_init_registrations() );
		self::assertFalse( has_filter( 'woocommerce_get_sections_advanced' ) );
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
