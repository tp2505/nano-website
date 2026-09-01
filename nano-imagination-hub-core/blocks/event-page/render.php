<?php
/**
 * Event page block — the single-event body: title, description, and a two-column
 * poster gallery. Gallery items are attachment IDs in nano_gallery (ACF Pro
 * Gallery field, read via nano_gallery_rows()): images render directly; videos
 * render their poster with a play button and swap to a playing <video> on click
 * (assets/js/nano.js → initGalleryVideos). Captions and video posters come from
 * the attachment itself. All lazy-loaded.
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

$description = function_exists( 'nano_field' ) ? nano_field( 'nano_description', $post_id ) : '';
if ( '' === trim( (string) $description ) ) {
	$description = get_the_content();
}

$gallery = function_exists( 'nano_gallery_rows' ) ? nano_gallery_rows( $post_id ) : array();

$date_raw = function_exists( 'nano_field' ) ? nano_field( 'nano_date', $post_id ) : '';
$date_out = '';
if ( $date_raw ) {
	$dt       = DateTime::createFromFormat( 'Ymd', (string) $date_raw );
	$date_out = $dt ? $dt->format( 'F j, Y' ) : (string) $date_raw;
}

$wrapper = get_block_wrapper_attributes( array( 'class' => 'nano-news nano-news--grid nano-event-page' ) );
?>
<section <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<header class="nano-section-head">
		<div class="nano-newshead">
			<h1 class="nano-section-head__title"><?php the_title(); ?></h1>
			<?php // LOGO EXPERIMENT: designer's Asset-6 underline — elastic line (svg, keeps the current length) whose right end curls up into the dot. ?>
			<svg class="nano-head__lines" aria-hidden="true">
				<?php echo nano_sag_edge( 'bottom' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			
				<g class="nano-head__curlg nano-head__curlg--sm">
				<path d="M-2.49,-22.37c.45.22,1.12.56,1.93,1,4.34,2.38,12.51,6.85,12.47,12.45,0,.24-.02,1.18-.47,2.27-2.48,6.05-13.94,6.52-16.41,6.63-.99.04-1.78.03-2.18.02" fill="none" stroke="currentColor" vector-effect="non-scaling-stroke"/>
				<circle cx="-1.76" cy="-22.35" r="5.12" fill="var(--wp--preset--color--accent-yellow)" stroke="none"/>
			</g>
			</svg>
		</div>
	</header>

	<?php if ( $date_out ) : ?>
		<p class="nano-event-page__date"><?php echo esc_html( $date_out ); ?></p>
	<?php endif; ?>

	<?php
	// Top media slot — the standard rule: a still (e.g. the announcement
	// poster, shown whole, never cropped), a short looping clip, or a Vimeo
	// player for long-form video (lecture recordings exceed the upload cap).
	// nano_media()'s image branch falls back to the featured image, which is
	// right for tiles — but here the featured image stays the card thumbnail,
	// so the slot renders only when explicitly filled.
	$top_media = array( 'type' => '' );
	if ( function_exists( 'nano_media' ) && function_exists( 'nano_render_media' ) ) {
		$top_media = nano_media( $post_id );
		if ( 'image' === $top_media['type'] && ! (int) nano_field( 'nano_image', $post_id ) ) {
			$top_media = array( 'type' => '' );
		}
	}
	if ( ! empty( $top_media['type'] ) ) :
		?>
		<figure class="nano-event-page__media">
			<?php echo nano_render_media( $top_media, array( 'sizes' => '(max-width: 781px) 100vw, 52rem', 'eager' => true ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</figure>
	<?php endif; ?>

	<?php if ( $description ) : ?>
		<div class="nano-event-page__body">
			<?php echo wp_kses_post( wpautop( $description ) ); ?>
		</div>
	<?php endif; ?>

	<?php if ( $gallery ) : ?>
		<ul class="nano-gallery" role="list">
			<?php
			foreach ( $gallery as $row ) :
				$att_id = (int) $row['media'];
				if ( ! $att_id ) {
					continue;
				}
				$alt = get_post_meta( $att_id, '_wp_attachment_image_alt', true );
				if ( ! $alt ) {
					$alt = get_the_title( $att_id );
				}
				$caption  = isset( $row['caption'] ) ? trim( (string) $row['caption'] ) : '';
				$is_video = wp_attachment_is( 'video', $att_id );
				?>
				<li class="nano-gallery__item<?php echo $is_video ? ' nano-gallery__item--video' : ''; ?>">
					<figure class="nano-gallery__figure">
						<span class="nano-gallery__media">
							<?php if ( $is_video ) : ?>
								<?php
								$poster_id  = (int) $row['poster'];
								$poster_url = $poster_id ? wp_get_attachment_image_url( $poster_id, 'large' ) : '';
								$video_url  = wp_get_attachment_url( $att_id );
								?>
								<button class="nano-gallery__play" type="button" data-nano-video-src="<?php echo esc_url( $video_url ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: video title */ __( 'Play video: %s', 'nano' ), $alt ) ); ?>">
									<?php if ( $poster_url ) : ?>
										<img class="nano-media nano-media--image" src="<?php echo esc_url( $poster_url ); ?>" alt="<?php echo esc_attr( $alt ); ?>" loading="lazy" decoding="async" />
									<?php else : ?>
										<span class="nano-media nano-media--empty" aria-hidden="true"></span>
									<?php endif; ?>
									<span class="nano-gallery__playicon" aria-hidden="true"></span>
								</button>
							<?php else : ?>
								<?php echo wp_get_attachment_image( $att_id, 'large', false, array( 'class' => 'nano-media nano-media--image', 'sizes' => '(max-width: 781px) 100vw, 50vw', 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<?php endif; ?>
						</span>
						<?php if ( $caption ) : ?>
							<figcaption class="nano-gallery__caption"><?php echo esc_html( $caption ); ?></figcaption>
						<?php endif; ?>
					</figure>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</section>
<?php
