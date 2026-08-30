<?php
/**
 * One-off data migrations, version-gated: each runs once per site, on the first
 * wp-admin load after the plugin updates (admin_init, like the term seeding).
 * Everything here is idempotent — safe to run again on a site that already has
 * the new shape.
 *
 * 0.2.0:
 *   - Initiative display order moves from the nano_order meta field to native
 *     menu_order (Page Attributes → Order). Existing values are copied over;
 *     the old meta is left in place (harmless, nothing reads it any more).
 *   - nano_related becomes bidirectional. ACF only maintains the reverse link
 *     for saves made AFTER the setting exists, so links created before it are
 *     backfilled here: every A → B link gains its B → A twin.
 *
 * 0.3.0:
 *   - The short-lived event announcement-poster field is gone — under the one
 *     media rule a poster is simply a still image in the event's standard top
 *     media slot. Any stored poster moves into that slot (nano_media_type =
 *     image + nano_image) unless the slot is already filled.
 *
 * @package Nano\ImaginationHubCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Run pending migrations once.
 */
function nano_core_upgrade() {
	$done = (string) get_option( 'nano_core_upgraded', '0' );
	if ( version_compare( $done, NANO_CORE_VERSION, '>=' ) ) {
		return;
	}
	nano_upgrade_initiative_menu_order();
	nano_upgrade_backfill_related();
	nano_upgrade_event_poster_to_media();
	update_option( 'nano_core_upgraded', NANO_CORE_VERSION );
}
add_action( 'admin_init', 'nano_core_upgrade' );

/**
 * Copy each initiative's legacy nano_order meta into menu_order, where
 * menu_order hasn't been set yet.
 */
function nano_upgrade_initiative_menu_order() {
	$initiatives = get_posts(
		array(
			'post_type'   => 'initiative',
			'numberposts' => -1,
			'post_status' => 'any',
		)
	);
	foreach ( $initiatives as $initiative ) {
		if ( (int) $initiative->menu_order ) {
			continue; // Already ordered natively — don't overwrite.
		}
		$legacy = (int) get_post_meta( $initiative->ID, 'nano_order', true );
		if ( $legacy ) {
			wp_update_post(
				array(
					'ID'         => $initiative->ID,
					'menu_order' => $legacy,
				)
			);
		}
	}
}

/**
 * Make every existing one-way nano_related link two-way: for each A → B, add A
 * to B's nano_related if it isn't there already. Values are written the way ACF
 * stores relationships (array of string IDs), and B gets the ACF field-key
 * reference (_nano_related) for its post type if missing, so the link shows up
 * in B's editor too.
 */
function nano_upgrade_backfill_related() {
	// The per-post-type key of the field that holds nano_related.
	$field_keys = array(
		'news'  => 'field_news_ref_related',
		'event' => 'field_event_ref_related',
		'class' => 'field_nano_class_related',
	);

	$ids = get_posts(
		array(
			'post_type'   => array_keys( $field_keys ),
			'numberposts' => -1,
			'post_status' => 'any',
			'fields'      => 'ids',
		)
	);

	foreach ( $ids as $source_id ) {
		$related = get_post_meta( $source_id, 'nano_related', true );
		if ( ! is_array( $related ) || ! $related ) {
			continue;
		}
		foreach ( $related as $target_id ) {
			$target_id   = (int) $target_id;
			$target_type = $target_id ? get_post_type( $target_id ) : false;
			if ( ! $target_type || ! isset( $field_keys[ $target_type ] ) ) {
				continue;
			}
			$reverse = get_post_meta( $target_id, 'nano_related', true );
			$reverse = is_array( $reverse ) ? array_map( 'strval', $reverse ) : array();
			if ( ! in_array( (string) $source_id, $reverse, true ) ) {
				$reverse[] = (string) $source_id;
				update_post_meta( $target_id, 'nano_related', array_values( $reverse ) );
			}
			if ( ! get_post_meta( $target_id, '_nano_related', true ) ) {
				update_post_meta( $target_id, '_nano_related', $field_keys[ $target_type ] );
			}
		}
	}
}

/**
 * Move any stored event announcement poster into the standard top media slot
 * (nano_media_type = image + nano_image), then drop the old meta. Skipped for
 * events whose slot is already filled — nothing gets overwritten.
 */
function nano_upgrade_event_poster_to_media() {
	$ids = get_posts(
		array(
			'post_type'   => 'event',
			'numberposts' => -1,
			'post_status' => 'any',
			'fields'      => 'ids',
		)
	);
	foreach ( $ids as $event_id ) {
		$poster = (int) get_post_meta( $event_id, 'nano_announcement_poster', true );
		if ( $poster ) {
			$slot_taken = get_post_meta( $event_id, 'nano_media_type', true )
				|| (int) get_post_meta( $event_id, 'nano_image', true )
				|| (int) get_post_meta( $event_id, 'nano_video', true );
			if ( ! $slot_taken ) {
				update_post_meta( $event_id, 'nano_media_type', 'image' );
				update_post_meta( $event_id, '_nano_media_type', 'field_event_media_type' );
				update_post_meta( $event_id, 'nano_image', $poster );
				update_post_meta( $event_id, '_nano_image', 'field_event_image' );
			}
		}
		delete_post_meta( $event_id, 'nano_announcement_poster' );
		delete_post_meta( $event_id, '_nano_announcement_poster' );
	}
}
