/**
 * `background-pro` option type — React renderer.
 *
 * The largest composite in the library: five stacked layers — colour, gradient,
 * image, video, overlay — each with its own shape. The stored value is always
 * the COMPLETE structure, never a partial one, because
 * `_get_value_from_input()` starts from the declared defaults and writes over
 * them. An element's view reads `image/size/selected` and similar deep paths
 * directly, so a value missing a branch is a fatal-free but broken render.
 *
 * That is why every write here spreads over the full default shape rather than
 * patching in place.
 *
 * ## Layers stack; there is no enable switch
 *
 * A layer is "on" when it has a value. Two consequences the control honours:
 *
 * - **Gradient** is on when its `stops` array holds two or more entries; the
 *   gradient control already enforces that.
 * - **Video** `enabled` is DERIVED. PHP recomputes it from whether a playable
 *   source is set, precisely because a stored `enabled: 'no'` sitting next to a
 *   real video "looks broken in exports and the Site Converter". Blocks never
 *   reach that recompute, so this control derives it on every change — otherwise
 *   the block path would produce exactly the dishonest value PHP goes out of its
 *   way to prevent.
 *
 * ## `disable`
 *
 * A schema can hide layers it cannot support — a box-preset fill renders as CSS
 * and so disables `video`, which has no DOM to hook. Accepts a string or an
 * array, and layers named there are not rendered at all.
 */

import { get as getControl } from '../registry.js';

const { BaseControl, Button, PanelBody, SelectControl, TextControl, ToggleControl } = wp.components;

/** Mirrors Fw_Option_Type_Background_Pro::_get_defaults()['value']. */
const DEFAULTS = {
	color: { value: { predefined: '', custom: '' } },
	gradient: { data: { type: 'linear', angle: 90, stops: [] } },
	image: {
		src: {},
		position: 'center center',
		size: { selected: 'cover', custom: '' },
		repeat: 'no-repeat',
		attachment: 'scroll',
	},
	video: {
		enabled: 'no',
		external_url: '',
		source_mp4: {},
		source_webm: {},
		poster: {},
		fallback: {},
		loop: 'yes',
		autoplay: 'yes',
		mute: 'yes',
		playsinline: 'yes',
		allow_interaction: 'no',
	},
	overlay: { color: '', gradient: { type: 'linear', angle: 90, stops: [] } },
	advanced: [],
};

const POSITIONS = [
	'top left', 'top center', 'top right',
	'center left', 'center center', 'center right',
	'bottom left', 'bottom center', 'bottom right',
];

const REPEATS = [
	[ 'no-repeat', 'No Repeat' ],
	[ 'repeat', 'Repeat (Tile)' ],
	[ 'repeat-x', 'Repeat Horizontally' ],
	[ 'repeat-y', 'Repeat Vertically' ],
	[ 'space', 'Space (No Crop)' ],
	[ 'round', 'Round (Stretch to Whole Tiles)' ],
];

const ATTACHMENTS = [
	[ 'scroll', 'Scroll' ],
	[ 'fixed', 'Fixed (Parallax)' ],
	[ 'local', 'Local' ],
];

const SIZES = [
	[ 'auto', 'Auto' ],
	[ 'cover', 'Cover' ],
	[ 'contain', 'Contain' ],
	[ 'custom', 'Custom' ],
];

/**
 * Render a child option type from the registry.
 *
 * @param {string}   type     Option type id.
 * @param {Object}   schema   Option schema for the child.
 * @param {*}        value    Current value.
 * @param {Function} onChange Called with the next value.
 * @return {Object|null} The element, or null when no control exists.
 */
function child( type, schema, value, onChange ) {
	const Control = getControl( type );

	if ( ! Control ) {
		return null;
	}

	return <Control option={ { type, ...schema } } value={ value } onChange={ onChange } />;
}

/**
 * Is there a playable video source in this value?
 *
 * Mirrors the derivation in _get_value_from_input(): an external URL, or an
 * uploaded mp4/webm with a resolved url.
 *
 * @param {Object} video The video branch.
 * @return {boolean} True when a source is set.
 */
function hasVideo( video ) {
	return Boolean(
		( video.external_url && String( video.external_url ).trim() !== '' ) ||
			( video.source_mp4 && video.source_mp4.url ) ||
			( video.source_webm && video.source_webm.url )
	);
}

/**
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry.
 * @param {Object}   props.value    Current background value.
 * @param {Function} props.onChange Called with the next value.
 */
