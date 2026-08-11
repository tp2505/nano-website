<?php
/**
 * Title: Return home
 * Slug: nano/return-home
 * Inserter: no
 * Description: The 404 body line with a correctly-rooted link home (the 404 template itself is static HTML and cannot call home_url()).
 *
 * @package Nano\ImaginationHub
 */

?>
<!-- wp:paragraph -->
<p><?php echo wp_kses_post( sprintf( /* translators: %s: home URL */ __( 'The page you were looking for isn’t here. <a href="%s">Return home</a>.', 'nano' ), esc_url( home_url( '/' ) ) ) ); ?></p>
<!-- /wp:paragraph -->
