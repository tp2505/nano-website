<?php
/**
 * Project page block — the single News item, structured EXACTLY like the
 * single event page (same field order, markup classes, and styling): title →
 * subtitle → participants → date / venue → page image → body → sponsors →
 * People / Related. News and event pages read as the same occasion in two
 * moments — the announcement before, the record after — so when the item is
 * linked to an event (nano_event) every blank field inherits the event's
 * value, and a value entered on the news item overrides it. Two deliberate
 * exceptions: the gallery is event-only (it documents what happened), and
 * the date/venue meta renders the linked event's "when" as a unit (the news
 * item's own nano_date stays the feed's display/sort date).
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

// The linked event, if any (published only) — the inheritance source.
$event_id = function_exists( 'nano_field' ) ? (int) nano_field( 'nano_event', $post_id ) : 0;
if ( $event_id && ( 'event' !== get_post_type( $event_id ) || 'publish' !== get_post_status( $event_id ) ) ) {
	$event_id = 0;
}

/**
 * A field with event fallback: the news item's own value when set, else the
 * linked event's ('' / empty array when neither).
 *
 * @param string $name Field name.
 * @return mixed
 */
$inherit = function ( $name ) use ( $post_id, $event_id ) {
	$own    = function_exists( 'nano_field' ) ? nano_field( $name, $post_id ) : null;
	$is_set = is_array( $own ) ? ! empty( array_filter( $own ) ) : '' !== trim( (string) $own );
	if ( $is_set ) {
		return $own;
	}
	if ( $event_id && function_exists( 'nano_field' ) ) {
		return nano_field( $name, $event_id );
	}
	return is_array( $own ) ? array() : '';
};

$subtitle = trim( (string) $inherit( 'nano_subtitle' ) );

$participants = $inherit( 'nano_people' );
$participants = array_values(
	array_filter(
		array_map( 'intval', is_array( $participants ) ? $participants : array() ),
		function ( $id ) {
			return $id && 'publish' === get_post_status( $id );
		}
	)
);

// The "when" renders as a unit: a linked event's dates and times (the news
// item's own nano_date is the feed's display/sort date, not this line);
// unlinked news uses its own date fields, same formatter, same US format.
$date_out = '';
if ( function_exists( 'nano_event_when' ) ) {
	$date_out = $event_id ? nano_event_when( $event_id ) : nano_event_when( $post_id, true );
}
$venue = trim( (string) $inherit( 'nano_venue' ) );

$page_image = (int) $inherit( 'nano_page_image' );

// Body: the news item's own article text wins; blank and linked, the event's
// full text (description + long-form) shows; the excerpt is the last resort.
$own_body   = trim( (string) get_post_field( 'post_content', $post_id ) );
$ev_desc    = '';
$ev_body    = '';
if ( '' === $own_body && $event_id ) {
	$ev_desc = function_exists( 'nano_field' ) ? trim( (string) nano_field( 'nano_description', $event_id ) ) : '';
	$ev_body = trim( (string) get_post_field( 'post_content', $event_id ) );
}
$excerpt = ( '' === $own_body && '' === $ev_desc && '' === $ev_body ) ? get_the_excerpt() : '';

// Sponsors: the news item's own rows, else the linked event's.
$sponsor_rows = function_exists( 'nano_sponsor_rows' ) ? nano_sponsor_rows( $post_id ) : array();
if ( ! $sponsor_rows && $event_id && function_exists( 'nano_sponsor_rows' ) ) {
	$sponsor_rows = nano_sponsor_rows( $event_id );
}

$wrapper = get_block_wrapper_attributes( array( 'class' => 'nano-news nano-news--grid nano-event-page nano-project-page' ) );
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

	<?php if ( '' !== $subtitle ) : ?>
		<p class="nano-event-page__subtitle"><?php echo esc_html( $subtitle ); ?></p>
	<?php endif; ?>
	<?php if ( $participants ) : ?>
		<p class="nano-event-page__participants">
			<?php
			$nano_names = array_map(
				function ( $id ) {
					return '<a href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html( get_the_title( $id ) ) . '</a>';
				},
				$participants
			);
			echo implode( ', ', $nano_names ); // phpcs:ignore WordPress.Security.EscapeOutput -- built escaped above
			?>
		</p>
	<?php endif; ?>
	<?php if ( $date_out ) : ?>
		<?php if ( $event_id ) : ?>
			<?php // Inherited from the event: the line quietly links to it. ?>
			<p class="nano-event-page__date nano-project-page__eventfacts"><a href="<?php echo esc_url( get_permalink( $event_id ) ); ?>"><?php echo esc_html( $date_out ); ?></a></p>
		<?php else : ?>
			<p class="nano-event-page__date"><?php echo esc_html( $date_out ); ?></p>
		<?php endif; ?>
	<?php endif; ?>
	<?php if ( '' !== $venue ) : ?>
		<p class="nano-event-page__date nano-event-page__venue"><?php echo esc_html( $venue ); ?></p>
	<?php endif; ?>

	<?php if ( $page_image ) : ?>
		<figure class="nano-event-page__pageimage">
			<?php echo wp_get_attachment_image( $page_image, 'large', false, array( 'class' => 'nano-media nano-media--image', 'sizes' => '(max-width: 781px) 100vw, 52rem', 'loading' => 'eager' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</figure>
	<?php endif; ?>

	<?php
	// Media slot, video only (news has no Vimeo option): a looping clip still
	// plays on the page; the slot's image type is the card thumbnail only.
	$top_media = array( 'type' => '' );
	if ( function_exists( 'nano_media' ) && function_exists( 'nano_render_media' ) ) {
		$top_media = nano_media( $post_id );
		if ( 'video' !== $top_media['type'] ) {
			$top_media = array( 'type' => '' );
		}
	}
	if ( ! empty( $top_media['type'] ) ) :
		?>
		<figure class="nano-event-page__media">
			<?php echo nano_render_media( $top_media, array( 'sizes' => '(max-width: 781px) 100vw, 52rem', 'eager' => true ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</figure>
	<?php endif; ?>

	<?php if ( '' !== $own_body ) : ?>
		<div class="nano-event-page__content nano-project-page__body">
			<?php echo apply_filters( 'the_content', $own_body ); // phpcs:ignore WordPress.Security.EscapeOutput -- core content pipeline ?>
		</div>
	<?php else : ?>
		<?php if ( '' !== $ev_desc ) : ?>
			<div class="nano-event-page__body">
				<?php echo wp_kses_post( wpautop( $ev_desc ) ); ?>
			</div>
		<?php endif; ?>
		<?php if ( '' !== $ev_body ) : ?>
			<div class="nano-event-page__content">
				<?php echo apply_filters( 'the_content', $ev_body ); // phpcs:ignore WordPress.Security.EscapeOutput -- core content pipeline ?>
			</div>
		<?php endif; ?>
		<?php if ( $excerpt ) : ?>
			<div class="nano-event-page__content nano-project-page__body">
				<?php echo wp_kses_post( wpautop( $excerpt ) ); ?>
			</div>
		<?php endif; ?>
	<?php endif; ?>

	<?php
	if ( function_exists( 'nano_render_sponsors' ) ) {
		nano_render_sponsors( $sponsor_rows );
	}
	// No gallery on news — galleries document what happened and stay
	// event-only, even when an event is linked.
	?>
</section>
<?php