export default function BackgroundPro( { option = {}, value, onChange } ) {
	const v = value && typeof value === 'object' ? value : {};

	// Deep-merge over the defaults: the element reads nested paths directly, so a
	// missing branch is worse than a wrong one.
	const current = {
		color: { ...DEFAULTS.color, ...( v.color || {} ) },
		gradient: { ...DEFAULTS.gradient, ...( v.gradient || {} ) },
		image: { ...DEFAULTS.image, ...( v.image || {} ) },
		video: { ...DEFAULTS.video, ...( v.video || {} ) },
		overlay: { ...DEFAULTS.overlay, ...( v.overlay || {} ) },
		advanced: v.advanced || DEFAULTS.advanced,
	};

	current.image.size = { ...DEFAULTS.image.size, ...( current.image.size || {} ) };

	const disabled = Array.isArray( option.disable )
		? option.disable
		: option.disable
			? [ option.disable ]
			: [];

	const shows = ( layer ) => ! disabled.includes( layer );

	const setLayer = ( layer, patch ) => {
		const next = { ...current, [ layer ]: { ...current[ layer ], ...patch } };

		// Keep `enabled` honest, exactly as the PHP does.
		if ( layer === 'video' ) {
			next.video.enabled = hasVideo( next.video ) ? 'yes' : 'no';
		}

		onChange( next );
	};

	const sw = ( layer, key, label ) => (
		<ToggleControl
			label={ label }
			checked={ current[ layer ][ key ] === 'yes' }
			onChange={ ( on ) => setLayer( layer, { [ key ]: on ? 'yes' : 'no' } ) }
			__nextHasNoMarginBottom
		/>
	);

	return (
		<BaseControl
			label={ option.label || '' }
			help={ option.desc || undefined }
			__nextHasNoMarginBottom
		>
			{ shows( 'color' ) && (
				<PanelBody title="Colour" initialOpen={ false }>
					{ child(
						'predefined-colors-color-picker-compact',
						{ label: '' },
						current.color.value,
						( next ) => onChange( { ...current, color: { value: next } } )
					) }
				</PanelBody>
			) }

			{ shows( 'gradient' ) && (
				<PanelBody title="Gradient" initialOpen={ false }>
					{ child( 'gradient-v2', { label: '' }, current.gradient.data, ( next ) =>
						onChange( { ...current, gradient: { data: next } } )
					) }
				</PanelBody>
			) }

			{ shows( 'image' ) && (
				<PanelBody title="Image" initialOpen={ false }>
					{ child(
						'upload',
						{ label: 'Image', images_only: true },
						current.image.src,
						( next ) => setLayer( 'image', { src: next } )
					) }

					<SelectControl
						label="Position"
						value={ current.image.position }
						options={ POSITIONS.map( ( p ) => ( {
							value: p,
							label: p.replace( /\b\w/g, ( c ) => c.toUpperCase() ),
						} ) ) }
						onChange={ ( next ) => setLayer( 'image', { position: next } ) }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>

					<SelectControl
						label="Size"
						value={ current.image.size.selected }
						options={ SIZES.map( ( [ value_, label ] ) => ( { value: value_, label } ) ) }
						onChange={ ( next ) =>
							setLayer( 'image', { size: { ...current.image.size, selected: next } } )
						}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>

					{ current.image.size.selected === 'custom' && (
						<TextControl
							label="Custom size"
							help='e.g. "400px" or "100% 50%"'
							value={ current.image.size.custom }
							onChange={ ( next ) =>
								setLayer( 'image', { size: { ...current.image.size, custom: next } } )
							}
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
					) }

					<SelectControl
						label="Repeat"
						value={ current.image.repeat }
						options={ REPEATS.map( ( [ value_, label ] ) => ( { value: value_, label } ) ) }
						onChange={ ( next ) => setLayer( 'image', { repeat: next } ) }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>

					<SelectControl
						label="Attachment"
						value={ current.image.attachment }
						options={ ATTACHMENTS.map( ( [ value_, label ] ) => ( { value: value_, label } ) ) }
						onChange={ ( next ) => setLayer( 'image', { attachment: next } ) }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			) }

			{ shows( 'video' ) && (
				<PanelBody title="Video" initialOpen={ false }>
					{ /*
					  * No enable switch, deliberately — the layer is on when a source is
					  * set. An extra toggle would be a second source of truth for the
					  * same fact, and the one PHP recomputes away.
					  */ }
					<TextControl
						label="External URL"
						value={ current.video.external_url }
						onChange={ ( next ) => setLayer( 'video', { external_url: next } ) }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					{ child( 'upload', { label: 'MP4 file' }, current.video.source_mp4, ( next ) =>
						setLayer( 'video', { source_mp4: next } )
					) }
					{ child( 'upload', { label: 'WebM file' }, current.video.source_webm, ( next ) =>
						setLayer( 'video', { source_webm: next } )
					) }
					{ child(
						'upload',
						{ label: 'Poster', images_only: true },
						current.video.poster,
						( next ) => setLayer( 'video', { poster: next } )
					) }
					{ child(
						'upload',
						{ label: 'Fallback image', images_only: true },
						current.video.fallback,
						( next ) => setLayer( 'video', { fallback: next } )
					) }

					{ sw( 'video', 'loop', 'Loop' ) }
					{ sw( 'video', 'autoplay', 'Autoplay' ) }
					{ sw( 'video', 'mute', 'Mute' ) }
					{ sw( 'video', 'playsinline', 'Play inline' ) }
					{ sw( 'video', 'allow_interaction', 'Allow interaction' ) }
				</PanelBody>
			) }

			{ shows( 'overlay' ) && (
				<PanelBody title="Overlay" initialOpen={ false }>
					{ child( 'rgba-color-picker', { label: 'Tint' }, current.overlay.color, ( next ) =>
						setLayer( 'overlay', { color: next } )
					) }
					{ child(
						'gradient-v2',
						{ label: 'Overlay gradient' },
						current.overlay.gradient,
						( next ) => setLayer( 'overlay', { gradient: next } )
					) }
				</PanelBody>
			) }
		</BaseControl>
	);
}
