<?php
/**
 * Custom post types + taxonomy for the Imagination Hub.
 *
 * @package Nano\ImaginationHubCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the News and Initiative post types and the News category taxonomy.
 */
function nano_register_post_types() {

	// News — the editorial feed. Newest first; cards may be image or video.
	register_post_type(
		'news',
		array(
			'labels'        => array(
				'name'               => __( 'News', 'nano' ),
				'singular_name'      => __( 'News item', 'nano' ),
				'add_new_item'       => __( 'Add news item', 'nano' ),
				'edit_item'          => __( 'Edit news item', 'nano' ),
				'new_item'           => __( 'New news item', 'nano' ),
				'view_item'          => __( 'View news item', 'nano' ),
				'search_items'       => __( 'Search news', 'nano' ),
				'not_found'          => __( 'No news found', 'nano' ),
				'menu_name'          => __( 'News', 'nano' ),
			),
			'public'        => true,
			'show_in_rest'  => true,
			'menu_icon'     => 'dashicons-megaphone',
			'menu_position' => 21,
			'has_archive'   => true,
			'rewrite'       => array( 'slug' => 'news' ),
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields' ),
		)
	);

	// Initiative (Program) — the four strands. Ordered by menu_order (the Page
	// Attributes → Order box, like Facilities and People), large media block.
	// The content editor holds the LONG description (single page only);
	// nano_intro is the short description (homepage row + top of the page).
	register_post_type(
		'initiative',
		array(
			'labels'        => array(
				'name'               => __( 'Initiatives', 'nano' ),
				'singular_name'      => __( 'Initiative', 'nano' ),
				'add_new_item'       => __( 'Add initiative', 'nano' ),
				'edit_item'          => __( 'Edit initiative', 'nano' ),
				'new_item'           => __( 'New initiative', 'nano' ),
				'view_item'          => __( 'View initiative', 'nano' ),
				'search_items'       => __( 'Search initiatives', 'nano' ),
				'not_found'          => __( 'No initiatives found', 'nano' ),
				'menu_name'          => __( 'Initiatives', 'nano' ),
			),
			'public'        => true,
			'show_in_rest'  => true,
			'menu_icon'     => 'dashicons-screenoptions',
			'menu_position' => 22,
			'has_archive'   => true,
			'rewrite'       => array( 'slug' => 'initiatives' ),
			'supports'      => array( 'title', 'editor', 'thumbnail', 'page-attributes', 'custom-fields' ),
		)
	);

	// Person — every human on the site: the Hub's team (About page, grouped by a
	// taxonomy) AND the participants who take part in Events / News / Classes.
	// One type, so a name shown inside an event links to the same page as on the
	// Participants list. Grouped via a taxonomy so the About groups (Leadership,
	// Advisory Board, …) stay extendable from the CMS.
	register_post_type(
		'person',
		array(
			'labels'        => array(
				'name'          => __( 'People', 'nano' ),
				'singular_name' => __( 'Person', 'nano' ),
				'add_new_item'  => __( 'Add person', 'nano' ),
				'edit_item'     => __( 'Edit person', 'nano' ),
				'new_item'      => __( 'New person', 'nano' ),
				'search_items'  => __( 'Search people', 'nano' ),
				'not_found'     => __( 'No people found', 'nano' ),
				'menu_name'     => __( 'People', 'nano' ),
			),
			'public'        => true,
			'show_in_rest'  => true,
			'menu_icon'     => 'dashicons-businessperson',
			'menu_position' => 24,
			'has_archive'   => false,
			'rewrite'       => array( 'slug' => 'people' ),
			'supports'      => array( 'title', 'thumbnail', 'page-attributes', 'custom-fields' ),
		)
	);

	// Event — a happening within one of the four Initiatives. Gallery + a manual
	// related/people section on the single page.
	register_post_type(
		'event',
		array(
			'labels'        => array(
				'name'          => __( 'Events', 'nano' ),
				'singular_name' => __( 'Event', 'nano' ),
				'add_new_item'  => __( 'Add event', 'nano' ),
				'edit_item'     => __( 'Edit event', 'nano' ),
				'new_item'      => __( 'New event', 'nano' ),
				'view_item'     => __( 'View event', 'nano' ),
				'search_items'  => __( 'Search events', 'nano' ),
				'not_found'     => __( 'No events found', 'nano' ),
				'menu_name'     => __( 'Events', 'nano' ),
			),
			'public'        => true,
			'show_in_rest'  => true,
			'menu_icon'     => 'dashicons-calendar-alt',
			'menu_position' => 22,
			'has_archive'   => true,
			'rewrite'       => array( 'slug' => 'events' ),
			'supports'      => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
		)
	);

	// Class — a course taught under the Pedagogies initiative. Belongs to
	// Pedagogies only (no cross-initiative dimension); the load-bearing field is
	// the term, which drives the current-vs-past listing. Reuses the shared
	// Related section on its single page.
	register_post_type(
		'class',
		array(
			'labels'        => array(
				'name'          => __( 'Classes', 'nano' ),
				'singular_name' => __( 'Class', 'nano' ),
				'add_new_item'  => __( 'Add class', 'nano' ),
				'edit_item'     => __( 'Edit class', 'nano' ),
				'new_item'      => __( 'New class', 'nano' ),
				'view_item'     => __( 'View class', 'nano' ),
				'search_items'  => __( 'Search classes', 'nano' ),
				'not_found'     => __( 'No classes found', 'nano' ),
				'menu_name'     => __( 'Classes', 'nano' ),
			),
			'public'        => true,
			'show_in_rest'  => true,
			'menu_icon'     => 'dashicons-welcome-learn-more',
			'menu_position' => 22,
			'has_archive'   => false,
			'rewrite'       => array( 'slug' => 'classes' ),
			'supports'      => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
		)
	);

	// People group — which section a person appears in on the About page. New
	// terms (e.g. "Fellows") create a new section automatically.
	register_taxonomy(
		'people_group',
		'person',
		array(
			'labels'            => array(
				'name'          => __( 'Groups', 'nano' ),
				'singular_name' => __( 'Group', 'nano' ),
				'menu_name'     => __( 'Groups', 'nano' ),
				'add_new_item'  => __( 'Add group', 'nano' ),
			),
			'public'            => false,
			'show_ui'           => true,
			'show_in_rest'      => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
		)
	);

	// News category — values mirror the Initiatives plus a general bucket.
	register_taxonomy(
		'news_category',
		'news',
		array(
			'labels'            => array(
				'name'          => __( 'News categories', 'nano' ),
				'singular_name' => __( 'News category', 'nano' ),
				'menu_name'     => __( 'Categories', 'nano' ),
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'news-category' ),
		)
	);
}
add_action( 'init', 'nano_register_post_types' );

