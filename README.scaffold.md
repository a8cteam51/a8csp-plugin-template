# EXAMPLE_REPO_NAME

**Contributors:** wpspecialprojects
**Tags:**
**Requires at least:** 7.0
**Tested up to:** 7.0
**Requires PHP:** 8.5
**Stable tag:** 1.0.0
**License:** GPL v2 or later
**License URI:** <https://www.gnu.org/licenses/gpl-2.0.html>

EXAMPLE_REPO_DESCRIPTION

## Description

Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed leo ligula, aliquam et sem luctus, placerat facilisis orci. Cras faucibus, odio ac aliquet scelerisque, nisi ligula dignissim nisi, sed tincidunt magna libero vitae dui. Sed varius lectus turpis, fringilla maximus libero posuere nec. Aenean volutpat pharetra sem, et cursus leo sodales quis.

## Installation

This plugin is a WooCommerce extension: it requires WooCommerce at the `WC requires at least` version declared in the plugin header, and stays off with an explanatory notice otherwise. Install `EXAMPLE_REPO_NAME` either manually or through your site's plugins page.

### INSTALL FROM WITHIN WORDPRESS

1. Visit the plugins page withing your dashboard and select `Add New`.
1. Search for `EXAMPLE_REPO_NAME` and click the `Install Now` button.
1. Activate the plugin from within your `Plugins` page.

### INSTALL MANUALLY

1. Download the plugin from <https://wordpress.org/plugins/> and unzip the archive.
1. Upload the `EXAMPLE_REPO_NAME` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the `Plugins` menu in WordPress.

### AFTER ACTIVATION

If the minimum required version of WooCommerce is present, you will find a section present in the `Advanced` tab of the WooCommerce `Settings` page. Aliquam dolor sem, convallis malesuada neque sit amet, dictum mattis velit. Vestibulum at pharetra metus. Suspendisse rhoncus libero nisi, sed rhoncus tortor aliquam pretium.

## Watering down to plain WordPress

To convert this plugin from a WooCommerce extension to a plain WordPress plugin, remove the WooCommerce tier:

1. Delete the `HELPERS` region — the host-requirements check and its notice closures — and the one gate line in `boot()` from `src/Plugin.php`.
2. Strip the WooCommerce surface from `src/Settings/Component.php`: the `add_section()` and `get_settings()` methods and their two filter registrations in `register_hooks()`.
3. Delete the `src/Integrations/` and `templates/myaccount/` directories — both example children extend the WooCommerce ecosystem — and remove `Integrations\Component::class` from the `COMPONENTS` list in `src/Plugin.php`.
4. Remove the `a8csp_template_wc_example_option` and `Integrations\WC_Subscriptions\Component` option lines from the `uninstall.php` footprint.
5. Remove the `wp-plugin/woocommerce` development dependency from `composer.json`; run `composer update`.
6. Remove the WooCommerce `scanDirectories` entry from `.phpstan.neon`.
7. Remove the `before_woocommerce_init` compatibility block from the plugin entry file, and the `WC requires at least` / `WC tested up to` plugin-header lines.
8. Delete `tests/Integration/PluginBootWithoutWooCommerceTest.php`, `tests/Unit/IntegrationsComponentTest.php`, `tests/Unit/WCSubscriptionsComponentTest.php`, `tests/Unit/WooPaymentsTest.php`, and their `tests/Unit/wcs-stubs.php` / `tests/Unit/wcpay-stubs.php` stand-ins; drop the WooCommerce assertions and stand-ins from `tests/Integration/PluginBootTest.php`, `tests/Unit/PluginBootGateTest.php`, and `tests/Unit/SettingsComponentTest.php` (including `tests/Unit/wc-host-stubs.php`).
9. Remove the WooCommerce-less proof section from `tests/README.md`.
10. Run `composer quality-check`. What remains — blocks, settings, the component list, the `includes/` loader, and a live uninstall footprint — is a complete plain WordPress plugin.

**For a plugin that persists nothing:** delete `src/Settings/`, its `COMPONENTS` entry in `src/Plugin.php`, its option lines in the `uninstall.php` footprint, and `includes/settings.php`.

Optional integrations live behind the `src/Integrations/` group root (`src/Integrations/Component.php`),
which gates, constructs, and initializes its children inside its own phases; add one child component
per companion plugin — a single-class leaf at first, promoted to its own folder the day it needs a
second class.

## Frequently Asked Questions

### How can I get help if I'm stuck?

Quisque volutpat tortor id varius pulvinar. Vivamus porttitor, mi non auctor pellentesque, leo purus interdum libero, at aliquam justo lectus sed ligula.

### I have a question that is not listed here

Duis efficitur, sapien ac scelerisque placerat, elit justo tempor nisl, ut feugiat magna orci quis odio.

## Screenshots

### 1. Example screenshot

[missing image]

## Changelog

### 1.0.0 (FIRST RELEASE DATE)

* First official release.
