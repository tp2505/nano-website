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
 * Field/meta contract (shared with the Core plugin's ACF defs and the seeder):
 *   nano_date        Ymd string            (news)
 *   nano_media_type  'image' | 'video'     (news, initiative)
 *   nano_image       attachment ID         (news, initiative)
 *   nano_video       attachment ID (mp4)   (news, initiative)
 *   nano_poster      attachment ID         (news, initiative)
 *   nano_descriptor  string                (initiative)
 *   nano_order       int                   (initiative)
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
