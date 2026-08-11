<?php
/**
 * Facilities grid block — a row-of-three grid of facility tiles, one per
 * published `facility` post: the post title is the caption, the featured image
 * the tile, and nano_url (ACF) the link target. Ordered by menu_order then
 * title — the first three fill the LEFT column, the rest the right (the grid
 * flows column-first). Renders nothing until facilities are entered, so the
 * page never shows content that can't be managed in wp-admin.
 *
 * @package Nano\ImaginationHubCore
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner content (unused).
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

$nano_query = new WP_Query(
	array(
		'post_type'      => 'facility',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'orderby'        => array(
			'menu_order' => 'ASC',
			'title'      => 'ASC',
		),
	)
);

if ( ! $nano_query->have_posts() ) {
	return;
}

$wrapper = get_block_wrapper_attributes( array( 'class' => 'nano-facilities' ) );
?>
<ul <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?> role="list">
	<?php foreach ( $nano_query->posts as $nano_post ) : ?>
		<?php
		$thumb_id = (int) get_post_thumbnail_id( $nano_post );
		$url      = function_exists( 'nano_field' ) ? (string) nano_field( 'nano_url', $nano_post->ID ) : '';
		$tag      = $url ? 'a' : 'span';
		?>
		<li class="nano-facility">
			<<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput ?> class="nano-facility__link"<?php echo $url ? ' href="' . esc_url( $url ) . '"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
				<span class="nano-facility__media">
					<?php // Image is decorative; the caption below is the accessible label. ?>
					<?php if ( $thumb_id ) : ?>
						<?php echo wp_get_attachment_image( $thumb_id, 'large', false, array( 'class' => 'nano-media nano-media--image', 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php else : ?>
						<span class="nano-media nano-media--empty" aria-hidden="true"></span>
					<?php endif; ?>
				</span>
				<span class="nano-facility__caption"><?php echo esc_html( get_the_title( $nano_post ) ); ?></span>
			</<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
		</li>
	<?php endforeach; ?>
</ul>
<?php
wp_reset_postdata();
