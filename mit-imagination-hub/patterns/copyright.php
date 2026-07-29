<?php
/**
 * Title: Footer copyright
 * Slug: nano/copyright
 * Categories: nano
 * Inserter: false
 * Description: The footer copyright line. The site name comes from Settings → General (Site Title) and the year is the current year — no hardcoded name.
 *
 * @package Nano\ImaginationHub
 */

$nano_name    = get_bloginfo( 'name' );
$nano_year    = wp_date( 'Y' );
$nano_privacy = get_privacy_policy_url();
?>
<!-- wp:paragraph {"className":"nano-footer__copyright","fontSize":"small"} -->
<p class="nano-footer__copyright has-small-font-size">&copy;&nbsp;<?php echo esc_html( $nano_year . ' ' . $nano_name ); ?>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="<?php echo esc_url( $nano_privacy ? $nano_privacy : '#' ); ?>">Privacy Policy</a></p>
<!-- /wp:paragraph -->
