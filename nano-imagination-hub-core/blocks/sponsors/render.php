<?php
/**
 * Sponsors block — the partner / sponsor logos at the bottom of the Support-us
 * page, laid out in rows. Logos come from the page's nano_sponsors repeater
 * (edited in wp-admin), read through nano_sponsor_rows() so the layout is fully
 * CMS-driven. Renders nothing until at least one logo is added.
 *
 * @package Nano\ImaginationHubCore
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner content (unused).
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

$post_id = get_the_ID();
$rows    = function_exists( 'nano_sponsor_rows' ) ? nano_sponsor_rows( $post_id ) : array();
if ( ! $rows ) {
	return;
}

$wrapper = get_block_wrapper_attributes( array( 'class' => 'nano-sponsors-wrap' ) );
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<?php nano_render_sponsors( $rows ); // Shared renderer (inc/sponsors.php) — same markup on Support-us, events, news. ?>
</div>
<?php
