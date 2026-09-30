<?php
/**
 * Newsletter signups — the stopgap store behind the homepage signup form.
 *
 * The theme's form handler (mit-imagination-hub/functions.php →
 * nano_handle_newsletter()) calls nano_store_newsletter_signup() on a valid
 * submission. Each address becomes a private `nano_signup` post (title =
 * email, submission time = post date, consent flagged in meta), listed under
 * the "Signups" admin menu with a CSV export button. Deliberately invisible
 * everywhere else: not public, not queryable, not in search or REST.
 *
 * This replaces the discard-everything placeholder until a real ESP
 * integration exists; the CSV is the hand-off format for that migration.
 *
 * @package Nano\ImaginationHubCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * The signups post type — admin-only storage, no front end, no manual "Add".
 */
function nano_register_signup_post_type() {
	register_post_type(
		'nano_signup',
		array(
			'labels'          => array(
				'name'          => 'Signups',
				'singular_name' => 'Signup',
				'menu_name'     => 'Signups',
				'search_items'  => 'Search signups',
				'not_found'     => 'No signups yet.',
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'show_in_rest'    => false,
			'menu_icon'       => 'dashicons-email-alt',
			'menu_position'   => 26,
			'supports'        => array( 'title' ),
			'capability_type' => 'post',
			'map_meta_cap'    => true,
			'capabilities'    => array(
				// Records come from the form only — no "Add New" in the admin.
				'create_posts' => 'do_not_allow',
			),
		)
	);
}
add_action( 'init', 'nano_register_signup_post_type' );

if ( ! function_exists( 'nano_store_newsletter_signup' ) ) {
	/**
	 * File one signup. Same address twice is not an error — the existing
	 * record simply stands (the directory is a set, not a log).
	 *
	 * @param string $email Validated email address.
	 * @return int Post ID of the (new or existing) record, 0 on failure.
	 */
	function nano_store_newsletter_signup( $email ) {
		$email = sanitize_email( $email );
		if ( ! is_email( $email ) ) {
			return 0;
		}

		$existing = get_posts(
			array(
				'post_type'      => 'nano_signup',
				'post_status'    => 'private',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'title'          => $email,
			)
		);
		if ( $existing ) {
			return (int) $existing[0];
		}

		$id = wp_insert_post(
			array(
				'post_type'   => 'nano_signup',
				'post_status' => 'private',
				'post_title'  => $email,
				'meta_input'  => array(
					// The form requires the consent box; recorded per entry so
					// the stored list carries its own audit trail.
					'nano_consent' => 1,
				),
			)
		);
		return is_wp_error( $id ) ? 0 : (int) $id;
	}
}

/**
 * Admin list: Email / Signed up columns (the defaults say "Title"/"Date",
 * which reads wrong for a mailing list).
 */
add_filter(
	'manage_nano_signup_posts_columns',
	function ( $columns ) {
		return array(
			'cb'    => isset( $columns['cb'] ) ? $columns['cb'] : '<input type="checkbox" />',
			'title' => 'Email',
			'date'  => 'Signed up',
		);
	}
);

/**
 * An Export CSV button above the signups list.
 *
 * @param string $which Table nav position.
 */
function nano_signup_export_button( $which ) {
	$screen = get_current_screen();
	if ( ! $screen || 'edit-nano_signup' !== $screen->id || 'top' !== $which ) {
		return;
	}
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=nano_signup_export' ), 'nano_signup_export' );
	echo '<div class="alignleft actions"><a class="button" href="' . esc_url( $url ) . '">Export CSV</a></div>';
}
add_action( 'manage_posts_extra_tablenav', 'nano_signup_export_button' );

/**
 * Stream every signup as CSV (email, signed-up timestamp, consent).
 */
function nano_signup_export() {
	if ( ! current_user_can( 'edit_others_posts' ) || ! check_admin_referer( 'nano_signup_export' ) ) {
		wp_die( 'Not allowed.' );
	}
	$rows = get_posts(
		array(
			'post_type'      => 'nano_signup',
			'post_status'    => 'private',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'ASC',
		)
	);
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=newsletter-signups-' . gmdate( 'Y-m-d' ) . '.csv' );
	$out = fopen( 'php://output', 'w' );
	fputcsv( $out, array( 'email', 'signed_up', 'consent' ) );
	foreach ( $rows as $row ) {
		fputcsv(
			$out,
			array(
				$row->post_title,
				get_post_time( 'Y-m-d H:i:s', true, $row ),
				get_post_meta( $row->ID, 'nano_consent', true ) ? 'yes' : '',
			)
		);
	}
	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	exit;
}
add_action( 'admin_post_nano_signup_export', 'nano_signup_export' );
