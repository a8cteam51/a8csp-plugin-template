<?php declare( strict_types=1 );

namespace A8C\SpecialProjects\Template\Tests\Integration;

use A8C\SpecialProjects\Template\Integrations;
use A8C\SpecialProjects\Template\Integrations\WC_Settings_Section;
use A8C\SpecialProjects\Template\Settings;
use PHPUnit\Framework\TestCase;

/**
 * Proves Plugin::boot() boots the whole component tree independently of WooCommerce: a
 * WooCommerce-independent block registers and Settings wires itself when WooCommerce is inactive,
 * while the WooCommerce-dependent component (WC_Settings_Section, nested under the Integrations
 * container) reports itself as not needed. Only meaningful with WooCommerce deactivated, so it
 * self-skips in the standard `composer test:integration` run (WooCommerce is active there) — see
 * tests/README.md for how to exercise it against a WooCommerce-less runtime.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
final class PluginBootWithoutWooCommerceTest extends TestCase {
	/**
	 * With WooCommerce inactive, the boot hook runs, Blocks registers its block, Settings wires its
	 * registration callback, the Integrations container declares WC_Settings_Section as its child,
	 * and the WC-dependent leaf reports itself as not needed.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function test_plugin_boots_and_blocks_register_without_woocommerce(): void {
		if ( \class_exists( 'WooCommerce' ) ) {
			self::markTestSkipped( 'This proof only runs against a WooCommerce-less runtime; WooCommerce is active in this run.' );
		}

		self::assertNotInstanceOf( \WP_Error::class, A8CSP_TEMPLATE_REQUIREMENTS );
		self::assertTrue( \function_exists( 'a8csp_template_plugin' ) );

		$block_metadata = \json_decode(
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local filesystem read of a tracked build artifact, not a remote resource.
			(string) \file_get_contents( \constant( 'A8CSP_TEMPLATE_DIR_PATH' ) . 'blocks/build/foobar/block.json' ),
			true,
			512,
			JSON_THROW_ON_ERROR
		);

		self::assertTrue( \WP_Block_Type_Registry::get_instance()->is_registered( $block_metadata['name'] ) );
		self::assertSame( 1, $this->count_settings_admin_init_registrations() );
		self::assertSame( array( WC_Settings_Section::class ), Integrations::get_child_component_classes() );
		self::assertFalse( ( new WC_Settings_Section() )->is_needed() );
	}

	/**
	 * Counts the `admin_init` hook registrations that target a `Settings` instance's
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
				if ( \is_array( $callback ) && ( $callback[0] ?? null ) instanceof Settings && 'register_settings' === ( $callback[1] ?? null ) ) {
					++$count;
				}
			}
		}

		return $count;
	}
}
