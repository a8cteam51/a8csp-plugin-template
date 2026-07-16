<?php declare( strict_types=1 );

namespace A8C\SpecialProjects\PluginTemplate\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

/**
 * Proves the GitHub release updater's channel and packaging decisions: a stable installation
 * follows only the latest-release endpoint while a prerelease installation scans the full
 * release list for the first non-draft entry, the update package is the release asset matched
 * by name, an up-to-date installation is offered nothing, and a failed fetch is negative-cached
 * briefly so update checks don't hammer a failing API.
 *
 * Loading the real `functions-bootstrap.php` would shadow the canned metadata stubs other
 * tests in the shared process rely on, so every test runs `#[RunInSeparateProcess]`.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
#[CoversFunction( 'a8csp_template_check_github_release_update' )]
final class GitHubReleaseUpdateTest extends TestCase {
	// region LIFECYCLE.

	/**
	 * Satisfies the bootstrap file's guards, stages the HTTP/transient stubs, and loads the
	 * real `functions-bootstrap.php` under test with clean ledgers.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	protected function setUp(): void {
		parent::setUp();

		\defined( 'ABSPATH' ) || \define( 'ABSPATH', '/tmp/' );
		\defined( 'A8CSP_TEMPLATE_BASENAME' ) || \define( 'A8CSP_TEMPLATE_BASENAME', 'a8csp-plugin-template/a8csp-template-plugin.php' );

		require_once __DIR__ . '/wp-http-stubs.php';
		require_once \dirname( __DIR__, 2 ) . '/functions-bootstrap.php';

		$GLOBALS['a8csp_template_test_transients']     = array();
		$GLOBALS['a8csp_template_test_transient_ttls'] = array();
		$GLOBALS['a8csp_template_test_http_requests']  = array();
	}

	// endregion.

	// region TESTS.

	/**
	 * A stable installation queries the latest-release endpoint — the stable channel by
	 * definition — and is offered the newer release packaged as its name-matched asset.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	#[RunInSeparateProcess]
	public function test_stable_install_follows_the_latest_release_endpoint(): void {
		$GLOBALS['a8csp_template_test_http_response'] = self::a_release_response(
			array(
				'tag_name' => 'v2.0.0',
				'html_url' => 'https://github.com/a8cteam51/a8csp-plugin-template/releases/tag/v2.0.0',
				'assets'   => array(
					array(
						'name'                 => 'not-the-plugin.zip',
						'browser_download_url' => 'https://example.com/wrong.zip',
					),
					array(
						'name'                 => 'a8csp-plugin-template.zip',
						'browser_download_url' => 'https://example.com/right.zip',
					),
				),
			)
		);

		$update = a8csp_template_check_github_release_update( false, self::plugin_data( '1.0.0' ), \constant( 'A8CSP_TEMPLATE_BASENAME' ) );

		self::assertStringEndsWith( '/releases/latest', $GLOBALS['a8csp_template_test_http_requests'][0] );
		self::assertIsArray( $update );
		self::assertSame( '2.0.0', $update['version'] );
		self::assertSame( 'https://example.com/right.zip', $update['package'], 'The package must be the asset matched by name, not the first asset' );
	}

	/**
	 * A prerelease installation queries the full release list and follows the first non-draft
	 * entry — the every-release channel — skipping drafts.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	#[RunInSeparateProcess]
	public function test_prerelease_install_follows_the_first_nondraft_of_the_release_list(): void {
		$GLOBALS['a8csp_template_test_http_response'] = self::a_release_response(
			array(
				array(
					'draft'    => true,
					'tag_name' => 'v1.2.0-beta.2',
				),
				array(
					'draft'    => false,
					'tag_name' => 'v1.2.0-beta.1',
					'html_url' => 'https://github.com/a8cteam51/a8csp-plugin-template/releases/tag/v1.2.0-beta.1',
					'assets'   => array(
						array(
							'name'                 => 'a8csp-plugin-template.zip',
							'browser_download_url' => 'https://example.com/beta.zip',
						),
					),
				),
			)
		);

		$update = a8csp_template_check_github_release_update( false, self::plugin_data( '1.1.0-beta.3' ), \constant( 'A8CSP_TEMPLATE_BASENAME' ) );

		self::assertStringContainsString( 'releases?per_page=10', $GLOBALS['a8csp_template_test_http_requests'][0] );
		self::assertIsArray( $update );
		self::assertSame( '1.2.0-beta.1', $update['version'], 'The draft entry must be skipped for the first published release' );
	}

	/**
	 * An up-to-date installation is offered nothing, and the usable release is cached for the
	 * full hour so the next check skips the network.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	#[RunInSeparateProcess]
	public function test_up_to_date_install_is_offered_nothing(): void {
		$GLOBALS['a8csp_template_test_http_response'] = self::a_release_response(
			array(
				'tag_name' => 'v2.0.0',
				'html_url' => 'https://github.com/a8cteam51/a8csp-plugin-template/releases/tag/v2.0.0',
				'assets'   => array(
					array(
						'name'                 => 'a8csp-plugin-template.zip',
						'browser_download_url' => 'https://example.com/right.zip',
					),
				),
			)
		);

		$update = a8csp_template_check_github_release_update( false, self::plugin_data( '2.0.0' ), \constant( 'A8CSP_TEMPLATE_BASENAME' ) );

		self::assertFalse( $update );
		self::assertSame( \constant( 'HOUR_IN_SECONDS' ), $GLOBALS['a8csp_template_test_transient_ttls']['a8csp_template_github_latest_release'], 'A usable release caches for the full hour even when no update is offered' );
	}

	/**
	 * A failed fetch offers nothing and negative-caches an empty result for five minutes, so
	 * repeated update checks back off a failing API instead of hammering it.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	#[RunInSeparateProcess]
	public function test_failed_fetch_negative_caches_for_five_minutes(): void {
		$GLOBALS['a8csp_template_test_http_response'] = array(
			'response' => array( 'code' => 500 ),
			'body'     => '',
		);

		$update = a8csp_template_check_github_release_update( false, self::plugin_data( '1.0.0' ), \constant( 'A8CSP_TEMPLATE_BASENAME' ) );

		self::assertFalse( $update );
		self::assertSame( array(), $GLOBALS['a8csp_template_test_transients']['a8csp_template_github_latest_release'] );
		self::assertSame( 5 * \constant( 'MINUTE_IN_SECONDS' ), $GLOBALS['a8csp_template_test_transient_ttls']['a8csp_template_github_latest_release'] );
	}

	/**
	 * The filter passes through untouched — no network, no cache — for other plugins' checks
	 * and when an earlier filter already supplied an update.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	#[RunInSeparateProcess]
	public function test_foreign_checks_and_settled_updates_pass_through(): void {
		$foreign = a8csp_template_check_github_release_update( false, self::plugin_data( '1.0.0' ), 'some-other-plugin/some-other-plugin.php' );
		self::assertFalse( $foreign );

		$settled = a8csp_template_check_github_release_update( array( 'version' => '9.9.9' ), self::plugin_data( '1.0.0' ), \constant( 'A8CSP_TEMPLATE_BASENAME' ) );
		self::assertSame( array( 'version' => '9.9.9' ), $settled );

		self::assertSame( array(), $GLOBALS['a8csp_template_test_http_requests'], 'Pass-through paths must not touch the network' );
	}

	// endregion.

	// region HELPERS.

	/**
	 * Builds a staged 200 response whose body is the given release payload as JSON.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @param   array<int|string, mixed> $payload The decoded release payload GitHub would return.
	 *
	 * @return  array<string, mixed>
	 */
	private static function a_release_response( array $payload ): array {
		return array(
			'response' => array( 'code' => 200 ),
			// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- The Unit process runs without WordPress, so wp_json_encode() does not exist here.
			'body'     => (string) \json_encode( $payload ),
		);
	}

	/**
	 * Builds the plugin-header data the updater receives, at the given installed version.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @param   string $version The installed plugin version.
	 *
	 * @return  array<string, string>
	 */
	private static function plugin_data( string $version ): array {
		return array(
			'Version'    => $version,
			'TextDomain' => 'a8csp-plugin-template',
		);
	}

	// endregion.
}
