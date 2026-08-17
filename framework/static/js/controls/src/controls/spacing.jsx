/**
 * `spacing` option type — React renderer.
 *
 * ## The value is a three-level structure, not a string
 *
 *   {
 *     margin:   { all, top, right, bottom, left },
 *     padding:  { all, top, right, bottom, left },
 *     advanced: { md: { margin, padding }, lg: { margin, padding } }
 *   }
 *
 * Every leaf is a Bootstrap utility **token name** — `'0'`–`'5'` by default, or
 * `''` for "not set". They are NOT CSS lengths: the server sanitises each slot
 * with `preg_replace( '/[^a-zA-Z0-9_-]/', '' )`, so `'1rem'` would be stored as
 * `'1rem'` minus nothing but `'12px'` keeps its digits and letters while `'12%'`
 * loses the percent. Offering a free-text length here would produce values that
 * survive sanitisation yet mean nothing to the CSS generator, so the control only
 * ever offers tokens from the scale.
 *
 * ## The scale is resolved server-side
 *
 * `get_scale()` applies the `fw_option_type_spacing_scale` filter, so a site can
 * replace Bootstrap's ladder entirely. That resolved scale is not part of the
 * option schema, so it cannot be read here — the control uses `option.scale` when
 * a caller supplies it and otherwise falls back to the same built-in default the
 * PHP type uses. A site that filters the scale and wants it in block sidebars must
 * pass it through explicitly; the fallback keeps the common case exact.
 *
 * ## `mode` narrows which sections exist
 *
 * `'both'` (default), `'margin'` or `'padding'`. The server only reads the
 * sections `mode` permits, so rendering the other one would offer edits that are
 * silently discarded on save.
 */

const { BaseControl, SelectControl, Button, ButtonGroup, Flex, FlexItem } = wp.components;
const { useState } = wp.element;

const SLOTS = [ 'all', 'top', 'right', 'bottom', 'left' ];
const SECTIONS = [ 'margin', 'padding' ];
const DEVICES = [
	{ key: 'base', label: 'Base' },
	{ key: 'md', label: 'md ≥768' },
	{ key: 'lg', label: 'lg ≥992' },
];

/** Mirrors FW_Option_Type_Spacing::default_scale() — Bootstrap 5's $spacers. */
const DEFAULT_SCALE = [
	{ name: '0', size: '0' },
	{ name: '1', size: '0.25rem' },
	{ name: '2', size: '0.5rem' },
	{ name: '3', size: '1rem' },
	{ name: '4', size: '1.5rem' },
	{ name: '5', size: '3rem' },
];

/** An empty section, matching the PHP default exactly. */
function emptySection() {
	return { all: '', top: '', right: '', bottom: '', left: '' };
}

/**
 * Fill a partial value out to the full stored shape.
 *
 * The server rebuilds this shape from its defaults on every save, so emitting it
 * whole keeps a React-saved value byte-identical to a builder-saved one.
 *
 * @param {Object} value Possibly partial value.
 * @return {Object} Complete value.
 */
function normalize( value ) {
	const v = value && typeof value === 'object' ? value : {};
	const section = ( s ) => Object.assign( emptySection(), s && typeof s === 'object' ? s : {} );
	const adv = v.advanced && typeof v.advanced === 'object' ? v.advanced : {};

	return {
		margin: section( v.margin ),
		padding: section( v.padding ),
		advanced: {
			md: { margin: section( adv.md && adv.md.margin ), padding: section( adv.md && adv.md.padding ) },
			lg: { margin: section( adv.lg && adv.lg.margin ), padding: section( adv.lg && adv.lg.padding ) },
		},
	};
}

/**
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry.
 * @param {Object}   props.value    Current value.
 * @param {Function} props.onChange Called with the next complete value.
 */
export default function Spacing( { option = {}, value, onChange } ) {
	const [ device, setDevice ] = useState( 'base' );

	const mode = [ 'both', 'margin', 'padding' ].includes( option.mode ) ? option.mode : 'both';
	const sections = 'both' === mode ? SECTIONS : [ mode ];

	const scale = Array.isArray( option.scale ) && option.scale.length ? option.scale : DEFAULT_SCALE;

	const choices = [ { value: '', label: '—' } ].concat(
		scale.map( ( s ) => ( {
			value: String( s.name ),
			label: `${ s.name }${ s.size ? ` (${ s.size })` : '' }`,
		} ) )
	);

	const current = normalize( value );

	/** Read a slot for the active device. */
	const read = ( section, slot ) =>
		'base' === device ? current[ section ][ slot ] : current.advanced[ device ][ section ][ slot ];

	/** Write a slot for the active device, emitting the whole value. */
	const write = ( section, slot, next ) => {
		const out = normalize( current );

		if ( 'base' === device ) {
			out[ section ][ slot ] = next;
		} else {
			out.advanced[ device ][ section ][ slot ] = next;
		}

		onChange( out );
	};

	return (
		<BaseControl
			label={ option.label || '' }
			help={ option.desc || undefined }
			__nextHasNoMarginBottom
		>
			<ButtonGroup style={ { marginBottom: 12 } }>
				{ DEVICES.map( ( d ) => (
					<Button
						key={ d.key }
						size="small"
						variant={ device === d.key ? 'primary' : 'secondary' }
						onClick={ () => setDevice( d.key ) }
					>
						{ d.label }
					</Button>
				) ) }
			</ButtonGroup>

			{ sections.map( ( section ) => (
				<div key={ section } style={ { marginBottom: 12 } }>
					<strong style={ { display: 'block', marginBottom: 6, textTransform: 'capitalize' } }>
						{ section }
					</strong>
					<Flex wrap gap={ 2 } justify="flex-start">
						{ SLOTS.map( ( slot ) => (
							<FlexItem key={ slot } style={ { minWidth: 92 } }>
								<SelectControl
									label={ slot }
									value={ read( section, slot ) }
									options={ choices }
									onChange={ ( next ) => write( section, slot, next ) }
									__next40pxDefaultSize
									__nextHasNoMarginBottom
								/>
							</FlexItem>
						) ) }
					</Flex>
				</div>
			) ) }
		</BaseControl>
	);
}
