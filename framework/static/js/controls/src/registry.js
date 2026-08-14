/**
 * The control registry.
 *
 * Maps an Unyson+ option `type` string to a React component that can render it.
 * This is the second renderer for the option schema — the first being the PHP
 * `FW_Option_Type::_render()` path, which is unchanged and stays authoritative
 * for the page builder and the options pages.
 *
 * A type may be absent here and still work everywhere PHP renders it. Absence
 * only means "this option cannot appear in a React surface yet".
 */

/** @type {Map<string, Function>} */
const controls = new Map();

/**
 * Register a React component for an option type.
 *
 * @param {string}   type      The option `type` string, e.g. 'text'.
 * @param {Function} component A component receiving { option, value, onChange }.
 */
export function register( type, component ) {
	if ( typeof type !== 'string' || ! type ) {
		throw new Error( 'fw.controls.register: type must be a non-empty string' );
	}

	if ( typeof component !== 'function' ) {
		throw new Error( `fw.controls.register: component for "${ type }" must be a function` );
	}

	controls.set( type, component );
}

/**
 * Look up the component for an option type.
 *
 * @param {string} type Option type string.
 * @return {Function|null} The component, or null when the type has no React renderer.
 */
export function get( type ) {
	return controls.get( type ) || null;
}

/**
 * Whether an option type has a React renderer.
 *
 * @param {string} type Option type string.
 * @return {boolean} True when registered.
 */
export function has( type ) {
	return controls.has( type );
}

/**
 * Every registered option type.
 *
 * @return {string[]} Sorted list of type strings.
 */
export function types() {
	return Array.from( controls.keys() ).sort();
}
