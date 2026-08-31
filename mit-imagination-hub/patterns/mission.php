<?php
/**
 * Title: Mission
 * Slug: nano/mission
 * Categories: nano
 * Inserter: true
 * Description: MISSION label at the left and the mission statement set inside the editorial bracket (red dot + diagonal + vertical line at screen-left, bottom line under the text).
 *
 * @package Nano\ImaginationHub
 */
?>
<!-- wp:group {"tagName":"section","className":"nano-mission","align":"full","layout":{"type":"default"}} -->
<section class="wp-block-group alignfull nano-mission" id="mission">
	<!-- wp:heading {"level":2,"className":"nano-label"} -->
	<h2 class="nano-label">MISSION</h2>
	<!-- /wp:heading -->

	<!-- wp:html -->
	<div class="nano-mission__frame">
		<?php // LOGO EXPERIMENT: bracket lines + designer's Asset-14 wave (red dot), same construction as the hero tagline — all svg strokes so the joins are exact. Edges are clipped sagged quadratics, never axis-aligned <line>s (see hero.php and the bracket-weight note in assets/css/app.css). ?>
		<svg class="nano-mission__lines" aria-hidden="true">
			<defs>
				<path id="nano-mission-vsag" d="M0,0 Q0.1,1500 0,3000"/>
				<path id="nano-mission-hsag" d="M0,0 Q1500,0.1 3000,0"/>
				<clipPath id="nano-mission-vclip"><rect x="-5" y="0" width="10" height="100%"/></clipPath>
				<clipPath id="nano-mission-hclip"><rect x="0" y="-10%" width="100%" height="120%"/></clipPath>
			</defs>
			<use href="#nano-mission-vsag" clip-path="url(#nano-mission-vclip)" fill="none" stroke="currentColor"/>
			<use href="#nano-mission-hsag" y="100%" clip-path="url(#nano-mission-hclip)" fill="none" stroke="currentColor"/>
			<g class="nano-waveg">
				<g fill="none" stroke="currentColor">
					<path vector-effect="non-scaling-stroke" d="M-67.14,-12.27c.22-.45.56-1.12,1-1.93C-63.76,-18.55,-59.29,-26.71,-53.68,-26.68c.24,0,1.18.02,2.27.47,6.05,2.48,6.52,13.94,6.63,16.41.04.99.03,1.78.02,2.18"/>
					<path vector-effect="non-scaling-stroke" d="M-22.39,-2.96c-.22.45-.56,1.12-1,1.93-2.38,4.34-6.85,12.51-12.45,12.47-.24,0-1.18-.02-2.27-.47-6.05-2.48-6.52-13.94-6.63-16.41-.04-.99-.03-1.78-.02-2.18"/>
					<path vector-effect="non-scaling-stroke" d="M-22.38,-3.03c.22-.45.56-1.12,1-1.93,2.38-4.34,6.85-12.51,12.45-12.47.24,0,1.18.02,2.27.47,6.05,2.48,6.52,13.94,6.63,16.41.04.99.03,1.78.02,2.18L0,5.82"/>
				</g>
				<circle cx="-67.14" cy="-12.24" r="5.12" fill="var(--wp--preset--color--accent-red)" stroke="none"/>
			</g>
		</svg>
		<p class="nano-mission__statement"><?php echo function_exists( 'nano_copy_html' ) ? nano_copy_html( 'nano_copy_mission' ) // phpcs:ignore WordPress.Security.EscapeOutput -- helper escapes
			: 'At Imagination Hub, we push beyond <br class="nano-br-d">the boundary of known — where art <br class="nano-br-d">and science, culture and technology <br class="nano-br-d">become a single, unified force.'; ?></p>
	</div>
	<!-- /wp:html -->
</section>
<!-- /wp:group -->
