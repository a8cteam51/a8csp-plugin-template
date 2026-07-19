<?php declare( strict_types=1 );

namespace A8C\SpecialProjects\PluginTemplate\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

/**
 * Proves the GitHub release updater's channel and packaging decisions: a stable installation
 * follows only the latest-release endpoint while a prerelease installation scans the full
 * release list for the first non-draft entry, each channel caches under its own transient key
 * so a channel switch never serves the other channel's releases, the update package is the
 * release asset matched by name, an up-to-date installation is offered nothing, and a failed
 * fetch is negative-cached briefly so update checks don't hammer a failing API.
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
	 * A prerelease installation queries the full release list — the every-release channel — and
	 * skips draft entries.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	#[RunInSeparateProcess]
	public function test_prerelease_install_skips_draft_entries_on_the_release_list(): void {
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
		self::assertSame( '1.2.0-beta.1', $update['version'], 'The draft entry must be skipped' );
		self::assertArrayHasKey( 'a8csp_template_github_latest_release_prerelease', $GLOBALS['a8csp_template_test_transients'], 'The prerelease channel caches under its own key' );
		self::assertArrayNotHasKey( 'a8csp_template_github_latest_release_stable', $GLOBALS['a8csp_template_test_transients'], 'The prerelease channel must not touch the stable cache' );
	}

	/**
	 * The release list is ordered by publish time, not version, so a newer prerelease listed after
	 * an older one is still offered — the updater scans for the highest version, not the first entry.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	#[RunInSeparateProcess]
	public function test_prerelease_install_follows_the_highest_version_not_the_first_listed(): void {
		$GLOBALS['a8csp_template_test_http_response'] = self::a_release_response(
			array(
				array(
					'draft'    => false,
					'tag_name' => 'v1.2.0-beta.1',
					'html_url' => 'https://github.com/a8cteam51/a8csp-plugin-template/releases/tag/v1.2.0-beta.1',
					'assets'   => array(
						array(
							'name'                 => 'a8csp-plugin-template.zip',
							'browser_download_url' => 'https://example.com/beta1.zip',
						),
					),
				),
				array(
					'draft'    => false,
					'tag_name' => 'v1.2.0-beta.2',
					'html_url' => 'https://github.com/a8cteam51/a8csp-plugin-template/releases/tag/v1.2.0-beta.2',
					'assets'   => array(
						array(
							'name'                 => 'a8csp-plugin-template.zip',
							'browser_download_url' => 'https://example.com/beta2.zip',
						),
					),
				),
			)
		);

		$update = a8csp_template_check_github_release_update( false, self::plugin_data( '1.2.0-beta.1' ), \constant( 'A8CSP_TEMPLATE_BASENAME' ) );

		self::assertIsArray( $update );
		self::assertSame( '1.2.0-beta.2', $update['version'], 'The highest-versioned prerelease is offered even when a lower version is listed first' );
		self::assertSame( 'https://example.com/beta2.zip', $update['package'] );
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
		self::assertSame( \constant( 'HOUR_IN_SECONDS' ), $GLOBALS['a8csp_template_test_transient_ttls']['a8csp_template_github_latest_release_stable'], 'A usable release caches for the full hour even when no update is offered' );
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
		self::assertSame( array(), $GLOBALS['a8csp_template_test_transients']['a8csp_template_github_latest_release_stable'] );
		self::assertSame( 5 * \constant( 'MINUTE_IN_SECONDS' ), $GLOBALS['a8csp_template_test_transient_ttls']['a8csp_template_github_latest_release_stable'] );
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

	/**
	 * Each channel caches under its own transient key, so a machine that switches between the
	 * stable and prerelease channels never serves the other channel's releases: after a
	 * prerelease check caches its list, a stable check on the same machine fetches the stable
	 * endpoint fresh instead of reusing the cached prerelease.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	#[RunInSeparateProcess]
	public function test_channels_cache_separately_so_a_switch_never_serves_the_other_channel(): void {
		$GLOBALS['a8csp_template_test_http_response'] = self::a_release_response(
			array(
				array(
					'draft'    => false,
					'tag_name' => 'v3.0.0-beta.1',
					'html_url' => 'https://github.com/a8cteam51/a8csp-plugin-template/releases/tag/v3.0.0-beta.1',
					'assets'   => array(
						array(
							'name'                 => 'a8csp-plugin-template.zip',
							'browser_download_url' => 'https://example.com/beta.zip',
						),
					),
				),
			)
		);
		a8csp_template_check_github_release_update( false, self::plugin_data( '2.0.0-beta.1' ), \constant( 'A8CSP_TEMPLATE_BASENAME' ) );

		// The machine is switched to a stable build; the network now answers with the stable latest.
		$GLOBALS['a8csp_template_test_http_response'] = self::a_release_response(
			array(
				'tag_name' => 'v2.0.0',
				'html_url' => 'https://github.com/a8cteam51/a8csp-plugin-template/releases/tag/v2.0.0',
				'assets'   => array(
					array(
						'name'                 => 'a8csp-plugin-template.zip',
						'browser_download_url' => 'https://example.com/stable.zip',
					),
				),
			)
		);

		$update = a8csp_template_check_github_release_update( false, self::plugin_data( '1.0.0' ), \constant( 'A8CSP_TEMPLATE_BASENAME' ) );

		self::assertCount( 2, $GLOBALS['a8csp_template_test_http_requests'], 'The stable check must fetch fresh rather than reuse the prerelease cache' );
		self::assertStringEndsWith( '/releases/latest', $GLOBALS['a8csp_template_test_http_requests'][1] );
		self::assertSame( '2.0.0', $update['version'], 'The stable install follows the stable release, never the cached prerelease' );
		self::assertSame( 'https://example.com/stable.zip', $update['package'] );
	}

	/**
	 * The mirror of the switch above: a stable check that caches its release must not satisfy a
	 * later prerelease check on the same machine. Reading the stable key for a prerelease install
	 * would suppress a newer prerelease behind stale stable data, so the prerelease check must
	 * fetch its own endpoint fresh and follow the prerelease.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	#[RunInSeparateProcess]
	public function test_channels_cache_separately_stable_then_prerelease(): void {
		$GLOBALS['a8csp_template_test_http_response'] = self::a_release_response(
			array(
				'tag_name' => 'v2.0.0',
				'html_url' => 'https://github.com/a8cteam51/a8csp-plugin-template/releases/tag/v2.0.0',
				'assets'   => array(
					array(
						'name'                 => 'a8csp-plugin-template.zip',
						'browser_download_url' => 'https://example.com/stable.zip',
					),
				),
			)
		);
		a8csp_template_check_github_release_update( false, self::plugin_data( '1.0.0' ), \constant( 'A8CSP_TEMPLATE_BASENAME' ) );

		// The machine is switched to a prerelease build; the network now answers with the release list.
		$GLOBALS['a8csp_template_test_http_response'] = self::a_release_response(
			array(
				array(
					'draft'    => false,
					'tag_name' => 'v3.0.0-beta.1',
					'html_url' => 'https://github.com/a8cteam51/a8csp-plugin-template/releases/tag/v3.0.0-beta.1',
					'assets'   => array(
						array(
							'name'                 => 'a8csp-plugin-template.zip',
							'browser_download_url' => 'https://example.com/beta.zip',
						),
					),
				),
			)
		);

		$update = a8csp_template_check_github_release_update( false, self::plugin_data( '2.0.0-beta.1' ), \constant( 'A8CSP_TEMPLATE_BASENAME' ) );

		self::assertCount( 2, $GLOBALS['a8csp_template_test_http_requests'], 'The prerelease check must fetch fresh rather than reuse the stable cache' );
		self::assertStringEndsWith( 'releases?per_page=10', $GLOBALS['a8csp_template_test_http_requests'][1] );
		self::assertSame( '3.0.0-beta.1', $update['version'], 'The prerelease install follows the prerelease, never the cached stable release' );
		self::assertSame( 'https://example.com/beta.zip', $update['package'] );
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
