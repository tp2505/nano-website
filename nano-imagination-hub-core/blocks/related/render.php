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

// News with a linked event: the event's people join this People row (event's
// first, deduped) — pulled live from the event record, same position and
// component as on the event page itself, never duplicated onto the news item.
if ( 'news' === get_post_type( $post_id ) && function_exists( 'nano_field' ) ) {
	$nano_event_id = (int) nano_field( 'nano_event', $post_id );
	if ( $nano_event_id && 'event' === get_post_type( $nano_event_id ) && 'publish' === get_post_status( $nano_event_id ) ) {
		$nano_event_people = nano_field( 'nano_people', $nano_event_id );
		$nano_event_people = is_array( $nano_event_people ) ? array_filter( array_map( 'intval', $nano_event_people ) ) : array();
		$people            = array_values( array_unique( array_merge( $nano_event_people, $people ) ) );
	}
}

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
	<?php // People first — directly beneath the page's own content, where they
	// read as belonging to it; Related content last as the most peripheral
	// element. Each row still renders nothing when empty. ?>
	<?php if ( $people && function_exists( 'nano_render_people_links' ) ) : ?>
		<div class="nano-about__row nano-related__row">
			<h2 class="nano-label">People</h2>
			<div class="nano-about__body">
				<?php nano_render_people_links( $people ); ?>
			</div>
		</div>
	<?php endif; ?>

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
</section>
<?php
