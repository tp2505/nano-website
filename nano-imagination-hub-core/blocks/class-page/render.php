<?php
/**
 * Class page block — the single-class body: title, subtitle, banner media, a
 * structured meta line (Term · Department · Level · Credits), Instructor / TA,
 * the content-editor body, the Documents band (PDFs + the external syllabus
 * link as a "Syllabus" row), and the shared documentation gallery. Every field
 * except the term is optional and renders nothing when empty — no empty
 * labels, no dangling separators. The manual People/Related section is a
 * separate reusable block.
 *
 * @package Nano\ImaginationHubCore
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner content (unused).
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

$post_id = get_the_ID();
if ( ! $post_id ) {
	return;
}

$field = function ( $name ) use ( $post_id ) {
	return function_exists( 'nano_field' ) ? nano_field( $name, $post_id ) : '';
};

$term       = trim( (string) $field( 'nano_term' ) );
$department = trim( (string) $field( 'nano_department' ) );
$level      = trim( (string) $field( 'nano_level' ) );
$credits    = trim( (string) $field( 'nano_credits' ) );

// The structured meta line — only the facts that are filled.
$facts = array_values( array_filter( array( $term, $department, $level, $credits ), 'strlen' ) );

// Instructors — the field is multiple (co-teaching), but legacy values are a
// single scalar ID: normalize either shape to an array of published people.
$instructor_raw = $field( 'nano_instructor' );
$instructor_ids = array_values(
	array_filter(
		array_map( 'intval', is_array( $instructor_raw ) ? $instructor_raw : array( $instructor_raw ) ),
		function ( $id ) {
			return $id && 'publish' === get_post_status( $id );
		}
	)
);
// TA is plain text (one or more names) — no People page, no link. A leftover
// numeric ID (pre-0.7.0 value not yet migrated) resolves to the person's name.
$ta = trim( (string) $field( 'nano_ta' ) );
if ( ctype_digit( $ta ) && 'person' === get_post_type( (int) $ta ) ) {
	$ta = get_the_title( (int) $ta );
}

// (nano_description is card / listing teaser text only — the page body is
// the content editor.)

// Long-form body — the post content editor, rendered below the description
// through the core content pipeline (blocks/formatting work). Optional;
// empty renders nothing. No longer doubles as the description fallback, so
// it can never render twice.
$long_form = trim( (string) get_post_field( 'post_content', $post_id ) );

// Subtitle — its own field (Headline group, after the title in the editor),
// same treatment as events/news. Optional; empty renders nothing.
$subtitle = trim( (string) $field( 'nano_subtitle' ) );

// The external syllabus link renders inside the Documents band (first row,
// labelled "Syllabus") — a syllabus is just another document.
$syllabus = function_exists( 'nano_class_syllabus' ) ? nano_class_syllabus( $post_id ) : null;

// Documentation gallery — the shared two-up component from the event page.
$gallery = function_exists( 'nano_gallery_rows' ) ? nano_gallery_rows( $post_id ) : array();

$wrapper = get_block_wrapper_attributes( array( 'class' => 'nano-news nano-news--grid nano-class-page' ) );
?>
<section <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<header class="nano-section-head">
		<div class="nano-newshead">
			<h1 class="nano-section-head__title"><?php the_title(); ?></h1>
			<?php // LOGO EXPERIMENT: designer's Asset-6 underline — elastic line (svg, keeps the current length) whose right end curls up into the dot. ?>
			<svg class="nano-head__lines" aria-hidden="true">
				<?php echo nano_sag_edge( 'bottom' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			
				<g class="nano-head__curlg nano-head__curlg--sm">
				<path d="M-2.49,-22.37c.45.22,1.12.56,1.93,1,4.34,2.38,12.51,6.85,12.47,12.45,0,.24-.02,1.18-.47,2.27-2.48,6.05-13.94,6.52-16.41,6.63-.99.04-1.78.03-2.18.02" fill="none" stroke="currentColor" vector-effect="non-scaling-stroke"/>
				<circle cx="-1.76" cy="-22.35" r="5.12" fill="var(--wp--preset--color--accent-yellow)" stroke="none"/>
			</g>
			</svg>
		</div>
	</header>

	<?php if ( '' !== $subtitle ) : ?>
		<p class="nano-event-page__subtitle nano-class-page__subtitle"><?php echo esc_html( $subtitle ); ?></p>
	<?php endif; ?>

	<?php
	// Banner — the standard image-or-clip media slot; nano_media() falls back
	// to the featured image when the slot is empty. Renders nothing without
	// either.
	$banner = function_exists( 'nano_media' ) ? nano_media( $post_id ) : array( 'type' => '' );
	if ( ! empty( $banner['type'] ) && function_exists( 'nano_render_media' ) ) :
		?>
		<figure class="nano-class-page__image">
			<?php echo nano_render_media( $banner, array( 'sizes' => '(max-width: 1024px) 100vw, 1024px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</figure>
	<?php endif; ?>

	<?php if ( $facts ) : ?>
		<p class="nano-class-page__facts">
			<?php foreach ( $facts as $fact ) : ?>
				<span class="nano-class-page__fact"><?php echo esc_html( $fact ); ?></span>
			<?php endforeach; ?>
		</p>
	<?php endif; ?>

	<?php if ( $instructor_ids || '' !== $ta ) : ?>
		<ul class="nano-class-page__people" role="list">
			<?php if ( $instructor_ids ) : ?>
				<li class="nano-class-page__person">
					<span class="nano-class-page__role"><?php echo esc_html( _n( 'Instructor', 'Instructors', count( $instructor_ids ), 'nano' ) ); ?></span>
					<?php
					// Comma-separated links — a single instructor renders
					// exactly as before (one link, no separators).
					$nano_links = array_map(
						function ( $id ) {
							return '<a href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html( get_the_title( $id ) ) . '</a>';
						},
						$instructor_ids
					);
					echo implode( ', ', $nano_links ); // phpcs:ignore WordPress.Security.EscapeOutput -- built escaped above
					?>
				</li>
			<?php endif; ?>
			<?php if ( '' !== $ta ) : ?>
				<li class="nano-class-page__person">
					<span class="nano-class-page__role"><?php esc_html_e( 'Teaching Assistant', 'nano' ); ?></span>
					<?php echo esc_html( $ta ); ?>
				</li>
			<?php endif; ?>
		</ul>
	<?php endif; ?>

	<?php if ( '' !== $long_form ) : ?>
		<div class="nano-class-page__content">
			<?php echo apply_filters( 'the_content', $long_form ); // phpcs:ignore WordPress.Security.EscapeOutput -- core content pipeline ?>
		</div>
	<?php endif; ?>

	<?php
	// Documents (PDF attachments) — includes any migrated syllabus PDF.
	// The external syllabus link joins the band as its first row, labelled
	// "Syllabus" (link-only style, no size meta). Renders nothing when both
	// are empty.
	if ( function_exists( 'nano_attachment_rows' ) && function_exists( 'nano_render_attachments' ) ) {
		$doc_rows = nano_attachment_rows( $post_id );
		if ( $syllabus ) {
			array_unshift(
				$doc_rows,
				array(
					'url'   => $syllabus['url'],
					'label' => __( 'Syllabus', 'nano' ),
					'meta'  => __( 'External link', 'nano' ),
				)
			);
		}
		nano_render_attachments( $doc_rows );
	}

	// Documentation gallery — the shared two-up component (same field and
	// rendering as events, click-to-play videos included).
	if ( function_exists( 'nano_render_gallery' ) ) {
		nano_render_gallery( $gallery );
	}
	?>
</section>
<?php
