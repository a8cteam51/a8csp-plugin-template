<?php declare( strict_types=1 );

namespace A8C\SpecialProjects\PluginTemplate\Tests\Unit;

use A8C\SpecialProjects\PluginTemplate\AbstractComponent;
use A8C\SpecialProjects\PluginTemplate\Blocks;
use A8C\SpecialProjects\PluginTemplate\Components;
use A8C\SpecialProjects\PluginTemplate\Integrations\WC_Subscriptions;
use A8C\SpecialProjects\PluginTemplate\Settings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * Exercises the shared component collection outside WordPress: gates run before construction so a
 * closed gate keeps a component out of the map entirely, activity is answerable by class, and the
 * phase loops run in registration order.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
#[CoversClass( Components::class )]
#[UsesClass( AbstractComponent::class )]
#[UsesClass( Blocks\Component::class )]
#[UsesClass( Settings\Component::class )]
#[UsesClass( WC_Subscriptions\Component::class )]
final class ComponentsTest extends TestCase {
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
	 * A closed gate keeps its component out of the collection — no construction, no activity —
	 * while an open gate admits its component; assembly itself registers nothing.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function test_assemble_gates_before_construction(): void {
		$components = Components::assemble( array( Settings\Component::class, WC_Subscriptions\Component::class ) );

		self::assertTrue( $components->is_active( Settings\Component::class ) );
		self::assertFalse( $components->is_active( WC_Subscriptions\Component::class ) );
		self::assertSame( array(), $GLOBALS['a8csp_template_test_hooks'] );
	}

	/**
	 * The readiness loop registers nothing, and the hook loop registers every survivor's hooks in
	 * registration order.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function test_phase_loops_run_in_registration_order(): void {
		$components = Components::assemble( array( Blocks\Component::class, Settings\Component::class ) );

		$components->initialize();
		self::assertSame( array(), $GLOBALS['a8csp_template_test_hooks'] );

		$components->register_hooks();
		self::assertSame( array( 'init', 'enqueue_block_editor_assets', 'admin_init', 'woocommerce_get_sections_advanced', 'woocommerce_get_settings_advanced' ), $GLOBALS['a8csp_template_test_hooks'] );
	}
}
