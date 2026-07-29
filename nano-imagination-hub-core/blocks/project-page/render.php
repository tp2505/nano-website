<?php
/**
 * Project page block — the single News item: title, a single feature image (the
 * item's own media — image, or a video), and the article body. Related items are
 * appended by the separate nano/related block in the template.
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

$media = function_exists( 'nano_media' ) ? nano_media( $post_id ) : array();

// Description: the post body, falling back to the excerpt.
$desc = get_the_content();
if ( '' === trim( wp_strip_all_tags( $desc ) ) ) {
	$desc = get_the_excerpt();
}

$wrapper = get_block_wrapper_attributes( array( 'class' => 'nano-news nano-news--grid nano-project-page' ) );
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

	<?php if ( ! empty( $media['type'] ) && ! empty( $media['url'] ) ) : ?>
		<div class="nano-project-page__feature">
			<?php echo nano_render_media( $media, array( 'sizes' => '(max-width: 781px) 100vw, 66vw' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	<?php endif; ?>

	<?php if ( $desc ) : ?>
		<div class="nano-project-page__body">
			<?php echo wp_kses_post( wpautop( $desc ) ); ?>
		</div>
	<?php endif; ?>
</section>
<?php
