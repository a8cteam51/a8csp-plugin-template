import { access, readFile, unlink, writeFile } from 'fs/promises';
import { join as joinPath } from 'path';
import process from 'process';

// A newline-joined block of source lines. Manifest spans are authored line by line so leading tabs
// and blank comment lines (`\t *`) are unambiguous in this file's own source.
const block = ( ...lines ) => lines.join( '\n' );

// The teaching-content strip manifest. It runs as the optional second scaffold phase, AFTER
// fill-in-scaffold.mjs has already substituted every identifier, so each `from` is matched against
// post-substitution source: every span here is deliberately free of substitutable tokens
// (a8csp_template, A8CSP Template Plugin, a8csp-plugin-template, the namespace) so it reads
// identically before and after that pass. Two entry shapes:
//   { action: 'replace-exact', path, from, to } — `from` must occur EXACTLY ONCE in `path`.
//   { action: 'delete', path }                  — `path` must exist.
// Each replacement rewrites an architectural teaching passage into the contract-level docblock a
// production plugin would carry; load-bearing constraint one-liners are left untouched by omission.
const MANIFEST = [
	// includes/_disabled-example.php exists only to teach the underscore opt-out convention.
	{ action: 'delete', path: 'includes/_disabled-example.php' },

	{
		action: 'replace-exact',
		path: 'functions.php',
		from: block(
			" *",
			" * Construction only — never boots: a peer calling this at include time would otherwise run the",
			" * component gates before every plugin has loaded. Booting stays tied to the `plugins_loaded`",
			" * attachment in the main plugin file.",
			" *",
		),
		to: block(
			" *",
			" * Construction only; it never boots the plugin.",
			" *",
		),
	},

	{
		action: 'replace-exact',
		path: 'src/Plugin.php',
		from: block(
			"/**",
			" * The plugin's composition root: `COMPONENTS` below is the plugin, and `boot()` runs it through",
			" * the `ComponentCollection`. This is the one file you edit to wire a top-level component in —",
			" * should the plugin ever outgrow manual wiring, the class list here evolves into root-owned",
			" * factories and a PSR-11 container takes over `ComponentCollection::assemble()`; the component",
			" * classes themselves stay untouched.",
			" *",
			" * The layout convention: one folder per feature, each owning a `Component` that composes it; the",
			" * `src/` root holds only this bootstrapping mechanism. The root itself deliberately does not",
			" * implement `ComponentInterface`: it runs the contract, so it cannot also be subject to it.",
			" *",
		),
		to: block(
			"/**",
			" * The plugin's composition root: assembles the top-level components and runs the boot pipeline.",
			" *",
		),
	},
	{
		action: 'replace-exact',
		path: 'src/Plugin.php',
		from: block(
			"\t * Runs the boot pipeline: gate the plugin on its host, assemble the component collection,",
			"\t * initialize every surviving component, then let every component register its hooks.",
			"\t *",
			"\t * The components take no constructor arguments; the day one needs shared state injected,",
			"\t * build the boundary here before `assemble()` — evolving the class list into factories that",
			"\t * receive it is the collection's next shape, not today's. A boot failure propagates",
			"\t * uncaught — fail loud; the entry latch already guarantees it cannot be retried into",
			"\t * duplicate hook registrations.",
		),
		to: block(
			"\t * Runs the plugin's boot pipeline.",
			"\t *",
			"\t * A boot failure propagates uncaught — fail loud; the entry latch already guarantees it cannot",
			"\t * be retried into duplicate hook registrations.",
		),
	},
	{
		action: 'replace-exact',
		path: 'src/Plugin.php',
		from: block(
			"\t\t$components->initialize();",
			"",
			"\t\t// The root seam: every contribution is in, no hook is live yet — cross-component",
			"\t\t// registries are validated and frozen here when the plugin grows some.",
			"",
			"\t\t$components->register_hooks();",
		),
		to: block(
			"\t\t$components->initialize();",
			"\t\t$components->register_hooks();",
		),
	},
	{
		action: 'replace-exact',
		path: 'src/Plugin.php',
		from: block(
			"\t * Whether the host requirements hold for this request: WooCommerce present, at the",
			"\t * header-declared `WC requires at least` floor. On failure, stages the explanatory notice on",
			"\t * `all_admin_notices` and returns false.",
			"\t *",
			"\t * This is the machinery of the plugin-wide rung of the `should_load()` ladder; the rung itself",
			"\t * is the one call in `boot()`, gating the whole plugin before any component exists. A failed",
			"\t * gate leaves the plugin un-booted and non-retryable for the request, with `is_booted()`",
			"\t * reporting false. The misconfiguration speaks through a notice instead of silently gating",
			"\t * off — the requirements-gate philosophy. `plugins_loaded` is the earliest the check is",
			"\t * reliable; at include time the host may simply not have loaded yet. A plugin that is not",
			"\t * WooCommerce-dependent deletes this region and the one `boot()` line.",
			"\t *",
		),
		to: block(
			"\t * Whether the host requirements hold for this request: WooCommerce present and at the",
			"\t * header-declared `WC requires at least` floor. On failure, stages an admin notice and returns",
			"\t * false.",
			"\t *",
			"\t * `plugins_loaded` is the earliest the check is reliable; at include time the host may simply",
			"\t * not have loaded yet.",
			"\t *",
		),
	},

	{
		action: 'replace-exact',
		path: 'src/AbstractComponent.php',
		from: block(
			"/**",
			" * Defaults-only base for components: an always-open gate and a no-op readiness phase — the",
			" * Laravel ServiceProvider / Symfony AbstractBundle shape. It exists ONLY to absorb no-op",
			" * boilerplate and must never grow state, shared behavior, or helpers; that road leads back to",
			" * inheritance-tree frameworks. Everything in the plugin types against `ComponentInterface`, so",
			" * extending is optional — implement the interface directly whenever any default doesn't fit or",
			" * the explicit methods teach better.",
		),
		to: block(
			"/**",
			" * Defaults-only base for components, supplying the no-op defaults of `ComponentInterface`. It",
			" * must never grow state, shared behavior, or helpers; every component types against the",
			" * interface, so extending this base is optional.",
		),
	},

	{
		action: 'replace-exact',
		path: 'src/ComponentCollection.php',
		from: block(
			"/**",
			" * A gated collection of components: the component-loop machinery, owned once. The composition",
			" * root and any group root delegate their gate-construct and phase loops here instead of",
			" * hand-rolling them — has-a, not is-a: the collection runs components, so it does not implement",
			" * `ComponentInterface` itself.",
		),
		to: block(
			"/**",
			" * A collection of components that gates its members on construction and runs their lifecycle",
			" * phases. Owns the component-loop machinery once for the composition root and every group root.",
		),
	},
	{
		action: 'replace-exact',
		path: 'src/ComponentCollection.php',
		from: block(
			"\t *",
			"\t * A fail-loud boot is all-or-nothing, so \"did component X boot?\" decomposes into the root's",
			"\t * `is_booted()` — the pipeline completed — plus this check — X survived its gate. There is",
			"\t * deliberately no per-component failure state: a component failure fails the whole boot. And",
			"\t * the check is deliberately collection-scoped: a caller holds the collection it asks, so the",
			"\t * answer never straddles composition levels, and the class-keyed lookup never becomes a public",
			"\t * contract that promoting a leaf into its own folder would silently break.",
			"\t *",
		),
		to: block(
			"\t *",
		),
	},

	{
		action: 'replace-exact',
		path: 'src/ComponentInterface.php',
		from: block(
			" *",
			" * Each feature lives in its own folder under `src/` and exposes exactly one implementation of",
			" * this contract — its `Component` — as the feature's composition point; descriptive leaf classes",
			" * inside a feature keep their own names. The `Interface` suffix follows the PSR interface",
			" * convention (`ContainerInterface`, `LoggerInterface`) and frees the `Component` name for the",
			" * implementations that folder convention creates.",
			" *",
		),
		to: block(
			" *",
		),
	},
	{
		action: 'replace-exact',
		path: 'src/ComponentInterface.php',
		from: block(
			"\t *",
			"\t * The gate recurs at four rungs of one ladder: the whole plugin (the host gate in",
			"\t * `Plugin::boot()`), a feature subtree (a parent component whose closed gate leaves everything",
			"\t * its `initialize()` would have constructed unbuilt), a leaf component's own gate, and finally",
			"\t * those capability checks inside hook callbacks.",
			"\t *",
		),
		to: block(
			"\t *",
		),
	},

	{
		action: 'replace-exact',
		path: 'src/Blocks/Component.php',
		from: block(
			"/**",
			" * Composes the Blocks feature: registers every built block from the build manifest as one",
			" * metadata collection and registers a block-editor script. Blocks have no environmental",
			" * dependency and no state to wire, so the defaults-only base fits.",
			" *",
			" * Imitate this manifest registration shape for any block work. Delete this feature folder, its",
			" * `COMPONENTS` entry, and the `blocks/` directory if your plugin ships no blocks.",
			" *",
		),
		to: block(
			"/**",
			" * Composes the Blocks feature: registers the plugin's blocks and the block-editor assets.",
			" *",
		),
	},
	{
		action: 'replace-exact',
		path: 'src/Blocks/Component.php',
		from: block(
			"\t * Registers all blocks from the build's metadata manifest collection: one filesystem",
			"\t * read for the whole collection instead of one per block directory.",
		),
		to: block(
			"\t * Registers the plugin's blocks from the build's metadata manifest.",
		),
	},
	{
		action: 'replace-exact',
		path: 'src/Blocks/Component.php',
		from: block(
			"\t * Registers and enqueues the plugin-level block-editor script. A registered-but-not-enqueued",
			"\t * handle never loads, so the editor hook hub in `assets/js/src/editor.js` — the entry point that",
			"\t * fires `editor.ready` for any editor extensions the plugin ships — only runs once the handle is",
			"\t * enqueued here.",
		),
		to: block(
			"\t * Registers and enqueues the plugin's block-editor script.",
		),
	},

	{
		action: 'replace-exact',
		path: 'src/Settings/Component.php',
		from: block(
			"/**",
			" * Composes the Settings feature: it owns both of the plugin's settings surfaces — one option",
			" * registered and persisted through the WordPress Settings API on the General options page, and",
			" * one section with a persisted option in WooCommerce → Settings → Advanced. Core functionality",
			" * behind the plugin-wide host gate: WooCommerce is guaranteed, no surface needs a gate of its",
			" * own, and the defaults-only base fits. Both surfaces double as the worked example for the",
			" * uninstall footprint.",
			" *",
			" * Imitate this feature for any option your plugin owns. Delete the surface — or the whole",
			" * feature folder — your plugin does not need.",
			" *",
		),
		to: block(
			"/**",
			" * Composes the Settings feature: owns the plugin's two example settings surfaces — an option on",
			" * the General options page and a section in WooCommerce → Settings → Advanced.",
			" *",
		),
	},
	{
		action: 'replace-exact',
		path: 'src/Settings/Component.php',
		from: block(
			"\t * Hangs the demo field on the core General options page to avoid inventing a whole admin page",
			"\t * for a one-field demo. Imitate the `register_setting()` shape and retarget the page as needed.",
		),
		to: block(
			"\t * Registers the example option on the core General options page.",
		),
	},
	{
		action: 'replace-exact',
		path: 'src/Settings/Component.php',
		from: block(
			"\t * Renders the persisted value as a text field. Escaping on output is the point this example",
			"\t * models, even though the value is also sanitized before WordPress persists it.",
			"\t * The value comes through the typed reader in `includes/settings.php`, the worked example of",
			"\t * reading an option this component registers.",
		),
		to: block(
			"\t * Renders the example option's settings field, escaping the persisted value on output even",
			"\t * though it is also sanitized before it is stored.",
		),
	},
	{
		action: 'replace-exact',
		path: 'src/Settings/Component.php',
		from: block(
			"\t * Enqueues the admin stylesheet on the General options page — the surface this component's demo",
			"\t * field lives on. Gating on the hook suffix keeps the stylesheet off every other admin screen,",
			"\t * the worked example of a scoped admin enqueue. The compiled asset carries its version and",
		),
		to: block(
			"\t * Enqueues the settings stylesheet on the General options page. The compiled asset carries its",
			"\t * version and",
		),
	},
	{
		action: 'replace-exact',
		path: 'src/Settings/Component.php',
		from: block(
			"\t * Adds the plugin's section to the Advanced settings tab. The section slug carries the prefix",
			"\t * token so generation rewrites it, and the label doubles as the plugin title.",
		),
		to: block(
			"\t * Adds the plugin's section to the Advanced settings tab.",
		),
	},
	{
		action: 'replace-exact',
		path: 'src/Settings/Component.php',
		from: block(
			"\t * Declares this section's WooCommerce settings rows. WooCommerce persists the field through its",
			"\t * own settings save, so declaring the field is the whole persistence story and the option key",
			"\t * still appears in the uninstallation footprint.",
		),
		to: block(
			"\t * Declares this section's WooCommerce settings rows. WooCommerce persists the field through its",
			"\t * own settings save, so declaring it is the whole persistence story.",
		),
	},

	{
		action: 'replace-exact',
		path: 'src/Integrations/Component.php',
		from: block(
			" *",
			" * The children are full Components, and this parent forwards the pipeline's phases to them",
			" * faithfully through the shared `ComponentCollection`: construction and readiness inside",
			" * `initialize()`, hooks inside `register_hooks()`. Children come in two shapes — a single-class",
			" * leaf with a descriptive name, and a grown sub-feature folder owning its own `Component`; a leaf",
			" * is promoted to the folder shape the day it needs a second class. This template nests exactly",
			" * one level; a plugin that finds itself wanting more is being told it has outgrown manual",
			" * composition — the authors' cue to consider a dependency injection container behind the",
			" * composition root, a judgment call that is theirs to make.",
			" *",
			" * The group's own gate stays at the base's always-open default because the children gate",
			" * individually; a gate every child shares — one companion they all require — would override",
			" * `should_load()` here instead, sparing each child the repetition.",
			" *",
		),
		to: block(
			" *",
		),
	},
	{
		action: 'replace-exact',
		path: 'src/Integrations/Component.php',
		from: block(
			"\t * Assembles and initializes the children — the same order the composition root uses, one",
			"\t * level down.",
		),
		to: block(
			"\t * Assembles and initializes the group's child components.",
		),
	},

	{
		action: 'replace-exact',
		path: 'src/Integrations/WooCommerceSubscriptions/Component.php',
		from: block(
			"/**",
			" * Composes the WooCommerce Subscriptions integration: it extends what the plugin already does",
			" * instead of smuggling in a feature of its own, and it gates on its companion so none of this",
			" * exists when Subscriptions is absent.",
			" *",
			" * This is what a leaf integration becomes the day it needs a second class: a folder owning a",
			" * `Component` plus plain collaborators. The collaborators are plain final classes, not",
			" * `ComponentInterface` implementers, so the forwarding depth is still one — contrast with a",
			" * Component whose children are Components, which is the dependency-injection-container signal.",
			" *",
		),
		to: block(
			"/**",
			" * Composes the WooCommerce Subscriptions integration, gated on the Subscriptions plugin being",
			" * active.",
			" *",
		),
	},
	{
		action: 'replace-exact',
		path: 'src/Integrations/WooCommerceSubscriptions/Component.php',
		from: block(
			"\t * Returns true when WooCommerce Subscriptions is active. The companion gate is the whole point",
			"\t * of an integration component: everything below may assume Subscriptions exists.",
		),
		to: block(
			"\t * Whether WooCommerce Subscriptions is active; everything in this integration may assume it is.",
		),
	},

	{
		action: 'replace-exact',
		path: 'src/Integrations/WooCommerceSubscriptions/PriceNote.php',
		from: block(
			"/**",
			" * Builds the demonstration note appended to subscription price strings; replace this with the",
			" * integration's real behavior.",
			" *",
			" * A plain collaborator: the feature's `Component` constructs it in `initialize()` and attaches",
			" * its callback in `register_hooks()`; it implements no plugin contract of its own.",
		),
		to: block(
			"/**",
			" * Builds the note appended to subscription price strings.",
		),
	},

	{
		action: 'replace-exact',
		path: 'src/Integrations/WooPayments.php',
		from: block(
			"/**",
			" * Provides the WooPayments integration — the worked example of the simple leaf shape: one",
			" * descriptively-named class, one companion gate, one honest filter. A leaf is promoted to a",
			" * folder owning its own `Component` the day it needs a second class; the WooCommerce",
			" * Subscriptions sibling shows that grown shape.",
		),
		to: block(
			"/**",
			" * The WooPayments integration: adds example metadata to the payment data WooPayments builds",
			" * from an order, when the WooPayments plugin is active.",
		),
	},
	{
		action: 'replace-exact',
		path: 'src/Integrations/WooPayments.php',
		from: block(
			"\t * Returns true when WooPayments is active. The companion gate is the whole point of an",
			"\t * integration component: everything below may assume WooPayments exists.",
		),
		to: block(
			"\t * Whether WooPayments is active; everything in this integration may assume it is.",
		),
	},
	{
		action: 'replace-exact',
		path: 'src/Integrations/WooPayments.php',
		from: block(
			"\t * Adds the template's demonstration entry to the payment metadata WooPayments generates from",
			"\t * an order; replace this with the integration's real behavior.",
		),
		to: block(
			"\t * Adds a demonstration entry to the payment metadata WooPayments generates from an order.",
		),
	},
];

