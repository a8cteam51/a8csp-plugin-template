# Tests

The test rig has four PHPUnit suites plus a Playwright end-to-end suite, run against wp-env
fixtures at three WordPress-version tiers.

## Suites

- **Unit** (`tests/Unit/`) — no WordPress, no wp-env. Runs against plain PHPUnit `TestCase` with
  recording `add_action()`/`add_filter()` stubs (`tests/Unit/wp-hook-stubs.php`) instead of Mockery
  or Brain Monkey, so the real `Plugin::boot()` loop is exercised outside WordPress.
  Fast; this is the suite `composer quality-check` runs on every push.
- **Integration** (`tests/Integration/`) — boots inside wp-env against a supported WordPress
  version and exercises the plugin's real boot path. `UninstallTest` runs the real
  `uninstall.php` end-to-end (seeds sentinels, defines `WP_UNINSTALL_PLUGIN`, asserts its
  footprint is gone and a canary key survives) inside `#[RunInSeparateProcess]`, since that
  constant must not leak into the rest of the suite.
- **Requirements** (`tests/Integration/RequirementsCheckTest.php`, run as its own suite) — boots
  inside wp-env against a below-floor WordPress version to verify the requirements gate degrades
  gracefully instead of fataling.
- **Multisite** (`tests/Integration/MultisiteUninstallTest.php`, run as its own suite) — boots
  inside a wp-env fixture converted into a multisite network and proves `uninstall.php`'s options
  sweep cleans every site of the network, not just the site the uninstall runs on. The fixture is
  dedicated because `wp core multisite-convert` is one-way; the test self-skips when the
  Integration suite runs it against the single-site fixture.
- **End-to-End** (`tests/EndToEnd/`) — Playwright, driving a real browser against the dev wp-env
  instance.

## WooCommerce-less boot proof

`PluginBootWithoutWooCommerceTest` verifies the plugin-wide host gate: with WooCommerce inactive
the plugin stays un-booted and stages the "requires WooCommerce" notice instead of registering
anything. It self-skips whenever WooCommerce is active, so the proof needs WooCommerce deactivated
just for its run. With the tests wp-env instance started, one command does the whole dance:

```sh
npm run wp-env:tests:start
npm run test:integration:no-wc
npm run wp-env:tests:stop
```

`test:integration:no-wc` deactivates WooCommerce in the tests wp-env instance, runs that one test
directly, then reactivates WooCommerce — carrying the test's exit status through — so the instance
is left ready for the Integration suite. Its three steps are:

```sh
wp-env --config .wp-env.tests.json run cli wp plugin deactivate woocommerce
wp-env --config .wp-env.tests.json run cli --env-cwd=wp-content/plugins/a8csp-plugin-template vendor/bin/phpunit --filter=PluginBootWithoutWooCommerceTest
wp-env --config .wp-env.tests.json run cli wp plugin activate woocommerce
```

## Running the suites

Each `composer test:*` verb starts its own wp-env environment first: a container already running
serves the mount set it was created with, and `start` is what replaces it when the resolved config
moved. The `[ -n "$GITHUB_ACTIONS" ]` guard in front of that start keeps it out of CI, which owns the
container's lifecycle in its own step. On the Integration leg it is also load-bearing: that is the
only matrix passing `wp-env-core`, so a second start would fall back to the config's own `core` and
the nightly leg would test the pinned version and pass.

Unit (no wp-env required):

```sh
composer test:unit
```

Integration:

```sh
composer test:integration
npm run wp-env:tests:stop
```

Requirements:

```sh
composer test:requirements
npm run wp-env:belowfloor:stop
```

Multisite (its `afterStart` converts the fresh install into a subdirectory network):

```sh
composer test:multisite
npm run wp-env:multisite:stop
```

End-to-end (Playwright starts and stops the dev wp-env instance itself via its `webServer` config):

```sh
npm run test:e2e
```

## Ports

| Environment | Config                    | Port |
| ----------- | ------------------------- | ---- |
| Dev / E2E   | `.wp-env.json`            | 8893 |
| Tests       | `.wp-env.tests.json`      | 8890 |
| Below-floor | `.wp-env.belowfloor.json` | 8891 |
| Multisite   | `.wp-env.multisite.json`  | 8892 |

Generated repositories get their own four-port block, derived from the repository name at
generation, so plugins started side by side don't contend for the same host ports. If two
environments still collide on one machine, wp-env's untracked override files take local
precedence (`.wp-env.override.json`; custom configs pair with e.g.
`.wp-env.tests.override.json`).

## Why plain `TestCase`, not `WP_UnitTestCase`

WordPress core's own PHPUnit scaffold still caps at PHPUnit <=9, and core's migration plan
(#62004) targets PHPUnit 10/11 with 12-readiness over several future releases — there is no
core-provided `WP_UnitTestCase` path onto a current PHPUnit today. This rig runs PHPUnit 13 directly, against
plain `TestCase`, inside wp-env, rather than waiting on that migration or pinning to an old
PHPUnit.

That trade gives up `$this->factory` fixture helpers, `go_to()` routing simulation, and
`WP_UnitTestCase`'s per-test transaction rollback. The first two exist for content- and
query-heavy plugins exercising post/term/user fixtures and template routing — this scaffold's
Integration suite is narrower (boot path, requirements gating), so their absence costs little.
Transaction rollback specifically would be counterproductive here: the Integration and
Requirements suites exist to observe persistence and boot-time side effects, and auto-rolling back
every test would mask exactly the behavior they're written to catch.

## Mutation testing

`composer test:unit:mutation` runs Infection against the Unit suite's source. It sits outside the
default `composer quality-check` target (only `quality-check:all` pulls it in) and does not gate
pull requests — it runs on its own weekly schedule in CI (`.github/workflows/tests-mutation.yml`),
since mutation testing is slow. Local runs on macOS are unreliable: a race in Infection's
coverage-XML tmpdir handling can produce zero generated mutants or a hang, independent of anything
in this repo's own configuration. Treat the CI job, not a local run, as authoritative for mutation
results.
