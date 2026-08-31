<?php
/**
 * Title: Newsletter
 * Slug: nano/newsletter
 * Categories: nano
 * Inserter: true
 * Description: Newsletter heading with accent dot, intro line, email field, consent checkbox and a SIGN UP button.
 *
 * @package Nano\ImaginationHub
 */

$nano_status = isset( $_GET['newsletter'] ) ? sanitize_key( wp_unslash( $_GET['newsletter'] ) ) : '';
$nano_action = esc_url( admin_url( 'admin-post.php' ) );
?>
<!-- wp:group {"tagName":"section","className":"nano-newsletter","align":"wide","layout":{"type":"default"}} -->
<section class="wp-block-group alignwide nano-newsletter" id="newsletter">
	<!-- wp:html -->
	<div class="nano-newsletterhead">
		<h2 class="nano-section-title nano-newsletter__title">Newsletter</h2>
		<?php // LOGO EXPERIMENT: designer's Asset-13 underline — elastic line (svg, keeps the current length) with the S-curl rising from its right end to the dot. The underline is a clipped sagged quadratic, never an axis-aligned <line> (see hero.php and the bracket-weight note in assets/css/app.css). ?>
		<svg class="nano-head__lines" aria-hidden="true">
			<defs>
				<path id="nano-news-hsag" d="M0,0 Q1500,0.1 3000,0"/>
				<clipPath id="nano-news-hclip"><rect x="0" y="-10%" width="100%" height="120%"/></clipPath>
			</defs>
			<use href="#nano-news-hsag" y="100%" clip-path="url(#nano-news-hclip)" fill="none" stroke="currentColor"/>

			<g class="nano-head__curlg">
			<g fill="none" stroke="currentColor">
				<path vector-effect="non-scaling-stroke" d="M3.58,-22.48c-.45-.21-1.14-.53-1.96-.96-4.39-2.28-12.66-6.57-12.75-12.17,0-.24,0-1.18.42-2.28,2.34-6.11,13.79-6.84,16.26-6.99.99-.06,1.77-.07,2.18-.07"/>
				<path vector-effect="non-scaling-stroke" d="M3.65,-22.47c.45.21,1.14.53,1.96.96,4.39,2.28,12.66,6.57,12.75,12.17,0,.24,0,1.18-.42,2.28-2.34,6.11-13.79,6.84-16.26,6.99-.99.06-1.77.07-2.18.07L-6.26,0"/>
			</g>
			<circle cx="9.19" cy="-45" r="5.12" fill="var(--wp--preset--color--accent-cyan)" stroke="none"/>
		</g>
		</svg>
	</div>
	<!-- /wp:html -->

	<!-- wp:html -->
	<div class="nano-newsletter__body">
		<p class="nano-newsletter__intro"><?php echo function_exists( 'nano_copy_html' ) ? nano_copy_html( 'nano_copy_newsletter' ) // phpcs:ignore WordPress.Security.EscapeOutput -- helper escapes
		: 'Receive unique and comprehensive insight into our activities and be among the first to know about our upcoming events.'; ?></p>

		<form class="nano-newsletter__form" method="post" action="<?php echo $nano_action; // phpcs:ignore WordPress.Security.EscapeOutput ?>">
			<input type="hidden" name="action" value="nano_newsletter">
			<?php wp_nonce_field( 'nano_newsletter', 'nano_newsletter_nonce' ); ?>

			<label class="screen-reader-text" for="nano-email">Email address</label>
			<input class="nano-newsletter__input" type="email" id="nano-email" name="nano_email" placeholder="your email" required>

			<label class="nano-newsletter__consent">
				<input type="checkbox" name="nano_consent" value="1" required>
				<span>I am interested in receiving newsletter(s) which may include news, updates and promotional offers. I understand that with just one click, I can unsubscribe at any time.</span>
			</label>

			<button class="nano-newsletter__button" type="submit">SIGN UP</button>

			<?php if ( 'success' === $nano_status ) : ?>
				<p class="nano-newsletter__msg nano-newsletter__msg--ok" role="status">Thanks — you're signed up.</p>
			<?php elseif ( 'error' === $nano_status ) : ?>
				<p class="nano-newsletter__msg nano-newsletter__msg--err" role="alert">Please enter a valid email and accept the terms.</p>
			<?php endif; ?>
		</form>
	</div>
	<!-- /wp:html -->
</section>
<!-- /wp:group -->
