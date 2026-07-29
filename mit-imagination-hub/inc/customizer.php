<?php
/**
 * Customizer — site-wide, editable-without-code settings.
 *
 * Hero media (the full-screen home-page video + its poster) live here rather than
 * as hard-coded theme files, so an editor can swap them from Appearance →
 * Customize → Hero without a code change / review round. Both fall back to the
 * bundled assets/video/ files when unset, so the theme works out of the box.
 *
 * @package Nano\ImaginationHub
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the Hero section + the video and poster media controls.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function nano_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'nano_hero',
		array(
			'title'       => __( 'Hero', 'nano' ),
			'priority'    => 30,
			'description' => __( 'The full-screen video and poster at the top of the home page. Leave either empty to use the bundled default.', 'nano' ),
		)
	);

	$wp_customize->add_setting(
		'nano_hero_video',
		array(
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Media_Control(
			$wp_customize,
			'nano_hero_video',
			array(
				'label'       => __( 'Hero video (MP4)', 'nano' ),
				'description' => __( 'Muted, looping background clip. Shown on desktop; phones and data-saver / reduced-motion visitors see the poster instead. Keep it short and well-compressed.', 'nano' ),
				'section'     => 'nano_hero',
				'mime_type'   => 'video',
			)
		)
	);

	$wp_customize->add_setting(
		'nano_hero_poster',
		array(
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Media_Control(
			$wp_customize,
			'nano_hero_poster',
			array(
				'label'       => __( 'Hero poster image', 'nano' ),
				'description' => __( 'Shown instantly while the video loads, and used on its own on phones. A wide (16:9) image works best.', 'nano' ),
				'section'     => 'nano_hero',
				'mime_type'   => 'image',
			)
		)
	);
}
add_action( 'customize_register', 'nano_customize_register' );

/**
 * Default editorial copy — used until an editor overrides it in the
 * Customizer, and as the Customizer fields' starting values.
 *
 * Convention for the multi-line settings: a single newline is a
 * desktop-only line break (hidden on phones so text rewraps naturally);
 * a blank line is a hard break on every screen.
 *
 * @return array<string,string>
 */
function nano_copy_defaults() {
	return array(
		'nano_copy_tagline'    => "Harnessing the frontiers of\ncreativity.\n\nConfronting advanced technology\nwith unrestrained imagination.",
		'nano_copy_mission'    => "At Imagination Hub, we push beyond\nthe boundary of known — where art\nand science, culture and technology\nbecome a single, unified force.",
		'nano_copy_newsletter' => 'Receive unique and comprehensive insight into our activities and be among the first to know about our upcoming events.',
	);
}

/**
 * Register the Homepage copy section — tagline, mission statement,
 * newsletter intro.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function nano_customize_register_copy( $wp_customize ) {
	$defaults = nano_copy_defaults();

	$wp_customize->add_section(
		'nano_copy',
		array(
			'title'       => __( 'Homepage copy', 'nano' ),
			'priority'    => 31,
			'description' => __( 'The fixed editorial texts on the home page. In the multi-line fields, each new line becomes a line break on desktop (phones rewrap naturally); leave an empty line between sentences for a break on every screen.', 'nano' ),
		)
	);

	$controls = array(
		'nano_copy_tagline'    => array( __( 'Hero tagline', 'nano' ), __( 'The statement inside the bracket under the hero video.', 'nano' ) ),
		'nano_copy_mission'    => array( __( 'Mission statement', 'nano' ), __( 'The statement inside the Mission bracket.', 'nano' ) ),
		'nano_copy_newsletter' => array( __( 'Newsletter intro', 'nano' ), __( 'The short paragraph beside the sign-up form.', 'nano' ) ),
	);
	foreach ( $controls as $id => $c ) {
		$wp_customize->add_setting(
			$id,
			array(
				'default'           => $defaults[ $id ],
				'sanitize_callback' => 'sanitize_textarea_field',
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			$id,
			array(
				'label'       => $c[0],
				'description' => $c[1],
				'section'     => 'nano_copy',
				'type'        => 'textarea',
			)
		);
	}
}
add_action( 'customize_register', 'nano_customize_register_copy' );

/**
 * Social platforms available in the footer CONTACT column. Labels are fixed;
 * each gets a URL setting in the Customizer and only set ones render.
 *
 * @return array<string,string> setting id => label.
 */
