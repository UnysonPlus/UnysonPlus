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
		'framework/extensions/gutenberg/blocks/newsletter/src/index.jsx',
		'framework/extensions/gutenberg/blocks/newsletter/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/animated-heading/src/index.jsx',
		'framework/extensions/gutenberg/blocks/animated-heading/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/modal-popup/src/index.jsx',
		'framework/extensions/gutenberg/blocks/modal-popup/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/notification/src/index.jsx',
		'framework/extensions/gutenberg/blocks/notification/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/highlight-text/src/index.jsx',
		'framework/extensions/gutenberg/blocks/highlight-text/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/tooltip/src/index.jsx',
		'framework/extensions/gutenberg/blocks/tooltip/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/social-share/src/index.jsx',
		'framework/extensions/gutenberg/blocks/social-share/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/tabs/src/index.jsx',
		'framework/extensions/gutenberg/blocks/tabs/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/steps/src/index.jsx',
		'framework/extensions/gutenberg/blocks/steps/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/timeline/src/index.jsx',
		'framework/extensions/gutenberg/blocks/timeline/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/logo-grid/src/index.jsx',
		'framework/extensions/gutenberg/blocks/logo-grid/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/accordion/src/index.jsx',
		'framework/extensions/gutenberg/blocks/accordion/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/testimonials/src/index.jsx',
		'framework/extensions/gutenberg/blocks/testimonials/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/social-icons/src/index.jsx',
		'framework/extensions/gutenberg/blocks/social-icons/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/divider/src/index.jsx',
		'framework/extensions/gutenberg/blocks/divider/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/carousel/src/index.jsx',
		'framework/extensions/gutenberg/blocks/carousel/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/feature-list/src/index.jsx',
		'framework/extensions/gutenberg/blocks/feature-list/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/comparison-table/src/index.jsx',
		'framework/extensions/gutenberg/blocks/comparison-table/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/icon/src/index.jsx',
		'framework/extensions/gutenberg/blocks/icon/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/image-box/src/index.jsx',
		'framework/extensions/gutenberg/blocks/image-box/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/gallery/src/index.jsx',
		'framework/extensions/gutenberg/blocks/gallery/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/team-member/src/index.jsx',
		'framework/extensions/gutenberg/blocks/team-member/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/progress/src/index.jsx',
		'framework/extensions/gutenberg/blocks/progress/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/business-info/src/index.jsx',
		'framework/extensions/gutenberg/blocks/business-info/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/audio-player/src/index.jsx',
		'framework/extensions/gutenberg/blocks/audio-player/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/author-box/src/index.jsx',
		'framework/extensions/gutenberg/blocks/author-box/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/calendar/src/index.jsx',
		'framework/extensions/gutenberg/blocks/calendar/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/image-hotspots/src/index.jsx',
		'framework/extensions/gutenberg/blocks/image-hotspots/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/map/src/index.jsx',
		'framework/extensions/gutenberg/blocks/map/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/avatar/src/index.jsx',
		'framework/extensions/gutenberg/blocks/avatar/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/media-image/src/index.jsx',
		'framework/extensions/gutenberg/blocks/media-image/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/media-video/src/index.jsx',
		'framework/extensions/gutenberg/blocks/media-video/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/text-block/src/index.jsx',
		'framework/extensions/gutenberg/blocks/text-block/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/featured-image/src/index.jsx',
		'framework/extensions/gutenberg/blocks/featured-image/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/lottie/src/index.jsx',
		'framework/extensions/gutenberg/blocks/lottie/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/svg-draw/src/index.jsx',
		'framework/extensions/gutenberg/blocks/svg-draw/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/model-viewer/src/index.jsx',
		'framework/extensions/gutenberg/blocks/model-viewer/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/global-section/src/index.jsx',
		'framework/extensions/gutenberg/blocks/global-section/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/snippet/src/index.jsx',
		'framework/extensions/gutenberg/blocks/snippet/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/scroll-indicator/src/index.jsx',
		'framework/extensions/gutenberg/blocks/scroll-indicator/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/site-search/src/index.jsx',
		'framework/extensions/gutenberg/blocks/site-search/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/image-sequence/src/index.jsx',
		'framework/extensions/gutenberg/blocks/image-sequence/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/svg-morph/src/index.jsx',
		'framework/extensions/gutenberg/blocks/svg-morph/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/webgl-object/src/index.jsx',
		'framework/extensions/gutenberg/blocks/webgl-object/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/posts/src/index.jsx',
		'framework/extensions/gutenberg/blocks/posts/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/toc/src/index.jsx',
		'framework/extensions/gutenberg/blocks/toc/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/button/src/index.jsx',
		'framework/extensions/gutenberg/blocks/button/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/pricing-table/src/index.jsx',
		'framework/extensions/gutenberg/blocks/pricing-table/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/call-to-action/src/index.jsx',
		'framework/extensions/gutenberg/blocks/call-to-action/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/image-content/src/index.jsx',
		'framework/extensions/gutenberg/blocks/image-content/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/countdown/src/index.jsx',
		'framework/extensions/gutenberg/blocks/countdown/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/code-block/src/index.jsx',
		'framework/extensions/gutenberg/blocks/code-block/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/post-title/src/index.jsx',
		'framework/extensions/gutenberg/blocks/post-title/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/post-content/src/index.jsx',
		'framework/extensions/gutenberg/blocks/post-content/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/post-date/src/index.jsx',
		'framework/extensions/gutenberg/blocks/post-date/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/post-excerpt/src/index.jsx',
		'framework/extensions/gutenberg/blocks/post-excerpt/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/post-meta/src/index.jsx',
		'framework/extensions/gutenberg/blocks/post-meta/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/post-terms/src/index.jsx',
		'framework/extensions/gutenberg/blocks/post-terms/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/post-author/src/index.jsx',
		'framework/extensions/gutenberg/blocks/post-author/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/site-logo/src/index.jsx',
		'framework/extensions/gutenberg/blocks/site-logo/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/nav-menu/src/index.jsx',
		'framework/extensions/gutenberg/blocks/nav-menu/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/menu-toggle/src/index.jsx',
		'framework/extensions/gutenberg/blocks/menu-toggle/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/widget-area/src/index.jsx',
		'framework/extensions/gutenberg/blocks/widget-area/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/scroll-to-top/src/index.jsx',
		'framework/extensions/gutenberg/blocks/scroll-to-top/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/flip-box/src/index.jsx',
		'framework/extensions/gutenberg/blocks/flip-box/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/gallery-3d/src/index.jsx',
		'framework/extensions/gutenberg/blocks/gallery-3d/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/section/src/index.jsx',
		'framework/extensions/gutenberg/blocks/section/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/column/src/index.jsx',
		'framework/extensions/gutenberg/blocks/column/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/container/src/index.jsx',
		'framework/extensions/gutenberg/blocks/container/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/flexbox/src/index.jsx',
		'framework/extensions/gutenberg/blocks/flexbox/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/bleed-section/src/index.jsx',
		'framework/extensions/gutenberg/blocks/bleed-section/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/masonry-section/src/index.jsx',
		'framework/extensions/gutenberg/blocks/masonry-section/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/table/src/index.jsx',
		'framework/extensions/gutenberg/blocks/table/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/contact-form/src/index.jsx',
		'framework/extensions/gutenberg/blocks/contact-form/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/portfolio/src/index.jsx',
		'framework/extensions/gutenberg/blocks/portfolio/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/project-details/src/index.jsx',
		'framework/extensions/gutenberg/blocks/project-details/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/project-gallery/src/index.jsx',
		'framework/extensions/gutenberg/blocks/project-gallery/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/project-nav/src/index.jsx',
		'framework/extensions/gutenberg/blocks/project-nav/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/project-results/src/index.jsx',
		'framework/extensions/gutenberg/blocks/project-results/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/project-testimonial/src/index.jsx',
		'framework/extensions/gutenberg/blocks/project-testimonial/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/related-projects/src/index.jsx',
		'framework/extensions/gutenberg/blocks/related-projects/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/wc-products/src/index.jsx',
		'framework/extensions/gutenberg/blocks/wc-products/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/wc-product/src/index.jsx',
		'framework/extensions/gutenberg/blocks/wc-product/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/wc-product-page/src/index.jsx',
		'framework/extensions/gutenberg/blocks/wc-product-page/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/wc-product-categories/src/index.jsx',
		'framework/extensions/gutenberg/blocks/wc-product-categories/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/wc-product-filters/src/index.jsx',
		'framework/extensions/gutenberg/blocks/wc-product-filters/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/wc-product-search/src/index.jsx',
		'framework/extensions/gutenberg/blocks/wc-product-search/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/wc-add-to-cart/src/index.jsx',
		'framework/extensions/gutenberg/blocks/wc-add-to-cart/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/wc-cart-link/src/index.jsx',
		'framework/extensions/gutenberg/blocks/wc-cart-link/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/wc-mini-cart/src/index.jsx',
		'framework/extensions/gutenberg/blocks/wc-mini-cart/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/wc-account/src/index.jsx',
		'framework/extensions/gutenberg/blocks/wc-account/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/wc-free-shipping/src/index.jsx',
		'framework/extensions/gutenberg/blocks/wc-free-shipping/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/wc-cart/src/index.jsx',
		'framework/extensions/gutenberg/blocks/wc-cart/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/wc-checkout/src/index.jsx',
		'framework/extensions/gutenberg/blocks/wc-checkout/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/wc-my-account/src/index.jsx',
		'framework/extensions/gutenberg/blocks/wc-my-account/build/index',
	],
	[
		'framework/extensions/gutenberg/blocks/wc-order-tracking/src/index.jsx',
		'framework/extensions/gutenberg/blocks/wc-order-tracking/build/index',
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
