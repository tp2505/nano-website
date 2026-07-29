<?php
/**
 * Class (course) helpers. The `class` post type belongs to Pedagogies only and
 * is organised by its term ("Fall 2026"). Everything downstream — the current-
 * vs-past split on the Pedagogies page and the term grouping in the Archive —
 * hangs off a single sortable key derived from that term string. Kept in one
 * place so the parsing rule (Season YYYY) has exactly one definition.
 *
 * @package Nano\ImaginationHubCore
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'nano_class_term_key' ) ) {
	/**
	 * A sortable integer for a term string, so newest (current) sorts highest.
	 * "Fall 2026" → 20263, "Spring 2026" → 20261, "Fall 2025" → 20253. The
	 * season order is academic (IAP/Winter < Spring < Summer < Fall). Returns 0
	 * when no four-digit year is present, so unparseable terms sink to the end.
	 *
	 * @param string $term Term label, e.g. "Fall 2026".
	 * @return int
	 */
	function nano_class_term_key( $term ) {
		$term = trim( (string) $term );
		if ( '' === $term || ! preg_match( '/(\d{4})/', $term, $m ) ) {
			return 0;
		}
		$year = (int) $m[1];
		$t    = strtolower( $term );
		$rank = 0; // IAP / January / Winter.
		if ( false !== strpos( $t, 'spring' ) ) {
			$rank = 1;
		} elseif ( false !== strpos( $t, 'summer' ) ) {
			$rank = 2;
		} elseif ( false !== strpos( $t, 'fall' ) || false !== strpos( $t, 'autumn' ) ) {
			$rank = 3;
		}
		return $year * 10 + $rank;
	}
}

if ( ! function_exists( 'nano_class_year' ) ) {
	/**
	 * The four-digit year of a term string, or '' if none.
	 *
	 * @param string $term Term label.
	 * @return string
	 */
	function nano_class_year( $term ) {
		return preg_match( '/(\d{4})/', (string) $term, $m ) ? $m[1] : '';
	}
}

if ( ! function_exists( 'nano_get_classes' ) ) {
	/**
	 * All published classes as array( array( 'id', 'term', 'key', 'year' ), … ),
	 * sorted newest term first, then by title within a term. One query, reused by
	 * the Pedagogies page and the Archive.
	 *
	 * @return array
	 */
	function nano_get_classes() {
		$q     = new WP_Query(
			array(
				'post_type'      => 'class',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);
		$items = array();
		foreach ( $q->posts as $p ) {
			$term    = function_exists( 'nano_field' ) ? (string) nano_field( 'nano_term', $p->ID ) : '';
			$items[] = array(
				'id'   => (int) $p->ID,
				'term' => $term,
				'key'  => nano_class_term_key( $term ),
				'year' => nano_class_year( $term ),
			);
		}
		wp_reset_postdata();

		// Newest term first; the query already ordered by title, and usort is
		// stable in PHP 8+, so same-term classes stay alphabetical.
		usort(
			$items,
			function ( $a, $b ) {
				return $b['key'] - $a['key'];
			}
		);
		return $items;
	}
}

if ( ! function_exists( 'nano_class_syllabus' ) ) {
	/**
	 * Resolve a class's syllabus to a single renderable link, preferring an
	 * uploaded PDF over an external link. Works whether the fields resolve through
	 * ACF (arrays) or raw post meta (an attachment ID / a URL string).
	 *
	 * @param int $post_id Class ID.
	 * @return array|null array( 'url', 'is_file', 'mime', 'size', 'title' ) or null.
	 */
	function nano_class_syllabus( $post_id ) {
		$file = function_exists( 'nano_field' ) ? nano_field( 'nano_syllabus_file', $post_id ) : null;

		if ( is_array( $file ) && ! empty( $file['url'] ) ) {
			return array(
				'url'     => $file['url'],
				'is_file' => true,
				'mime'    => ! empty( $file['subtype'] ) ? strtoupper( $file['subtype'] ) : 'PDF',
				'size'    => isset( $file['filesize'] ) ? size_format( (int) $file['filesize'] ) : '',
				'title'   => '',
			);
		}
		if ( is_numeric( $file ) && (int) $file ) {
			$id  = (int) $file;
			$url = wp_get_attachment_url( $id );
			if ( $url ) {
				$path  = get_attached_file( $id );
				$bytes = ( $path && file_exists( $path ) ) ? filesize( $path ) : 0;
				return array(
					'url'     => $url,
					'is_file' => true,
					'mime'    => 'PDF',
					'size'    => $bytes ? size_format( $bytes ) : '',
					'title'   => '',
				);
			}
		}

		$link = function_exists( 'nano_field' ) ? nano_field( 'nano_syllabus_link', $post_id ) : null;
		if ( is_array( $link ) && ! empty( $link['url'] ) ) {
			return array(
				'url'     => $link['url'],
				'is_file' => false,
				'mime'    => '',
				'size'    => '',
				'title'   => isset( $link['title'] ) ? $link['title'] : '',
			);
		}
		if ( is_string( $link ) && '' !== $link ) {
			return array(
				'url'     => $link,
				'is_file' => false,
				'mime'    => '',
				'size'    => '',
				'title'   => '',
			);
		}
		return null;
	}
}

if ( ! function_exists( 'nano_current_classes' ) ) {
	/**
	 * Classes in the most-recent term only (the "current" term). Empty array if
	 * there are no classes.
	 *
	 * @return array Same shape as nano_get_classes() entries.
	 */
	function nano_current_classes() {
		$all = nano_get_classes();
		if ( empty( $all ) ) {
			return array();
		}
		$top = $all[0]['key']; // Sorted desc, so the first is the newest term.
		return array_values(
			array_filter(
				$all,
				function ( $it ) use ( $top ) {
					return $it['key'] === $top;
				}
			)
		);
	}
}
