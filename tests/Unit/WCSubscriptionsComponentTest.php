<?php declare( strict_types=1 );

namespace A8C\SpecialProjects\PluginTemplate\Tests\Unit;

use A8C\SpecialProjects\PluginTemplate\Integrations\WC_Subscriptions\Component;
use A8C\SpecialProjects\PluginTemplate\Integrations\WC_Subscriptions\Price_Note;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * Exercises the grown WooCommerce Subscriptions integration outside WordPress: the readiness
 * phase wires the collaborator without registering anything, the hook phase attaches both the
 * component's and the collaborator's callbacks, and a hook phase without a readiness phase fails
 * loud. Companion-gated paths run in separate processes so the stand-in never leaks into the
 * shared process.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
#[CoversClass( Component::class )]
#[UsesClass( Price_Note::class )]
final class WCSubscriptionsComponentTest extends TestCase {
	/**
	 * Satisfies the production files' `ABSPATH` boot guard and loads the recording hook stubs
	 * before the component classes are first autoloaded.
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
	 * Without the Subscriptions companion, the gate reports the integration as not needed.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	#[RunInSeparateProcess]
	public function test_is_needed_is_false_without_the_companion(): void {
		self::assertFalse( Component::is_needed() );
	}

	/**
	 * The readiness phase wires the collaborator without registering anything; the hook phase
	 * attaches the component's settings filter and the collaborator's price-string callback.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	#[RunInSeparateProcess]
	public function test_hook_phase_attaches_component_and_collaborator_callbacks(): void {
		require_once __DIR__ . '/wcs-stubs.php';

		self::assertTrue( Component::is_needed() );

		$component = new Component();

		$component->initialize();
		self::assertSame( array(), $GLOBALS['a8csp_template_test_hooks'] );

		$component->register_hooks();
		self::assertContains( 'woocommerce_get_settings_advanced', $GLOBALS['a8csp_template_test_hooks'] );
		self::assertContains( 'woocommerce_subscriptions_product_price_string', $GLOBALS['a8csp_template_test_hooks'] );
	}

	/**
	 * A hook phase without a readiness phase fails loud instead of attaching a half-built
	 * component.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function test_hook_phase_before_readiness_fails_loud(): void {
		$this->expectException( \LogicException::class );

		( new Component() )->register_hooks();
	}
}
