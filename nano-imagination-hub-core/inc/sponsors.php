<?php
/**
 * Sponsor logos for the Support-us page. Reads the ACF Pro repeater
 * (nano_sponsors) via get_field when available, and otherwise reconstructs it
 * straight from ACF's repeater meta layout (count + indexed sub-fields) so it
 * renders even where ACF Pro isn't loaded — the same isolation contract the
 * event gallery uses (see nano_gallery_rows(), which reads the ACF Pro Gallery
 * field's ID-array meta the same way).
 *
 * @package Nano\ImaginationHubCore
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'nano_sponsor_rows' ) ) {
	/**
	 * Sponsor rows as array( array( 'logo' => id, 'name' => str, 'url' => str ), … ).
	 * Rows without a logo attachment are skipped.
	 *
	 * @param int $post_id Support-us page ID.
	 * @return array
	 */
	function nano_sponsor_rows( $post_id ) {
		$post_id = (int) $post_id;
		if ( ! $post_id ) {
			return array();
		}

		if ( function_exists( 'get_field' ) ) {
			$val = get_field( 'nano_sponsors', $post_id );
			if ( is_array( $val ) && $val && is_array( reset( $val ) ) ) {
				$rows = array();
				foreach ( $val as $row ) {
					$logo = isset( $row['logo'] ) ? (int) $row['logo'] : 0;
					if ( $logo ) {
						$rows[] = array(
							'logo' => $logo,
							'name' => isset( $row['name'] ) ? (string) $row['name'] : '',
							'url'  => isset( $row['url'] ) ? (string) $row['url'] : '',
						);
					}
				}
				return $rows;
			}
		}

		$count = (int) get_post_meta( $post_id, 'nano_sponsors', true );
		$rows  = array();
		for ( $i = 0; $i < $count; $i++ ) {
			$logo = (int) get_post_meta( $post_id, "nano_sponsors_{$i}_logo", true );
			if ( ! $logo ) {
				continue;
			}
			$rows[] = array(
				'logo' => $logo,
				'name' => (string) get_post_meta( $post_id, "nano_sponsors_{$i}_name", true ),
				'url'  => (string) get_post_meta( $post_id, "nano_sponsors_{$i}_url", true ),
			);
		}
		return $rows;
	}
}

if ( ! function_exists( 'nano_render_sponsors' ) ) {
	/**
	 * Echo the sponsors band — label + logo grid — for a set of rows. One
	 * renderer serves the Support-us page, event pages, and news pages (which
	 * may pass a linked event's rows), so the markup can't drift. Echoes
	 * nothing for an empty set.
	 *
	 * @param array $rows Rows from nano_sponsor_rows().
	 */
	function nano_render_sponsors( $rows ) {
		if ( ! $rows ) {
			return;
		}
		?>
		<section class="nano-sponsors">
			<h2 class="nano-label nano-sponsors__label"><?php esc_html_e( 'With the support of', 'nano' ); ?></h2>
			<ul class="nano-sponsors__grid" role="list">
				<?php
				foreach ( $rows as $row ) :
					$logo_id = (int) $row['logo'];
					$name    = trim( (string) $row['name'] );
					$url     = trim( (string) $row['url'] );
					$img     = wp_get_attachment_image(
						$logo_id,
						'medium',
						false,
						array(
							'class'    => 'nano-media nano-media--image',
							'loading'  => 'lazy',
							'decoding' => 'async',
							'alt'      => $name, // Empty when unnamed → decorative.
						)
					);
					if ( ! $img ) {
						continue;
					}
					?>
					<li class="nano-sponsor">
						<?php if ( $url ) : ?>
							<a class="nano-sponsor__link" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"<?php echo $name ? ' title="' . esc_attr( $name ) . '"' : ''; ?>>
								<?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</a>
						<?php else : ?>
							<?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php
	}
}
