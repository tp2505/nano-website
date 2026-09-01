<?php
/**
 * MIT Imagination Hub — theme functions.
 *
 * Presentation only. The content model (post types, fields, dynamic blocks)
 * lives in the Nano Imagination Hub Core plugin. This file wires assets, the
 * field accessor, and a minimal newsletter form handler.
 *
 * @package Nano\ImaginationHub
 */

defined( 'ABSPATH' ) || exit;

define( 'NANO_THEME_VERSION', wp_get_theme()->get( 'Version' ) ?: '0.1.0' );

// The field-access isolation layer (nano_field / nano_media / nano_render_media).
require_once get_stylesheet_directory() . '/inc/fields.php';

// Customizer settings (editable hero video + poster).
require_once get_stylesheet_directory() . '/inc/customizer.php';

/**
 * Front-end styles and scripts.
 */
function nano_enqueue_assets() {
	$dir = get_stylesheet_directory();
	$uri = get_stylesheet_directory_uri();

	wp_enqueue_style(
		'nano-app',
		$uri . '/assets/css/app.css',
		array(),
		file_exists( "$dir/assets/css/app.css" ) ? filemtime( "$dir/assets/css/app.css" ) : NANO_THEME_VERSION
	);

	wp_enqueue_script(
		'nano-app',
		$uri . '/assets/js/nano.js',
		array(),
		file_exists( "$dir/assets/js/nano.js" ) ? filemtime( "$dir/assets/js/nano.js" ) : NANO_THEME_VERSION,
		array( 'strategy' => 'defer', 'in_footer' => true )
	);
}
add_action( 'wp_enqueue_scripts', 'nano_enqueue_assets' );

/**
 * Editor styles so blocks look right inside the site editor too.
 */
function nano_theme_supports() {
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/app.css' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', array( 'style', 'script', 'navigation-widgets' ) );
}
add_action( 'after_setup_theme', 'nano_theme_supports' );

/**
 * Internal-link helpers for the header / footer / 404 patterns.
 *
 * The site lives at different roots per environment (domain root locally,
 * /studionano/ on sites.mit.edu, later studionano.mit.edu), so nothing may
 * hardcode a root-relative path. These resolve the real object's permalink
 * where one exists and otherwise build the conventional path off home_url(),
 * which carries the subdirectory in every environment.
 */
if ( ! function_exists( 'nano_page_url' ) ) {
	/**
	 * Permalink for a page by its path/slug (e.g. 'about-us').
	 *
	 * @param string $path Page path, no leading slash.
	 * @return string
	 */
	function nano_page_url( $path ) {
		$page = get_page_by_path( $path );
		return $page ? get_permalink( $page ) : home_url( user_trailingslashit( '/' . ltrim( $path, '/' ) ) );
	}
}

if ( ! function_exists( 'nano_initiative_url' ) ) {
	/**
	 * Permalink for an Initiative by slug (e.g. 'encounters').
	 *
	 * @param string $slug Initiative post slug.
	 * @return string
	 */
	function nano_initiative_url( $slug ) {
		$post = get_page_by_path( $slug, OBJECT, 'initiative' );
		return $post ? get_permalink( $post ) : home_url( user_trailingslashit( '/initiatives/' . $slug ) );
	}
}

if ( ! function_exists( 'nano_news_url' ) ) {
	/**
	 * The News archive.
	 *
	 * @return string
	 */
	function nano_news_url() {
		$link = get_post_type_archive_link( 'news' );
		return $link ? $link : home_url( user_trailingslashit( '/news' ) );
	}
}

/**
 * Pattern category so the homepage sections group together in the inserter.
 */
function nano_register_pattern_category() {
	register_block_pattern_category(
		'nano',
		array( 'label' => __( 'Imagination Hub', 'nano' ) )
	);
}
add_action( 'init', 'nano_register_pattern_category' );

/**
 * Minimal, dependency-free newsletter submission handler.
 *
 * The form (see patterns/newsletter.php) posts here with a nonce. We validate
 * the email and the consent box, then redirect back with a status flag. Nothing
 * is stored — this is a wired-up placeholder for a real ESP integration later.
 */
function nano_handle_newsletter() {
	$ok = isset( $_POST['nano_newsletter_nonce'] )
		&& wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nano_newsletter_nonce'] ) ), 'nano_newsletter' );

	$email   = isset( $_POST['nano_email'] ) ? sanitize_email( wp_unslash( $_POST['nano_email'] ) ) : '';
	$consent = ! empty( $_POST['nano_consent'] );
	$status  = ( $ok && is_email( $email ) && $consent ) ? 'success' : 'error';

	$back = wp_get_referer() ? wp_get_referer() : home_url( '/' );
	wp_safe_redirect( add_query_arg( 'newsletter', $status, remove_query_arg( 'newsletter', $back ) ) . '#newsletter' );
	exit;
}
add_action( 'admin_post_nano_newsletter', 'nano_handle_newsletter' );
add_action( 'admin_post_nopriv_nano_newsletter', 'nano_handle_newsletter' );
