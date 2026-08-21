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
import Number_ from './controls/number.jsx';
import AddablePopup from './controls/addable-popup.jsx';
import MultiPicker from './controls/multi-picker.jsx';
import ImageStylePicker from './controls/image-style-picker.jsx';
import Checkboxes from './controls/checkboxes.jsx';
import ButtonStylePicker from './controls/button-style-picker.jsx';
import ButtonHoverAnimation from './controls/button-hover-animation.jsx';
import ColumnSplit from './controls/column-split.jsx';
import CodeEditor from './controls/code-editor.jsx';
import DatetimePicker from './controls/datetime-picker.jsx';
import MultiUpload from './controls/multi-upload.jsx';
import AddableBox from './controls/addable-box.jsx';
import DatePicker from './controls/date-picker.jsx';
import MultiInline from './controls/multi-inline.jsx';
import GmapKey from './controls/gmap-key.jsx';
import SplitSlider from './controls/split-slider.jsx';
import Popover from './controls/popover.jsx';
import BoxShadow from './controls/box-shadow.jsx';
import Responsive from './controls/responsive.jsx';
import GradientV2 from './controls/gradient-v2.jsx';
import RgbaColorPicker from './controls/rgba-color-picker.jsx';
import BackgroundPro from './controls/background-pro.jsx';
import Table from './controls/table.jsx';
import TableStylePicker from './controls/table-style-picker.jsx';
import FormBuilder from './controls/form-builder.jsx';
import Mailer from './controls/mailer.jsx';
import NullControl from './controls/null-control.jsx';

const { Notice } = wp.components;

register( 'text', Text );
register( 'switch', Switch );
register( 'select', Select );
register( 'short-select', Select );
register( 'number', Number_ );
// `short-text` and `medium-text` subclass FW_Option_Type_Text and change only the
// input's WIDTH. Width is meaningless in a block sidebar, where every control is
// the panel's width, so they share the text control rather than get a copy of it.
register( 'short-text', Text );
register( 'medium-text', Text );
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
// `addable-popup-full` is the same option type with a wider modal in the page
// builder. Width is the only difference, and it has none in a sidebar.
register( 'addable-popup', AddablePopup );
register( 'addable-popup-full', AddablePopup );
register( 'multi-picker', MultiPicker );
register( 'image-style-picker', ImageStylePicker );
register( 'checkboxes', Checkboxes );
register( 'button-style-picker', ButtonStylePicker );
register( 'button-hover-animation', ButtonHoverAnimation );
register( 'column-split', ColumnSplit );
register( 'code-editor', CodeEditor );
register( 'datetime-picker', DatetimePicker );
register( 'multi-upload', MultiUpload );
register( 'addable-box', AddableBox );
register( 'date-picker', DatePicker );
register( 'multi-inline', MultiInline );
register( 'fw-multi-inline', MultiInline );
register( 'gmap-key', GmapKey );
register( 'split-slider', SplitSlider );
register( 'popover', Popover );
register( 'box-shadow', BoxShadow );
register( 'responsive', Responsive );
register( 'gradient-v2', GradientV2 );
// NOT `gradient`: the legacy type stores { primary, secondary }, a different
// shape entirely. Sharing this control would write { type, angle, stops } into
// an option that cannot read it — the same trap the colour pickers set, where
// the tempting consistency is with the control you wrote last rather than with
// the PHP you are pairing with.
register( 'rgba-color-picker', RgbaColorPicker );
register( 'background-pro', BackgroundPro );
register( 'table', Table );
register( 'table-style-picker', TableStylePicker );
register( 'form-builder', FormBuilder );
register( 'mailer', Mailer );
// Value-less page-builder visual aids: render nothing rather than the registry's
// "no control" warning, which is for fields a user needs and cannot reach.
register( 'gallery-3d-preview', NullControl );
register( 'html', NullControl );
register( 'html-full', NullControl );
register( 'html-fixed', NullControl );
// `svg-code` stores a plain SVG markup string; the code control is exactly the
// right shape for it, down to the monospace font and disabled autocorrect.
register( 'svg-code', CodeEditor );

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
