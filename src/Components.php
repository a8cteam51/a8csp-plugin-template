<?php declare( strict_types=1 );

namespace A8C\SpecialProjects\PluginTemplate;

\defined( 'ABSPATH' ) || exit;

/**
 * A gated collection of components: the component-loop machinery, owned once. The composition
 * root and any group root delegate their gate-construct and phase loops here instead of
 * hand-rolling them — has-a, not is-a: the collection runs components, so it does not implement
 * `ComponentInterface` itself.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
final class Components {
	// region FIELDS AND CONSTANTS

	/**
	 * The components that survived their gates, keyed by class so activity is answerable by name.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @var     array<class-string<ComponentInterface>, ComponentInterface>
	 */
	private array $components = array();

	// endregion

	// region CONSTRUCTORS

	/**
	 * Components constructor. Private: `assemble()` is the only way to build the collection, so a
	 * component can never enter it without passing its gate.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	private function __construct() {}

	// endregion

	// region METHODS

	/**
	 * Gates and constructs the given component classes into a collection.
	 *
	 * The static gates run BEFORE construction, so an optional integration whose companion is
	 * absent is never autoloaded into a fatal; the survivors construct in registration order and
	 * run each later phase in that same order.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @param   array<int, class-string<ComponentInterface>> $component_classes The component classes to gate and construct.
	 *
	 * @return  self
	 */
	public static function assemble( array $component_classes ): self {
		$collection = new self();
		foreach ( $component_classes as $component_class ) {
			if ( $component_class::is_needed() ) {
				$collection->components[ $component_class ] = new $component_class();
			}
		}

		return $collection;
	}

	/**
	 * Runs the readiness phase across every component in the collection.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function initialize(): void {
		foreach ( $this->components as $component ) {
			$component->initialize();
		}
	}

	/**
	 * Runs the hook phase across every component in the collection.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function register_hooks(): void {
		foreach ( $this->components as $component ) {
			$component->register_hooks();
		}
	}

	/**
	 * Whether the given component class survived its gate into this collection.
	 *
	 * A fail-loud boot is all-or-nothing, so "did component X boot?" decomposes into the root's
	 * `is_booted()` — the pipeline completed — plus this check — X survived its gate. There is
	 * deliberately no per-component failure state: a component failure fails the whole boot. And
	 * the check is deliberately collection-scoped: a caller holds the collection it asks, so the
	 * answer never straddles composition levels, and the class-keyed lookup never becomes a public
	 * contract that promoting a leaf into its own folder would silently break.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @param   class-string<ComponentInterface> $component_class The component class to look up.
	 *
	 * @return  bool
	 */
	public function is_active( string $component_class ): bool {
		return isset( $this->components[ $component_class ] );
	}

	// endregion
}
