<?php
/**
 * Plugin Name:       Nano — Imagination Hub Core
 * Description:        Content model for the MIT Imagination Hub: registers the post types (News, Event, Class, Initiative, Person), the News-category and People-group taxonomies, the ACF field groups, and the server-rendered blocks that present that data (news feed, initiative pages, the filtered Archive, single Event/Class/Person, About, Participants, Related). Kept separate from the presentation theme so content survives theme switches. See README.md.
 * Version:           0.2.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Nano Studio
 * License:           GPL-2.0-or-later
 * Text Domain:       nano
 *
 * @package Nano\ImaginationHubCore
 */

defined( 'ABSPATH' ) || exit;

define( 'NANO_CORE_VERSION', '0.2.0' );
define( 'NANO_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'NANO_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once NANO_CORE_DIR . 'inc/cpt.php';
require_once NANO_CORE_DIR . 'inc/fields-acf.php';
require_once NANO_CORE_DIR . 'inc/news-card.php';
require_once NANO_CORE_DIR . 'inc/classes.php';
require_once NANO_CORE_DIR . 'inc/people.php';
require_once NANO_CORE_DIR . 'inc/sponsors.php';
require_once NANO_CORE_DIR . 'inc/upgrade.php';

/**
 * Register the server-rendered blocks (News feed, Initiatives list).
 *
 * Each block lives in blocks/<name>/ with a block.json + render.php. They run
 * a WP_Query against the post types so the homepage is driven by data, never
 * hardcoded — this is the primary thing the build is meant to prove.
 */
function nano_core_register_blocks() {
	foreach ( array( 'news-feed', 'initiatives-list', 'initiative-page', 'project-page', 'page-heading', 'participants-list', 'initiatives-archive', 'facilities-grid', 'sponsors', 'about', 'related', 'event-page', 'class-page', 'person-page' ) as $block ) {
		register_block_type( NANO_CORE_DIR . 'blocks/' . $block );
	}
}
add_action( 'init', 'nano_core_register_blocks' );

/**
 * Flush rewrite rules on activation so the CPT permalinks work immediately.
 */
function nano_core_activate() {
	require_once NANO_CORE_DIR . 'inc/cpt.php';
	nano_register_post_types();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'nano_core_activate' );

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
