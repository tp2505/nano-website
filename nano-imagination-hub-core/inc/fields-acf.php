<?php
/**
 * ACF field groups for News and Initiative, defined in PHP so they are
 * version-controlled and load with no manual admin/export step.
 *
 * IMPORTANT — the isolation boundary the project requires:
 * Templates and blocks must NEVER call get_field() directly. They go through
 * nano_field() / nano_media() (theme: inc/fields.php), which read ACF when it
 * is active and fall back to raw post meta otherwise. That means if CampusPress
 * disallows ACF, deleting this file + this plugin's dependency on it is enough —
 * the seeded/native post meta keeps resolving through the same accessor. The
 * meta keys below (nano_date, nano_media_type, nano_image, nano_video,
 * nano_poster, nano_descriptor, nano_announcement_poster, …) are the contract
 * shared by ACF, the seed script, and the accessor. Display order is NOT meta:
 * person / initiative / facility order by WordPress's native menu_order.
 *
 * @package Nano\ImaginationHubCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Also expose a Local JSON save point in the theme, so that if an editor edits
 * a field group through the ACF admin UI it is exported into the theme as JSON
 * (version-controllable) rather than living only in the database.
 */
function nano_acf_json_save_point( $path ) {
	$theme_path = get_stylesheet_directory() . '/acf-json';
	return is_dir( $theme_path ) ? $theme_path : $path;
}
add_filter( 'acf/settings/save_json', 'nano_acf_json_save_point' );

function nano_acf_json_load_point( $paths ) {
	$theme_path = get_stylesheet_directory() . '/acf-json';
	if ( is_dir( $theme_path ) ) {
		$paths[] = $theme_path;
	}
	return $paths;
}
add_filter( 'acf/settings/load_json', 'nano_acf_json_load_point' );

/**
 * Register the field groups. Runs only when ACF is present.
 */
