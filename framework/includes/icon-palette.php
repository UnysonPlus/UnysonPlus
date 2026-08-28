<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * The UnysonPlus ICON palette.
 *
 * One palette for every glyph the framework draws itself — the option-picker
 * thumbnails (section / column / flexbox alignment), the Animation Engine
 * effect tiles, and anything added later. It exists so those glyphs read as one
 * family instead of each file inventing its own greys and blues.
 *
 * The blue is #3858e9 -- WordPress's CURRENT brand blue, the accent of the
 * "modern" admin scheme. It replaced the previous house blue #2f74e6 (azure,
 * hue 217deg) because that sat 12deg off the chrome around it: close enough to read
 * as the same colour, different enough to look like a near-miss wherever a glyph
 * sat beside a primary button. Matching exactly beats nearly matching. (#2271b1,
 * the OLD admin blue, is legacy and appears nowhere in this palette.)
 *
 * The companions are hue-matched to it rather than picked by eye, so the family
 * holds together: 'accent_dark' and 'field' keep their previous lightness and
 * saturation but adopt the 229deg hue. 'accent_light' is #7b90ff -- WordPress's
 * own lighter tone from the same scheme, which independently lands at 230deg.
 *
 * The STRUCTURE of the palette came from the Animation Engine's scroll-effect
 * icons, already the largest coherent set, rather than being invented.
 *
 * ---------------------------------------------------------------------------
 * WHY THIS IS A FIXED PALETTE AND NOT THE ADMIN COLOUR SCHEME
 *
 * The framework's CHROME follows the user's Administration Color Scheme (see
 * --fw-accent in backend-options.css / backend-options-skin.css). These icons
 * deliberately do NOT. They are artwork, not chrome: a glyph's blue is part of
 * a drawing whose greys, tints and hairlines were chosen to sit against that
 * exact blue, and recolouring one channel of an illustration to an arbitrary
 * scheme accent (Sunrise's ochre, Ectoplasm's olive) breaks the drawing rather
 * than theming it. WordPress treats its own block icons the same way.
 *
 * It is also not technically possible for most of these: they are emitted as
 * `data:image/svg+xml` URIs in <img>/background-image, which cannot read CSS
 * custom properties or currentColor. The colour is frozen when PHP renders it.
 *
 * ---------------------------------------------------------------------------
 * HOW TO USE THE PALETTE (the part that keeps glyphs legible)
 *
 * - 'field' / 'field_strong' are CANVASES: the area a thing sits IN, never the
 *   subject itself. 'field' tints it; 'field_strong' makes it a solid plane.
 *
 *   'field_strong' is legible ONLY when both of these hold, and it is worth
 *   checking rather than assuming — an earlier revision of this file banned it
 *   outright, which turned out to be too strong a rule:
 *     1. No baked caption sits ON it. Caption grey is 6.01:1 on 'field' but
 *        1.42:1 on a saturated plane. Section's captions are drawn BELOW the
 *        coloured band, on the tile, so they are unaffected; a glyph that puts
 *        text inside the band must use 'field' or switch the text to #fff.
 *     2. Whatever sits on it stays lighter and neutral, so figure and ground
 *        do not invert. Neutral greys on 'field_strong' read cleanly; more
 *        blue-on-blue does not.
 *   Also weigh the CONTEXT: in a picker, every tile saturated competes with the
 *   selected state the user is actually scanning for. 'field' keeps unselected
 *   tiles quiet; 'field_strong' trades that for presence.
 * - 'accent' is for SMALL indicative marks that show WHAT THE ICON DEMONSTRATES
 *   — the dot in blur.svg is the thing being blurred. It is not a generic
 *   highlight: in an alignment glyph the subject is the POSITION of the
 *   columns, so the content line inside a text block is 'ink', not 'accent'.
 *   Painting content with the accent mislabels it as the demonstrated thing.
 *   Sparingly, either way: an icon where everything is accent
 *   has no focal point, and in a picker it also competes with the selected
 *   state, which is what the user is actually scanning for.
 * - Structure (a column, a frame) is 'structure' / 'structure_line'. Cool
 *   slates, not warm greys, so they sit with the blues.
 * - 'caption' is for text baked INTO an SVG. It is 6.01:1 on 'field' (passes
 *   WCAG AA) but only 1.42:1 on 'accent' — so captions belong on 'field' or on
 *   white, never on a saturated fill. If a design needs text on 'accent', it
 *   must switch to #fff (5.17:1), not stay grey.
 *
 * @return array<string,string>
 */
function fw_upw_icon_palette() {
	return array(
		// Blues — the family tie. Fixed; see above.
		'accent'         => '#3858e9', // primary mark
		'accent_dark'    => '#1b33a0', // pressed / depth
		'accent_light'   => '#7b90ff', // secondary mark
		'field'          => '#dbe1fe', // pale canvas the subject sits in
		'field_strong'   => '#e7ebfc', // the container plane: accent at 12% on white

		// NEUTRAL greys — structure. Deliberately NOT slates or any other
		// blue-leaning grey, even though slates would "go with" the blues.
		//
		//
		// 'field_strong' is the accent mixed 12% toward white, so it is literally
		// the same blue at a lower weight -- the hue never drifts. It was a SOLID
		// #3858e9 plane; across six picker tiles that much saturation made every
		// unselected tile shout and left the selected one nothing to stand out
		// against, and it pulled the eye to the container rather than to what the
		// icon actually demonstrates (where the columns sit).
		//
		// PAIRING RULE: lightening the plane forces the shape ON it to darken, or
		// the legend collapses. Measured on this plane: 'structure' #dadada is
		// 1.18:1 -- it dissolves -- while 'structure_strong' gives 2.34:1, and
		// 'accent' marks give 4.73:1 where 'accent_light' manages only 2.43:1. On
		// the old SOLID plane the ranking inverted (#dadada was 4.02:1 there and
		// #9b9b9b only 2.02:1). The right grey is a function of the plane, not a
		// preference -- which is why they are separate tokens.
		//
		// The trade this weight makes, stated plainly: the COLUMN reads far better
		// (2.34:1 here vs 1.50:1 at 40%), which is right because the column is what
		// these glyphs are about. The cost is the SECTION cue -- the plane against
		// the white tile behind it is only 1.19:1 (it was 1.85:1 at 40%, 5.61:1
		// solid). If the section boundary ever needs to reassert itself, a hairline
		// stroke on the plane restores it without adding weight.
		// Blue is SEMANTIC in these glyphs: it means the container (the section).
		// A grey with blue in it therefore reads as a lighter part of that same
		// container instead of as a different material, and the legend the icon
		// depends on — blue = section, grey = column — stops being legible. The
		// palette keeps its two channels separate on purpose.
		//
		// Two fills, because the BACKGROUND decides the weight: 'structure'
		// (#dadada) reads correctly as a light shape on the blue 'field_strong'
		// plane, but on white it nearly vanishes (1.40:1). Artwork drawn on white
		// -- the column ratio thumbnails, for instance -- needs 'structure_strong'
		// (#9b9b9b, 2.78:1). Choosing by context rather than habit is the
		// difference between a legible icon and a faint one.
		'structure_soft' => '#ececec', // faintest block
		'structure'      => '#dadada', // a column / frame ON A TINTED PLANE
		'structure_strong' => '#9b9b9b', // the same shape ON WHITE
		'structure_line' => '#dcdcde', // hairline + edges

		// Content.
		'content'        => '#ffffff', // a content element
		'ink'            => '#1f2430', // TEXT inside a content element
		'caption'        => '#50575e', // text baked into the SVG
	);
}
