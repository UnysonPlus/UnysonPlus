<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * SUGGESTION QUEUE — how another extension offers the user a job for the assistant.
 *
 * The alternative designs are both worse. An extension that could make the assistant *speak* turns the
 * panel into a popup, which is the pattern this is meant to avoid. An extension that shows its own modal
 * competes with every other notice on the screen and gets scrolled past, which is exactly how the Site
 * Converter's beta warning failed. A queued suggestion instead appears as one more starter chip in a panel
 * the user opens deliberately: an offer, not an interruption.
 *
 * THE QUEUE IS AN OPTION, AND IT IS WRITTEN WHETHER OR NOT THIS EXTENSION IS ACTIVE. That is the whole
 * point of keeping it here rather than behind a function the suggester has to guard on: the AI Assistant
 * ships INACTIVE, so the common case is a converter finishing while nothing is listening. If the queue only
 * existed when the assistant did, a user would click "Enable the AI Assistant", the page would reload with
 * it on, and the panel would be empty — they did what was asked and got nothing, which is worse than never
 * having offered. Writing to a well-known option means activation order stops mattering: the suggestion
 * waits, and the assistant picks it up the moment it comes online.
 *
 * Suggestions are per user, because a job offered to the person who ran the conversion is not a job the
 * next editor needs to see, and a stale chip in someone else's panel is noise.
 *
 * WHY THIS LIVES IN THE FRAMEWORK and not in the AI Assistant extension: an extension's code is not loaded
 * while that extension is inactive, so a helper defined there would be missing in exactly the case it has
 * to work in -- the assistant switched off, a conversion finishing, a suggestion that must survive until
 * the user turns it on. Putting the queue in core means the writer (any extension) and the reader (the
 * assistant) never have to know whether the other exists.
 */
class FW_AI_Suggestions {

	const OPTION   = 'upw_ai_suggestions';
	/** Which suggestion ids this user's launcher has already drawn attention to. */
	const SEEN_META = 'upw_ai_suggestions_seen';
	/** A suggestion nobody acted on is stale rather than permanent. */
	const MAX_AGE  = WEEK_IN_SECONDS;
	const MAX_KEEP = 12;

	/**
	 * Queue a suggestion (last write for an id wins, so re-running a conversion refreshes rather than
	 * duplicates).
	 *
	 * @param array $s id, title, prompt, source, user_id (defaults to the current user)
	 * @return bool
	 */
	public static function add( array $s ) {
		$id    = isset( $s['id'] ) ? sanitize_key( (string) $s['id'] ) : '';
		$title = isset( $s['title'] ) ? trim( wp_strip_all_tags( (string) $s['title'] ) ) : '';
		$promt = isset( $s['prompt'] ) ? trim( (string) $s['prompt'] ) : '';
		if ( '' === $id || '' === $title || '' === $promt ) { return false; }

		$uid = isset( $s['user_id'] ) ? (int) $s['user_id'] : get_current_user_id();
		if ( ! $uid ) { return false; }

		$all = self::all_raw();
		$all[ $uid . ':' . $id ] = array(
			'id'      => $id,
			'user_id' => $uid,
			'title'   => $title,
			'prompt'  => $promt,
			'source'  => isset( $s['source'] ) ? sanitize_key( (string) $s['source'] ) : '',
			'at'      => time(),
		);
		return self::save( $all );
	}

	/** This user's live suggestions, newest first. */
	public static function for_user( $uid = 0 ) {
		$uid = $uid ? (int) $uid : get_current_user_id();
		$out = array();
		foreach ( self::all_raw() as $row ) {
			if ( (int) $row['user_id'] !== $uid ) { continue; }
			$out[] = $row;
		}
		usort( $out, function ( $a, $b ) { return $b['at'] - $a['at']; } );
		return $out;
	}

	/** Ids this user has not yet been shown — what the launcher animates for, at most once each. */
	public static function unseen_for_user( $uid = 0 ) {
		$uid  = $uid ? (int) $uid : get_current_user_id();
		$seen = (array) get_user_meta( $uid, self::SEEN_META, true );
		$out  = array();
		foreach ( self::for_user( $uid ) as $row ) {
			if ( ! in_array( $row['id'], $seen, true ) ) { $out[] = $row['id']; }
		}
		return $out;
	}

	/**
	 * Record that the launcher has drawn attention to these ids.
	 *
	 * Without this the button would pulse on every admin page load for as long as the suggestion lives,
	 * which is the nag the queue exists to avoid.
	 */
	public static function mark_seen( array $ids, $uid = 0 ) {
		$uid = $uid ? (int) $uid : get_current_user_id();
		if ( ! $uid ) { return; }
		$seen = array_values( array_unique( array_merge( (array) get_user_meta( $uid, self::SEEN_META, true ), $ids ) ) );
		update_user_meta( $uid, self::SEEN_META, array_slice( $seen, -50 ) );
	}

	/** Drop one suggestion (it was run, or dismissed). */
	public static function remove( $id, $uid = 0 ) {
		$uid = $uid ? (int) $uid : get_current_user_id();
		$all = self::all_raw();
		unset( $all[ $uid . ':' . sanitize_key( (string) $id ) ] );
		return self::save( $all );
	}

	/* ------------------------------------------------------------------ internals */

	private static function all_raw() {
		$v = get_option( self::OPTION, array() );
		if ( ! is_array( $v ) ) { return array(); }
		$now  = time();
		$keep = array();
		foreach ( $v as $k => $row ) {
			if ( ! is_array( $row ) || empty( $row['id'] ) || empty( $row['user_id'] ) ) { continue; }
			if ( ( $now - (int) ( $row['at'] ?? 0 ) ) > self::MAX_AGE ) { continue; }
			$keep[ $k ] = $row;
		}
		return $keep;
	}

	private static function save( array $all ) {
		if ( count( $all ) > self::MAX_KEEP ) {
			uasort( $all, function ( $a, $b ) { return $b['at'] - $a['at']; } );
			$all = array_slice( $all, 0, self::MAX_KEEP, true );
		}
		// Not autoloaded: read on the admin screens that show the panel, not on every front-end request.
		return update_option( self::OPTION, $all, false );
	}
}

if ( ! function_exists( 'fw_ai_suggest' ) ) {
	/**
	 * Offer the user a job for the AI Assistant.
	 *
	 * Safe to call when the assistant is inactive — see the class docblock: the queue is an option, so the
	 * suggestion simply waits. Callers therefore do NOT need to guard on the assistant being present, and
	 * should not: guarding is what loses the suggestion in the one case that matters most.
	 *
	 *     fw_ai_suggest( array(
	 *         'id'     => 'site-converter/finish-conversion',
	 *         'title'  => 'Finish the conversion — 3 things to review',
	 *         'prompt' => '…pre-filled with the findings…',
	 *         'source' => 'site-converter',
	 *     ) );
	 */
	function fw_ai_suggest( array $suggestion ) {
		return FW_AI_Suggestions::add( $suggestion );
	}
}
