<?php
/**
 * Facilities grid block — a row-of-three grid of facility tiles. The images are
 * blank placeholders for now (real photos drop in later); each has a caption
 * below. Captions are a simple placeholder list — swap them (or wire to a field)
 * when the real facilities and images are ready.
 *
 * @package Nano\ImaginationHubCore
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner content (unused).
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

// CMS-managed: `facility` posts (title = caption, featured image = tile,
// nano_url = link), ordered by menu_order then title. The first three fill
// the LEFT column, the rest the right (the grid flows column-first).
// Falls back to the bundled placeholder set until facilities are entered.
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
$facilities = array();
if ( $nano_query->have_posts() ) {
	foreach ( $nano_query->posts as $nano_post ) {
		$facilities[] = array(
			'caption'  => get_the_title( $nano_post ),
			'thumb_id' => (int) get_post_thumbnail_id( $nano_post ),
			'img'      => '',
			'url'      => function_exists( 'nano_field' ) ? (string) nano_field( 'nano_url', $nano_post->ID ) : '',
		);
	}
	wp_reset_postdata();
}

// Bundled placeholders, used only while no facility posts exist.
$nano_placeholders = array(
	array( 'caption' => 'MIT.nano Fabrication clean room', 'img' => 'cleanroom.jpg',        'url' => 'https://nanousers.mit.edu/fabnano' ),
	array( 'caption' => 'MIT.nano Characterization',       'img' => 'characterization.jpg', 'url' => 'https://nanousers.mit.edu/characterizenano' ),
	array( 'caption' => 'MIT.nano Immersion Lab',          'img' => 'immersion-lab.jpg',    'url' => 'https://nanousers.mit.edu/immersion-lab' ),
	array( 'caption' => 'ACT Cube',                        'img' => 'act-cube.jpg',         'url' => 'https://act.mit.edu/facilities/act-cube/' ),
	array( 'caption' => 'Barthos Theater',                 'img' => 'theater.jpg',          'url' => 'https://listart.mit.edu/visit' ),
	array( 'caption' => 'Mars Lab Fabrication workshop',   'img' => 'fabrication.jpg',      'url' => 'https://act.mit.edu/facilities/equipment/' ),
);
if ( empty( $facilities ) ) {
	$facilities = $nano_placeholders;
}

$wrapper = get_block_wrapper_attributes( array( 'class' => 'nano-facilities' ) );
?>
<ul <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?> role="list">
	<?php foreach ( $facilities as $f ) : ?>
		<li class="nano-facility">
			<a class="nano-facility__link" href="<?php echo esc_url( $f['url'] ); ?>">
				<span class="nano-facility__media">
					<?php // Image is decorative; the caption below is the accessible label. ?>
					<?php if ( ! empty( $f['thumb_id'] ) ) : ?>
						<?php echo wp_get_attachment_image( $f['thumb_id'], 'large', false, array( 'class' => 'nano-media nano-media--image', 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php elseif ( ! empty( $f['img'] ) ) : ?>
						<img class="nano-media nano-media--image" src="<?php echo esc_url( plugins_url( 'img/' . $f['img'], __FILE__ ) ); ?>" alt="" loading="lazy" decoding="async" />
					<?php endif; ?>
				</span>
				<span class="nano-facility__caption"><?php echo esc_html( $f['caption'] ); ?></span>
			</a>
		</li>
	<?php endforeach; ?>
</ul>
<?php
