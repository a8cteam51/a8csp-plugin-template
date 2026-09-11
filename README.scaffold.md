# EXAMPLE_REPO_NAME

EXAMPLE_REPO_DESCRIPTION

## Requirements

This plugin is a WooCommerce extension. It needs the WordPress, PHP, and WooCommerce versions its plugin header declares (`Requires at least`, `Requires PHP`, `WC requires at least`); below the WooCommerce floor it stays off and shows an admin notice naming the version it needs.

## Installation

1. Download `EXAMPLE_REPO_SLUG.zip` from the latest release of this repository.
1. Upload it through **Plugins → Add New Plugin → Upload Plugin**, or unzip it into `/wp-content/plugins/`; it unpacks to an `EXAMPLE_REPO_SLUG` folder.
1. Activate the plugin from the **Plugins** screen.

With WooCommerce active at the required version, the plugin's settings appear in two places: a field on the **Settings → General** page, and a section of the **Advanced** tab on the WooCommerce **Settings** page.

## Updates

The plugin updates itself from this repository's GitHub releases through its `Update URI` header, so WordPress offers a new release like any other plugin update. The check calls the GitHub API without authentication, which works only while the repository is public. An installed prerelease (a version containing `-`) follows every release; a stable installation follows stable releases only.

## Development

Install dependencies, build the assets, and start the local environment:

```sh
composer install
npm install
npm run build
npm run wp-env:start
```

The plugin is available at the wp-env port declared in `.wp-env.json`; `tests/README.md` documents the dedicated test environment. wp-env publishes the site on all network interfaces with fixed development credentials -- treat the dev site as visible to your local network, not just localhost.

The architecture map, the component model, and the reshaping recipes (including watering the
plugin down to plain WordPress) live in [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md); the test
workflow lives in [`tests/README.md`](tests/README.md).

`composer packages-update` and `npm run packages-update` update every dependency within the range its manifest declares. `npm run packages-update:wp` moves the `@wordpress/*` packages, across major versions, to the npm dist-tag named in the script; keep that tag at the plugin's WordPress floor.

### Releasing

Releases are cut by pushing a version tag; the release workflow fails closed unless the plugin header, `package.json`, and the newest `CHANGELOG.md` entry all agree with the tag, and unless green trunk-push Quality and Tests runs exist at the exact tagged commit — so tag trunk `HEAD` only after those runs finish. `CHANGELOG.md` is generated from the fragments in `changelog/` by `composer changelog:write`, which derives the next version from the newest existing changelog entry and the fragments' significance. The first release starts from the scaffold's empty changelog, so it must pass its version explicitly:

```sh
composer changelog:write -- --use-version=1.0.0
```

Prerelease entries also take an explicit version (`--use-version`, or the `--prerelease` suffix option); from the first stable entry onward, a bare `composer changelog:write` suffices.

The release history is [`CHANGELOG.md`](CHANGELOG.md).

## Publishing on wordpress.org

This plugin releases as a GitHub zip. Publishing it in the wordpress.org plugin directory is a per-plugin decision, and it takes at least:

- a `readme.txt` in the directory's readme format;
- the slug wordpress.org assigns from the plugin name, which can differ from this repository's name, with the `Text Domain` changed to match it so language packs apply;
- removing the GitHub self-updater (the `Update URI` header and its `update_plugins_github.com` filter), because directory plugins may only update through wordpress.org;
- a release step that deploys each tagged release to the plugin's wordpress.org SVN repository.