function nano_register_acf_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	// Shared media sub-fields (image OR video), reused by both post types.
	$media_fields = function ( $prefix ) {
		return array(
			array(
				'key'           => "field_{$prefix}_media_type",
				'label'         => 'Media type',
				'name'          => 'nano_media_type',
				'type'          => 'button_group',
				'choices'       => array(
					'image' => 'Image',
					'video' => 'Video',
				),
				'default_value' => 'image',
				'instructions'  => 'Tiles can be a still image or a looping muted video.',
			),
			array(
				'key'               => "field_{$prefix}_image",
				'label'             => 'Image',
				'name'              => 'nano_image',
				'type'              => 'image',
				'return_format'     => 'id',
				'preview_size'      => 'medium',
				'library'           => 'all',
				'conditional_logic' => array(
					array(
						array(
							'field'    => "field_{$prefix}_media_type",
							'operator' => '==',
							'value'    => 'image',
						),
					),
				),
			),
			array(
				'key'               => "field_{$prefix}_video",
				'label'             => 'Video (MP4)',
				'name'              => 'nano_video',
				'type'              => 'file',
				'return_format'     => 'id',
				'mime_types'        => 'mp4,webm',
				'conditional_logic' => array(
					array(
						array(
							'field'    => "field_{$prefix}_media_type",
							'operator' => '==',
							'value'    => 'video',
						),
					),
				),
			),
			array(
				'key'               => "field_{$prefix}_poster",
				'label'             => 'Video poster (still)',
				'name'              => 'nano_poster',
				'type'              => 'image',
				'return_format'     => 'id',
				'instructions'      => 'Shown before the video plays / as a fallback.',
				'conditional_logic' => array(
					array(
						array(
							'field'    => "field_{$prefix}_media_type",
							'operator' => '==',
							'value'    => 'video',
						),
					),
				),
			),
		);
	};

	// Shared reference fields (Initiative link + manual Related + People), reused
	// by News and Event. Keys are prefixed per group; field names stay identical
	// so the accessor reads them the same way.
	$ref_fields = function ( $prefix ) {
		return array(
			array(
				'key'           => "field_{$prefix}_initiative",
				'label'         => 'Initiative',
				'name'          => 'nano_initiative',
				'type'          => 'post_object',
				'post_type'     => array( 'initiative' ),
				'return_format' => 'id',
				'ui'            => 1,
				'instructions'  => 'Which Initiative this belongs to. Leave empty for umbrella-level “General” news — it appears on the homepage and in the Archive, but on no Initiative page.',
			),
			array(
				'key'                  => "field_{$prefix}_related",
				'label'                => 'Related content',
				'name'                 => 'nano_related',
				'type'                 => 'relationship',
				'post_type'            => array( 'event', 'news', 'class' ),
				'filters'              => array( 'search', 'post_type' ),
				'return_format'        => 'id',
				'instructions'         => 'Hand-picked Events / News / Classes for the Related section (shows nothing if empty). Links are two-way: adding one here also adds this item on the other side.',
				// Two-way: saving a link here writes the reverse link into the
				// other post's nano_related (whichever of the three per-type
				// fields it carries). ACF ≥ 6.2.
				'bidirectional'        => 1,
				'bidirectional_target' => array( 'field_news_ref_related', 'field_event_ref_related', 'field_nano_class_related' ),
			),
			array(
				'key'           => "field_{$prefix}_people",
				'label'         => 'People',
				'name'          => 'nano_people',
				'type'          => 'relationship',
				'post_type'     => array( 'person' ),
				'filters'       => array( 'search' ),
				'return_format' => 'id',
				'instructions'  => 'Linked artists / people for the Related section (manual).',
			),
		);
	};

	// News.
	acf_add_local_field_group(
		array(
			'key'                   => 'group_nano_news',
			'title'                 => 'News details',
			'location'              => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'news',
					),
				),
			),
			'menu_order'            => 0,
			'position'              => 'side',
			'fields'                => array_merge(
				array(
					array(
						'key'            => 'field_nano_news_date',
						'label'          => 'Display date',
						'name'           => 'nano_date',
						'type'           => 'date_picker',
						'display_format' => 'd.m.Y',
						'return_format'  => 'Ymd',
						'first_day'      => 1,
						'instructions'   => 'Shown on the card; also used to sort newest-first.',
						'required'       => 1,
					),
				),
				$media_fields( 'news' ),
				$ref_fields( 'news_ref' )
			),
		)
	);

	// Event.
	acf_add_local_field_group(
		array(
			'key'        => 'group_nano_event',
			'title'      => 'Event details',
			'location'   => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'event',
					),
				),
			),
			'menu_order' => 0,
			'position'   => 'normal',
			'fields'     => array_merge(
				array(
					array(
						'key'            => 'field_nano_event_date',
						'label'          => 'Date',
						'name'           => 'nano_date',
						'type'           => 'date_picker',
						'display_format' => 'd.m.Y',
						'return_format'  => 'Ymd',
						'first_day'      => 1,
						'instructions'   => 'Shown on the card; also used to sort.',
					),
					array(
						'key'           => 'field_nano_event_announcement',
						'label'         => 'Announcement poster',
						'name'          => 'nano_announcement_poster',
						'type'          => 'image',
						'return_format' => 'id',
						'preview_size'  => 'medium',
						'library'       => 'all',
						'instructions'  => 'The announcement artwork (e.g. the Letter-format flyer). Shown whole — never cropped — below the date on the event page. Separate from the Featured image, which stays the card thumbnail in listings. Leave empty to show nothing.',
					),
					array(
						'key'          => 'field_nano_event_description',
						'label'        => 'Description',
						'name'         => 'nano_description',
						'type'         => 'wysiwyg',
						'tabs'         => 'visual',
						'media_upload' => 0,
						'instructions' => 'Body of the event page.',
					),
					array(
						'key'          => 'field_nano_event_gallery',
						'label'        => 'Gallery',
						'name'         => 'nano_gallery',
						'type'         => 'repeater',
						'layout'       => 'block',
						'button_label' => 'Add media',
						'instructions' => 'Photos and videos, shown two-up. Videos play on click.',
						'sub_fields'   => array(
							array(
								'key'           => 'field_nano_gallery_media',
								'label'         => 'Image or video',
								'name'          => 'media',
								'type'          => 'file',
								'return_format' => 'id',
								'mime_types'    => 'jpg,jpeg,png,gif,webp,mp4,webm',
								'required'      => 1,
								'instructions'  => 'Upload an image or an MP4 / WebM video.',
							),
							array(
								'key'           => 'field_nano_gallery_poster',
								'label'         => 'Video poster (still)',
								'name'          => 'poster',
								'type'          => 'image',
								'return_format' => 'id',
								'preview_size'  => 'medium',
								'instructions'  => 'For videos: the still shown before playback. Leave empty for images.',
							),
							array(
								'key'          => 'field_nano_gallery_caption',
								'label'        => 'Caption',
								'name'         => 'caption',
								'type'         => 'text',
								'instructions' => 'Optional one-sentence caption shown beneath this image or video.',
							),
						),
					),
				),
				$ref_fields( 'event_ref' )
			),
		)
	);

	// Class (a Pedagogies course). Only the term is required; every other field
	// is optional and its template renders nothing when empty.
	acf_add_local_field_group(
		array(
			'key'        => 'group_nano_class',
			'title'      => 'Class details',
			'location'   => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'class',
					),
				),
			),
			'menu_order' => 0,
			'position'   => 'normal',
			'fields'     => array(
				array(
					'key'          => 'field_nano_class_term',
					'label'        => 'Term',
					'name'         => 'nano_term',
					'type'         => 'text',
					'required'     => 1,
					'placeholder'  => 'Fall 2026',
					'instructions' => 'The load-bearing field: drives current-vs-past ordering. Use a consistent “Season YYYY” format, e.g. “Fall 2026” or “Spring 2027”.',
				),
				array(
					'key'          => 'field_nano_class_department',
					'label'        => 'Department',
					'name'         => 'nano_department',
					'type'         => 'select',
					'allow_null'   => 1,
					'ui'           => 1,
					'choices'      => array(
						'Art, Culture & Technology'    => 'Art, Culture & Technology',
						'Architecture'                 => 'Architecture',
						'Media Arts & Sciences'        => 'Media Arts & Sciences',
						'Materials Science & Engineering' => 'Materials Science & Engineering',
						'Mechanical Engineering'       => 'Mechanical Engineering',
						'Comparative Media Studies'    => 'Comparative Media Studies',
					),
					'instructions' => 'Optional. Shown in the meta line when set.',
				),
				array(
					'key'           => 'field_nano_class_instructor',
					'label'         => 'Instructor',
					'name'          => 'nano_instructor',
					'type'          => 'post_object',
					'post_type'     => array( 'person' ),
					'return_format' => 'id',
					'ui'            => 1,
					'allow_null'    => 1,
					'instructions'  => 'Optional. Links to the instructor’s People page.',
				),
				array(
					'key'           => 'field_nano_class_ta',
					'label'         => 'Teaching Assistant',
					'name'          => 'nano_ta',
					'type'          => 'post_object',
					'post_type'     => array( 'person' ),
					'return_format' => 'id',
					'ui'            => 1,
					'allow_null'    => 1,
					'instructions'  => 'Optional. Links to the TA’s People page.',
				),
				array(
					'key'          => 'field_nano_class_level',
					'label'        => 'Level',
					'name'         => 'nano_level',
					'type'         => 'select',
					'allow_null'   => 1,
					'ui'           => 1,
					'choices'      => array(
						'Introductory' => 'Introductory',
						'Advanced'     => 'Advanced',
						'Graduate'     => 'Graduate',
					),
					'instructions' => 'Optional.',
				),
				array(
					'key'          => 'field_nano_class_credits',
					'label'        => 'Credits',
					'name'         => 'nano_credits',
					'type'         => 'text',
					'placeholder'  => '12 units',
					'instructions' => 'Optional, free text, e.g. “12 units”.',
				),
				array(
					'key'          => 'field_nano_class_description',
					'label'        => 'Description',
					'name'         => 'nano_description',
					'type'         => 'wysiwyg',
					'tabs'         => 'visual',
					'media_upload' => 0,
					'instructions' => 'Optional. Body of the class page; may describe level/credits in prose.',
				),
				array(
					'key'           => 'field_nano_class_syllabus_file',
					'label'         => 'Syllabus (PDF)',
					'name'          => 'nano_syllabus_file',
					'type'          => 'file',
					'return_format' => 'array',
					'mime_types'    => 'pdf',
					'instructions'  => 'Optional. Upload the syllabus as a PDF — the usual way to share it. The page links straight to this file.',
				),
				array(
					'key'          => 'field_nano_class_syllabus_link',
					'label'        => 'Syllabus (external link)',
					'name'         => 'nano_syllabus_link',
					'type'         => 'link',
					'return_format' => 'array',
					'instructions' => 'Optional alternative to the PDF: link to a syllabus published elsewhere. Used only if no PDF is uploaded — leave empty if there is no public page.',
				),
				array(
					'key'                  => 'field_nano_class_related',
					'label'                => 'Related content',
					'name'                 => 'nano_related',
					'type'                 => 'relationship',
					'post_type'            => array( 'event', 'news', 'class' ),
					'filters'              => array( 'search', 'post_type' ),
					'return_format'        => 'id',
					'instructions'         => 'Hand-picked Events / News / Classes for the Related section (shows nothing if empty). Links are two-way: adding one here also adds this class on the other side.',
					'bidirectional'        => 1,
					'bidirectional_target' => array( 'field_news_ref_related', 'field_event_ref_related', 'field_nano_class_related' ),
				),
				array(
					'key'           => 'field_nano_class_people',
					'label'         => 'People',
					'name'          => 'nano_people',
					'type'          => 'relationship',
					'post_type'     => array( 'person' ),
					'filters'       => array( 'search' ),
					'return_format' => 'id',
					'instructions'  => 'Additional linked people for the Related section (beyond instructor / TA).',
				),
			),
		)
	);

	// Initiative.
	acf_add_local_field_group(
		array(
			'key'                   => 'group_nano_initiative',
			'title'                 => 'Initiative details',
			'location'              => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'initiative',
					),
				),
			),
			'menu_order'            => 0,
			'position'              => 'normal',
			'fields'                => array_merge(
				array(
					array(
						'key'          => 'field_nano_init_descriptor',
						'label'        => 'Descriptor',
						'name'         => 'nano_descriptor',
						'type'         => 'text',
						'instructions' => 'Short line, e.g. "projects and residencies".',
					),
					// Display order moved to WordPress's own menu_order (the
					// Order box under Page Attributes), the same mechanism
					// Facilities and People use. Existing nano_order values are
					// migrated once by inc/upgrade.php.
				),
				array(
						array(
							'key'          => 'field_nano_init_intro',
							'label'        => 'Intro text',
							'name'         => 'nano_intro',
							'type'         => 'textarea',
							'rows'         => 6,
							'instructions' => 'Lead paragraph shown on the initiative page, beneath the title.',
						),
					),
					$media_fields( 'init' )
			),
		)
	);

	// Support us — sponsor / partner logos. A repeater so an editor can add any
	// number of logos in wp-admin; the Support-us page block lays them out in
	// rows. Bound to that page specifically when it exists, otherwise to any page
	// so the box is still reachable before the page is seeded.
	$support = get_page_by_path( 'support-us' );
	$support_location = $support
		? array(
			array(
				array(
					'param'    => 'page',
					'operator' => '==',
					'value'    => (string) $support->ID,
				),
			),
		)
		: array(
			array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => 'page',
				),
			),
		);
	acf_add_local_field_group(
		array(
			'key'        => 'group_nano_sponsors',
			'title'      => 'Sponsors',
			'location'   => $support_location,
			'menu_order' => 0,
			'position'   => 'normal',
			'fields'     => array(
				array(
					'key'          => 'field_nano_sponsors',
					'label'        => 'Sponsor logos',
					'name'         => 'nano_sponsors',
					'type'         => 'repeater',
					'layout'       => 'table',
					'button_label' => 'Add sponsor',
					'instructions' => 'Logos shown at the bottom of the Support us page. Add as many as you like — they flow into rows. A plain logo on a transparent or white background works best.',
					'sub_fields'   => array(
						array(
							'key'           => 'field_nano_sponsor_logo',
							'label'         => 'Logo',
							'name'          => 'logo',
							'type'          => 'image',
							'return_format' => 'id',
							'preview_size'  => 'medium',
							'library'       => 'all',
							'required'      => 1,
						),
						array(
							'key'          => 'field_nano_sponsor_name',
							'label'        => 'Name',
							'name'         => 'name',
							'type'         => 'text',
							'instructions' => 'Used as the logo’s alt text (and link title).',
						),
						array(
							'key'           => 'field_nano_sponsor_url',
							'label'         => 'Website',
							'name'          => 'url',
							'type'          => 'url',
							'instructions'  => 'Optional — makes the logo link out.',
						),
					),
				),
			),
		)
	);

	// Person. Group is the people_group taxonomy (its own box).
	acf_add_local_field_group(
		array(
			'key'        => 'group_nano_person',
			'title'      => 'Person details',
			'location'   => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'person',
					),
				),
			),
			'menu_order' => 0,
			'position'   => 'normal',
			'fields'     => array(
				array(
					'key'          => 'field_nano_person_role',
					'label'        => 'Role / title',
					'name'         => 'nano_role',
					'type'         => 'text',
					'instructions' => 'e.g. "Director" or "Advisory Board Member".',
				),
				array(
					'key'           => 'field_nano_person_photo',
					'label'         => 'Photo',
					'name'          => 'nano_photo',
					'type'          => 'image',
					'return_format' => 'id',
					'preview_size'  => 'medium',
					'library'       => 'all',
					'instructions'  => 'Portrait photo (optional).',
				),
				array(
					'key'          => 'field_nano_person_bio',
					'label'        => 'Short bio',
					'name'         => 'nano_bio',
					'type'         => 'textarea',
					'rows'         => 3,
					'instructions' => 'A sentence or two.',
				),
			),
		)
	);

	// Facility tiles — the caption is the post title, the image the featured
	// image; this adds the link target. Same meta-key contract as the rest:
	// templates read it through nano_field( 'nano_url' ).
	acf_add_local_field_group(
		array(
			'key'      => 'group_nano_facility',
			'title'    => 'Facility details',
			'location' => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'facility',
					),
				),
			),
			'fields'   => array(
				array(
					'key'          => 'field_nano_facility_url',
					'label'        => 'Link URL',
					'name'         => 'nano_url',
					'type'         => 'url',
					'instructions' => 'Where the tile links (e.g. the facility\'s page on mitnano.mit.edu or act.mit.edu).',
				),
			),
		)
	);
}
add_action( 'acf/init', 'nano_register_acf_fields' );
