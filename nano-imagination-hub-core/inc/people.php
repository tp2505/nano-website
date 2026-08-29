<?php
/**
 * People helpers. Every human is a `person`. Events, News and Classes point at
 * the people involved through their nano_people relationship; these helpers read
 * that link in reverse — "what did this person take part in", and "who has taken
 * part in anything" — so a person page can list their content and the
 * Participants page can list everyone who has participated.
 *
 * The membership test is done in PHP (read each post's nano_people array and
 * check for the id) rather than a serialized-meta LIKE query, so it is correct
 * whether the value was written by ACF (string ids) or the seeder (int ids).
 *
 * @package Nano\ImaginationHubCore
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'nano_sort_people' ) ) {
	/**
	 * Order a list of person posts for display: explicit menu_order first
	 * (ascending), everyone unset (order 0) after them, alphabetically by last
	 * name. Plain menu_order ASC would put the unset majority BEFORE anyone an
	 * editor deliberately ordered (0 < 1), which is exactly backwards for
	 * curated lists — so 0 means "no opinion, sort by name at the end".
	 *
	 * @param WP_Post[] $people Person posts (modified in place and returned).
	 * @return WP_Post[]
	 */
	function nano_sort_people( $people ) {
		$last = function ( $name ) {
			$parts = preg_split( '/\s+/', trim( $name ) );
			return end( $parts );
		};
		usort(
			$people,
			function ( $a, $b ) use ( $last ) {
				$oa = (int) $a->menu_order;
				$ob = (int) $b->menu_order;
				if ( $oa !== $ob ) {
					if ( 0 === $oa ) {
						return 1;
					}
					if ( 0 === $ob ) {
						return -1;
					}
					return $oa <=> $ob;
				}
				$cmp = strcasecmp( $last( $a->post_title ), $last( $b->post_title ) );
				return 0 !== $cmp ? $cmp : strcasecmp( $a->post_title, $b->post_title );
			}
		);
		return $people;
	}
}

if ( ! function_exists( 'nano_content_people_map' ) ) {
	/**
	 * Map of every published Event/News/Class to the person ids it references,
	 * with each item's sort date. Built once per request, then cached.
	 *
	 * @return array array( post_id => array( 'people' => int[], 'date' => 'Ymd' ), … )
	 */
	function nano_content_people_map() {
		static $map = null;
		if ( null !== $map ) {
			return $map;
		}
		$map = array();
		$q   = new WP_Query(
			array(
				'post_type'      => array( 'event', 'news', 'class' ),
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		foreach ( $q->posts as $pid ) {
			$people = function_exists( 'nano_field' ) ? nano_field( 'nano_people', $pid ) : array();
			$people = is_array( $people ) ? array_values( array_filter( array_map( 'intval', $people ) ) ) : array();
			$date   = function_exists( 'nano_field' ) ? (string) nano_field( 'nano_date', $pid ) : '';
			if ( '' === $date && 'class' === get_post_type( $pid ) && function_exists( 'nano_class_term_key' ) ) {
				// Classes have no date; fold the term into a comparable Ymd-ish key.
				$key  = nano_class_term_key( (string) nano_field( 'nano_term', $pid ) );
				$date = $key ? (string) ( intdiv( $key, 10 ) ) . '0000' : '';
			}
			$map[ $pid ] = array(
				'people' => $people,
				'date'   => $date ? $date : '00000000',
			);
		}
		return $map;
	}
}

if ( ! function_exists( 'nano_person_related_content' ) ) {
	/**
	 * Post ids (Events / News / Classes) that reference a person, newest first.
	 *
	 * @param int $person_id Person ID.
	 * @return int[]
	 */
	function nano_person_related_content( $person_id ) {
		$person_id = (int) $person_id;
		if ( ! $person_id ) {
			return array();
		}
		$rows = array();
		foreach ( nano_content_people_map() as $pid => $info ) {
			if ( in_array( $person_id, $info['people'], true ) ) {
				$rows[] = array(
					'id'   => $pid,
					'date' => $info['date'],
				);
			}
		}
		usort(
			$rows,
			function ( $a, $b ) {
				return strcmp( $b['date'], $a['date'] );
			}
		);
		return array_map(
			function ( $r ) {
				return $r['id'];
			},
			$rows
		);
	}
}

if ( ! function_exists( 'nano_participant_person_ids' ) ) {
	/**
	 * Every person id referenced by at least one Event/News/Class — the people
	 * who have taken part in something.
	 *
	 * @return int[]
	 */
	function nano_participant_person_ids() {
		$ids = array();
		foreach ( nano_content_people_map() as $info ) {
			foreach ( $info['people'] as $pid ) {
				$ids[ $pid ] = true;
			}
		}
		return array_keys( $ids );
	}
}
