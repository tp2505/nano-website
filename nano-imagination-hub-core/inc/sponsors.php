<?php
/**
 * Sponsor logos for the Support-us page. Reads the ACF Pro repeater
 * (nano_sponsors) via get_field when available, and otherwise reconstructs it
 * straight from ACF's repeater meta layout (count + indexed sub-fields) so it
 * renders even where ACF Pro isn't loaded — the same isolation contract the
 * event gallery uses (see nano_gallery_rows(), which reads the ACF Pro Gallery
 * field's ID-array meta the same way).
 *
 * @package Nano\ImaginationHubCore
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'nano_sponsor_rows' ) ) {
	/**
	 * Sponsor rows as array( array( 'logo' => id, 'name' => str, 'url' => str ), … ).
	 * Rows without a logo attachment are skipped.
	 *
	 * @param int $post_id Support-us page ID.
	 * @return array
	 */
	function nano_sponsor_rows( $post_id ) {
		$post_id = (int) $post_id;
		if ( ! $post_id ) {
			return array();
		}

		if ( function_exists( 'get_field' ) ) {
			$val = get_field( 'nano_sponsors', $post_id );
			if ( is_array( $val ) && $val && is_array( reset( $val ) ) ) {
				$rows = array();
				foreach ( $val as $row ) {
					$logo = isset( $row['logo'] ) ? (int) $row['logo'] : 0;
					if ( $logo ) {
						$rows[] = array(
							'logo' => $logo,
							'name' => isset( $row['name'] ) ? (string) $row['name'] : '',
							'url'  => isset( $row['url'] ) ? (string) $row['url'] : '',
						);
					}
				}
				return $rows;
			}
		}

		$count = (int) get_post_meta( $post_id, 'nano_sponsors', true );
		$rows  = array();
		for ( $i = 0; $i < $count; $i++ ) {
			$logo = (int) get_post_meta( $post_id, "nano_sponsors_{$i}_logo", true );
			if ( ! $logo ) {
				continue;
			}
			$rows[] = array(
				'logo' => $logo,
				'name' => (string) get_post_meta( $post_id, "nano_sponsors_{$i}_name", true ),
				'url'  => (string) get_post_meta( $post_id, "nano_sponsors_{$i}_url", true ),
			);
		}
		return $rows;
	}
}
