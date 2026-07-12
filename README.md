# A8CSP Template Plugin

A template for A8C Special Projects WordPress plugins.

This repository is a template plugin, not a finished product plugin. It contains
the PHP bootstrap, block and asset build setup, automated test suite, and
GitHub Actions workflow used to turn this template into a new plugin repository.

## What is in this repository

A plugin is a list of components; a component is a class with `is_needed()` and
`initialize()`; the boot is a foreach you can read.

- `a8csp-template-plugin.php` defines the plugin header and constants, requires
  `functions-bootstrap.php`, and wires the requirements gate and plugin boot.
- `functions-bootstrap.php` provides plugin metadata, version-compatibility checks, the requirements
  gate, and its admin-notice reporter; both root bootstrap files stay parsable below the plugin's PHP
  floor, and CI lints them against the older PHP versions.
- `functions.php` boots the component list and loads the PHP helper files under `includes/`.
- `src/` contains the PSR-4 classes: `src/Component.php` is the one contract, and `src/Plugin.php` is
  the one file to edit when adding components to `COMPONENTS`; they boot in registration order.
- `src/Settings.php` is the base-WordPress example component, persists
  `a8csp_template_example_option` through the Settings API on the General options page, and
  demonstrates the uninstall footprint. Teardown recipes live in `README.scaffold.md`.
- `src/Integrations/` groups the deletable WooCommerce tier;
  `src/Integrations/WC_Settings_Section.php` gates itself on WooCommerce core, registers a section in
  WooCommerce → Settings → Advanced, and persists `a8csp_template_wc_example_option`. See "Watering
  down to plain WordPress" in `README.scaffold.md`.

  When integrations multiply behind one shared gate, give them a parent component whose
  `initialize()` constructs and gates its children — five lines, written the day they're needed.

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
  `src/Blocks.php` uses to register all built blocks as one metadata collection.
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
3. Runs `.github/workflows/fill-in-scaffold.mjs` to replace template placeholder strings.
4. Commits and pushes the renamed and filled files.

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

The plugin boots its component list unconditionally. The WooCommerce-dependent example
settings-section component (`src/Integrations/WC_Settings_Section.php`) gates itself through `is_needed()`, checking
that WooCommerce is active and meets the `WC requires at least` header floor. The main bootstrap
declares HPOS (`custom_order_tables`) compatibility whether or not WooCommerce is active.

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
- Component `is_needed()` gates run on every request, so per-site environmental differences —
  such as WooCommerce being active on only some sites — resolve correctly site by site.
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
`.composer-require-checker.json`, and the shared `a8csp/configs` package:

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
