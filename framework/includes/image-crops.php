<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }
/**
 * Responsive server-side crops for images shown at a fixed aspect ratio.
 *
 * An element that shows an attachment in a ratio box (the Image element's Aspect
 * Ratio + Crop Position) used to crop with CSS only: object-fit hid the overflow,
 * but every visitor still downloaded the full original. fw_image_crop_renditions()
 * cuts the ratio out of the original around the focal point and saves it at a few
 * widths, so fw_image_tag() can offer a srcset of real, pre-cropped files and each
 * device downloads only what it displays.
 *
 * Files are made on first request and reused after that; they live in
 * uploads/unysonplus/image-crops/ and are named "<attachment id>-<name>-<w>x<h>-<sig>.<ext>".
 * The signature covers the ratio, the focal point and the original's size and
 * modification time, so replacing the image or changing the crop makes new files.
 * Deleting the attachment deletes its crops.
 */

if ( ! function_exists( 'fw_image_parse_ratio' ) ) :
	/**
	 * Width / height from a ratio string: '16/9', '4 / 3.4', '1:1' or '1.5'. 0 when invalid.
	 *
	 * @param string $ratio
	 * @return float
	 */
	function fw_image_parse_ratio( $ratio ) {
		$ratio = trim( (string) $ratio );
		if ( preg_match( '#^([\d.]+)\s*[/:]\s*([\d.]+)$#', $ratio, $m ) ) {
			return (float) $m[2] > 0 ? (float) $m[1] / (float) $m[2] : 0.0;
		}
		return is_numeric( $ratio ) && (float) $ratio > 0 ? (float) $ratio : 0.0;
	}
endif;

if ( ! function_exists( 'fw_image_parse_focal' ) ) :
	/**
	 * Focal point (0..1, 0..1) from an object-position value: keywords
	 * ('left top', 'center bottom') and/or percentages ('50% 20%').
	 *
	 * @param string $position
	 * @return float[] array( x, y )
	 */
	function fw_image_parse_focal( $position ) {
		$map   = array( 'left' => 0.0, 'top' => 0.0, 'center' => 0.5, 'right' => 1.0, 'bottom' => 1.0 );
		$parts = preg_split( '/\s+/', strtolower( trim( (string) $position ) ) );
		$x     = 0.5;
		$y     = 0.5;
		foreach ( array_values( array_filter( $parts, 'strlen' ) ) as $i => $p ) {
			if ( preg_match( '/^(-?[\d.]+)%$/', $p, $m ) ) {
				$v = max( 0.0, min( 1.0, (float) $m[1] / 100 ) );
				if ( 0 === $i ) { $x = $v; } else { $y = $v; }
			} elseif ( 'top' === $p || 'bottom' === $p ) {
				$y = $map[ $p ];
			} elseif ( 'left' === $p || 'right' === $p ) {
				$x = $map[ $p ];
			} elseif ( 'center' === $p && 1 === $i ) {
				$y = 0.5;
			}
		}
		return array( $x, $y );
	}
endif;

