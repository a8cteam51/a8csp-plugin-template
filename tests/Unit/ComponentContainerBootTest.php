<?php declare( strict_types=1 );

namespace A8C\SpecialProjects\Template\Tests\Unit;

use A8C\SpecialProjects\Template\Boot\ComponentTree;
use A8C\SpecialProjects\Template\Integrations;
use A8C\SpecialProjects\Template\Integrations\WC_Settings_Section;
use A8C\SpecialProjects\Template\Plugin;
use A8C\SpecialProjects\Template\Tests\Unit\Doubles\ComponentBootLedger;
use A8C\SpecialProjects\Template\Tests\Unit\Doubles\RecordingContainerA;
use A8C\SpecialProjects\Template\Tests\Unit\Doubles\RecordingContainerB;
use A8C\SpecialProjects\Template\Tests\Unit\Doubles\RecordingLeafA;
use A8C\SpecialProjects\Template\Tests\Unit\Doubles\RecordingLeafB;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Exercises the public component-tree walker, subtree pruning, and graph validation.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
#[CoversClass( ComponentTree::class )]
#[CoversClass( Plugin::class )]
#[CoversClass( Integrations::class )]
final class ComponentContainerBootTest extends TestCase {
	/**
	 * Satisfies the production files' `ABSPATH` boot guard before their classes are first
	 * autoloaded.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public static function setUpBeforeClass(): void {
		if ( ! defined( 'ABSPATH' ) ) {
			define( 'ABSPATH', __DIR__ . '/' );
		}
	}

	/**
	 * Resets static double state before each test.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	protected function setUp(): void {
		parent::setUp();

		ComponentBootLedger::reset();
		RecordingContainerA::reset();
		RecordingContainerB::reset();
		RecordingLeafA::reset();
		RecordingLeafB::reset();
	}

	/**
	 * A container initializes before its children, which boot in declaration order.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function test_container_boots_parent_then_children_in_declared_order(): void {
		RecordingContainerA::$child_component_classes = array(
			RecordingLeafA::class,
			RecordingLeafB::class,
		);

		( new ComponentTree() )->boot( array( RecordingContainerA::class ) );

		self::assertSame(
			array(
				array(
					'event'     => 'constructed',
					'component' => RecordingContainerA::class,
				),
				array(
					'event'     => 'initialized',
					'component' => RecordingContainerA::class,
				),
				array(
					'event'     => 'constructed',
					'component' => RecordingLeafA::class,
				),
				array(
					'event'     => 'initialized',
					'component' => RecordingLeafA::class,
				),
				array(
					'event'     => 'constructed',
					'component' => RecordingLeafB::class,
				),
				array(
					'event'     => 'initialized',
					'component' => RecordingLeafB::class,
				),
			),
			ComponentBootLedger::$events
		);
	}

	/**
	 * A false container gate prunes its subtree before any child is constructed.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function test_false_container_gate_prunes_child_before_construction(): void {
		RecordingContainerA::$needed                  = false;
		RecordingContainerA::$child_component_classes = array( RecordingLeafA::class );

		( new ComponentTree() )->boot( array( RecordingContainerA::class ) );

		self::assertArrayHasKey( RecordingContainerA::class, ComponentBootLedger::$constructed );
		self::assertArrayNotHasKey( RecordingContainerA::class, ComponentBootLedger::$initialized );
		self::assertArrayNotHasKey( RecordingLeafA::class, ComponentBootLedger::$constructed );
	}

	/**
	 * A child class owned by two top-level containers is rejected across the shared graph.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function test_duplicate_child_across_top_level_containers_throws(): void {
		RecordingContainerA::$child_component_classes = array( RecordingLeafA::class );
		RecordingContainerB::$child_component_classes = array( RecordingLeafA::class );

		self::expectException( \LogicException::class );
		self::expectExceptionMessage(
			\sprintf(
				'Component %s is registered more than once; a component may belong to a single parent.',
				RecordingLeafA::class
			)
		);

		( new ComponentTree() )->boot(
			array(
				RecordingContainerA::class,
				RecordingContainerB::class,
			)
		);
	}

	/**
	 * A cycle is rejected when recursion reaches an already-seen component class.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function test_cycle_throws_when_class_is_revisited(): void {
		RecordingContainerA::$child_component_classes = array( RecordingContainerB::class );
		RecordingContainerB::$child_component_classes = array( RecordingContainerA::class );

		self::expectException( \LogicException::class );
		self::expectExceptionMessage(
			\sprintf(
				'Component %s is registered more than once; a component may belong to a single parent.',
				RecordingContainerA::class
			)
		);

		( new ComponentTree() )->boot( array( RecordingContainerA::class ) );
	}

	/**
	 * The plugin-wide gate is open by default.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function test_plugin_is_needed_returns_true_by_default(): void {
		self::assertTrue( ( new Plugin() )->is_needed() );
	}

	/**
	 * The real `Integrations` container declares `WC_Settings_Section` as its one child, the
	 * fleet's worked example of the container pattern in the flesh.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function test_integrations_children_include_wc_settings_section(): void {
		self::assertSame(
			array( WC_Settings_Section::class ),
			Integrations::get_child_component_classes()
		);
	}

	/**
	 * The walker marks every component it reaches regardless of that component's own gate. A
	 * second top-level mention of the real `WC_Settings_Section` leaf can therefore collide only
	 * when descent through the real `Integrations` container already reaches and marks that leaf.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function test_booting_integrations_reaches_the_wc_settings_section_leaf(): void {
		self::expectException( \LogicException::class );
		self::expectExceptionMessage(
			\sprintf(
				'Component %s is registered more than once; a component may belong to a single parent.',
				WC_Settings_Section::class
			)
		);

		( new ComponentTree() )->boot(
			array(
				Integrations::class,
				WC_Settings_Section::class,
			)
		);
	}
}
