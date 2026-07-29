<?php
/**
 * Sponsors block — the partner / sponsor logos at the bottom of the Support-us
 * page, laid out in rows. Logos come from the page's nano_sponsors repeater
 * (edited in wp-admin), read through nano_sponsor_rows() so the layout is fully
 * CMS-driven. Renders nothing until at least one logo is added.
 *
 * @package Nano\ImaginationHubCore
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner content (unused).
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

$post_id = get_the_ID();
$rows    = function_exists( 'nano_sponsor_rows' ) ? nano_sponsor_rows( $post_id ) : array();
if ( ! $rows ) {
	return;
}

$wrapper = get_block_wrapper_attributes( array( 'class' => 'nano-sponsors' ) );
?>
<section <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<h2 class="nano-label nano-sponsors__label"><?php esc_html_e( 'With the support of', 'nano' ); ?></h2>
	<ul class="nano-sponsors__grid" role="list">
		<?php
		foreach ( $rows as $row ) :
			$logo_id = (int) $row['logo'];
			$name    = trim( (string) $row['name'] );
			$url     = trim( (string) $row['url'] );
			$img     = wp_get_attachment_image(
				$logo_id,
				'medium',
				false,
				array(
					'class'   => 'nano-media nano-media--image',
					'loading' => 'lazy',
					'decoding' => 'async',
					'alt'     => $name, // Empty when unnamed → decorative.
				)
			);
			if ( ! $img ) {
				continue;
			}
			?>
			<li class="nano-sponsor">
				<?php if ( $url ) : ?>
					<a class="nano-sponsor__link" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"<?php echo $name ? ' title="' . esc_attr( $name ) . '"' : ''; ?>>
						<?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</a>
				<?php else : ?>
					<?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
<?php
