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
