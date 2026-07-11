<?php declare( strict_types=1 );

namespace A8C\SpecialProjects\Template\Tests\Unit\Doubles;

use A8C\SpecialProjects\Template\Integrations\WC_Settings_Section;

/**
 * Hand-rolled recording double for WC_Settings_Section: reports a configurable needed-state and
 * records whether `initialize()` ran instead of registering the real hooks.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
final class RecordingWCSettingsSection extends WC_Settings_Section {
	/**
	 * Whether `initialize()` was invoked.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @var     bool
	 */
	public static bool $initialized = false;

	/**
	 * The configured return value for `is_needed()`.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @var     bool
	 */
	public static bool $needed = true;

	/**
	 * Restores the double's default state.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public static function reset(): void {
		self::$needed      = true;
		self::$initialized = false;
	}

	/**
	 * Returns the canned needed-state instead of checking for the real plugin.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  bool
	 */
	public function is_needed(): bool {
		return self::$needed;
	}

	/**
	 * Records that initialization ran instead of registering real hooks.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function initialize(): void {
		self::$initialized = true;
	}
}
