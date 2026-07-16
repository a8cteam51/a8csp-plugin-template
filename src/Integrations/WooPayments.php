<?php declare( strict_types=1 );

namespace A8C\SpecialProjects\PluginTemplate\Integrations;

use A8C\SpecialProjects\PluginTemplate\AbstractComponent;

\defined( 'ABSPATH' ) || exit;

/**
 * Provides the WooPayments integration — the worked example of the simple leaf shape: one
 * descriptively-named class, one companion gate, one honest filter. A leaf is promoted to a
 * folder owning its own `Component` the day it needs a second class; the WooCommerce
 * Subscriptions sibling shows that grown shape.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
final class WooPayments extends AbstractComponent {
	// region METHODS

	/**
	 * Returns true when WooPayments is active. The companion gate is the whole point of an
	 * integration component: everything below may assume WooPayments exists.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  bool
	 */
	public static function should_load(): bool {
		return \class_exists( 'WC_Payments' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function register_hooks(): void {
		add_filter( 'wcpay_metadata_from_order', array( $this, 'add_order_metadata' ) );
	}

	// endregion

	// region HOOKS

	/**
	 * Adds the template's demonstration entry to the payment metadata WooPayments generates from
	 * an order; replace this with the integration's real behavior.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @param   array<string, mixed> $metadata The payment metadata generated from the order.
	 *
	 * @return  array<string, mixed>
	 */
	public function add_order_metadata( array $metadata ): array {
		$metadata['a8csp_template_example'] = 'demonstration-value';

		return $metadata;
	}

	// endregion
}
