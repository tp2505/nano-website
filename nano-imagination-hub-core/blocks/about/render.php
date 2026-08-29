<?php
/**
 * About page block. Three sections — Mission, History, People — each laid out as
 * a small uppercase label on the left (like the homepage Mission) with the
 * content on the right. A banner image sits above Mission and above History.
 *
 * Mission/History text come from page meta (nano_mission, nano_history); the
 * banner images from nano_about_img1 / nano_about_img2 (attachment IDs, blank =
 * placeholder). People are grouped from the people_group taxonomy — one section
 * per group, so new people/groups flow in automatically.
 *
 * @package Nano\ImaginationHubCore
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner content (unused).
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

$post_id = get_the_ID();

$intro   = function_exists( 'nano_field' ) ? nano_field( 'nano_about_intro', $post_id ) : '';
$mission = function_exists( 'nano_field' ) ? nano_field( 'nano_mission', $post_id ) : '';
$history = function_exists( 'nano_field' ) ? nano_field( 'nano_history', $post_id ) : '';
$img1    = function_exists( 'nano_field' ) ? (int) nano_field( 'nano_about_img1', $post_id ) : 0;
$img2    = function_exists( 'nano_field' ) ? (int) nano_field( 'nano_about_img2', $post_id ) : 0;

/**
 * Echo a banner image (or a blank placeholder box).
 *
 * @param int $id Attachment ID.
 */
$nano_about_image = function ( $id ) {
	if ( $id ) {
		echo '<figure class="nano-about__image">' . wp_get_attachment_image( (int) $id, 'large', false, array( 'class' => 'nano-media nano-media--image' ) ) . '</figure>'; // phpcs:ignore WordPress.Security.EscapeOutput
	} else {
		echo '<figure class="nano-about__image nano-about__image--empty" aria-hidden="true"></figure>';
	}
};

$groups = get_terms(
	array(
		'taxonomy'   => 'people_group',
		'hide_empty' => true,
		'orderby'    => 'term_id',
		'order'      => 'ASC',
	)
);

