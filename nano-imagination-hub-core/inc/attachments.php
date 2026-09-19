<?php
/**
 * PDF attachments — flyers, catalogues, syllabi — on events, classes, and
 * news. A repeater (nano_attachments: file + optional label + optional
 * thumbnail), read through nano_attachment_rows() with the same raw-meta
 * fallback contract as the sponsors repeater, and rendered by
 * nano_render_attachments() as first-page thumbnails with label and file
 * size, linking to the file.
 *
 * Thumbnails are MANUALLY UPLOADED images only — deliberately no automatic
 * first-page rendering: the Hub's documents are often a single long page, so
 * a first-page render would be a tall unusable strip. A row without a
 * thumbnail falls back to a plain labelled download link.
 *
 * @package Nano\ImaginationHubCore
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'nano_attachment_rows' ) ) {
	/**
	 * Attachment rows as array( array( 'file' => id, 'label' => str,
	 * 'thumb' => id ), … ). Rows without a resolvable file are skipped.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	function nano_attachment_rows( $post_id ) {
		$post_id = (int) $post_id;
		if ( ! $post_id ) {
			return array();
		}

		if ( function_exists( 'get_field' ) ) {
			$val = get_field( 'nano_attachments', $post_id );
			if ( is_array( $val ) && $val && is_array( reset( $val ) ) ) {
				$rows = array();
				foreach ( $val as $row ) {
					$file = isset( $row['file'] ) ? (int) $row['file'] : 0;
					if ( $file ) {
						$rows[] = array(
							'file'  => $file,
							'label' => isset( $row['label'] ) ? (string) $row['label'] : '',
							'thumb' => isset( $row['thumb'] ) ? (int) $row['thumb'] : 0,
						);
					}
				}
				return $rows;
			}
		}

		$count = (int) get_post_meta( $post_id, 'nano_attachments', true );
		$rows  = array();
		for ( $i = 0; $i < $count; $i++ ) {
			$file = (int) get_post_meta( $post_id, "nano_attachments_{$i}_file", true );
			if ( ! $file ) {
				continue;
			}
			$rows[] = array(
				'file'  => $file,
				'label' => (string) get_post_meta( $post_id, "nano_attachments_{$i}_label", true ),
				'thumb' => (int) get_post_meta( $post_id, "nano_attachments_{$i}_thumb", true ),
			);
		}
		return $rows;
	}
}

if ( ! function_exists( 'nano_render_attachments' ) ) {
	/**
	 * Echo the Documents band — thumbnail + label + size per file, each
	 * linking to the PDF. Echoes nothing for an empty set.
	 *
	 * Besides uploaded-file rows, a row may carry an external 'url' (+ 'label',
	 * optional 'meta' string) instead of a 'file' ID — how the class syllabus
	 * link joins the band as an ordinary document.
	 *
	 * @param array $rows Rows from nano_attachment_rows(), optionally with
	 *                    url-rows mixed in.
	 */
	function nano_render_attachments( $rows ) {
		$rows = array_filter(
			(array) $rows,
			function ( $row ) {
				if ( ! empty( $row['url'] ) && ! empty( $row['label'] ) ) {
					return true;
				}
				return ! empty( $row['file'] ) && wp_get_attachment_url( (int) $row['file'] );
			}
		);
		if ( ! $rows ) {
			return;
		}
		?>
		<section class="nano-attachments">
			<h2 class="nano-label nano-attachments__label"><?php esc_html_e( 'Documents', 'nano' ); ?></h2>
			<ul class="nano-attachments__grid" role="list">
				<?php
				foreach ( $rows as $row ) :
					$file_id = ! empty( $row['file'] ) ? (int) $row['file'] : 0;
					$label   = trim( (string) $row['label'] );
					if ( $file_id ) {
						$url = wp_get_attachment_url( $file_id );
						if ( '' === $label ) {
							$label = get_the_title( $file_id );
						}
						$path  = get_attached_file( $file_id );
						$bytes = ( $path && file_exists( $path ) ) ? filesize( $path ) : 0;
						$meta  = strtoupper( (string) preg_replace( '#^.+/#', '', (string) get_post_mime_type( $file_id ) ) );
						if ( $bytes ) {
							$meta .= ' · ' . size_format( $bytes );
						}
					} else {
						// External-URL row (the class syllabus link).
						$url  = (string) $row['url'];
						$meta = isset( $row['meta'] ) ? (string) $row['meta'] : '';
					}
					// The manually-uploaded thumbnail only (no automatic
				// first-page render — long single-page documents would
				// produce unusable strips). Without one, the row is a
				// plain labelled download link.
				$thumb = ! empty( $row['thumb'] )
					? wp_get_attachment_image( (int) $row['thumb'], 'medium', false, array( 'class' => 'nano-media', 'loading' => 'lazy', 'alt' => '' ) )
					: '';
				?>
					<li class="nano-attachment<?php echo $thumb ? '' : ' nano-attachment--linkonly'; ?>">
						<a class="nano-attachment__link" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener">
							<?php if ( $thumb ) : ?>
								<span class="nano-attachment__thumb"><?php echo $thumb; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
							<?php endif; ?>
							<span class="nano-attachment__label"><?php echo esc_html( $label ); ?></span>
							<?php if ( '' !== $meta ) : ?>
								<span class="nano-attachment__meta"><?php echo esc_html( $meta ); ?></span>
							<?php endif; ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php
	}
}
