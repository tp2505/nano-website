<?php
/**
 * Initiatives list block — the four strands as alternating media/text rows.
 *
 * Data-driven: a WP_Query against the `initiative` post type ordered by the
 * nano_order field. The media/text columns alternate left/right down the page.
 *
 * @package Nano\ImaginationHubCore
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner content (unused).
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

$count       = isset( $attributes['count'] ) ? (int) $attributes['count'] : 4;
$nano_helper = function_exists( 'nano_render_media' );

$query = new WP_Query(
	array(
		'post_type'      => 'initiative',
		'posts_per_page' => $count,
		'post_status'    => 'publish',
		'meta_key'       => 'nano_order',
		'orderby'        => array(
			'meta_value_num' => 'ASC',
			'title'          => 'ASC',
		),
	)
);

if ( ! $query->have_posts() ) {
	return;
}

$wrapper = get_block_wrapper_attributes( array( 'class' => 'nano-initiatives' ) );
$i       = 0;

// LOGO EXPERIMENT: designer's corner brackets (Assets 7-10) — one per row
// position, drawn INSIDE the same svg as the elastic bracket lines. Curve
// coordinates are rebased so the join point with the line is the group's
// origin; CSS places the group with a % translate + scale (see
// .nano-initiative__curlg). One svg = one raster pass: iOS rounds separate
// svg elements to different device-pixel phases, which broke the seams.
$nano_curls = array(
	1 => array( // Asset 7: curl at the bottom of the left vertical.
		'd'  => 'M22.49,3.59c-.21.45-.53,1.14-.95,1.96-2.26,4.41-6.5,12.7-12.1,12.82-.24,0-1.18.01-2.28-.4-6.12-2.31-6.91-13.75-7.08-16.22-.07-.99-.08-1.77-.08-2.18L0,-8.23',
		'cx' => '22.49',
		'cy' => '4.32',
	),
	2 => array( // Asset 12: curl on the upper part of the right vertical.
		'd'  => 'M-22.5,20.22c.21.45.53,1.14.95,1.96,2.26,4.41,6.5,12.7,12.1,12.82.24,0,1.18.01,2.28-.4,6.12-2.31,6.91-13.75,7.08-16.22.07-.99.08-1.77.08-2.18L0,8.5',
		'cx' => '-22.49',
		'cy' => '20.95',
	),
	3 => array( // Asset 9: curl hanging from the top line's right end.
		'd'  => 'M4.02,22.49c.45-.21,1.14-.53,1.96-.95,4.4-2.27,12.68-6.53,12.79-12.13,0-.24,0-1.18-.41-2.28-2.32-6.12-13.77-6.88-16.24-7.05-.99-.07-1.77-.08-2.18-.08L-8.34,0',
		'cx' => '4.75',
		'cy' => '22.48',
	),
	4 => array( // Asset 10: curl at the bottom of the right vertical.
		'd'  => 'M-22.49,3.59c.21.45.53,1.14.95,1.96,2.26,4.41,6.5,12.7,12.1,12.82.24,0,1.18.01,2.28-.4,6.12-2.31,6.91-13.75,7.08-16.22.07-.99.08-1.77.08-2.18L0,-8.23',
		'cx' => '-22.49',
		'cy' => '4.32',
	),
);
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<?php
	while ( $query->have_posts() ) :
		$query->the_post();
		$post_id    = get_the_ID();
		$descriptor = function_exists( 'nano_field' ) ? nano_field( 'nano_descriptor', $post_id ) : '';
		$flip       = ( 0 === $i % 2 ) ? '' : ' nano-initiative--flip';
		$pos        = ' nano-initiative--pos' . ( $i + 1 );
		++$i;
		?>
		<article class="nano-initiative<?php echo esc_attr( $flip . $pos ); ?>">
			<a class="nano-initiative__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
				<?php
				if ( $nano_helper ) {
					echo nano_render_media( nano_media( $post_id ), array( 'sizes' => '(max-width: 900px) 100vw, 50vw' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
			</a>
			<div class="nano-initiative__text">
				<?php $nano_curl = $nano_curls[ $i ]; ?>
				<svg class="nano-initiative__lines" aria-hidden="true">
					<line x1="0" y1="0" x2="100%" y2="0" stroke="currentColor"/>
					<?php if ( $flip ) : ?>
						<line x1="100%" y1="0" x2="100%" y2="100%" stroke="currentColor"/>
					<?php else : ?>
						<line x1="0" y1="0" x2="0" y2="100%" stroke="currentColor"/>
					<?php endif; ?>
					<g class="nano-initiative__curlg">
						<path d="<?php echo esc_attr( $nano_curl['d'] ); ?>" fill="none" stroke="currentColor" vector-effect="non-scaling-stroke"/>
						<circle cx="<?php echo esc_attr( $nano_curl['cx'] ); ?>" cy="<?php echo esc_attr( $nano_curl['cy'] ); ?>" r="5.12" fill="currentColor" stroke="none"/>
					</g>
				</svg>
				<h3 class="nano-initiative__name"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
				<?php if ( $descriptor ) : ?>
					<p class="nano-initiative__descriptor"><?php echo esc_html( $descriptor ); ?></p>
				<?php endif; ?>
				<div class="nano-initiative__body">
					<?php the_content(); ?>
				</div>
			</div>
		</article>
	<?php endwhile; ?>
</div>
<?php
wp_reset_postdata();
