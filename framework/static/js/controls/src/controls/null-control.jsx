/**
 * A control that renders nothing, for VALUE-LESS option types.
 *
 * Some option types are not fields at all — they are visual aids that live in
 * the page builder's options modal. `gallery-3d-preview` is the example: its
 * `_get_value_from_input()` returns `null`, it stores nothing, and its whole
 * behaviour is client-side JS that reads its sibling options out of the modal's
 * DOM and drives a live scene.
 *
 * None of that can work in a block sidebar — there is no modal, and the DOM it
 * reads does not exist. More to the point, none of it is NEEDED there: the block
 * canvas already previews the real element, rendered by the real PHP. A second,
 * approximate preview beside it would only be one more thing that can disagree.
 *
 * So these render nothing at all rather than the registry's "no React control"
 * warning. That warning is right for a field the user needs and cannot reach; it
 * is noise for something that was never a field.
 */

export default function NullControl() {
	return null;
}
