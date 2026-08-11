<?php
/**
 * Shared card renderer — one <li class="nano-card"> for a News or Event post.
 * Used by the homepage slider, the News archive, the Initiative pages (scaled
 * variant) and the Related section, so they all share one component.
 *
 * @package Nano\ImaginationHubCore
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'nano_gallery_poster_id' ) ) {
	/**
	 * Poster still for a video gallery item: the editor-chosen attachment field
	 * (nano_poster on the attachment, set in the Gallery sidebar / media modal),
	 * else the video's own thumbnail (WP extracts embedded cover art on upload,
	 * and Edit Media lets an editor set one as the featured image).
	 *
	 * @param int $att_id Video attachment ID.
	 * @return int Poster attachment ID (0 if none).
	 */
	function nano_gallery_poster_id( $att_id ) {
		$att_id = (int) $att_id;
		$poster = function_exists( 'nano_field' )
			? (int) nano_field( 'nano_poster', $att_id )
			: (int) get_post_meta( $att_id, 'nano_poster', true );
		return $poster ? $poster : (int) get_post_thumbnail_id( $att_id );
	}
}

if ( ! function_exists( 'nano_gallery_rows' ) ) {
	/**
	 * Event gallery rows as array( array( 'media' => id, 'poster' => id,
	 * 'caption' => str ), … ).
	 *
	 * Reads the raw nano_gallery meta rather than get_field(): ACF Pro's Gallery
	 * field stores an array of attachment IDs there, while the pre-Pro repeater
	 * stored a row count at the same key (with nano_gallery_{i}_* sub-rows), and
	 * the raw value is what disambiguates the two — get_field() would format a
	 * leftover count as a one-item gallery. Captions and video posters live on
	 * the attachment itself (caption field / nano_poster), so per-item data
	 * survives without repeater sub-fields.
	 *
	 * @param int $post_id Event ID.
	 * @return array
	 */
	function nano_gallery_rows( $post_id ) {
		$post_id = (int) $post_id;
		$raw     = get_post_meta( $post_id, 'nano_gallery', true );
		$rows    = array();

		// ACF Pro Gallery format: an ordered array of attachment IDs.
		if ( is_array( $raw ) ) {
			foreach ( $raw as $media ) {
				$media = (int) $media;
				if ( ! $media ) {
					continue;
				}
				$is_video = wp_attachment_is( 'video', $media );
				$rows[]   = array(
					'media'   => $media,
					'poster'  => $is_video ? nano_gallery_poster_id( $media ) : 0,
					'caption' => (string) wp_get_attachment_caption( $media ),
				);
			}
			return $rows;
		}

		// Legacy repeater layout (pre-Pro seeds): count + indexed sub-fields.
		$count = (int) $raw;
		for ( $i = 0; $i < $count; $i++ ) {
			$media = (int) get_post_meta( $post_id, "nano_gallery_{$i}_media", true );
			if ( ! $media ) {
				continue;
			}
			$rows[] = array(
				'media'   => $media,
				'poster'  => (int) get_post_meta( $post_id, "nano_gallery_{$i}_poster", true ),
				'caption' => (string) get_post_meta( $post_id, "nano_gallery_{$i}_caption", true ),
			);
		}
		return $rows;
	}
}

if ( ! function_exists( 'nano_card_thumb_id' ) ) {
	/**
	 * Best thumbnail attachment ID for a post: featured image, else the News
	 * media (image or video poster), else the first Event gallery item.
	 *
	 * @param int $post_id Post ID.
	 * @return int Attachment ID (0 if none).
	 */
	function nano_card_thumb_id( $post_id ) {
		$tid = (int) get_post_thumbnail_id( $post_id );
		if ( $tid ) {
			return $tid;
		}
		if ( function_exists( 'nano_media' ) ) {
			$media = nano_media( $post_id );
			if ( ! empty( $media['image_id'] ) ) {
				return (int) $media['image_id'];
			}
		}
		$poster = function_exists( 'nano_field' ) ? (int) nano_field( 'nano_poster', $post_id ) : 0;
		if ( $poster ) {
			return $poster;
		}
		$rows = nano_gallery_rows( $post_id );
		if ( $rows ) {
			$first = $rows[0];
			if ( wp_attachment_is( 'video', $first['media'] ) ) {
				if ( $first['poster'] ) {
					return $first['poster'];
				}
			} else {
				return $first['media'];
			}
		}
		return 0;
	}
}

