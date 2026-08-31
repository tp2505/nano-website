<?php
/**
 * Bracket SVG helpers.
 *
 * @package Nano\ImaginationHubCore
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'nano_sag_edge' ) ) {
	/**
	 * One "straight" bracket edge, drawn as a clipped sagged quadratic — never
	 * an axis-aligned <line>. Axis-aligned strokes take a snapped fast
	 * rasterization path in some engines while curves anti-alias, so a
	 * straight run could read a different weight than the curl it joins; a
	 * 0.05px max sag sends it through the same anti-aliasing pipeline (see
	 * the bracket-weight note in the theme's assets/css/app.css and the
	 * converted patterns, e.g. hero.php). The edge is a long fixed sagged
	 * path clipped to the elastic box by a percentage clip rect and anchored
	 * on its corner via <use>; at the clipped far end the sag leaves a join
	 * short by at most ~0.03px, an order below the anti-aliasing itself.
	 *
	 * IDs are generated per instance — these edges render inside loops
	 * (cards, About groups, initiative rows), and SVG ids must be unique
	 * per document.
	 *
	 * @param string $edge Which box edge: 'top' | 'bottom' | 'left' | 'right'.
	 * @return string SVG markup (a <defs> + <use> pair).
	 */
	function nano_sag_edge( $edge ) {
		static $nano_sag_n = 0;
		$id       = 'nano-sag-' . ++$nano_sag_n;
		$vertical = ( 'left' === $edge || 'right' === $edge );

		$d    = $vertical ? 'M0,0 Q0.1,1500 0,3000' : 'M0,0 Q1500,0.1 3000,0';
		$clip = $vertical
			? '<rect x="-10%" y="0" width="120%" height="100%"/>'
			: '<rect x="0" y="-10%" width="100%" height="120%"/>';

		$pos = '';
		if ( 'bottom' === $edge ) {
			$pos = ' y="100%"';
		} elseif ( 'right' === $edge ) {
			$pos = ' x="100%"';
		}

		return '<defs><path id="' . esc_attr( $id ) . '" d="' . esc_attr( $d ) . '"/>'
			. '<clipPath id="' . esc_attr( $id ) . 'c">' . $clip . '</clipPath></defs>'
			. '<use href="#' . esc_attr( $id ) . '"' . $pos . ' clip-path="url(#' . esc_attr( $id ) . 'c)" fill="none" stroke="currentColor"/>';
	}
}
