# A8CSP Template Plugin

A template for A8C Special Projects WordPress plugins.

This repository is a template plugin, not a finished product plugin. It contains
the PHP bootstrap, block and asset build setup, automated test suite, and
GitHub Actions workflow used to turn this template into a new plugin repository.

## Trunk-only

This repository never versions itself: no tags, no releases, and no changelog
fragments in template pull requests — its history lives in git. A repository
ruleset blocks tag creation; `template-guard.yml` fails any tag run loudly and
pins the fragment set, the header-only `CHANGELOG.md`, and the `1.0.0` spawn
seed; and `release.yml` skips its release job here. Everything is keyed to this
repository's ID, so none of it constrains generated plugins — and generation
deletes `template-guard.yml` outright (the fill-in push may delete workflow
files but never edit them), leaving only the one-line release condition behind.

The versioning machinery itself is scaffold payload, not template process:
changelogger and its `changelog/` fragments directory, `CHANGELOG.md`, and
`release.yml` carry into generated plugins, which version by default — removing
any of it is the generated plugin author's choice. The one tracked fragment,
`changelog/initial-template-population`, is the machinery's fixture: it keeps
`changelog:validate` non-vacuous here, and generation deletes it along with the
example POT.

## What is in this repository

A plugin is a list of components; a component is a class with a static `should_load()` gate, an
`initialize()` readiness phase, and a `register_hooks()` attachment phase; the boot is a few
foreach loops you can read — gate and construct, initialize all, then register all hooks, so
every surviving component is initialized before any hook can fire.

- `a8csp-template-plugin.php` defines the plugin header and constants, requires
  `functions-bootstrap.php`, and wires the requirements gate and plugin boot.
- `functions-bootstrap.php` provides plugin metadata, version-compatibility checks, the requirements
  gate, and its admin-notice reporter; both root bootstrap files stay parsable below the plugin's PHP
  floor, and CI lints them against the older PHP versions.
- `functions.php` provides the construction-only plugin accessor (booting stays tied to the
  `plugins_loaded` attachment in the entry file) and loads the PHP helper files under `includes/`.
- `src/` follows one folder per feature, each owning a `Component` that composes it; the `src/`
  root holds only the bootstrapping mechanism. `src/ComponentInterface.php` is the one contract,
  and `src/Plugin.php` is the one file to edit when adding components to `COMPONENTS`; they boot
  in registration order. `src/ComponentCollection.php` is the shared gated collection both the composition
  root and group roots delegate their gate-construct and phase loops to — has-a, not is-a: the
  collection does not implement the contract. `src/AbstractComponent.php` is the optional
  defaults-only base (open gate, no-op readiness) for components that need neither. The plugin is
  a WooCommerce extension, so `boot()` opens with the plugin-wide host gate: without WooCommerce
  at the header-declared floor it stages an explanatory notice and stays un-booted.
- `src/Settings/` is the settings example feature and owns both settings surfaces: it persists
  `a8csp_template_example_option` through the Settings API on the General options page, registers a
  section in WooCommerce → Settings → Advanced persisting `a8csp_template_wc_example_option` — the
  host gate guarantees WooCommerce, so neither surface carries a gate — and demonstrates the
  uninstall footprint. Teardown recipes live in `README.scaffold.md`.
- `src/Integrations/` groups the optional integrations behind one nested example:
  `src/Integrations/Component.php` is the group root that gates, constructs, and initializes its
  children inside its own phases. Its children model the two child shapes:
  `src/Integrations/WooPayments.php` is the single-class leaf — gated on WooPayments and hooking
  one of its payment-metadata filters — and `src/Integrations/WooCommerceSubscriptions/` is the grown
  sub-feature folder owning its own `Component` plus a plain collaborator, still forwarding-depth
  one. More nesting than this is the signal a plugin has outgrown manual composition; see the
  group root's notes.
- `includes/` contains automatically loaded procedural helpers, including typed option readers; PHP
  files dropped there load automatically inside WordPress, while underscore-prefixed files are skipped.
