<?php
/**
 * Field-access isolation layer.
 *
 * This is the ONE place that knows whether custom-field data comes from ACF or
 * from native post meta. Templates, patterns, and the dynamic blocks call
 * nano_field() / nano_media() / nano_render_media() and never touch get_field()
 * or get_post_meta() directly. To drop ACF (e.g. if CampusPress disallows it),
 * the only change needed is here — register native meta boxes and the rest of
 * the site keeps working because the meta keys are identical.
 *
 * Field/meta contract (shared with the Core plugin's ACF defs and the seeder).
 * ONE media rule: every media slot is a still image or a short looping clip;
 * the event top slot additionally takes a Vimeo URL for long-form video:
 *   nano_date        Ymd string                     (news, event)
 *   nano_media_type  'image' | 'video' | 'vimeo'    ('vimeo' on event only)
 *   nano_image       attachment ID
 *   nano_video       attachment ID (mp4/webm)
 *   nano_poster      attachment ID (clip poster)
 *   nano_vimeo       Vimeo URL or ID                (event)
 *   nano_descriptor  string                         (initiative)
 * (Display order is not meta: person / initiative / facility use native
 * menu_order — the Page Attributes → Order box.)
 *
 * @package Nano\ImaginationHub
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'nano_field' ) ) {
	/**
	 * Read a custom field. Prefers ACF (with its formatting) and falls back to
	 * raw post meta, so seeded values and a no-ACF environment both resolve.
	 *
	 * @param string   $name    Field / meta key.
	 * @param int|null $post_id Post ID (defaults to current post).
	 * @return mixed
	 */
	function nano_field( $name, $post_id = null ) {
		$post_id = $post_id ? (int) $post_id : get_the_ID();
		if ( ! $post_id ) {
			return null;
		}

		if ( function_exists( 'get_field' ) ) {
			$value = get_field( $name, $post_id );
			if ( null !== $value && false !== $value && '' !== $value ) {
				return $value;
			}
		}

		$meta = get_post_meta( $post_id, $name, true );
		return ( '' === $meta ) ? null : $meta;
	}
}

if ( ! function_exists( 'nano_vimeo_parse' ) ) {
	/**
	 * Parse a Vimeo URL or bare ID. Accepts "123456789",
	 * "https://vimeo.com/123456789", unlisted links ("…/123456789/abcdef" or
	 * ?h= URLs) and player.vimeo.com URLs.
	 *
	 * @param string $raw Editor-entered value.
	 * @return array{id:string,hash:string}|null Null if it isn't a Vimeo video.
	 */
	function nano_vimeo_parse( $raw ) {
		$raw  = trim( (string) $raw );
		$id   = '';
		$hash = '';
		if ( preg_match( '/^\d+$/', $raw ) ) {
			$id = $raw;
		} elseif ( preg_match( '#vimeo\.com/(?:video/)?(\d+)(?:/([0-9a-zA-Z]+))?#', $raw, $m ) ) {
			$id   = $m[1];
			$hash = isset( $m[2] ) ? $m[2] : '';
		}
		if ( '' === $id ) {
			return null;
		}
		if ( '' === $hash && preg_match( '/[?&]h=([0-9a-zA-Z]+)/', $raw, $m ) ) {
			$hash = $m[1]; // Unlisted-video privacy hash.
		}
		return array(
			'id'   => $id,
			'hash' => $hash,
		);
	}
}

if ( ! function_exists( 'nano_vimeo_player_url' ) ) {
	/**
	 * Embed URL for a WATCHABLE long-form Vimeo video (the event top slot):
	 * native playback controls stay (a lecture needs play/seek/audio), but the
	 * Vimeo chrome — title, byline, portrait — is hidden and tracking is off.
	 * The chrome-LESS background variant (hero) is nano_vimeo_embed_url() in
	 * inc/customizer.php.
	 *
	 * @param string $raw Editor-entered URL or ID.
	 * @return string Embed URL, or '' if it doesn't look like a Vimeo video.
	 */
	function nano_vimeo_player_url( $raw ) {
		$vimeo = nano_vimeo_parse( $raw );
		if ( ! $vimeo ) {
			return '';
		}
		$args = array(
			'title'    => '0',
			'byline'   => '0',
			'portrait' => '0',
			'dnt'      => '1',
		);
		if ( '' !== $vimeo['hash'] ) {
			$args['h'] = $vimeo['hash'];
		}
		return add_query_arg( $args, 'https://player.vimeo.com/video/' . $vimeo['id'] );
	}
}

