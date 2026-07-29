<?php
/**
 * Related block — the manual Related section for single News and single Event.
 * Two purely-manual parts, each shown only if its field has picks:
 *   - Related content: the nano_related items (Events / News) as small cards.
 *   - People: the nano_people entries as name + small photo, linking to each.
 * Renders nothing at all when both are empty. Uses the About-page label/offset
 * treatment for the "Related" and "People" sub-headings.
 *
 * @package Nano\ImaginationHubCore
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner content (unused).
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

$post_id = get_the_ID();

$related = function_exists( 'nano_field' ) ? nano_field( 'nano_related', $post_id ) : array();
$people  = function_exists( 'nano_field' ) ? nano_field( 'nano_people', $post_id ) : array();
$related = is_array( $related ) ? array_filter( array_map( 'intval', $related ) ) : array();
$people  = is_array( $people ) ? array_filter( array_map( 'intval', $people ) ) : array();

// Keep only published items.
$related = array_values(
	array_filter(
		$related,
		function ( $id ) {
			return 'publish' === get_post_status( $id );
		}
	)
);
$people = array_values(
	array_filter(
		$people,
		function ( $id ) {
			return 'publish' === get_post_status( $id );
		}
	)
);

if ( empty( $related ) && empty( $people ) ) {
	return;
}

$wrapper = get_block_wrapper_attributes( array( 'class' => 'nano-related' ) );
?>
<section <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<?php if ( $related ) : ?>
		<div class="nano-about__row nano-related__row">
			<h2 class="nano-label">Related</h2>
			<div class="nano-about__body">
				<ul class="nano-cards nano-cards--2col" role="list">
					<?php
					foreach ( $related as $rid ) {
						nano_render_card( $rid );
					}
					?>
				</ul>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( $people ) : ?>
		<div class="nano-about__row nano-related__row">
			<h2 class="nano-label">People</h2>
			<div class="nano-about__body">
				<ul class="nano-people-links" role="list">
					<?php
					foreach ( $people as $pid ) :
						$photo = (int) get_post_thumbnail_id( $pid );
						if ( ! $photo && function_exists( 'nano_field' ) ) {
							$photo = (int) nano_field( 'nano_photo', $pid );
						}
						$role = function_exists( 'nano_field' ) ? nano_field( 'nano_role', $pid ) : '';
						?>
						<li class="nano-people-links__item">
							<a class="nano-people-links__link" href="<?php echo esc_url( get_permalink( $pid ) ); ?>">
								<span class="nano-people-links__photo">
									<?php
									if ( $photo ) {
										echo wp_get_attachment_image( $photo, 'thumbnail', false, array( 'class' => 'nano-media nano-media--image', 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
									}
									?>
								</span>
								<span class="nano-people-links__text">
									<span class="nano-people-links__name"><?php echo esc_html( get_the_title( $pid ) ); ?></span>
									<?php if ( $role ) : ?>
										<span class="nano-people-links__role"><?php echo esc_html( $role ); ?></span>
									<?php endif; ?>
								</span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	<?php endif; ?>
</section>
<?php
