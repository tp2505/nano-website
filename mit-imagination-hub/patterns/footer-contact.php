<?php
/**
 * Title: Footer contact links
 * Slug: nano/footer-contact
 * Categories: nano
 * Inserter: false
 * Description: The footer CONTACT column list — the contact email and whichever
 * social platforms have a URL set, all from Appearance → Customize →
 * Contact & Social Links. Empty fields are hidden. Text links only, matching
 * the other columns.
 *
 * @package Nano\ImaginationHub
 */

$nano_socials = function_exists( 'nano_social_links' ) ? nano_social_links() : array();
$nano_email   = trim( (string) get_theme_mod( 'nano_contact_email', 'hello@example.mit.edu' ) );
?>
<!-- wp:html -->
<ul class="wp-block-list nano-footer__list" role="list">
	<?php if ( '' !== $nano_email ) : ?>
		<li><a href="mailto:<?php echo esc_attr( $nano_email ); ?>">Email</a></li>
	<?php endif; ?>
	<?php foreach ( $nano_socials as $nano_social ) : ?>
		<li><a href="<?php echo esc_url( $nano_social['url'] ); ?>"><?php echo esc_html( $nano_social['label'] ); ?></a></li>
	<?php endforeach; ?>
</ul>
<!-- /wp:html -->
