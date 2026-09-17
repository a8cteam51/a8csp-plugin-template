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

Every pull request that changes behaviour carries a changelog fragment:

```sh
composer changelog:add
```

The command asks for a significance (`patch`, `minor`, `major`), a type, and the entry text, then writes one file under `changelog/`; `-s`, `-t` and `-e` supply the same answers non-interactively. `composer changelog:validate` only checks the fragments that exist and passes on an empty directory, so a forgotten fragment surfaces as a missing release note rather than as a red check.

Releases are cut from trunk, in four steps.

1. **Materialize the changelog.** `composer changelog:write` derives the next version from the newest `CHANGELOG.md` entry and the pending fragments' significance, writes that section, and deletes the fragments it consumed. There is nothing to derive from while the changelog is empty, so the first release names its version explicitly, as does any prerelease:

   ```sh
   composer changelog:write -- --use-version=1.0.0
   composer changelog:write -- --prerelease=beta.1
   ```

2. **Bump the other two versions to the same string.** The release refuses to run unless the plugin header's `Version:` in `EXAMPLE_REPO_SLUG.php`, `"version"` in `package.json`, and the newest `CHANGELOG.md` heading all state one version. The self-updater compares an installed copy against the header rather than against the tag, so the header bump belongs in the commit that gets tagged. Commit the three together and land them on trunk.

3. **Let trunk go green, then rehearse.** The release reuses the trunk-push Quality and Tests runs from the exact commit it tags, so tag only once those have finished. With them green, run the **Release** workflow from the Actions tab leaving **Create the GitHub release** off: that exercises the version check, the provenance check, the build and the smoke install without creating anything.

4. **Tag the green commit and publish the tag.**

   ```sh
   git tag -s "v1.0.0" -m "v1.0.0"
   git push origin "v1.0.0"
   ```

   Tagging is the maintainer's step and the tag is signed, so expect the signing key's own confirmation prompt and GitHub reporting the tag as verified. The workflow triggers on `v*` tags only, and the publish step passes `--verify-tag`, so the tag has to reach the remote before the release can be created.

Both paths — the tag and the rehearsal — run the same jobs, and only the last one is conditional:

| Job | What it proves |
| --- | --- |
| Verify release version | The plugin header, `package.json` and the newest `CHANGELOG.md` entry state one version. On a tag the tag states it too; on a dispatch there is no tag, so only the three declared versions are held against each other. Turning the release on from a non-tag ref fails here by design, which is why a dispatch cannot publish by accident. |
| Verify release provenance | Trunk-push runs of `quality.yml` and `tests.yml` succeeded at this exact commit. A missing or red run fails the job; fix it, land the fix, and re-tag. |
| Build the release artifact | Validates the changelog, installs production dependencies only, regenerates the committed POT — failing loudly if `make-pot` does not recognise the plugin — and packs `EXAMPLE_REPO_SLUG.zip`. |
| Smoke test the artifact | Installs and activates that zip in a throwaway wp-env and checks the site serves. The artifact differs from the tested tree (production dependencies, a regenerated POT, `.distignore` filtering), so it is proven on its own. |
| Publish the release | Runs only when the release is turned on. Takes the `CHANGELOG.md` section matching the tag as the release notes and creates the GitHub release with the zip attached. A hyphenated version such as `1.1.0-beta.1` publishes as a prerelease and stays off the latest-release endpoint, so stable installations are not offered it. |

The release history is [`CHANGELOG.md`](CHANGELOG.md).

## Publishing on wordpress.org

This plugin releases as a GitHub zip. Publishing it in the wordpress.org plugin directory is a per-plugin decision, and it takes at least:

- a `readme.txt` in the directory's readme format;
- the slug wordpress.org assigns from the plugin name, which can differ from this repository's name, with the `Text Domain` changed to match it so language packs apply;
- removing the GitHub self-updater (the `Update URI` header and its `update_plugins_github.com` filter), because directory plugins may only update through wordpress.org;
- a release step that deploys each tagged release to the plugin's wordpress.org SVN repository.