function nano_social_platforms() {
	return array(
		'nano_social_instagram' => 'Instagram',
		'nano_social_linkedin'  => 'LinkedIn',
		'nano_social_x'         => 'X',
	);
}

/**
 * Register the Social Links section — one URL field per platform.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function nano_customize_register_social( $wp_customize ) {
	$wp_customize->add_section(
		'nano_social',
		array(
			'title'       => __( 'Contact & Social Links', 'nano' ),
			'priority'    => 32,
			'description' => __( 'Shown in the footer CONTACT column. Leave a social field empty to hide that platform.', 'nano' ),
		)
	);
	$wp_customize->add_setting(
		'nano_contact_email',
		array(
			'default'           => 'hello@example.mit.edu',
			'sanitize_callback' => 'sanitize_email',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'nano_contact_email',
		array(
			'label'       => __( 'Contact email', 'nano' ),
			'description' => __( 'The "Email" link in the footer. Leave empty to hide it.', 'nano' ),
			'section'     => 'nano_social',
			'type'        => 'email',
		)
	);
	foreach ( nano_social_platforms() as $id => $label ) {
		$wp_customize->add_setting(
			$id,
			array(
				'default'           => '',
				'sanitize_callback' => 'esc_url_raw',
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			$id,
			array(
				/* translators: %s: platform name. */
				'label'   => sprintf( __( '%s URL', 'nano' ), $label ),
				'section' => 'nano_social',
				'type'    => 'url',
			)
		);
	}
}
add_action( 'customize_register', 'nano_customize_register_social' );

if ( ! function_exists( 'nano_social_links' ) ) {
	/**
	 * The social links an editor has set, in platform order.
	 *
	 * @return array<int,array{label:string,url:string}>
	 */
	function nano_social_links() {
		$links = array();
		foreach ( nano_social_platforms() as $id => $label ) {
			$url = trim( (string) get_theme_mod( $id, '' ) );
			if ( '' !== $url ) {
				$links[] = array(
					'label' => $label,
					'url'   => $url,
				);
			}
		}
		return $links;
	}
}

if ( ! function_exists( 'nano_copy_html' ) ) {
	/**
	 * Resolve an editorial-copy setting to escaped HTML: newlines become
	 * desktop-only breaks (<br class="nano-br-d">), blank lines hard breaks.
	 *
	 * @param string $setting Setting id (see nano_copy_defaults()).
	 * @return string Safe HTML.
	 */
	function nano_copy_html( $setting ) {
		$defaults = nano_copy_defaults();
		$raw      = (string) get_theme_mod( $setting, isset( $defaults[ $setting ] ) ? $defaults[ $setting ] : '' );
		$blocks   = preg_split( '/\n\s*\n/', trim( $raw ) );
		$out      = array();
		foreach ( $blocks as $block ) {
			$lines = array_map( 'esc_html', array_map( 'trim', explode( "\n", $block ) ) );
			$out[] = implode( ' <br class="nano-br-d">', $lines );
		}
		return implode( '<br>', $out );
	}
}


if ( ! function_exists( 'nano_hero_media' ) ) {
	/**
	 * Resolve the hero video + poster URLs — the Customizer picks if set, else the
	 * bundled theme files.
	 *
	 * @return array{video:string,poster:string}
	 */
	function nano_hero_media() {
		$uri = get_stylesheet_directory_uri();
		$dir = get_stylesheet_directory();

		// Bundled-file URL with a filemtime cache-buster, so replacing the file
		// (same name) still busts browser caches — otherwise the old clip lingers.
		$bundled = function ( $rel ) use ( $uri, $dir ) {
			$url  = $uri . $rel;
			$path = $dir . $rel;
			return file_exists( $path ) ? add_query_arg( 'v', filemtime( $path ), $url ) : $url;
		};

		$video_id  = (int) get_theme_mod( 'nano_hero_video' );
		$video_url = $video_id ? wp_get_attachment_url( $video_id ) : '';
		if ( ! $video_url ) {
			$video_url = $bundled( '/assets/video/hero.mp4' );
		}

		$poster_id  = (int) get_theme_mod( 'nano_hero_poster' );
		$poster_url = $poster_id ? wp_get_attachment_image_url( $poster_id, 'full' ) : '';
		if ( ! $poster_url ) {
			$poster_url = $bundled( '/assets/video/hero-poster.jpg' );
		}

		return array(
			'video'  => $video_url,
			'poster' => $poster_url,
		);
	}
}
