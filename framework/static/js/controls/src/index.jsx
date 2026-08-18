/**
 * fw.controls — the React renderer for the Unyson+ option schema.
 *
 * Entry point. Registers the built-in controls and publishes the public API on
 * `window.fw.controls`, alongside the existing `fw.*` admin globals.
 *
 * ## The rule this file exists to enforce
 *
 * There is no `import React` anywhere in this bundle, and there never should be.
 * Components are authored in JSX, but the build compiles JSX to
 * `wp.element.createElement` — WordPress's own React, already on every wp-admin
 * page. That is what guarantees a second copy of React can never ship, and it is
 * what makes these components usable inside a Gutenberg block inspector.
 *
 * @see framework/src/Admin/Controls/Registry.php — the PHP side that enqueues this.
 */

import { register, get, has, types } from './registry.js';
import Text from './controls/text.jsx';
import Switch from './controls/switch.jsx';
import Select from './controls/select.jsx';
import Upload from './controls/upload.jsx';
import Textarea from './controls/textarea.jsx';
import Radio from './controls/radio.jsx';
import Checkbox from './controls/checkbox.jsx';
import ColorPicker from './controls/color-picker.jsx';
import Slider from './controls/slider.jsx';
import UnitInput from './controls/unit-input.jsx';
import MultiSelect from './controls/multi-select.jsx';
import ImagePicker from './controls/image-picker.jsx';
import Spacing from './controls/spacing.jsx';
import Typography from './controls/typography.jsx';
import Icon from './controls/icon.jsx';
import PredefinedColorsCompact from './controls/predefined-colors-compact.jsx';
import WpEditor from './controls/wp-editor.jsx';
import BorderStylePicker from './controls/border-style-picker.jsx';

const { Notice } = wp.components;

register( 'text', Text );
register( 'switch', Switch );
register( 'select', Select );
register( 'short-select', Select );
register( 'upload', Upload );
register( 'textarea', Textarea );
register( 'radio', Radio );
register( 'checkbox', Checkbox );
register( 'color-picker', ColorPicker );
register( 'slider', Slider );
register( 'short-slider', Slider );
register( 'unit-input', UnitInput );
register( 'multi-select', MultiSelect );
register( 'image-picker', ImagePicker );
register( 'spacing', Spacing );
register( 'typography', Typography );
register( 'typography-v2', Typography );
register( 'icon', Icon );
register( 'predefined-colors-color-picker-compact', PredefinedColorsCompact );
register( 'wp-editor', WpEditor );
register( 'border-style-picker', BorderStylePicker );

/**
 * Shown when an option type has no React renderer yet.
 *
 * Mirrors the intent of FW_Option_Type_Undefined: say so plainly rather than
 * failing silently or rendering nothing. Most option types are PHP-only today,
 * and that is expected — this is not an error state, it is a coverage gap.
 *
 * @param {Object} props
 * @param {string} props.type The unregistered option type.
 */
function Undefined( { type } ) {
	return (
		<Notice status="warning" isDismissible={ false }>
			{ `No React control for option type "${ type }" yet — edit this option in the page builder.` }
		</Notice>
	);
}

/**
 * Render one option from its schema.
 *
 * This is the bridge: hand it the same array an `options.php` file declares
 * (JSON-encoded into the page) and it picks the right control.
 *
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry — must carry `type`.
 * @param {*}        props.value    Current value.
 * @param {Function} props.onChange Called with the next value.
 */
function Option( { option, value, onChange } ) {
	const type = option && option.type;

	if ( ! type ) {
		return null;
	}

	const Control = get( type );

	if ( ! Control ) {
		return <Undefined type={ type } />;
	}

	return <Control option={ option } value={ value } onChange={ onChange } />;
}

/**
 * Render a whole options object, in declaration order.
 *
 * @param {Object}   props
 * @param {Object}   props.options  Map of option id → option schema.
 * @param {Object}   props.values   Map of option id → current value.
 * @param {Function} props.onChange Called with (id, nextValue).
 */
function Options( { options = {}, values = {}, onChange } ) {
	return (
		<>
			{ Object.keys( options ).map( ( id ) => (
				<Option
					key={ id }
					option={ options[ id ] }
					value={ values[ id ] }
					onChange={ ( next ) => onChange( id, next ) }
				/>
			) ) }
		</>
	);
}

window.fw = window.fw || {};
window.fw.controls = { register, get, has, types, Option, Options };