- `languages/` contains translations and an example POT generated from the template's strings;
  regenerate it with `composer i18n:makepot`.
- `models/` is an extension point for classmapped data/model classes.
- `templates/` is an extension point for template partials rendered by components.
- `uninstall.php` holds and deletes the complete persisted footprint during WordPress's cold uninstall
  bootstrap, with every option and user-meta key grouped by owning component; add an entry with each
  corresponding write, and cross-reference persisted keys with `@see uninstall.php` in the component
  class docblock.
- `blocks/src/foobar/` contains the example block source, while `blocks/build/` contains tracked build
  output; `npm run build` generates the committed `blocks/build/blocks-manifest.php`, which
  `src/Blocks/Component.php` uses to register all built blocks as one metadata collection.
- `assets/js/src/editor.js` defines the shared editor hook entry point, and `assets/js/build/` contains
  its tracked output.
- `tests/` contains the automated test suite; see `tests/README.md` for the local workflow.
- `.github/workflows/` contains PHP, JavaScript, CSS, syntax, and scaffold-fill workflows.

## Scaffold generation

The `.github/workflows/fill-in-scaffold.yml` workflow runs on
`repository_dispatch` with the `fill_scaffold` type, or manually through
`workflow_dispatch`. It is guarded so it does not run on this template
repository itself.

For generated repositories, the workflow:

1. Renames `README.scaffold.md` to `README.md`.
2. Renames `a8csp-template-plugin.php` to the generated repository name.
3. Deletes the template's changelog fragments and example POT.
4. Runs `.github/workflows/fill-in-scaffold.mjs` to replace template placeholder strings.
5. Re-locks Composer against the renamed package name.
6. Deletes the spent scaffold workflows and the template guard.
7. Commits and pushes the generated files.

The replacement script uses the GitHub repository name, repository description,
and these repository custom properties:

- `human-title` for the human-readable plugin title.
- `php-globals-short-prefix` for the PHP global function and constant prefix.

The script replaces the following tracked template values:

- `EXAMPLE_REPO_NAME` and `EXAMPLE_REPO_DESCRIPTION` in the generated
  `README.md`.
- `A8CSP Template Plugin` and `A template for A8C Special Projects plugins.`
  outside the generated README, with the repository's title and description.
- `a8csp/plugin-template` (the Composer package name) with `a8csp/` followed by
  the generated repository name.
- `a8csp-template-plugin.php` (the entry file, already renamed to the repository
  name by this point) with the generated repository name.
- `a8csp-template-plugin` (elsewhere — the wp-env mapping and Playwright slug)
  with the title-derived slug.
- `a8csp-plugin-template` (the repository slug and text domain, elsewhere) with
  the generated repository name.
