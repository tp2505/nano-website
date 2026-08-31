<?php
/**
 * Title: Hero
 * Slug: nano/hero
 * Categories: nano
 * Inserter: true
 * Description: Full-width muted looping background video (poster-first; plays on desktop via JS, poster-only on phones / data-saver / reduced-motion) with the two-line tagline below it.
 *
 * @package Nano\ImaginationHub
 */

// Hero video + poster come from the Customizer (Appearance → Customize → Hero)
// when set, otherwise the bundled theme files. See inc/customizer.php.
$nano_hero = function_exists( 'nano_hero_media' )
	? nano_hero_media()
	: array(
		'vimeo'  => '',
		'video'  => get_stylesheet_directory_uri() . '/assets/video/hero.mp4',
		'poster' => get_stylesheet_directory_uri() . '/assets/video/hero-poster.jpg',
	);
?>
<!-- wp:group {"tagName":"section","className":"nano-hero","align":"full","layout":{"type":"default"}} -->
<section class="wp-block-group alignfull nano-hero" id="top">
	<!-- wp:html -->
	<div class="nano-hero__media">
		<?php if ( ! empty( $nano_hero['vimeo'] ) ) : ?>
			<?php // Vimeo-hosted background clip: the poster paints instantly and is all that phones / data-saver / reduced-motion visitors ever get; on desktop, assets/js/nano.js builds the chrome-less player iframe (data-nano-vimeo) and fades it in over the poster once playback starts. ?>
			<div class="nano-hero__vimeo" data-nano-vimeo="<?php echo esc_url( $nano_hero['vimeo'] ); ?>" aria-hidden="true">
				<img class="nano-hero__poster" src="<?php echo esc_url( $nano_hero['poster'] ); ?>" alt="" fetchpriority="high" decoding="async" />
			</div>
		<?php else : ?>
			<video class="nano-media nano-media--video" muted loop playsinline preload="none" poster="<?php echo esc_url( $nano_hero['poster'] ); ?>" aria-hidden="true" data-nano-video="hero" data-nano-src="<?php echo esc_url( $nano_hero['video'] ); ?>">
				<source type="video/mp4">
			</video>
		<?php endif; ?>
		<?php // LOGO EXPERIMENT (branch logo-experiment): inline-svg lockup — Asset-15 line bracket (non-scaling 1px strokes, matching all other bracket lines) + Space Grotesk type as outlined paths. White via CSS color. ?>
		<svg class="nano-hero__logo" viewBox="0 0 297.58 178.76" aria-hidden="true">
			<g fill="none" stroke="currentColor" stroke-miterlimit="10" transform="translate(6 6)">
				<path vector-effect="non-scaling-stroke" d="M72.27,17.94 Q72.37,94.8 72.27,171.66"/>
				<path vector-effect="non-scaling-stroke" d="M166.63,171.66 Q119.45,171.76 72.27,171.66"/>
				<path vector-effect="non-scaling-stroke" d="M49.88,14.97c-.22.45-.56,1.12-1,1.93-2.38,4.34-6.85,12.51-12.45,12.47-.24,0-1.18-.02-2.27-.47-6.05-2.48-6.52-13.94-6.63-16.41-.04-.99-.03-1.78-.02-2.18"/>
				<path vector-effect="non-scaling-stroke" d="M49.89,14.91c.22-.45.56-1.12,1-1.93,2.38-4.34,6.85-12.51,12.45-12.47.24,0,1.18.02,2.27.47,6.05,2.48,6.52,13.94,6.63,16.41.04.99.03,1.78.02,2.18"/>
			</g>
			<circle cx="33.49" cy="16.82" r="5.12" fill="currentColor"/>
			<g fill="currentColor">
			<path transform="translate(92.74 79.36) scale(0.034714 -0.034714)" d="M73.0 0.0V700.0H276.0L429.0 72.0H445.0L598.0 700.0H801.0V0.0H696.0V624.0H680.0L528.0 0.0H346.0L194.0 624.0H178.0V0.0ZM947.0 0.0V700.0H1055.0V0.0ZM1368.0 0.0V602.0H1153.0V700.0H1691.0V602.0H1476.0V0.0Z"/>
			<path transform="translate(92.74 118.86) scale(0.034714 -0.034714)" d="M73.0 0.0V700.0H181.0V0.0ZM330.0 0.0V493.0H431.0V435.0H447.0Q461.0 461.0 492.0 481.0Q523.0 501.0 576.0 501.0Q631.0 501.0 664.5 478.5Q698.0 456.0 715.0 421.0H731.0Q748.0 455.0 780.5 478.0Q813.0 501.0 873.0 501.0Q921.0 501.0 958.0 481.5Q995.0 462.0 1017.0 423.5Q1039.0 385.0 1039.0 328.0V0.0H936.0V320.0Q936.0 365.0 911.5 389.5Q887.0 414.0 842.0 414.0Q794.0 414.0 765.0 383.0Q736.0 352.0 736.0 294.0V0.0H633.0V320.0Q633.0 365.0 608.5 389.5Q584.0 414.0 539.0 414.0Q491.0 414.0 462.0 383.0Q433.0 352.0 433.0 294.0V0.0ZM1340.0 -14.0Q1287.0 -14.0 1245.5 4.0Q1204.0 22.0 1179.5 57.0Q1155.0 92.0 1155.0 142.0Q1155.0 193.0 1179.5 226.5Q1204.0 260.0 1246.5 277.0Q1289.0 294.0 1343.0 294.0H1493.0V326.0Q1493.0 369.0 1467.0 395.0Q1441.0 421.0 1387.0 421.0Q1334.0 421.0 1306.0 396.0Q1278.0 371.0 1269.0 331.0L1173.0 362.0Q1185.0 402.0 1211.5 434.5Q1238.0 467.0 1282.0 487.0Q1326.0 507.0 1388.0 507.0Q1484.0 507.0 1539.0 458.0Q1594.0 409.0 1594.0 319.0V116.0Q1594.0 86.0 1622.0 86.0H1664.0V0.0H1587.0Q1552.0 0.0 1530.0 18.0Q1508.0 36.0 1508.0 67.0V70.0H1493.0Q1485.0 55.0 1469.0 35.0Q1453.0 15.0 1422.0 0.5Q1391.0 -14.0 1340.0 -14.0ZM1355.0 71.0Q1417.0 71.0 1455.0 106.5Q1493.0 142.0 1493.0 204.0V214.0H1349.0Q1308.0 214.0 1283.0 196.5Q1258.0 179.0 1258.0 145.0Q1258.0 111.0 1284.0 91.0Q1310.0 71.0 1355.0 71.0ZM1740.0 244.0V259.0Q1740.0 337.0 1771.5 392.0Q1803.0 447.0 1855.0 477.0Q1907.0 507.0 1969.0 507.0Q2041.0 507.0 2079.5 480.0Q2118.0 453.0 2136.0 422.0H2152.0V493.0H2252.0V-101.0Q2252.0 -146.0 2225.5 -173.0Q2199.0 -200.0 2153.0 -200.0H1821.0V-110.0H2121.0Q2150.0 -110.0 2150.0 -80.0V77.0H2134.0Q2123.0 59.0 2103.0 40.0Q2083.0 21.0 2050.5 9.0Q2018.0 -3.0 1969.0 -3.0Q1907.0 -3.0 1854.5 26.5Q1802.0 56.0 1771.0 111.5Q1740.0 167.0 1740.0 244.0ZM1997.0 87.0Q2064.0 87.0 2107.5 129.5Q2151.0 172.0 2151.0 247.0V256.0Q2151.0 333.0 2108.0 374.5Q2065.0 416.0 1997.0 416.0Q1931.0 416.0 1887.5 374.5Q1844.0 333.0 1844.0 256.0V247.0Q1844.0 172.0 1887.5 129.5Q1931.0 87.0 1997.0 87.0ZM2404.0 0.0V493.0H2507.0V0.0ZM2456.0 560.0Q2426.0 560.0 2405.5 579.5Q2385.0 599.0 2385.0 630.0Q2385.0 661.0 2405.5 680.5Q2426.0 700.0 2456.0 700.0Q2487.0 700.0 2507.0 680.5Q2527.0 661.0 2527.0 630.0Q2527.0 599.0 2507.0 579.5Q2487.0 560.0 2456.0 560.0ZM2660.0 0.0V493.0H2761.0V419.0H2777.0Q2791.0 449.0 2827.0 475.0Q2863.0 501.0 2934.0 501.0Q2990.0 501.0 3033.5 476.0Q3077.0 451.0 3102.0 405.5Q3127.0 360.0 3127.0 296.0V0.0H3024.0V288.0Q3024.0 352.0 2992.0 382.5Q2960.0 413.0 2904.0 413.0Q2840.0 413.0 2801.5 371.0Q2763.0 329.0 2763.0 249.0V0.0ZM3428.0 -14.0Q3375.0 -14.0 3333.5 4.0Q3292.0 22.0 3267.5 57.0Q3243.0 92.0 3243.0 142.0Q3243.0 193.0 3267.5 226.5Q3292.0 260.0 3334.5 277.0Q3377.0 294.0 3431.0 294.0H3581.0V326.0Q3581.0 369.0 3555.0 395.0Q3529.0 421.0 3475.0 421.0Q3422.0 421.0 3394.0 396.0Q3366.0 371.0 3357.0 331.0L3261.0 362.0Q3273.0 402.0 3299.5 434.5Q3326.0 467.0 3370.0 487.0Q3414.0 507.0 3476.0 507.0Q3572.0 507.0 3627.0 458.0Q3682.0 409.0 3682.0 319.0V116.0Q3682.0 86.0 3710.0 86.0H3752.0V0.0H3675.0Q3640.0 0.0 3618.0 18.0Q3596.0 36.0 3596.0 67.0V70.0H3581.0Q3573.0 55.0 3557.0 35.0Q3541.0 15.0 3510.0 0.5Q3479.0 -14.0 3428.0 -14.0ZM3443.0 71.0Q3505.0 71.0 3543.0 106.5Q3581.0 142.0 3581.0 204.0V214.0H3437.0Q3396.0 214.0 3371.0 196.5Q3346.0 179.0 3346.0 145.0Q3346.0 111.0 3372.0 91.0Q3398.0 71.0 3443.0 71.0ZM4037.0 0.0Q3992.0 0.0 3965.5 27.0Q3939.0 54.0 3939.0 99.0V406.0H3803.0V493.0H3939.0V656.0H4042.0V493.0H4189.0V406.0H4042.0V117.0Q4042.0 87.0 4070.0 87.0H4173.0V0.0ZM4310.0 0.0V493.0H4413.0V0.0ZM4362.0 560.0Q4332.0 560.0 4311.5 579.5Q4291.0 599.0 4291.0 630.0Q4291.0 661.0 4311.5 680.5Q4332.0 700.0 4362.0 700.0Q4393.0 700.0 4413.0 680.5Q4433.0 661.0 4433.0 630.0Q4433.0 599.0 4413.0 579.5Q4393.0 560.0 4362.0 560.0ZM4797.0 -14.0Q4723.0 -14.0 4665.5 16.5Q4608.0 47.0 4575.0 104.0Q4542.0 161.0 4542.0 239.0V254.0Q4542.0 332.0 4575.0 388.5Q4608.0 445.0 4665.5 476.0Q4723.0 507.0 4797.0 507.0Q4871.0 507.0 4929.0 476.0Q4987.0 445.0 5020.0 388.5Q5053.0 332.0 5053.0 254.0V239.0Q5053.0 161.0 5020.0 104.0Q4987.0 47.0 4929.0 16.5Q4871.0 -14.0 4797.0 -14.0ZM4797.0 78.0Q4865.0 78.0 4907.5 121.5Q4950.0 165.0 4950.0 242.0V251.0Q4950.0 328.0 4908.0 371.5Q4866.0 415.0 4797.0 415.0Q4730.0 415.0 4687.5 371.5Q4645.0 328.0 4645.0 251.0V242.0Q4645.0 165.0 4687.5 121.5Q4730.0 78.0 4797.0 78.0ZM5181.0 0.0V493.0H5282.0V419.0H5298.0Q5312.0 449.0 5348.0 475.0Q5384.0 501.0 5455.0 501.0Q5511.0 501.0 5554.5 476.0Q5598.0 451.0 5623.0 405.5Q5648.0 360.0 5648.0 296.0V0.0H5545.0V288.0Q5545.0 352.0 5513.0 382.5Q5481.0 413.0 5425.0 413.0Q5361.0 413.0 5322.5 371.0Q5284.0 329.0 5284.0 249.0V0.0Z"/>
			<path transform="translate(92.74 160.66) scale(0.034714 -0.034714)" d="M73.0 0.0V700.0H181.0V400.0H475.0V700.0H583.0V0.0H475.0V302.0H181.0V0.0ZM921.0 -9.0Q865.0 -9.0 821.0 16.0Q777.0 41.0 752.0 87.0Q727.0 133.0 727.0 197.0V493.0H830.0V204.0Q830.0 140.0 862.0 109.5Q894.0 79.0 951.0 79.0Q1014.0 79.0 1052.5 121.5Q1091.0 164.0 1091.0 244.0V493.0H1194.0V0.0H1093.0V74.0H1077.0Q1063.0 44.0 1027.0 17.5Q991.0 -9.0 921.0 -9.0ZM1631.0 -14.0Q1559.0 -14.0 1520.5 12.0Q1482.0 38.0 1463.0 70.0H1447.0V0.0H1346.0V700.0H1449.0V426.0H1465.0Q1477.0 446.0 1497.0 464.5Q1517.0 483.0 1550.0 495.0Q1583.0 507.0 1631.0 507.0Q1693.0 507.0 1745.0 477.0Q1797.0 447.0 1828.0 390.0Q1859.0 333.0 1859.0 254.0V239.0Q1859.0 159.0 1827.5 102.5Q1796.0 46.0 1744.5 16.0Q1693.0 -14.0 1631.0 -14.0ZM1601.0 76.0Q1668.0 76.0 1711.5 119.0Q1755.0 162.0 1755.0 242.0V251.0Q1755.0 330.0 1712.0 373.0Q1669.0 416.0 1601.0 416.0Q1535.0 416.0 1491.5 373.0Q1448.0 330.0 1448.0 251.0V242.0Q1448.0 162.0 1491.5 119.0Q1535.0 76.0 1601.0 76.0Z"/>
			</g>
		</svg>
	</div>
	<!-- /wp:html -->

	<!-- wp:group {"className":"nano-hero__tagline","align":"full","layout":{"type":"default"}} -->
	<div class="wp-block-group alignfull nano-hero__tagline">
		<!-- wp:html -->
		<div class="nano-hero__frame">
			<?php // LOGO EXPERIMENT: the bracket's vertical + bottom lines are SVG strokes (not CSS borders) so they rasterize identically to the wave and the join can't drift by a subpixel. ?>
			<?php // LOGO EXPERIMENT: designer's Asset-14 wave + dot (two humps, dot at the far left) drawn INSIDE the same svg as the bracket lines, coordinates rebased so its join with the vertical line is the group origin at the frame's top-left corner (see .nano-waveg). One svg = one raster pass, so the seam can't drift on iOS. ?>
			<?php // The bracket edges are imperceptibly sagged quadratics (0.05px max deviation), never axis-aligned <line>s: axis-aligned strokes take a snapped fast rasterization path in some engines while curves anti-alias, so a straight run could read a different weight than the wave it joins. Percentages can't go in a path, so each edge is a long fixed sagged path clipped to the box by a percentage clip rect; the bottom edge is placed with use y="100%". Each edge starts exactly on its anchor corner (the wave joins the vertical at the origin, exactly); at clipped ends the sag leaves the join short by at most ~0.03px, an order below the anti-aliasing itself. See the bracket-weight note in assets/css/app.css. ?>
			<svg class="nano-hero__lines" aria-hidden="true">
				<defs>
					<path id="nano-hero-vsag" d="M0,0 Q0.1,1500 0,3000"/>
					<path id="nano-hero-hsag" d="M0,0 Q1500,0.1 3000,0"/>
					<clipPath id="nano-hero-vclip"><rect x="-5" y="0" width="10" height="100%"/></clipPath>
					<clipPath id="nano-hero-hclip"><rect x="0" y="-10%" width="100%" height="120%"/></clipPath>
				</defs>
				<use href="#nano-hero-vsag" clip-path="url(#nano-hero-vclip)" fill="none" stroke="currentColor"/>
				<use href="#nano-hero-hsag" y="100%" clip-path="url(#nano-hero-hclip)" fill="none" stroke="currentColor"/>
				<g class="nano-waveg">
					<g fill="none" stroke="currentColor">
						<path vector-effect="non-scaling-stroke" d="M-67.14,-12.27c.22-.45.56-1.12,1-1.93C-63.76,-18.55,-59.29,-26.71,-53.68,-26.68c.24,0,1.18.02,2.27.47,6.05,2.48,6.52,13.94,6.63,16.41.04.99.03,1.78.02,2.18"/>
						<path vector-effect="non-scaling-stroke" d="M-22.39,-2.96c-.22.45-.56,1.12-1,1.93-2.38,4.34-6.85,12.51-12.45,12.47-.24,0-1.18-.02-2.27-.47-6.05-2.48-6.52-13.94-6.63-16.41-.04-.99-.03-1.78-.02-2.18"/>
						<path vector-effect="non-scaling-stroke" d="M-22.38,-3.03c.22-.45.56-1.12,1-1.93,2.38-4.34,6.85-12.51,12.45-12.47.24,0,1.18.02,2.27.47,6.05,2.48,6.52,13.94,6.63,16.41.04.99.03,1.78.02,2.18L0,5.82"/>
					</g>
					<circle cx="-67.14" cy="-12.24" r="5.12" fill="var(--wp--preset--color--accent-purple)" stroke="none"/>
				</g>
			</svg>
			<h1 class="nano-hero__text"><?php echo function_exists( 'nano_copy_html' ) ? nano_copy_html( 'nano_copy_tagline' ) // phpcs:ignore WordPress.Security.EscapeOutput -- helper escapes
				: 'Harnessing the frontiers of <br class="nano-br-d">creativity.<br>Confronting advanced technology <br class="nano-br-d">with unrestrained imagination.'; ?></h1>
		</div>
		<!-- /wp:html -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
