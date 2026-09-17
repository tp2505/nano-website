<?php
/**
 * Event date/time helpers.
 *
 * An event has a required start date (nano_date, Ymd — also the sort key),
 * an optional end date (nano_date_end, Ymd), and optional start / end times
 * (nano_time_start / nano_time_end, H:i:s). nano_event_when() renders
 * whichever combination is filled in US format (month first, 12-hour clock),
 * collapsing redundant parts:
 *
 *   May 28, 2026
 *   May 28, 2026, 6:00–8:00 pm
 *   May 28 – 30, 2026                 (same-month range)
 *   May 28 – June 14, 2026            (same-year range)
 *   May 28, 2026 – Jan 4, 2027        (cross-year range)
 *   May 28 – 30, 2026, 9:00 am – 5:00 pm
 *
 * Times apply to each day of a range (set daily hours), not one continuous
 * span. Same-meridiem times collapse ("6:00–8:00 pm"); mixed keep both
 * ("9:00 am – 5:00 pm"). Partial input renders gracefully: an end date on or
 * before the start is ignored, an end time without a start time is ignored.
 *
 * @package Nano\ImaginationHubCore
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'nano_strip_ids' ) ) {
	/**
	 * The vertical image strip's ordered attachment IDs (ACF Pro Gallery
	 * field nano_strip), reading raw meta like nano_gallery_rows() so it
	 * resolves with or without ACF loaded. Empty array when unset.
	 *
	 * @param int $post_id Event ID.
	 * @return int[]
	 */
	function nano_strip_ids( $post_id ) {
		$raw = get_post_meta( (int) $post_id, 'nano_strip', true );
		if ( ! is_array( $raw ) ) {
			return array();
		}
		return array_values(
			array_filter(
				array_map( 'intval', $raw ),
				function ( $id ) {
					return $id && wp_attachment_is_image( $id );
				}
			)
		);
	}
}

if ( ! function_exists( 'nano_render_strip' ) ) {
	/**
	 * Echo the vertical image strip: one horizontally-scrolling row of 9:16
	 * panels with minimal gaps, each a button opening the shared lightbox
	 * (assets/js/nano.js → initLightbox). Fractional panel widths leave a
	 * partial panel peeking at the right edge at every viewport, so the
	 * scroll is discoverable without extra chrome. Echoes nothing when empty.
	 *
	 * Markup contract for the lightbox (reusable by other components): a
	 * [data-nano-lightbox] container whose buttons carry data-full (the
	 * full-size URL) and data-alt.
	 *
	 * @param int[] $ids Image attachment IDs.
	 */
	function nano_render_strip( $ids ) {
		$ids = array_values( array_filter( array_map( 'intval', (array) $ids ) ) );
		if ( ! $ids ) {
			return;
		}
		$total = count( $ids );
		?>
		<div class="nano-strip" data-nano-lightbox>
			<ul class="nano-strip__track" role="list">
				<?php foreach ( $ids as $i => $id ) : ?>
					<?php
					$full = wp_get_attachment_image_url( $id, 'large' );
					$alt  = (string) get_post_meta( $id, '_wp_attachment_image_alt', true );
					?>
					<li class="nano-strip__item">
						<button
							class="nano-strip__thumb"
							type="button"
							data-full="<?php echo esc_url( $full ); ?>"
							data-alt="<?php echo esc_attr( $alt ); ?>"
							aria-label="<?php echo esc_attr( sprintf( /* translators: 1: position, 2: total */ __( 'View image %1$d of %2$d full screen', 'nano' ), $i + 1, $total ) ); ?>"
						>
							<?php echo wp_get_attachment_image( $id, 'medium', false, array( 'class' => 'nano-media nano-media--image', 'loading' => 'lazy', 'sizes' => '(max-width: 781px) 55vw, 20vw' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</button>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}
}

if ( ! function_exists( 'nano_event_when' ) ) {
	/**
	 * The event's formatted date(s) and, optionally, times.
	 *
	 * @param int  $post_id    Event ID.
	 * @param bool $with_times Include the time part (false for compact
	 *                         contexts: cards, the news facts line).
	 * @return string Formatted string, '' when no start date is set.
	 */
	function nano_event_when( $post_id, $with_times = true ) {
		$post_id = (int) $post_id;
		$field   = function ( $name ) use ( $post_id ) {
			return function_exists( 'nano_field' )
				? trim( (string) nano_field( $name, $post_id ) )
				: trim( (string) get_post_meta( $post_id, $name, true ) );
		};

		$start = DateTime::createFromFormat( '!Ymd', $field( 'nano_date' ) );
		if ( ! $start ) {
			return '';
		}
		$end = DateTime::createFromFormat( '!Ymd', $field( 'nano_date_end' ) );
		if ( $end && $end <= $start ) {
			$end = null; // Same-day or inverted range: render as a single date.
		}

		if ( ! $end ) {
			$dates = $start->format( 'F j, Y' );
		} elseif ( $start->format( 'Ym' ) === $end->format( 'Ym' ) ) {
			$dates = $start->format( 'F j' ) . ' – ' . $end->format( 'j, Y' );
		} elseif ( $start->format( 'Y' ) === $end->format( 'Y' ) ) {
			$dates = $start->format( 'F j' ) . ' – ' . $end->format( 'F j, Y' );
		} else {
			$dates = $start->format( 'F j, Y' ) . ' – ' . $end->format( 'F j, Y' );
		}

		if ( ! $with_times ) {
			return $dates;
		}

		$parse = function ( $raw ) {
			$t = DateTime::createFromFormat( 'H:i:s', $raw );
			return $t ? $t : DateTime::createFromFormat( 'H:i', $raw );
		};
		$t1 = $parse( $field( 'nano_time_start' ) );
		$t2 = $t1 ? $parse( $field( 'nano_time_end' ) ) : null; // End time without a start time is ignored.

		if ( ! $t1 ) {
			return $dates;
		}
		if ( ! $t2 ) {
			$times = $t1->format( 'g:i a' );
		} elseif ( $t1->format( 'a' ) === $t2->format( 'a' ) ) {
			$times = $t1->format( 'g:i' ) . '–' . $t2->format( 'g:i a' );
		} else {
			$times = $t1->format( 'g:i a' ) . ' – ' . $t2->format( 'g:i a' );
		}

		return $dates . ', ' . $times;
	}
}
