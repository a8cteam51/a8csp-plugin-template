<?php declare( strict_types=1 );

namespace A8C\SpecialProjects\Template\Tests\Unit;

use A8C\SpecialProjects\Template\Integrations\WC_Settings_Section;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Exercises the WooCommerce-core gate's negative case and the pure version comparison without
 * WordPress.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
#[CoversClass( WC_Settings_Section::class )]
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
