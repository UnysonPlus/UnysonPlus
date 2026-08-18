/**
 * Build the React control bundle (`fw.controls`).
 *
 * This is separate from build.mjs on purpose. build.mjs is a *transform-only*
 * minifier: it never bundles, because the framework's classic admin scripts
 * depend on globals and on wp_enqueue_script() ordering, and bundling would
 * destroy that graph. The control layer is new code with real ES module
 * imports, so it genuinely needs bundling — and gets its own pipeline rather
 * than compromising the other one.
 *
 *   cd unysonplus/build
 *   npm install            # once
 *   npm run build:controls
 *
 * ## Why no React ends up in the output
 *
 * JSX is compiled to `wp.element.createElement` instead of `React.createElement`,
 * and nothing imports React. WordPress already loads React in wp-admin and
 * exposes it as `wp.element` (script handle `wp-element`, declared as a
 * dependency in UnysonPlus\Admin\Controls\Registry). So the bundle *uses* React
 * without ever *containing* it.
 *
 * `external` below is belt-and-braces: if someone later adds `import … from
 * 'react'`, the build fails loudly at that import rather than silently shipping
 * a second React. That failure is the point — do not "fix" it by removing the
 * externals.
 */

import path from 'node:path';
import { fileURLToPath } from 'node:url';
import * as esbuild from 'esbuild';

const HERE = path.dirname( fileURLToPath( import.meta.url ) );
const ROOT = path.resolve( HERE, '..' ); // unysonplus/ (plugin root)

/**
 * Everything built with this pipeline: [ entry, output basename without ext ].
 *
 * Gutenberg blocks live here too. NOTE for release: the gutenberg extension ships
 * through the Extensions manager, which downloads a GitHub source archive — and a
 * source archive contains only what is COMMITTED. No build runs on the user's
 * machine, so each block's `build/` output must be committed to that extension's
 * repo (with node_modules ignored), and this build must run before tagging.
 */
const ENTRIES = [
	[
		'framework/static/js/controls/src/index.jsx',
		'framework/static/js/controls/build/fw-controls',
	],
	[
		'framework/extensions/gutenberg/blocks/before-after/src/index.jsx',
		'framework/extensions/gutenberg/blocks/before-after/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/counter/src/index.jsx',
		'framework/extensions/gutenberg/blocks/counter/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/star-rating/src/index.jsx',
		'framework/extensions/gutenberg/blocks/star-rating/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/video-popup/src/index.jsx',
		'framework/extensions/gutenberg/blocks/video-popup/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/icon-box/src/index.jsx',
		'framework/extensions/gutenberg/blocks/icon-box/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/special-heading/src/index.jsx',
		'framework/extensions/gutenberg/blocks/special-heading/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/text-expander/src/index.jsx',
		'framework/extensions/gutenberg/blocks/text-expander/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/blockquote/src/index.jsx',
		'framework/extensions/gutenberg/blocks/blockquote/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/badge/src/index.jsx',
		'framework/extensions/gutenberg/blocks/badge/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/tag-list/src/index.jsx',
		'framework/extensions/gutenberg/blocks/tag-list/build/index',
	],
];

/** Shared options for every pass. */
const common = {
	bundle: true,
	format: 'iife',
	target: [ 'es2019' ],
	// JSX → WordPress's React, never our own.
	jsx: 'transform',
	jsxFactory: 'wp.element.createElement',
	jsxFragment: 'wp.element.Fragment',
	// Anything React-ish must come from the page, not the bundle.
	external: [ 'react', 'react-dom', '@wordpress/*' ],
	logLevel: 'info',
};

for ( const [ entry, out ] of ENTRIES ) {
	const entryPoints = [ path.join( ROOT, entry ) ];

	// Readable + sourcemapped for SCRIPT_DEBUG, minified for production.
	const passes = [
		{ outfile: path.join( ROOT, `${ out }.js` ), minify: false, sourcemap: true },
		{ outfile: path.join( ROOT, `${ out }.min.js` ), minify: true, legalComments: 'none' },
	];

	for ( const pass of passes ) {
		const result = await esbuild.build( { ...common, entryPoints, ...pass } );

		if ( result.errors.length ) {
			process.exitCode = 1;
		}
	}
}

console.log( '\nModern JS bundles built.' );
