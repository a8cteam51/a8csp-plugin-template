<?php declare( strict_types=1 );

namespace A8C\SpecialProjects\Template\Tests\Unit;

use A8C\SpecialProjects\Template\Framework\ComponentTree;
use A8C\SpecialProjects\Template\Integrations\WC_Settings_Section;
use A8C\SpecialProjects\Template\Tests\Unit\Doubles\RecordingWCSettingsSection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Exercises WC_Settings_Section::is_needed(), WC_Settings_Section::meets_minimum_wc_version(), and
 * the public ComponentTree::boot() walker: the negative integration gate, its pure version
 * comparison, and component gating. The production classes require only the ABSPATH boot guard
 * for these Unit tests, which use hand-rolled recording doubles instead of Mockery or Brain-Monkey.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
#[CoversClass( WC_Settings_Section::class )]
#[CoversClass( ComponentTree::class )]
final class WCSettingsSectionTest extends TestCase {
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
	 * Without WooCommerce core loaded, the settings section reports itself as not needed.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function test_is_needed_is_false_without_the_real_plugin(): void {
		self::assertFalse( ( new WC_Settings_Section() )->is_needed() );
	}

	/**
	 * The registry gate never initializes a component that reports itself as not needed.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function test_component_tree_skips_initialization_when_not_needed(): void {
		RecordingWCSettingsSection::reset();
		RecordingWCSettingsSection::$needed = false;

		( new ComponentTree() )->boot( array( RecordingWCSettingsSection::class ) );

		self::assertFalse( RecordingWCSettingsSection::$initialized );
	}

	/**
	 * The registry gate initializes a component that reports itself as needed.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function test_component_tree_runs_initialization_when_needed(): void {
		RecordingWCSettingsSection::reset();

		( new ComponentTree() )->boot( array( RecordingWCSettingsSection::class ) );

		self::assertTrue( RecordingWCSettingsSection::$initialized );
	}

	/**
	 * Without a header-declared floor, every installed WooCommerce version meets the requirement.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function test_minimum_wc_version_accepts_a_missing_floor(): void {
		self::assertTrue( WC_Settings_Section::meets_minimum_wc_version( '1.0.0', null ) );
	}

	/**
	 * An installed WooCommerce version below the header-declared floor fails the requirement.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function test_minimum_wc_version_rejects_a_version_below_the_floor(): void {
		self::assertFalse( WC_Settings_Section::meets_minimum_wc_version( '9.9.0', '10.0.0' ) );
	}

	/**
	 * An installed WooCommerce version equal to the header-declared floor meets the requirement.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function test_minimum_wc_version_accepts_a_version_equal_to_the_floor(): void {
		self::assertTrue( WC_Settings_Section::meets_minimum_wc_version( '10.0.0', '10.0.0' ) );
	}

	/**
	 * An installed WooCommerce version above the header-declared floor meets the requirement.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function test_minimum_wc_version_accepts_a_version_above_the_floor(): void {
		self::assertTrue( WC_Settings_Section::meets_minimum_wc_version( '10.0.1', '10.0.0' ) );
	}
}