/**
 * Facility — one tile on the Facilities page: title (caption), featured image,
 * link URL (nano_url), ordered by menu_order. No public single pages — tiles
 * link out to each facility's own site.
 */
function nano_register_facility_post_type() {
	register_post_type(
		'facility',
		array(
			'labels'        => array(
				'name'          => __( 'Facilities', 'nano' ),
				'singular_name' => __( 'Facility', 'nano' ),
				'add_new_item'  => __( 'Add facility', 'nano' ),
				'edit_item'     => __( 'Edit facility', 'nano' ),
				'new_item'      => __( 'New facility', 'nano' ),
				'search_items'  => __( 'Search facilities', 'nano' ),
				'not_found'     => __( 'No facilities found', 'nano' ),
				'menu_name'     => __( 'Facilities', 'nano' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_rest'    => true,
			'menu_icon'       => 'dashicons-building',
			'menu_position'   => 24,
			'hierarchical'    => false,
			'supports'        => array( 'title', 'thumbnail', 'page-attributes', 'custom-fields' ),
		)
	);
}
add_action( 'init', 'nano_register_facility_post_type' );


/**
 * Manually-ordered types get an Order column in their admin list (sortable), so
 * an editor can see and check the sequence without opening every post. The
 * value itself is edited in the post's Page Attributes → Order box.
 */
function nano_register_order_columns() {
	foreach ( array( 'person', 'initiative', 'facility' ) as $nano_pt ) {
		add_filter(
			"manage_{$nano_pt}_posts_columns",
			function ( $columns ) {
				$columns['nano_menu_order'] = __( 'Order', 'nano' );
				return $columns;
			}
		);
		add_action(
			"manage_{$nano_pt}_posts_custom_column",
			function ( $column, $post_id ) {
				if ( 'nano_menu_order' === $column ) {
					$order = (int) get_post_field( 'menu_order', $post_id );
					echo $order ? (int) $order : '&mdash;';
				}
			},
			10,
			2
		);
		add_filter(
			"manage_edit-{$nano_pt}_sortable_columns",
			function ( $columns ) {
				$columns['nano_menu_order'] = 'menu_order';
				return $columns;
			}
		);
	}
}
add_action( 'admin_init', 'nano_register_order_columns' );

/**
 * Seed the default News category terms once, on admin load, if missing.
 * Mirrors the four Initiatives plus a general bucket.
 */
function nano_seed_news_terms() {
	$terms = array( 'Encounters', 'Resonances', 'Pedagogies', 'Correlations', 'General' );
	foreach ( $terms as $term ) {
		if ( ! term_exists( $term, 'news_category' ) ) {
			wp_insert_term( $term, 'news_category' );
		}
	}
}
add_action( 'admin_init', 'nano_seed_news_terms' );

/**
 * Seed the default People groups once, on admin load, if missing. More can be
 * added in the CMS and they'll get their own section on the About page.
 */
function nano_seed_people_groups() {
	$groups = array( 'Leadership', 'Advisory Board', 'Student Advisory Board' );
	foreach ( $groups as $group ) {
		if ( ! term_exists( $group, 'people_group' ) ) {
			wp_insert_term( $group, 'people_group' );
		}
	}
}
add_action( 'admin_init', 'nano_seed_people_groups' );
