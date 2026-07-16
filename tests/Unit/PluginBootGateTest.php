<?php declare( strict_types=1 );

namespace A8C\SpecialProjects\PluginTemplate\Tests\Unit;

use A8C\SpecialProjects\PluginTemplate\AbstractComponent;
use A8C\SpecialProjects\PluginTemplate\Blocks;
use A8C\SpecialProjects\PluginTemplate\Components;
use A8C\SpecialProjects\PluginTemplate\Integrations;
use A8C\SpecialProjects\PluginTemplate\Plugin;
use A8C\SpecialProjects\PluginTemplate\Settings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * Exercises the real `Plugin::boot()` pipeline outside WordPress through the recording hook
 * stubs — the plugin-wide host gate latches an un-booted plugin behind an explanatory notice,
 * open-gate components register their hooks once the host is present, a closed-gate integration
 * registers nothing, and a second boot is a no-op.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
#[CoversClass( Plugin::class )]
#[UsesClass( AbstractComponent::class )]
#[UsesClass( Blocks\Component::class )]
#[UsesClass( Components::class )]
#[UsesClass( Settings\Component::class )]
#[UsesClass( Integrations\Component::class )]
final class PluginBootGateTest extends TestCase {
	/**
	 * Satisfies the production files' `ABSPATH` boot guard and loads the recording hook stubs and
	 * the canned plugin metadata before the component classes are first autoloaded. The WooCommerce
	 * host stand-ins load per test instead, so the host-absent proofs stay meaningful.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public static function setUpBeforeClass(): void {
		if ( ! \defined( 'ABSPATH' ) ) {
			\define( 'ABSPATH', __DIR__ . '/' );
		}

		require_once __DIR__ . '/wp-hook-stubs.php';
		require_once __DIR__ . '/plugin-metadata-stubs.php';
	}

	/**
	 * Starts each test with an empty hook-registration ledger.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	protected function setUp(): void {
		parent::setUp();

		$GLOBALS['a8csp_template_test_hooks'] = array();
	}

	/**
	 * Without the WooCommerce host, the boot latches un-booted behind the explanatory notice and
	 * constructs nothing — and stays that way: a second boot cannot retry the gate.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	#[RunInSeparateProcess]
	public function test_boot_without_the_host_stays_unbooted(): void {
		$plugin = new Plugin();
		$plugin->boot();

		self::assertFalse( $plugin->is_booted() );
		self::assertSame( array( 'all_admin_notices' ), $GLOBALS['a8csp_template_test_hooks'] );

		$plugin->boot();

		self::assertFalse( $plugin->is_booted() );
		self::assertSame( array( 'all_admin_notices' ), $GLOBALS['a8csp_template_test_hooks'] );
	}

	/**
	 * With the host present but below the header-declared floor, the boot latches un-booted behind
	 * the below-floor notice and constructs nothing.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	#[RunInSeparateProcess]
	public function test_boot_below_the_host_floor_stays_unbooted(): void {
		\define( 'WC_VERSION', '9.0.0' );
		require_once __DIR__ . '/wc-host-stubs.php';

		$plugin = new Plugin();
		$plugin->boot();

		self::assertFalse( $plugin->is_booted() );
		self::assertSame( array( 'all_admin_notices' ), $GLOBALS['a8csp_template_test_hooks'] );
	}

	/**
	 * With the host at the floor, the boot flag reads false before the pipeline and true after it;
	 * open-gate components register their hooks while the Subscriptions-gated integration registers
	 * nothing when its companion is absent.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function test_boot_initializes_open_gate_components_and_skips_closed_gates(): void {
		require_once __DIR__ . '/wc-host-stubs.php';

		$plugin = new Plugin();
		self::assertFalse( $plugin->is_booted() );

		$plugin->boot();
		self::assertTrue( $plugin->is_booted() );

		self::assertContains( 'init', $GLOBALS['a8csp_template_test_hooks'] );
		self::assertContains( 'enqueue_block_editor_assets', $GLOBALS['a8csp_template_test_hooks'] );
		self::assertContains( 'admin_init', $GLOBALS['a8csp_template_test_hooks'] );
		self::assertContains( 'woocommerce_get_sections_advanced', $GLOBALS['a8csp_template_test_hooks'] );
		self::assertContains( 'woocommerce_get_settings_advanced', $GLOBALS['a8csp_template_test_hooks'] );

		self::assertNotContains( 'all_admin_notices', $GLOBALS['a8csp_template_test_hooks'] );
		self::assertNotContains( 'wcpay_metadata_from_order', $GLOBALS['a8csp_template_test_hooks'] );
		self::assertNotContains( 'woocommerce_subscriptions_product_price_string', $GLOBALS['a8csp_template_test_hooks'] );
	}

	/**
	 * A second boot on the same instance leaves the hook-registration ledger unchanged.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function test_second_boot_is_a_no_op(): void {
		require_once __DIR__ . '/wc-host-stubs.php';

		$plugin = new Plugin();
		$plugin->boot();

		$hook_count = \count( $GLOBALS['a8csp_template_test_hooks'] );

		$plugin->boot();

		self::assertCount( $hook_count, $GLOBALS['a8csp_template_test_hooks'] );
	}
}