- `A8C\SpecialProjects\PluginTemplate` (including the JSON-escaped form in
  `composer.json`'s autoload keys) with a title-derived namespace.
- `a8csp_template` and `A8CSP_TEMPLATE` with the configured PHP prefix.
- `8890`–`8893` (the wp-env ports, matched only inside their `"port":`,
  `localhost:`, and ports-table anchors) with a four-port block derived from a
  hash of the repository name, so fleet plugins started side by side don't
  contend for the same host ports.

After generation, review the remaining example identifiers that the script does
not replace, including the example block copy (block title, description, and
sample text), the example Settings and WooCommerce-section labels, and the demo
option keys.

## Runtime requirements

The tracked template files declare these runtime targets:

- WordPress `7.0` in the plugin header.
- PHP `>=8.5` in `composer.json` and `8.5` in `.wp-env.json`.
- WooCommerce `10.0` in the plugin header and `wp-plugin/woocommerce`
  `10.9.*` as a development dependency.
- Composer for PHP dependency installation and autoload generation.
- Node.js `>=26` and npm `>=11` for JavaScript, CSS, block, and markdown
  tooling.
- Docker for the `wp-env` local environment.

The plugin is a WooCommerce extension: `Plugin::boot()` gates plugin-wide on WooCommerce presence
and the `WC requires at least` header floor, staging an explanatory admin notice and staying
un-booted when either is unmet. The WooCommerce Subscriptions integration
(`src/Integrations/WooCommerceSubscriptions/`) gates itself on its companion being active. The main
bootstrap declares HPOS (`custom_order_tables`) compatibility whether or not WooCommerce is
active.

## Multisite

Generated plugins are expected to support multisite networks and to be tested on one when the
client runs one. Multisite is a design consideration while building, not a porting step at the
end — when adding a component, decide its scope deliberately:

- Options are per-site; user meta is network-global. `uninstall.php` models the consequence:
  its options sweep visits every site of a network, while its user-meta pass runs once.
- The requirements gate reports through `all_admin_notices`, which fires on site and network
  admin screens alike, so a failed network activation is explained where it happened.
- The template registers no activation or deactivation hooks. A plugin that adds them must
  handle the `$network_wide` activation flag and provision sites created after network
  activation (`wp_initialize_site`).
- The host gate and the component `should_load()` gates run on every request, so per-site
  environmental differences — such as WooCommerce or a companion being active on only some
  sites — resolve correctly site by site.
- The multisite wp-env fixture (`.wp-env.multisite.json`) converts itself into a network on
  start, and `composer test:multisite` proves the uninstall sweep against it.

## Development

Install PHP dependencies:

```sh
composer run-script packages-install
```

Install JavaScript dependencies:

```sh
npm ci
```

Build blocks and editor assets:

```sh
npm run build
```

Run watch builds:

```sh
npm start
```

Run the local WordPress environment:

```sh
npm run wp-env:start
```

Stop the local WordPress environment:

```sh
npm run wp-env:stop
```

Generate translation files:

```sh
composer run-script internationalize
```

The tracked `languages/*.pot` file is an example of that output, generated from the template's own
strings; regenerate it after changing translatable strings with `composer i18n:makepot` (the full
`internationalize` script additionally refreshes `.po`/`.mo`/`.l10n.php` files when translations
exist).

## Quality checks

PHP checks are configured through `.phpcs.xml`, `.phpcs.tests.xml`, `.phpstan.neon`,
and the shared `a8csp/configs` package:

```sh
composer run-script lint:php
```

JavaScript, CSS, package metadata, and README markdown checks are defined in
`package.json`:

```sh
npm run lint:scripts
npm run lint:styles
npm run lint:pkg-json
npm run lint:readme-md
```

The GitHub workflows, including the JavaScript/CSS and PHP syntax workflows, run
on `trunk` pushes and on pull requests.

## Tests

The suite has four PHPUnit tiers (Unit, Integration, Requirements, Multisite) plus a Playwright
end-to-end suite. See `tests/README.md` for how to run each suite, the wp-env ports involved, and
why the rig runs PHPUnit 13 against plain `TestCase` instead of `WP_UnitTestCase`.

```sh
composer test:unit
npm run wp-env:tests:start && composer test:integration
composer test:requirements
npm run wp-env:multisite:start && composer test:multisite
npm run test:e2e
```

## Maintenance notes

- Customize source files under `src/`, `includes/`, `models/`, `templates/`,
  `blocks/src/`, `assets/js/src/`, `assets/css/src/`, and `languages/`.
- Rebuild generated assets after changing block or editor sources. The tracked
  generated outputs live in `blocks/build/` and `assets/js/build/`.
- Composer autoloading uses PSR-4 for `src/` plus a classmap for `models/`. Files
  loaded from `includes/` and classes loaded through either Composer mapping may
  carry an `ABSPATH` guard, but any file added to Composer's
  `autoload.files` is eagerly required in non-WordPress CLI processes and must not
  carry an unconditional guard that exits when `ABSPATH` is undefined.
- Do not commit dependency directories such as `vendor/` or `node_modules/`.
