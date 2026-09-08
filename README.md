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
example POT. A generated plugin's first release starts from the header-only
`CHANGELOG.md`, where `changelogger write` has no entry to derive a version
from, so that one write passes its version explicitly
(`composer changelog:write -- --use-version=1.0.0`); the scaffold README
documents this in its Releasing section.

## What is in this repository

The architecture map — the component model, every file's role, the multisite posture, and the
reshaping recipes (watering down to plain WordPress, growing integrations) — lives in
[`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md), which ships with generated plugins so the map
survives generation. The sections below cover only what is template-specific.

## Scaffold generation

The `.github/workflows/fill-in-scaffold.yml` workflow runs on
`repository_dispatch` with the `fill_scaffold` type, or manually through
`workflow_dispatch`. It is guarded so it does not run on this template
repository itself.

For generated repositories, the workflow:

1. Requires its ref to be the repository's default branch, and never runs on this template
   repository itself.
2. Validates the `human-title` and `php-globals-short-prefix` custom properties, the repository
   description (single line, no `*/`), and the repository name (lowercase-kebab) before checkout,
   so a malformed value leaves the repository untouched.
3. Renames `README.scaffold.md` to `README.md` and `a8csp-template-plugin.php` to the generated
   repository name.
4. Deletes the template's changelog fragments and example POT.
5. Runs `.github/workflows/fill-in-scaffold.mjs` to replace template placeholder strings.
6. Optionally runs `.github/workflows/fill-in-scaffold-content.mjs` to strip the
   template's teaching prose (see below).
7. Syntax-checks every generated PHP file.
8. Regenerates `package-lock.json`, rebuilds the committed assets, and re-locks Composer against
   the renamed identity.
9. Deletes the spent scaffold workflows and the template guard.
10. Commits and pushes the generated files.

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
- `a8csp-template-plugin` (the Playwright slug) with the title-derived slug:
  WordPress and the E2E utilities derive it from the plugin `Name` header,
  never from the plugin folder.
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

### Optional teaching-content strip

The template's docblocks and comments carry teaching prose — the architectural
rationale that makes the scaffold a worked example. Ask for the strip in the
dispatch that triggers generation — the `strip-teaching-content` checkbox on a
manual `workflow_dispatch` run, or a `"strip-teaching-content": true` key in the
`repository_dispatch` client payload — and generation runs a second phase,
`.github/workflows/fill-in-scaffold-content.mjs`, that rewrites each teaching
passage into the contract-level docblock a production plugin would carry and
deletes the `includes/_disabled-example.php` teaching stub. Load-bearing
constraint comments (below-floor parsability, the boot latch, cache staging, the
uninstall footprint, and the like) are left verbatim. A dispatch that does not
ask for the strip keeps the teaching prose, with a notice.

The strip is driven by an exact-match manifest, not markers or regexes: each
passage is matched by its literal text, which must occur **exactly once** in its
file. A passage that has drifted — zero or multiple matches — fails the
generation build loudly rather than stripping the wrong span, and the template's
own `template-guard.yml` runs the manifest in `--check` mode on every change so
that drift is caught on the template before it can reach a generated repository.

## Runtime requirements

The tracked template files declare these runtime targets:

- WordPress `7.1` in the plugin header.
- PHP `>=8.5` in `composer.json` and `8.5` in `.wp-env.json`.
- WooCommerce `10.0` in the plugin header and `wp-plugin/woocommerce`
  `11.1.*` as a development dependency.
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

`npm start` watches blocks, scripts, and Sass, but for `assets/css` the watcher runs Sass only -- the PostCSS vendor-prefix pass and the RTL stylesheet come from `npm run build`, so that watched CSS differs from a production build.

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
composer test:integration
composer test:requirements
composer test:multisite
npm run test:e2e
```

## Maintenance notes

- Customize source files under `src/`, `includes/`, `models/`, `templates/`,
  `blocks/src/`, `assets/js/src/`, `assets/css/src/`, and `languages/`.
- Rebuild generated assets after changing block, editor, or `assets/css/src/` Sass
  sources. The tracked generated outputs live in `blocks/build/`, `assets/js/build/`,
  and `assets/css/build/` (compiled CSS, its RTL variant, and sourcemaps); the
  build-integrity gate fails if they drift from a fresh build.
- Composer autoloading uses PSR-4 for `src/` plus a classmap for `models/`. Files
  loaded from `includes/` and classes loaded through either Composer mapping may
  carry an `ABSPATH` guard, but any file added to Composer's
  `autoload.files` is eagerly required in non-WordPress CLI processes and must not
  carry an unconditional guard that exits when `ABSPATH` is undefined.
- Do not commit dependency directories such as `vendor/` or `node_modules/`.