if ( ! function_exists( 'fw_image_crop_renditions' ) ) :
	/**
	 * Pre-cropped copies of an attachment at $ratio, keeping $focal in view.
	 *
	 * @param int     $attachment_id
	 * @param float   $ratio  Width / height (see fw_image_parse_ratio()).
	 * @param float[] $focal  array( x, y ), each 0..1.
	 * @return array  width => array( 'url' => string, 'width' => int, 'height' => int ),
	 *                ascending by width; empty when the image can't be cropped (not a
	 *                JPG/PNG/WebP, missing file, no image editor), so callers fall back.
	 */
	function fw_image_crop_renditions( $attachment_id, $ratio, $focal = array( 0.5, 0.5 ) ) {
		$attachment_id = (int) $attachment_id;
		$ratio         = (float) $ratio;
		if ( $attachment_id <= 0 || $ratio <= 0 || ! function_exists( 'fw_upw_uploads_dir' ) ) {
			return array();
		}
		$file = get_attached_file( $attachment_id );
		if ( ! $file || ! preg_match( '/\.(jpe?g|png|webp)$/i', $file, $ext ) || ! is_readable( $file ) ) {
			return array();
		}
		$meta = wp_get_attachment_metadata( $attachment_id );
		$ow   = ! empty( $meta['width'] ) ? (int) $meta['width'] : 0;
		$oh   = ! empty( $meta['height'] ) ? (int) $meta['height'] : 0;
		if ( $ow < 2 || $oh < 2 ) {
			return array();
		}

		// The largest rectangle of the target ratio inside the original, centred on the
		// focal point and clamped to the image edges.
		if ( $ow / $oh > $ratio ) {
			$ch = $oh;
			$cw = (int) floor( $oh * $ratio );
		} else {
			$cw = $ow;
			$ch = (int) floor( $ow / $ratio );
		}
		$fx = isset( $focal[0] ) ? (float) $focal[0] : 0.5;
		$fy = isset( $focal[1] ) ? (float) $focal[1] : 0.5;
		$cx = (int) round( max( 0, min( $ow - $cw, $fx * $ow - $cw / 2 ) ) );
		$cy = (int) round( max( 0, min( $oh - $ch, $fy * $oh - $ch / 2 ) ) );

		/**
		 * Widths to offer for a cropped image. Widths above the crop's own width are
		 * dropped (never upscaled); the crop's full width is always included.
		 *
		 * @param int[] $widths
		 * @param int   $attachment_id
		 */
		// The ladder is deliberately dense at the small/middle end, where most
		// layout slots actually land. `srcset` makes the browser take the first
		// candidate at or above the slot it needs, so the bytes it wastes are the
		// gap to the next rung: with the old 320/480/640/768 ladder a 540px slot
		// pulled the 640 file and threw ~12 KiB away, because the step from 480 to
		// 640 is 1.33x. No step here exceeds ~1.25x below 1024, which halves that
		// worst case. Renditions are written on demand, so a width nothing asks
		// for costs nothing.
		$widths = (array) apply_filters( 'fw_image_crop_widths', array( 320, 400, 480, 560, 640, 768, 900, 1024, 1280, 1600 ), $attachment_id );
		$widths = array_filter( array_map( 'intval', $widths ), function ( $w ) use ( $cw ) { return $w > 0 && $w < $cw * 0.9; } );
		$widths[] = $cw;
		$widths   = array_values( array_unique( $widths ) );
		sort( $widths );

		/**
		 * Encoder quality for generated crops, 1-100.
		 *
		 * Until this existed the crops were written with whatever the image editor
		 * defaulted to, which on a real site produced files at roughly q88 -
		 * 0.167 bytes per pixel, about double what a well-tuned WebP needs. 82 is
		 * WordPress's own default and measured ~12% smaller than the q88 output on
		 * the same pixels, with 80 at ~18% and 75 at ~33%.
		 *
		 * @param int    $quality
		 * @param int    $attachment_id
		 * @param string $file          Absolute path of the source image.
		 */
		$quality = (int) apply_filters( 'fw_image_crop_quality', 82, $attachment_id, $file );
		$quality = max( 1, min( 100, $quality ) );

		$dir  = fw_upw_uploads_dir( 'image-crops' );
		// Quality is part of the signature: without it, changing the setting would
		// silently do nothing, because every crop would still be found on disk
		// under its old name and never re-encoded.
		$sig  = substr( md5( $ratio . '|' . $cx . ',' . $cy . ',' . $cw . ',' . $ch . '|' . filesize( $file ) . '|' . filemtime( $file ) . '|q' . $quality ), 0, 8 );
		$name = $attachment_id . '-' . sanitize_file_name( pathinfo( $file, PATHINFO_FILENAME ) );
		$ext  = strtolower( 'jpeg' === strtolower( $ext[1] ) ? 'jpg' : $ext[1] );

		$out   = array();
		$ready = false;
		foreach ( $widths as $w ) {
			$h    = max( 1, (int) round( $w / $ratio ) );
			$base = $name . '-' . $w . 'x' . $h . '-' . $sig . '.' . $ext;
			$path = $dir['path'] . '/' . $base;
			if ( ! file_exists( $path ) ) {
				if ( ! $ready ) {
					if ( ! wp_mkdir_p( $dir['path'] ) ) {
						return array();
					}
					$ready = true;
				}
				// A fresh editor per size: crop() changes the loaded image in place (and
				// Imagick objects are shared by clone), so every size starts from the original.
				$editor = wp_get_image_editor( $file );
				if ( is_wp_error( $editor ) ) {
					return array();
				}
				$res = $editor->crop( $cx, $cy, $cw, $ch, $w, $h );
				if ( is_wp_error( $res ) ) {
					continue;
				}
				// After crop(), before save(): WP_Image_Editor applies the quality at
				// save time, and setting it earlier is discarded by some editors when
				// the image is re-loaded.
				$editor->set_quality( $quality );
				$saved = $editor->save( $path );
				if ( is_wp_error( $saved ) ) {
					continue;
				}
				/**
				 * Fires after a cropped rendition is written, e.g. to make a WebP copy of it.
				 *
				 * @param string $path          Absolute path of the new file.
				 * @param int    $attachment_id
				 */
				do_action( 'fw_image_rendition_saved', $path, $attachment_id );
			}
			$out[ $w ] = array( 'url' => $dir['url'] . '/' . rawurlencode( $base ), 'width' => $w, 'height' => $h );
		}
		return $out;
	}
endif;

// Remove an attachment's crops together with the attachment.
add_action( 'delete_attachment', function ( $attachment_id ) {
	if ( ! function_exists( 'fw_upw_uploads_dir' ) ) {
		return;
	}
	$dir = fw_upw_uploads_dir( 'image-crops' );
	foreach ( (array) glob( $dir['path'] . '/' . (int) $attachment_id . '-*' ) as $f ) {
		if ( is_file( $f ) ) {
			@unlink( $f );
		}
	}
} );