if ( ! function_exists( 'nano_render_card' ) ) {
	/**
	 * Echo one card for a News or Event post.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $args    Optional: show_type (bool, add a News/Event label),
	 *                       static (bool, always a poster still — no autoplay),
	 *                       data (array of data-* attributes for the <li>).
	 */
	function nano_render_card( $post_id, $args = array() ) {
		$post_id   = (int) $post_id;
		$type      = get_post_type( $post_id );
		$link      = get_permalink( $post_id );
		$show_type = ! empty( $args['show_type'] );
		$static    = ! empty( $args['static'] );
		if ( 'event' === $type ) {
			$type_label = __( 'Event', 'nano' );
		} elseif ( 'class' === $type ) {
			$type_label = __( 'Class', 'nano' );
		} else {
			$type_label = __( 'News', 'nano' );
		}

		$data_attr = '';
		if ( isset( $args['data'] ) && is_array( $args['data'] ) ) {
			foreach ( $args['data'] as $k => $v ) {
				$data_attr .= ' data-' . esc_attr( $k ) . '="' . esc_attr( $v ) . '"';
			}
		}

		// Category name drives the corner bracket. News → its category term;
		// Event → the Initiative it belongs to (same four names); Class → belongs
		// to Pedagogies, and shows its term in place of a category.
		$instructor = '';
		if ( 'class' === $type ) {
			$category      = function_exists( 'nano_field' ) ? (string) nano_field( 'nano_term', $post_id ) : '';
			$cat_slug      = 'pedagogies'; // Classes ride under Pedagogies — same bracket family.
			$cat_label     = $category;
			$instructor_id = function_exists( 'nano_field' ) ? (int) nano_field( 'nano_instructor', $post_id ) : 0;
			$instructor    = $instructor_id ? get_the_title( $instructor_id ) : '';
		} else {
			if ( 'event' === $type ) {
				$init_id  = function_exists( 'nano_field' ) ? (int) nano_field( 'nano_initiative', $post_id ) : 0;
				$category = $init_id ? get_the_title( $init_id ) : '';
			} else {
				$terms    = get_the_terms( $post_id, 'news_category' );
				$category = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : '';
			}
			$cat_slug = $category ? sanitize_title( $category ) : '';

			// News with no Initiative is umbrella-level "General": its own bracket
			// (the Resonances bracket mirrored) and a plain "News" label shown in
			// place of an initiative name.
			if ( 'event' !== $type && ( '' === $cat_slug || 'general' === $cat_slug ) ) {
				$cat_slug  = 'general';
				$cat_label = __( 'News', 'nano' );
			} else {
				$cat_label = $category;
			}
		}

		$date_raw = function_exists( 'nano_field' ) ? nano_field( 'nano_date', $post_id ) : '';
		$date_out = '';
		if ( $date_raw ) {
			$dt       = DateTime::createFromFormat( 'Ymd', (string) $date_raw );
			$date_out = $dt ? $dt->format( 'm/d/Y' ) : (string) $date_raw;
		}

		// Description sentence — Events and Classes use their description field,
		// News the excerpt (shown only where the grid opts in).
		$excerpt = '';
		if ( 'event' === $type || 'class' === $type ) {
			$desc = function_exists( 'nano_field' ) ? nano_field( 'nano_description', $post_id ) : '';
			$desc = trim( wp_strip_all_tags( (string) $desc ) );
			if ( $desc ) {
				$excerpt = wp_trim_words( $desc, 24 );
			}
		} elseif ( has_excerpt( $post_id ) ) {
			$excerpt = get_the_excerpt( $post_id );
		}
		?>
		<li class="nano-card<?php echo $cat_slug ? ' nano-card--' . esc_attr( $cat_slug ) : ''; ?>"<?php echo $data_attr; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
			<a class="nano-card__media" href="<?php echo esc_url( $link ); ?>" tabindex="-1" aria-hidden="true">
				<?php
				if ( 'event' === $type || 'class' === $type || $static ) {
					$thumb = nano_card_thumb_id( $post_id );
					echo $thumb
						? wp_get_attachment_image( $thumb, 'large', false, array( 'class' => 'nano-media nano-media--image', 'sizes' => '(max-width: 640px) 100vw, 50vw', 'loading' => 'lazy' ) ) // phpcs:ignore WordPress.Security.EscapeOutput
						: '<span class="nano-media nano-media--empty" aria-hidden="true"></span>';
				} elseif ( function_exists( 'nano_render_media' ) ) {
					echo nano_render_media( nano_media( $post_id ), array( 'sizes' => '(max-width: 640px) 100vw, 50vw' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
			</a>
			<div class="nano-card__caption">
				<div class="nano-card__meta">
					<?php if ( $show_type ) : ?>
						<span class="nano-card__type"><?php echo esc_html( $type_label ); ?></span>
					<?php endif; ?>
					<?php if ( $cat_label && ! ( $show_type && 'general' === $cat_slug ) ) : ?>
						<span class="nano-card__cat"><?php echo esc_html( strtoupper( $cat_label ) ); ?></span>
					<?php endif; ?>
					<?php if ( $date_out ) : ?>
						<time class="nano-card__date"><?php echo esc_html( $date_out ); ?></time>
					<?php endif; ?>
					<?php if ( 'class' === $type && $instructor ) : ?>
						<span class="nano-card__by"><?php echo esc_html( $instructor ); ?></span>
					<?php endif; ?>
				</div>
				<h3 class="nano-card__title">
					<a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></a>
				</h3>
				<?php if ( $excerpt ) : ?>
					<p class="nano-card__excerpt"><?php echo esc_html( $excerpt ); ?></p>
				<?php endif; ?>
				<?php
				// LOGO EXPERIMENT: category captions carry the designer's corner
				// curls (Assets 7/12/9/10, General = Asset 17) INSIDE the same svg
				// as the elastic bracket lines. Curve coordinates are rebased so
				// the join point with the line is the group's origin; CSS places
				// the group with a % translate + scale (see .nano-card__curlg).
				// One svg = one raster pass: iOS rounds separate svg elements to
				// different device-pixel phases, which broke the seams.
				$nano_card_curls = array(
					'general'      => array( // Asset 17: curl on the upper part of the left vertical, dot swinging out left.
						'side' => 'left',
						'd'    => 'M-22.49,20.41c.21.45.53,1.14.95,1.96,2.26,4.41,6.5,12.7,12.1,12.82.24,0,1.18.01,2.28-.4,6.12-2.31,6.91-13.75,7.08-16.22.07-.99.08-1.77.08-2.18L0,8.5',
						'cx'   => '-22.49',
						'cy'   => '21.14',
					),
					'encounters'   => array( // Asset 7: curl at the bottom of the left vertical.
						'side' => 'left',
						'd'    => 'M22.49,3.59c-.21.45-.53,1.14-.95,1.96-2.26,4.41-6.5,12.7-12.1,12.82-.24,0-1.18.01-2.28-.4-6.12-2.31-6.91-13.75-7.08-16.22-.07-.99-.08-1.77-.08-2.18L0,-8.23',
						'cx'   => '22.49',
						'cy'   => '4.32',
					),
					'resonances'   => array( // Asset 12: curl on the upper part of the right vertical.
						'side' => 'right',
						'd'    => 'M-22.5,20.22c.21.45.53,1.14.95,1.96,2.26,4.41,6.5,12.7,12.1,12.82.24,0,1.18.01,2.28-.4,6.12-2.31,6.91-13.75,7.08-16.22.07-.99.08-1.77.08-2.18L0,8.5',
						'cx'   => '-22.49',
						'cy'   => '20.95',
					),
					'pedagogies'   => array( // Asset 9: curl hanging from the top line's right end.
						'side' => 'left',
						'd'    => 'M4.02,22.49c.45-.21,1.14-.53,1.96-.95,4.4-2.27,12.68-6.53,12.79-12.13,0-.24,0-1.18-.41-2.28-2.32-6.12-13.77-6.88-16.24-7.05-.99-.07-1.77-.08-2.18-.08L-8.34,0',
						'cx'   => '4.75',
						'cy'   => '22.48',
					),
					'correlations' => array( // Asset 10: curl at the bottom of the right vertical.
						'side' => 'right',
						'd'    => 'M-22.49,3.59c.21.45.53,1.14.95,1.96,2.26,4.41,6.5,12.7,12.1,12.82.24,0,1.18.01,2.28-.4,6.12-2.31,6.91-13.75,7.08-16.22.07-.99.08-1.77.08-2.18L0,-8.23',
						'cx'   => '-22.49',
						'cy'   => '4.32',
					),
				);
				?>
				<?php if ( isset( $nano_card_curls[ $cat_slug ] ) ) : ?>
					<?php $nano_curl = $nano_card_curls[ $cat_slug ]; ?>
					<svg class="nano-card__lines" aria-hidden="true">
						<line x1="0" y1="0" x2="100%" y2="0" stroke="currentColor"/>
						<?php if ( 'right' === $nano_curl['side'] ) : ?>
							<line x1="100%" y1="0" x2="100%" y2="100%" stroke="currentColor"/>
						<?php else : ?>
							<line x1="0" y1="0" x2="0" y2="100%" stroke="currentColor"/>
						<?php endif; ?>
						<g class="nano-card__curlg">
							<path d="<?php echo esc_attr( $nano_curl['d'] ); ?>" fill="none" stroke="currentColor" vector-effect="non-scaling-stroke"/>
							<?php // r 4.35 (not the assets' 5.12): caption dots run slightly smaller ?>
							<circle cx="<?php echo esc_attr( $nano_curl['cx'] ); ?>" cy="<?php echo esc_attr( $nano_curl['cy'] ); ?>" r="4.35" fill="currentColor" stroke="none"/>
						</g>
					</svg>
				<?php else : ?>
					<span class="nano-card__tail" aria-hidden="true"><span class="nano-dot"></span></span>
				<?php endif; ?>
			</div>
		</li>
		<?php
	}
}

if ( ! function_exists( 'nano_render_news_card' ) ) {
	/**
	 * Back-compat wrapper — render a card for the current post in the loop.
	 */
	function nano_render_news_card() {
		nano_render_card( get_the_ID() );
	}
}
