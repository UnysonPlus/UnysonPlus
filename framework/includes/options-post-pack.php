<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }
/**
 * Packed options transport — the Theme Settings (and any FW_Form) submit past PHP's `max_input_vars`.
 *
 * A lazy-tab settings form injects EVERY tab into the DOM on submit, so the POST carries thousands of
 * `fw_options[...]` fields. On a host with the default `max_input_vars = 1000`, PHP silently drops the
 * fields past the limit — the tabs rendered last (Footer, Miscellaneous) came back EMPTY on every save,
 * their values reset to defaults, while a localhost with a raised limit never showed it.
 *
 * The client (fw-form-helpers.js / backend-options.js) packs all `fw_options[...]` fields into ONE
 * `fw_options_json` field — a JSON list of [name, value] pairs, in form order — which counts as a single
 * input variable. Here it is unpacked back into `$_POST['fw_options']` (slashed like a normal POST, so
 * every reader — FW_Request::POST, fw_get_options_values_from_input — sees the usual shape) before any
 * save handler runs. Only `post_max_size` bounds it now.
 */

/**
 * Nest a list of [name, value] pairs (`fw_options[a][b][]`) into an array, form-encoding semantics.
 *
 * @param array $pairs
 * @return array
 */
function fw_upw_unpack_option_pairs( array $pairs ) {
	$out = array();
	foreach ( $pairs as $pair ) {
		if ( ! is_array( $pair ) || count( $pair ) < 2 ) { continue; }
		$name = (string) $pair[0]; $value = $pair[1];
		if ( ! is_scalar( $value ) && null !== $value ) { continue; }
		if ( ! preg_match( '/^([^\[]+)((?:\[[^\]]*\])*)$/', $name, $m ) ) { continue; }
		$keys = array( $m[1] );
		if ( '' !== $m[2] && preg_match_all( '/\[([^\]]*)\]/', $m[2], $km ) ) { foreach ( $km[1] as $k ) { $keys[] = $k; } }
		$ref = &$out;
		$last = count( $keys ) - 1;
		foreach ( $keys as $i => $k ) {
			if ( $i === $last ) {
				if ( '' === $k ) { if ( ! is_array( $ref ) ) { $ref = array(); } $ref[] = (string) $value; }
				else { if ( ! is_array( $ref ) ) { $ref = array(); } $ref[ $k ] = (string) $value; }
				break;
			}
			if ( '' === $k ) { // an anonymous level (`[][x]`) — PHP appends
				if ( ! is_array( $ref ) ) { $ref = array(); }
				$ref[] = array(); end( $ref ); $k = key( $ref );
			}
			if ( ! isset( $ref[ $k ] ) || ! is_array( $ref[ $k ] ) ) { $ref[ $k ] = array(); }
			$ref = &$ref[ $k ];
		}
		unset( $ref );
	}
	return $out;
}

/** Unpack `fw_options_json` into `$_POST` (once, early — before admin-ajax / admin-post save handlers). */
function fw_upw_unpack_posted_options() {
	if ( empty( $_POST['fw_options_json'] ) || ! is_string( $_POST['fw_options_json'] ) ) { return; }
	$raw = wp_unslash( $_POST['fw_options_json'] );
	$pairs = json_decode( $raw, true );
	unset( $_POST['fw_options_json'], $_REQUEST['fw_options_json'] );
	if ( ! is_array( $pairs ) ) { return; }
	$nested = fw_upw_unpack_option_pairs( $pairs );
	foreach ( $nested as $top => $vals ) {
		if ( ! preg_match( '/^[a-z0-9_-]+$/i', (string) $top ) ) { continue; }
		// the packed transport is authoritative for these top-level names (the client sent them ONLY packed)
		$_POST[ $top ]    = wp_slash( $vals );
		$_REQUEST[ $top ] = $_POST[ $top ];
	}
}
add_action( 'init', 'fw_upw_unpack_posted_options', 0 );
