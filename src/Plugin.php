<?php declare( strict_types=1 );

namespace A8C\SpecialProjects\Template;

use A8C\SpecialProjects\Template\Boot\Component;
use A8C\SpecialProjects\Template\Boot\ComponentTree;

defined( 'ABSPATH' ) || exit;

/**
 * This is the plugin file engineers edit: `COMPONENTS` holds the top-level component registry,
 * and `is_needed()` provides the optional whole-plugin gate. The boot plumbing lives in
 * `src/Boot/`.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
final class Plugin {
	// region FIELDS AND CONSTANTS

	/**
	 * Add the plugin's top-level components here; they boot in registration order. A component
	 * implementing `ComponentContainer` boots its declared children immediately after itself.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @var     array<int, class-string<Component>>
	 */
	private const COMPONENTS = array(
		Blocks::class,
		Settings::class,
		Integrations::class,
	);

	/**
	 * Whether `boot()` has already run.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @var     bool
	 */
	private bool $booted = false;

	// endregion

	// region METHODS

	/**
	 * Returns true if the plugin should boot on the current site.
	 *
	 * A plugin that is gated as a whole — e.g. one that requires WooCommerce for everything it
	 * does — expresses that check here once instead of in every component.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  bool
	 */
	public function is_needed(): bool {
		return true;
	}

	// endregion

	// region HOOKS

	/**
	 * Boots the plugin's component tree through the `Boot\ComponentTree` loader when the plugin
	 * reports itself as needed. Idempotent: only the first eligible call has any effect.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function boot(): void {
		if ( $this->booted || ! $this->is_needed() ) {
			return;
		}

		$this->booted = true;

		( new ComponentTree() )->boot( self::COMPONENTS );
	}

	// endregion
}