if ( ! function_exists( 'nano_media' ) ) {
	/**
	 * Resolve a post's media tile to a normalized shape, regardless of whether
	 * it is an image or a looping video.
	 *
	 * @param int|null $post_id Post ID.
	 * @return array{type:string,url:string,poster:string,alt:string,width:int,height:int,image_id:int}
	 */
	function nano_media( $post_id = null ) {
		$post_id = $post_id ? (int) $post_id : get_the_ID();
		$empty   = array(
			'type'     => '',
			'url'      => '',
			'poster'   => '',
			'alt'      => '',
			'width'    => 0,
			'height'   => 0,
			'image_id' => 0,
		);
		if ( ! $post_id ) {
			return $empty;
		}

		$type = nano_field( 'nano_media_type', $post_id );

		if ( 'vimeo' === $type ) {
			$raw = (string) nano_field( 'nano_vimeo', $post_id );
			if ( ! function_exists( 'nano_vimeo_parse' ) || ! nano_vimeo_parse( $raw ) ) {
				return $empty;
			}
			return array(
				'type'     => 'vimeo',
				'url'      => $raw,
				'poster'   => '',
				'alt'      => get_the_title( $post_id ),
				'width'    => 0,
				'height'   => 0,
				'image_id' => 0,
			);
		}

		if ( 'video' === $type ) {
			$video_id  = (int) nano_field( 'nano_video', $post_id );
			$poster_id = (int) nano_field( 'nano_poster', $post_id );
			$url       = $video_id ? wp_get_attachment_url( $video_id ) : '';
			if ( ! $url ) {
				return $empty;
			}
			return array(
				'type'     => 'video',
				'url'      => $url,
				'poster'   => $poster_id ? ( wp_get_attachment_image_url( $poster_id, 'large' ) ?: '' ) : '',
				'alt'      => get_the_title( $post_id ),
				'width'    => 0,
				'height'   => 0,
				'image_id' => 0,
			);
		}

		// Default: image. Fall back to the featured image if no field is set.
		$image_id = (int) nano_field( 'nano_image', $post_id );
		if ( ! $image_id ) {
			$image_id = (int) get_post_thumbnail_id( $post_id );
		}
		if ( ! $image_id ) {
			return $empty;
		}

		$meta = wp_get_attachment_metadata( $image_id );
		return array(
			'type'     => 'image',
			'url'      => wp_get_attachment_image_url( $image_id, 'large' ) ?: '',
			'poster'   => '',
			'alt'      => get_post_meta( $image_id, '_wp_attachment_image_alt', true ) ?: get_the_title( $post_id ),
			'width'    => isset( $meta['width'] ) ? (int) $meta['width'] : 0,
			'height'   => isset( $meta['height'] ) ? (int) $meta['height'] : 0,
			'image_id' => $image_id,
		);
	}
}

if ( ! function_exists( 'nano_render_media' ) ) {
	/**
	 * Render a media tile as either a responsive <img> or a muted, looping,
	 * inline-playing <video>. Used by both dynamic blocks and patterns so the
	 * image/video markup lives in exactly one place.
	 *
	 * Videos get playsinline (no iOS fullscreen hijack) and data-nano-video so
	 * the front-end script can lazy-play them on scroll. preload="metadata"
	 * keeps the page light.
	 *
	 * @param array $media Result of nano_media().
	 * @param array $args  Optional: sizes, class, eager (bool, hero only).
	 * @return string HTML.
	 */
	function nano_render_media( $media, $args = array() ) {
		$args  = wp_parse_args(
			$args,
			array(
				'sizes' => '100vw',
				'class' => '',
				'eager' => false,
			)
		);
		$class = trim( 'nano-media ' . $args['class'] );

		if ( empty( $media['type'] ) || empty( $media['url'] ) ) {
			// Graceful placeholder so the layout still holds at the right ratio.
			return sprintf( '<span class="%s nano-media--empty" aria-hidden="true"></span>', esc_attr( $class ) );
		}

		if ( 'vimeo' === $media['type'] ) {
			$src = function_exists( 'nano_vimeo_player_url' ) ? nano_vimeo_player_url( $media['url'] ) : '';
			if ( ! $src ) {
				return sprintf( '<span class="%s nano-media--empty" aria-hidden="true"></span>', esc_attr( $class ) );
			}
			return sprintf(
				'<div class="%1$s nano-media--vimeo"><iframe src="%2$s" title="%3$s" loading="lazy" allow="fullscreen; picture-in-picture" allowfullscreen></iframe></div>',
				esc_attr( $class ),
				esc_url( $src ),
				esc_attr( $media['alt'] )
			);
		}

		if ( 'video' === $media['type'] ) {
			$poster = $media['poster'] ? sprintf( ' poster="%s"', esc_url( $media['poster'] ) ) : '';
			return sprintf(
				'<video class="%1$s nano-media--video" %2$s muted loop playsinline preload="metadata" data-nano-video="%3$s" aria-label="%4$s"><source src="%5$s" type="video/mp4"></video>',
				esc_attr( $class ),
				$poster,
				$args['eager'] ? 'eager' : 'lazy',
				esc_attr( $media['alt'] ),
				esc_url( $media['url'] )
			);
		}

		if ( $media['image_id'] ) {
			return wp_get_attachment_image(
				$media['image_id'],
				'large',
				false,
				array(
					'class'   => $class . ' nano-media--image',
					'sizes'   => $args['sizes'],
					'loading' => $args['eager'] ? 'eager' : 'lazy',
					'alt'     => $media['alt'],
				)
			);
		}

		return sprintf(
			'<img class="%1$s nano-media--image" src="%2$s" alt="%3$s" loading="%4$s" decoding="async" />',
			esc_attr( $class ),
			esc_url( $media['url'] ),
			esc_attr( $media['alt'] ),
			$args['eager'] ? 'eager' : 'lazy'
		);
	}
}
