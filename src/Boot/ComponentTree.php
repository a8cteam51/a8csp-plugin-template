<?php declare( strict_types=1 );

namespace A8C\SpecialProjects\Template\Boot;

defined( 'ABSPATH' ) || exit;

/**
 * The component loader: boots a registry of component classes pre-order (parent before children),
 * gates each one through `is_needed()` — a false gate prunes the whole subtree unconstructed —
 * descends into a container's listed children, and throws `LogicException` when a class is
 * reached twice anywhere in the graph (a component may belong to a single parent).
 *
 * You don't edit or subclass this — you list components in `Plugin::COMPONENTS` and it boots
 * them.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
final class ComponentTree {
	// region METHODS

	/**
	 * Boots the top-level registry in order with one duplicate ledger shared across the graph.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @param   array<int, class-string<Component>> $registry The top-level component classes to boot, in order.
	 *
	 * @throws  \LogicException When a component class appears more than once in the graph.
	 *
	 * @return  void
	 */
	public function boot( array $registry ): void {
		$seen = array();
		foreach ( $registry as $component_class ) {
			$this->boot_component( $component_class, $seen );
		}
	}

	/**
	 * Boots one component and recurses into its declared children: gate, initialize, descend. A
	 * component whose `is_needed()` returns false prunes its whole subtree unconstructed. A class
	 * reached twice anywhere in the graph is a developer error.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @param   class-string<Component>              $component_class The component class to boot.
	 * @param   array<class-string<Component>, true> $seen             Component classes already
	 *                                                                 reached in the graph.
	 *
	 * @throws  \LogicException When a component class appears more than once in the graph.
	 *
	 * @return  void
	 */
	private function boot_component( string $component_class, array &$seen ): void {
		if ( isset( $seen[ $component_class ] ) ) {
			// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Registered class names are developer-controlled identifiers in a LogicException.
			throw new \LogicException(
				\sprintf(
					'Component %s is registered more than once; a component may belong to a single parent.',
					$component_class
				)
			);
			// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}
		$seen[ $component_class ] = true;

		$component = new $component_class();
		if ( ! $component->is_needed() ) {
			return;
		}

		$component->initialize();

		if ( $component instanceof ComponentContainer ) {
			foreach ( $component::get_child_component_classes() as $child_class ) {
				$this->boot_component( $child_class, $seen );
			}
		}
	}

	// endregion
}