$wrapper = get_block_wrapper_attributes( array( 'class' => 'nano-about' ) );
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>

	<?php if ( $intro ) : ?>
		<div class="nano-about__frame">
			<?php // LOGO EXPERIMENT: same construction as the homepage motto — svg bracket lines + the designer's Asset-14 triple wave with a purple dot. ?>
			<svg class="nano-about__lines" aria-hidden="true">
				<line x1="0" y1="0" x2="0" y2="100%" stroke="currentColor"/>
				<line x1="0" y1="100%" x2="100%" y2="100%" stroke="currentColor"/>
				<g class="nano-waveg">
					<g fill="none" stroke="currentColor">
						<path vector-effect="non-scaling-stroke" d="M-67.14,-12.27c.22-.45.56-1.12,1-1.93C-63.76,-18.55,-59.29,-26.71,-53.68,-26.68c.24,0,1.18.02,2.27.47,6.05,2.48,6.52,13.94,6.63,16.41.04.99.03,1.78.02,2.18"/>
						<path vector-effect="non-scaling-stroke" d="M-22.39,-2.96c-.22.45-.56,1.12-1,1.93-2.38,4.34-6.85,12.51-12.45,12.47-.24,0-1.18-.02-2.27-.47-6.05-2.48-6.52-13.94-6.63-16.41-.04-.99-.03-1.78-.02-2.18"/>
						<path vector-effect="non-scaling-stroke" d="M-22.38,-3.03c.22-.45.56-1.12,1-1.93,2.38-4.34,6.85-12.51,12.45-12.47.24,0,1.18.02,2.27.47,6.05,2.48,6.52,13.94,6.63,16.41.04.99.03,1.78.02,2.18L0,5.82"/>
					</g>
					<circle cx="-67.14" cy="-12.24" r="5.12" fill="var(--wp--preset--color--accent-purple)" stroke="none"/>
				</g>
			</svg>
			<p class="nano-about__intro"><?php echo esc_html( $intro ); ?></p>
		</div>
	<?php endif; ?>

	<?php $nano_about_image( $img1 ); ?>

	<section class="nano-about__row">
		<h2 class="nano-label">Mission</h2>
		<div class="nano-about__body">
			<?php echo wp_kses_post( wpautop( $mission ) ); ?>
		</div>
	</section>

	<?php $nano_about_image( $img2 ); ?>

	<section class="nano-about__row">
		<h2 class="nano-label">History</h2>
		<div class="nano-about__body">
			<?php echo wp_kses_post( wpautop( $history ) ); ?>
		</div>
	</section>

	<?php if ( ! empty( $groups ) && ! is_wp_error( $groups ) ) : ?>
		<section class="nano-about__row nano-about__row--people">
			<h2 class="nano-label">People</h2>
			<div class="nano-about__body nano-people">
				<?php
				// Accent dot per group — Leadership (first) red, Advisory Board
				// (second) cyan/blue, cycling for any further groups.
				$nano_dot_colors = array( 'nano-dot--red', 'nano-dot--cyan', 'nano-dot--yellow', 'nano-dot--green', 'nano-dot--purple' );
				$nano_gi         = 0;
				foreach ( $groups as $group ) :
					$q = new WP_Query(
						array(
							'post_type'      => 'person',
							'posts_per_page' => -1,
							'post_status'    => 'publish',
							'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
								array(
									'taxonomy' => 'people_group',
									'field'    => 'term_id',
									'terms'    => $group->term_id,
								),
							),
						)
					);
					if ( ! $q->have_posts() ) {
						continue;
					}
					// Curated order first (Page Attributes → Order), the rest
					// alphabetically by last name — see nano_sort_people().
					if ( function_exists( 'nano_sort_people' ) ) {
						$q->posts = nano_sort_people( $q->posts );
					}
					$dot_class = $nano_dot_colors[ $nano_gi % count( $nano_dot_colors ) ];
					++$nano_gi;
					?>
					<div class="nano-people__group">
						<div class="nano-grouphead">
							<h3 class="nano-people__group-title"><?php echo esc_html( $group->name ); ?></h3>
							<?php // LOGO EXPERIMENT: Asset-13 underline + S-curl, scaled to the group title; the dot keeps the group's accent colour. ?>
							<svg class="nano-head__lines" aria-hidden="true">
								<line x1="0" y1="100%" x2="100%" y2="100%" stroke="currentColor"/>
								<g class="nano-grouphead__curlg <?php echo esc_attr( $dot_class ); ?>">
									<g fill="none" stroke="currentColor">
										<path vector-effect="non-scaling-stroke" d="M3.58,-22.48c-.45-.21-1.14-.53-1.96-.96-4.39-2.28-12.66-6.57-12.75-12.17,0-.24,0-1.18.42-2.28,2.34-6.11,13.79-6.84,16.26-6.99.99-.06,1.77-.07,2.18-.07"/>
										<path vector-effect="non-scaling-stroke" d="M3.65,-22.47c.45.21,1.14.53,1.96.96,4.39,2.28,12.66,6.57,12.75,12.17,0,.24,0,1.18-.42,2.28-2.34,6.11-13.79,6.84-16.26,6.99-.99.06-1.77.07-2.18.07L-6.26,0"/>
									</g>
									<circle cx="9.19" cy="-45" r="5.12" fill="var(--nano-dot, currentColor)" stroke="none"/>
								</g>
							</svg>
						</div>
						<ul class="nano-people__grid" role="list">
							<?php
							while ( $q->have_posts() ) :
								$q->the_post();
								$role     = function_exists( 'nano_field' ) ? nano_field( 'nano_role' ) : '';
								$bio      = function_exists( 'nano_field' ) ? nano_field( 'nano_bio' ) : '';
								$photo_id = function_exists( 'nano_field' ) ? (int) nano_field( 'nano_photo' ) : 0;
								if ( ! $photo_id ) {
									$photo_id = (int) get_post_thumbnail_id();
								}
								?>
								<li class="nano-person">
									<a class="nano-person__link" href="<?php the_permalink(); ?>">
										<span class="nano-person__photo">
											<?php
											if ( $photo_id ) {
												// Photo is decorative here — the name beside it is the link's label.
												echo wp_get_attachment_image( $photo_id, 'medium', false, array( 'class' => 'nano-media nano-media--image', 'loading' => 'lazy', 'alt' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
											}
											?>
										</span>
										<h4 class="nano-person__name"><?php the_title(); ?></h4>
										<?php if ( $role ) : ?>
											<p class="nano-person__role"><?php echo esc_html( $role ); ?></p>
										<?php endif; ?>
									</a>
									<?php if ( $bio ) : ?>
										<p class="nano-person__bio"><?php echo esc_html( $bio ); ?></p>
									<?php endif; ?>
								</li>
							<?php endwhile; ?>
						</ul>
					</div>
					<?php
					wp_reset_postdata();
				endforeach;
				?>
			</div>
		</section>
	<?php endif; ?>

</div>
<?php
