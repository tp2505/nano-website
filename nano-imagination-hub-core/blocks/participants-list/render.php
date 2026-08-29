<?php
/**
 * Participants list block — everyone who has taken part in an Event, News item
 * or Class, as a clickable directory: each row is the person's name + title and
 * links to their page (photo, bio, and the content they took part in). Data-
 * driven: the people are the `person` posts referenced by any content through
 * nano_people (see nano_participant_person_ids()).
 *
 * @package Nano\ImaginationHubCore
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner content (unused).
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

$ids = function_exists( 'nano_participant_person_ids' ) ? nano_participant_person_ids() : array();
if ( ! $ids ) {
	return;
}

$people = get_posts(
	array(
		'post_type'      => 'person',
		'post__in'       => $ids,
		'posts_per_page' => -1,
		'post_status'    => 'publish',
	)
);

if ( ! $people ) {
	return;
}

// Curated order first (menu_order, ascending), then everyone without an
// explicit order alphabetically by last name — see nano_sort_people().
$people = nano_sort_people( $people );

$wrapper = get_block_wrapper_attributes( array( 'class' => 'nano-participants' ) );
?>
<ul <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?> role="list">
	<?php
	foreach ( $people as $person ) :
		$role = function_exists( 'nano_field' ) ? nano_field( 'nano_role', $person->ID ) : '';
		?>
		<li class="nano-participant">
			<a class="nano-participant__link" href="<?php echo esc_url( get_permalink( $person->ID ) ); ?>">
				<h3 class="nano-participant__name"><?php echo esc_html( get_the_title( $person->ID ) ); ?></h3>
				<?php if ( $role ) : ?>
					<span class="nano-participant__role"><?php echo esc_html( $role ); ?></span>
				<?php endif; ?>
			</a>
		</li>
	<?php endforeach; ?>
</ul>
<?php
