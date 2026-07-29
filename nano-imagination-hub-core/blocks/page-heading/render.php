<?php
/**
 * Page heading block — the current page's title, in the exact same markup as the
 * News archive heading (nano-news--grid removes the bracket, so it's the plain
 * top-left title). Used by page.html for the Resources placeholder pages.
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

$wrapper = get_block_wrapper_attributes( array( 'class' => 'nano-news nano-news--grid nano-page-heading' ) );
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<header class="nano-section-head">
		<div class="nano-newshead">
			<h1 class="nano-section-head__title"><?php echo esc_html( get_the_title( $post_id ) ); ?></h1>
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
</div>
<?php
