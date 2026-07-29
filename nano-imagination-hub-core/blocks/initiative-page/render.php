<?php
/**
 * Initiative page block — for each of the four Initiatives:
 *   1. the title + description (the initiative's own text);
 *   2. EVENTS — Events whose `initiative` = this one;
 *   3. NEWS   — News whose `initiative` = this one.
 * EVENTS and NEWS use the About-page treatment: a small uppercase label offset
 * to the left with the content on the right. Each section ends with a "View all"
 * link to the Archive page (Resources).
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

$intro = function_exists( 'nano_field' ) ? nano_field( 'nano_intro', $post_id ) : '';

// "View all" deep-links into the Archive, pre-filtered by Type + this Initiative.
$archive_page = get_page_by_path( 'archive' );
$archive_url  = $archive_page ? get_permalink( $archive_page ) : home_url( '/archive/' );
$init_slug    = get_post_field( 'post_name', $post_id );
$events_archive = add_query_arg(
	array(
		'ftype' => 'events',
		'finit' => $init_slug,
		'fyear' => 'all',
	),
	$archive_url
);
$news_archive = add_query_arg(
	array(
		'ftype' => 'news',
		'finit' => $init_slug,
		'fyear' => 'all',
	),
	$archive_url
);

// Pedagogies is the one Initiative that also hosts Classes. Its "View all"
// targets the Archive filtered to Type=Classes (Initiative is auto-constrained
// to Pedagogies there, so no initiative param is needed).
$is_pedagogies   = ( 'pedagogies' === $init_slug );
$current_classes = ( $is_pedagogies && function_exists( 'nano_current_classes' ) ) ? nano_current_classes() : array();
$classes_archive = add_query_arg( array( 'ftype' => 'classes' ), $archive_url );

/**
 * Posts of a type that reference this initiative, newest first.
 *
 * @param string $type Post type.
 * @param int    $init Initiative ID.
 * @return WP_Query
 */
function nano_initiative_children( $type, $init ) {
	return new WP_Query(
		array(
			'post_type'      => $type,
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_key'       => 'nano_date', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'orderby'        => array(
				'meta_value' => 'DESC',
				'date'       => 'DESC',
			),
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => 'nano_initiative',
					'value' => (string) $init,
				),
			),
		)
	);
}

/**
 * A "View all" link to the Archive.
 *
 * @param string $url Archive URL.
 */
function nano_view_all_link( $url ) {
	?>
	<a class="nano-viewall" href="<?php echo esc_url( $url ); ?>">View all</a>
	<?php
}

$events = nano_initiative_children( 'event', $post_id );
$news   = nano_initiative_children( 'news', $post_id );

$wrapper = get_block_wrapper_attributes( array( 'class' => 'nano-news nano-news--grid nano-initiative-page' ) );
?>
<section <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<header class="nano-section-head">
		<div class="nano-newshead">
			<h1 class="nano-section-head__title"><?php the_title(); ?></h1>
			<?php // LOGO EXPERIMENT: secondary-page titles carry no bracket — the initiative's corner curl lives on the image-caption brackets instead (see inc/news-card.php). ?>
		</div>
	</header>

	<?php if ( $intro ) : ?>
		<div class="nano-initiative-page__intro">
			<?php echo wp_kses_post( wpautop( $intro ) ); ?>
		</div>
	<?php endif; ?>

	<?php if ( $is_pedagogies && $current_classes ) : ?>
		<div class="nano-about__row nano-initiative-page__section">
			<h2 class="nano-label">Classes</h2>
			<div class="nano-about__body">
				<ul class="nano-cards nano-cards--2col nano-cards--desc" role="list">
					<?php
					foreach ( $current_classes as $c ) {
						nano_render_card( $c['id'] );
					}
					?>
				</ul>
				<?php nano_view_all_link( $classes_archive ); ?>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( $events->have_posts() ) : ?>
		<div class="nano-about__row nano-initiative-page__section">
			<h2 class="nano-label">Events</h2>
			<div class="nano-about__body">
				<ul class="nano-cards nano-cards--2col nano-cards--feature" role="list">
					<?php
					while ( $events->have_posts() ) :
						$events->the_post();
						nano_render_card( get_the_ID() );
					endwhile;
					wp_reset_postdata();
					?>
				</ul>
				<?php nano_view_all_link( $events_archive ); ?>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( $news->have_posts() ) : ?>
		<div class="nano-about__row nano-initiative-page__section">
			<h2 class="nano-label">News</h2>
			<div class="nano-about__body">
				<ul class="nano-cards nano-cards--2col" role="list">
					<?php
					while ( $news->have_posts() ) :
						$news->the_post();
						nano_render_card( get_the_ID() );
					endwhile;
					wp_reset_postdata();
					?>
				</ul>
				<?php nano_view_all_link( $news_archive ); ?>
			</div>
		</div>
	<?php endif; ?>
</section>
<?php
