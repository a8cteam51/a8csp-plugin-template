<?php declare( strict_types=1 );

/**
 * Minimal WooPayments stand-in for Unit tests that open the WooPayments integration's companion
 * gate outside WordPress. The guard keeps the file inert wherever the real plugin is loaded.
 *
 * @since   1.0.0
 * @version 1.0.0
 * @package A8C\SpecialProjects\PluginTemplate
 */

if ( ! \class_exists( 'WC_Payments' ) ) {
	/**
	 * Stand-in for the WooPayments main class; presence is all the companion gate checks.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	class WC_Payments {}
}
