<?php
/**
 * Person page block — a single person: photo on the left, name / role / bio on
 * the right. Target of the People links in the Related section.
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

$role  = function_exists( 'nano_field' ) ? nano_field( 'nano_role', $post_id ) : '';
$bio   = function_exists( 'nano_field' ) ? nano_field( 'nano_bio', $post_id ) : '';
$photo = (int) get_post_thumbnail_id( $post_id );
if ( ! $photo && function_exists( 'nano_field' ) ) {
	$photo = (int) nano_field( 'nano_photo', $post_id );
}

// Everything this person took part in — Events, News and Classes that link to
// them, newest first.
$related = function_exists( 'nano_person_related_content' ) ? nano_person_related_content( $post_id ) : array();

$wrapper = get_block_wrapper_attributes( array( 'class' => 'nano-person-page-block' ) );
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
<article class="nano-person-page">
	<div class="nano-person-page__photo">
		<?php
		if ( $photo ) {
			echo wp_get_attachment_image( $photo, 'large', false, array( 'class' => 'nano-media nano-media--image' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
		?>
	</div>
	<div class="nano-person-page__text">
		<h1 class="nano-person-page__name"><?php the_title(); ?></h1>
		<?php if ( $role ) : ?>
			<p class="nano-person-page__role"><?php echo esc_html( $role ); ?></p>
		<?php endif; ?>
		<?php if ( $bio ) : ?>
			<div class="nano-person-page__bio"><?php echo wp_kses_post( wpautop( $bio ) ); ?></div>
		<?php endif; ?>
	</div>
</article>

<?php if ( $related && function_exists( 'nano_render_card' ) ) : ?>
	<section class="nano-related nano-person-related">
		<div class="nano-about__row nano-related__row">
			<h2 class="nano-label">Related</h2>
			<div class="nano-about__body">
				<ul class="nano-cards nano-cards--sm" role="list">
					<?php
					foreach ( $related as $rid ) {
						nano_render_card( $rid, array( 'show_type' => true, 'static' => true ) );
					}
					?>
				</ul>
			</div>
		</div>
	</section>
<?php endif; ?>
</div>
<?php
