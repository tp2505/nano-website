<?php
/**
 * Title: Footer copyright
 * Slug: nano/copyright
 * Categories: nano
 * Inserter: false
 * Description: The footer copyright line, under the Institute name spelled out, with Privacy Policy and Accessibility links (MIT branding requirements). The site name comes from Settings → General (Site Title) and the year is the current year — no hardcoded name.
 *
 * @package Nano\ImaginationHub
 */

$nano_name    = get_bloginfo( 'name' );
$nano_year    = wp_date( 'Y' );
$nano_privacy = get_privacy_policy_url();
?>
<!-- wp:paragraph {"className":"nano-footer__copyright","fontSize":"small"} -->
<p class="nano-footer__copyright has-small-font-size"><span class="nano-footer__institute">Massachusetts Institute of Technology</span><br>&copy;&nbsp;<?php echo esc_html( $nano_year . ' ' . $nano_name ); ?><span class="nano-footer__sep">&nbsp;&nbsp;|&nbsp;&nbsp;</span><span class="nano-footer__legal"><a href="<?php echo esc_url( $nano_privacy ? $nano_privacy : '#' ); ?>">Privacy Policy</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="https://accessibility.mit.edu/">Accessibility</a></span></p>
<!-- /wp:paragraph -->
