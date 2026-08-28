/**
 * `addable-box` option type — React renderer.
 *
 * A repeater, like `addable-popup`, storing the same thing: an ARRAY OF OBJECTS
 * keyed by the child option ids. Two differences, both of which matter:
 *
 * 1. Its child options live under **`box-options`**, not `popup-options`.
 * 2. `_get_value_from_input()` DOES run each child's validator, where
 *    addable-popup stores what it is handed. That changes nothing on the block
 *    path, where no validator runs at all — but it means the children must emit
 *    the WIRE format for the page-builder path to agree, which is what rendering
 *    them through the shared registry provides.
 *
 * Presentation matches addable-popup: items expand in place rather than opening
 * a modal, because a modal launched from a narrow sidebar covers the preview you
 * are editing against.
 *
 * This wraps that component rather than copying it. A second copy of a repeater
 * is a second thing to keep in step, and they would drift on the first fix that
 * only one of them received.
 */

import AddablePopup from './addable-popup.jsx';

/**
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry.
 * @param {Array}    props.value    Current array of item objects.
 * @param {Function} props.onChange Called with the next array.
 */
export default function AddableBox( { option = {}, value, onChange } ) {
	// Present the schema in the shape AddablePopup reads. The child-options key
	// is the only structural difference between the two types.
	const remapped = {
		...option,
		'popup-options': option[ 'box-options' ] || option[ 'popup-options' ] || {},
	};

	return <AddablePopup option={ remapped } value={ value } onChange={ onChange } />;
}
