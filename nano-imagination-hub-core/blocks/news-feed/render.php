<?php
/**
 * News feed block — News items newest-first, in one of two layouts:
 *   - "slider" (default): the homepage card row with a "view all" arrow.
 *   - "grid": a paginated vertical grid for the News archive.
 *
 * Data-driven: a WP_Query against the `news` post type, ordered by the nano_date
 * field. The card markup is shared via nano_render_news_card().
 *
 * @package Nano\ImaginationHubCore
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner content (unused).
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

$count    = isset( $attributes['count'] ) ? (int) $attributes['count'] : 4;
$view_all = isset( $attributes['viewAllUrl'] ) ? esc_url( $attributes['viewAllUrl'] ) : '#';
$layout   = ( isset( $attributes['layout'] ) && 'grid' === $attributes['layout'] ) ? 'grid' : 'slider';

$order = array(
	// Sort by the display date (Ymd string sorts correctly), newest first;
	// fall back to publish date for items missing the field.
	'meta_key' => 'nano_date',
	'orderby'  => array(
		'meta_value' => 'DESC',
		'date'       => 'DESC',
	),
);

/* -------------------------------------------------------------------------
 * Grid layout — the News archive.
 * ---------------------------------------------------------------------- */
if ( 'grid' === $layout ) {
	$paged = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
	$per   = $count > 0 ? $count : 12;

	$query = new WP_Query(
		array_merge(
			array(
				'post_type'      => 'news',
				'posts_per_page' => $per,
				'paged'          => $paged,
				'post_status'    => 'publish',
			),
			$order
		)
	);

	if ( ! $query->have_posts() ) {
		return;
	}

	$wrapper = get_block_wrapper_attributes( array( 'class' => 'nano-news nano-news--grid' ) );
	?>
	<section <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
		<header class="nano-section-head">
			<div class="nano-newshead">
				<h1 class="nano-section-head__title"><?php esc_html_e( 'News', 'nano' ); ?></h1>
				<span class="nano-newshead__tail" aria-hidden="true"><span class="nano-dot nano-dot--yellow"></span></span>
			</div>
		</header>

		<ul class="nano-news__grid" role="list">
			<?php
			while ( $query->have_posts() ) :
				$query->the_post();
				nano_render_news_card();
			endwhile;
			?>
		</ul>

		<?php
		$links = paginate_links(
			array(
				'total'     => (int) $query->max_num_pages,
				'current'   => $paged,
				'base'      => trailingslashit( get_post_type_archive_link( 'news' ) ) . '%_%',
				'format'    => 'page/%#%/',
				'prev_text' => '‹',
				'next_text' => '›',
			)
		);
		if ( $links ) {
			echo '<nav class="nano-news__pagination" aria-label="' . esc_attr__( 'News pages', 'nano' ) . '">' . $links . '</nav>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		?>
	</section>
	<?php
	wp_reset_postdata();
	return;
}

/* -------------------------------------------------------------------------
 * Slider layout — the homepage row (default).
 * ---------------------------------------------------------------------- */
$query = new WP_Query(
	array_merge(
		array(
			'post_type'      => 'news',
			'posts_per_page' => $count,
			'post_status'    => 'publish',
		),
		$order
	)
);

if ( ! $query->have_posts() ) {
	return;
}

$wrapper = get_block_wrapper_attributes( array( 'class' => 'nano-news' ) );
?>
<section <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<?php $nano_archive = get_post_type_archive_link( 'news' ); ?>
	<header class="nano-section-head">
		<div class="nano-newshead">
			<h2 class="nano-section-head__title"><a href="<?php echo esc_url( $nano_archive ? $nano_archive : $view_all ); ?>"><?php esc_html_e( 'News', 'nano' ); ?></a></h2>
			<?php // LOGO EXPERIMENT: designer's Asset-13 underline — elastic line (svg, keeps the current length) with the S-curl rising from its right end to the dot. ?>
			<svg class="nano-head__lines" aria-hidden="true">
				<line x1="0" y1="100%" x2="100%" y2="100%" stroke="currentColor"/>
			
				<g class="nano-head__curlg">
				<g fill="none" stroke="currentColor">
					<path vector-effect="non-scaling-stroke" d="M3.58,-22.48c-.45-.21-1.14-.53-1.96-.96-4.39-2.28-12.66-6.57-12.75-12.17,0-.24,0-1.18.42-2.28,2.34-6.11,13.79-6.84,16.26-6.99.99-.06,1.77-.07,2.18-.07"/>
					<path vector-effect="non-scaling-stroke" d="M3.65,-22.47c.45.21,1.14.53,1.96.96,4.39,2.28,12.66,6.57,12.75,12.17,0,.24,0,1.18-.42,2.28-2.34,6.11-13.79,6.84-16.26,6.99-.99.06-1.77.07-2.18.07L-6.26,0"/>
				</g>
				<circle cx="9.19" cy="-45" r="5.12" fill="var(--wp--preset--color--accent-yellow)" stroke="none"/>
			</g>
			</svg>
		</div>
		<button class="nano-arrow" type="button" data-nano-slide aria-label="<?php esc_attr_e( 'Show more news', 'nano' ); ?>"><span class="nano-arrow__head" aria-hidden="true"></span></button>
	</header>

	<div class="nano-news__viewport" data-nano-slider>
	<ul class="nano-news__grid" role="list">
		<?php
		while ( $query->have_posts() ) :
			$query->the_post();
			nano_render_news_card();
		endwhile;
		?>
	</ul>
	</div>
</section>
<?php
wp_reset_postdata();
