/**
 * UnysonPlus asset build pipeline.
 *
 * Reads the readable source CSS/JS under framework/ and writes minified
 * (and, for CSS, autoprefixed) `*.min.css` / `*.min.js` siblings next to
 * each source file. The plugin enqueues the .min versions in production via
 * fw_get_framework_asset_uri() (framework/helpers/general.php), falling back
 * to the source when a .min is absent — so running this build is optional for
 * the plugin to work, but recommended before shipping.
 *
 *   cd unysonplus/build
 *   npm install      # once
 *   npm run build    # regenerate .min files after editing any listed source
 *
 * CSS is processed with PostCSS (autoprefixer + cssnano); autoprefixer uses
 * the browser targets in .browserslistrc. JS is minified per-file with esbuild
 * (transform only — NOT bundled), so the framework's global-based load order
 * and dependency graph are preserved exactly.
 */

import { readFile, writeFile, readdir } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import postcss from 'postcss';
import autoprefixer from 'autoprefixer';
import cssnano from 'cssnano';
import * as esbuild from 'esbuild';

const HERE = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(HERE, '..'); // unysonplus/ (plugin root)

/** Recursively collect *.css (excluding *.min.css) under a plugin-root-relative dir. */
async function collectCss(relDir) {
	const out = [];
	async function walk(abs) {
		for (const entry of await readdir(abs, { withFileTypes: true })) {
			const child = path.join(abs, entry.name);
			if (entry.isDirectory()) {
				await walk(child);
			} else if (entry.name.endsWith('.css') && !entry.name.endsWith('.min.css')) {
				out.push(path.relative(ROOT, child).split(path.sep).join('/'));
			}
		}
	}
	await walk(path.join(ROOT, relDir));
	return out;
}

/** Vendored JS we must NOT reprocess (third-party libs living among own code). */
const JS_VENDOR_SKIP = /^jquery[.\-]|^bootstrap-datepicker\.js$|^image-picker\.js$/i;

/**
 * Recursively collect own-code *.js under a plugin-root-relative dir, skipping
 * *.min.js, any `libs/` directory, and known vendored filenames.
 */
async function collectJs(relDir) {
	const out = [];
	async function walk(abs) {
		for (const entry of await readdir(abs, { withFileTypes: true })) {
			const child = path.join(abs, entry.name);
			if (entry.isDirectory()) {
				if (entry.name === 'libs') continue; // never reprocess vendored libraries
				await walk(child);
			} else if (
				entry.name.endsWith('.js') &&
				!entry.name.endsWith('.min.js') &&
				!JS_VENDOR_SKIP.test(entry.name)
			) {
				out.push(path.relative(ROOT, child).split(path.sep).join('/'));
			}
		}
	}
	await walk(path.join(ROOT, relDir));
	return out;
}

/**
 * Source files to build, relative to the plugin root. Kept in sync with the
 * enqueues switched to fw_get_framework_asset_uri() in PHP. The helper falls
 * back gracefully for any source that isn't built, so this list is safe to grow.
 *
 * Coverage: the 3 core backend stylesheets, every option-type stylesheet
 * (wired via the helper in their _enqueue_static()), and dynamic-content.
 */
const CSS_FILES = [
	'framework/static/css/fw.css',
	'framework/static/css/backend-options.css',
	'framework/static/css/option-types.css',
	...(await collectCss('framework/includes/option-types')),
	...(await collectCss('framework/includes/dynamic-content')),
];

/**
 * Framework JS. Core files (enqueued from backend.php + class-fw-option-type.php)
 * plus every own-code option-type / dynamic-content script (vendored libs skipped
 * by collectJs). Wired via fw_get_framework_asset_uri(); the helper falls back to
 * source for anything not built, so this list is safe to grow.
 */
const JS_FILES = [
	'framework/static/js/fw-events.js',
	'framework/static/js/fw.js',
	'framework/static/js/fw-reactive-options-registry.js',
	'framework/static/js/fw-reactive-options-simple-options.js',
	'framework/static/js/fw-reactive-options-undefined-option.js',
	'framework/static/js/fw-reactive-options.js',
	'framework/static/js/backend-options.js',
	'framework/static/js/fw-form-helpers.js',
	'framework/static/js/backend-customizer.js',
	'framework/static/js/option-types.js',
	...(await collectJs('framework/includes/option-types')),
	...(await collectJs('framework/includes/dynamic-content')),
];

const cssProcessor = postcss([autoprefixer(), cssnano({ preset: 'default' })]);

const toMin = (rel) => rel.replace(/\.(css|js)$/, '.min.$1');

async function buildCss(rel) {
	const from = path.join(ROOT, rel);
	const to = path.join(ROOT, toMin(rel));
	const src = await readFile(from, 'utf8');
	const out = await cssProcessor.process(src, { from, to });
	await writeFile(to, out.css, 'utf8');
	return { rel, inBytes: Buffer.byteLength(src), outBytes: Buffer.byteLength(out.css) };
}

async function buildJs(rel) {
	const from = path.join(ROOT, rel);
	const to = path.join(ROOT, toMin(rel));
	const src = await readFile(from, 'utf8');
	const out = await esbuild.transform(src, {
		minify: true,
		// Conservative first JS pass: strip whitespace/comments + safe syntax
		// compression, but DON'T rename identifiers — avoids any reliance on
		// function .name or eval-scoped locals in this jQuery/Backbone-era code.
		// Can be enabled later for more compression once verified in the browser.
		minifyIdentifiers: false,
		loader: 'js',
		legalComments: 'none',
	});
	await writeFile(to, out.code, 'utf8');
	return { rel, inBytes: Buffer.byteLength(src), outBytes: Buffer.byteLength(out.code) };
}

const results = [];
for (const f of CSS_FILES) results.push(await buildCss(f));
for (const f of JS_FILES) results.push(await buildJs(f));

let totalIn = 0;
let totalOut = 0;
for (const r of results) {
	totalIn += r.inBytes;
	totalOut += r.outBytes;
	const pct = ((1 - r.outBytes / r.inBytes) * 100).toFixed(1);
	console.log(
		`  ${r.rel.padEnd(46)} ${(r.inBytes / 1024).toFixed(1).padStart(7)}KB -> ${(r.outBytes / 1024).toFixed(1).padStart(6)}KB  (-${pct}%)`
	);
}
const totalPct = totalIn ? ((1 - totalOut / totalIn) * 100).toFixed(1) : '0.0';
console.log(
	`\n  Built ${results.length} file(s): ${(totalIn / 1024).toFixed(1)}KB -> ${(totalOut / 1024).toFixed(1)}KB (-${totalPct}%)`
);
