<?php
/**
 * Class page block — the single-class body: title, a structured meta line
 * (Term · Department · Level · Credits), Instructor / TA links to their People
 * pages, the description, and a syllabus link/file. Every field except the term
 * is optional and renders nothing when empty — no empty labels, no dangling
 * separators. The manual Related section is a separate reusable block.
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

$instructor_id = (int) $field( 'nano_instructor' );
$ta_id         = (int) $field( 'nano_ta' );
$instructor_id = ( $instructor_id && 'publish' === get_post_status( $instructor_id ) ) ? $instructor_id : 0;
$ta_id         = ( $ta_id && 'publish' === get_post_status( $ta_id ) ) ? $ta_id : 0;

$description = $field( 'nano_description' );
if ( '' === trim( (string) $description ) ) {
	$description = get_the_content();
}

$syllabus = function_exists( 'nano_class_syllabus' ) ? nano_class_syllabus( $post_id ) : null;

$wrapper = get_block_wrapper_attributes( array( 'class' => 'nano-news nano-news--grid nano-class-page' ) );
?>
<section <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<header class="nano-section-head">
		<div class="nano-newshead">
			<h1 class="nano-section-head__title"><?php the_title(); ?></h1>
			<?php // LOGO EXPERIMENT: designer's Asset-6 underline — elastic line (svg, keeps the current length) whose right end curls up into the dot. ?>
			<svg class="nano-head__lines" aria-hidden="true">
				<line x1="0" y1="100%" x2="100%" y2="100%" stroke="currentColor"/>
			
				<g class="nano-head__curlg nano-head__curlg--sm">
				<path d="M-2.49,-22.37c.45.22,1.12.56,1.93,1,4.34,2.38,12.51,6.85,12.47,12.45,0,.24-.02,1.18-.47,2.27-2.48,6.05-13.94,6.52-16.41,6.63-.99.04-1.78.03-2.18.02" fill="none" stroke="currentColor" vector-effect="non-scaling-stroke"/>
				<circle cx="-1.76" cy="-22.35" r="5.12" fill="var(--wp--preset--color--accent-yellow)" stroke="none"/>
			</g>
			</svg>
		</div>
	</header>

	<?php
	$image_id = (int) get_post_thumbnail_id( $post_id );
	if ( $image_id ) :
		?>
		<figure class="nano-class-page__image">
			<?php echo wp_get_attachment_image( $image_id, 'large', false, array( 'class' => 'nano-media nano-media--image', 'sizes' => '(max-width: 1024px) 100vw, 1024px', 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</figure>
	<?php endif; ?>

	<?php if ( $facts ) : ?>
		<p class="nano-class-page__facts">
			<?php foreach ( $facts as $fact ) : ?>
				<span class="nano-class-page__fact"><?php echo esc_html( $fact ); ?></span>
			<?php endforeach; ?>
		</p>
	<?php endif; ?>

	<?php if ( $instructor_id || $ta_id ) : ?>
		<ul class="nano-class-page__people" role="list">
			<?php if ( $instructor_id ) : ?>
				<li class="nano-class-page__person">
					<span class="nano-class-page__role"><?php esc_html_e( 'Instructor', 'nano' ); ?></span>
					<a href="<?php echo esc_url( get_permalink( $instructor_id ) ); ?>"><?php echo esc_html( get_the_title( $instructor_id ) ); ?></a>
				</li>
			<?php endif; ?>
			<?php if ( $ta_id ) : ?>
				<li class="nano-class-page__person">
					<span class="nano-class-page__role"><?php esc_html_e( 'Teaching Assistant', 'nano' ); ?></span>
					<a href="<?php echo esc_url( get_permalink( $ta_id ) ); ?>"><?php echo esc_html( get_the_title( $ta_id ) ); ?></a>
				</li>
			<?php endif; ?>
		</ul>
	<?php endif; ?>

	<?php if ( $description ) : ?>
		<div class="nano-class-page__body">
			<?php echo wp_kses_post( wpautop( $description ) ); ?>
		</div>
	<?php endif; ?>

	<?php
	if ( $syllabus ) :
		if ( $syllabus['is_file'] ) {
			$bits  = array_filter( array( $syllabus['mime'], $syllabus['size'] ) );
			$label = sprintf(
				/* translators: %s: file type and size, e.g. "PDF, 240 KB". */
				__( 'Syllabus (%s)', 'nano' ),
				implode( ', ', $bits )
			);
			$aria = sprintf(
				/* translators: %s: file type and size, e.g. "PDF, 240 KB". */
				__( 'Download the syllabus (%s)', 'nano' ),
				implode( ', ', $bits )
			);
		} else {
			$label = __( 'Syllabus', 'nano' );
			$aria  = __( 'Open the syllabus (external link)', 'nano' );
		}
		?>
		<p class="nano-class-page__syllabus">
			<a class="nano-class-page__syllabus-link"
				href="<?php echo esc_url( $syllabus['url'] ); ?>"
				aria-label="<?php echo esc_attr( $aria ); ?>"
				<?php echo $syllabus['is_file'] ? '' : 'target="_blank" rel="noopener noreferrer"'; ?>>
				<?php echo esc_html( $label ); ?>
			</a>
		</p>
	<?php endif; ?>
</section>
<?php