const checkOnly = process.argv.includes( '--check' );

// Group the manifest by target file so each file is read once and every entry against it is
// applied to one working buffer in manifest order — the same buffer whether checking or writing,
// so `--check` and the default apply never diverge.
const entriesByPath = new Map();
for ( const entry of MANIFEST ) {
	if ( ! entriesByPath.has( entry.path ) ) {
		entriesByPath.set( entry.path, [] );
	}
	entriesByPath.get( entry.path ).push( entry );
}

const fileExists = async ( path ) => {
	try {
		await access( path );
		return true;
	} catch {
		return false;
	}
};

const errors = [];
const pendingWrites = [];
const pendingDeletes = [];

for ( const [ path, entries ] of entriesByPath ) {
	const absolutePath = joinPath( '.', path );

	const deleteEntries  = entries.filter( ( entry ) => 'delete' === entry.action );
	const replaceEntries = entries.filter( ( entry ) => 'replace-exact' === entry.action );

	for ( const entry of deleteEntries ) {
		if ( await fileExists( absolutePath ) ) {
			pendingDeletes.push( absolutePath );
		} else {
			errors.push( `delete: ${ path } does not exist` );
		}
	}

	if ( 0 === replaceEntries.length ) {
		continue;
	}

	if ( ! ( await fileExists( absolutePath ) ) ) {
		for ( const entry of replaceEntries ) {
			errors.push( `replace-exact: ${ path } does not exist for span starting "${ entry.from.split( '\n' )[0] }"` );
		}
		continue;
	}

	let buffer = await readFile( absolutePath, 'utf-8' );
	for ( const entry of replaceEntries ) {
		const occurrences = buffer.split( entry.from ).length - 1;
		if ( 1 !== occurrences ) {
			errors.push( `replace-exact: ${ path } — span occurs ${ occurrences } times (want exactly 1): "${ entry.from.split( '\n' )[0] }"` );
			continue;
		}
		buffer = buffer.replace( entry.from, entry.to );
	}

	pendingWrites.push( { absolutePath, buffer } );
}

if ( 0 !== errors.length ) {
	console.error( 'fill-in-scaffold-content: manifest failed with %d violation(s):', errors.length );
	for ( const error of errors ) {
		console.error( '  - %s', error );
	}
	process.exit( 1 );
}

if ( checkOnly ) {
	console.log( 'fill-in-scaffold-content: --check passed; every span matches exactly once.' );
	process.exit( 0 );
}

for ( const { absolutePath, buffer } of pendingWrites ) {
	console.log( 'Stripping teaching content from %s', absolutePath );
	await writeFile( absolutePath, buffer );
}
for ( const absolutePath of pendingDeletes ) {
	console.log( 'Deleting %s', absolutePath );
	await unlink( absolutePath );
}
