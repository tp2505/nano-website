<?php
/**
 * PDF attachments — flyers, catalogues, syllabi — on events, classes, and
 * news. A repeater (nano_attachments: file + optional label + optional
 * thumbnail), read through nano_attachment_rows() with the same raw-meta
 * fallback contract as the sponsors repeater, and rendered by
 * nano_render_attachments() as first-page thumbnails with label and file
 * size, linking to the file.
 *
 * Thumbnails: WordPress generates first-page previews for PDF uploads only
 * when the server has Imagick + Ghostscript. Where that produced sizes, the
 * automatic preview is used; otherwise the row's manual thumbnail image; a
 * neutral "PDF" placeholder card is the last resort.
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
	 * @param array $rows Rows from nano_attachment_rows().
	 */
	function nano_render_attachments( $rows ) {
		$rows = array_filter(
			(array) $rows,
			function ( $row ) {
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
					$file_id = (int) $row['file'];
					$url     = wp_get_attachment_url( $file_id );
					$label   = trim( (string) $row['label'] );
					if ( '' === $label ) {
						$label = get_the_title( $file_id );
					}
					$path  = get_attached_file( $file_id );
					$bytes = ( $path && file_exists( $path ) ) ? filesize( $path ) : 0;
					$meta  = strtoupper( (string) preg_replace( '#^.+/#', '', (string) get_post_mime_type( $file_id ) ) );
					if ( $bytes ) {
						$meta .= ' · ' . size_format( $bytes );
					}
					// First-page preview where the host generated one
					// (Imagick + Ghostscript); else the manual thumbnail;
					// else a neutral placeholder card.
					$thumb = wp_get_attachment_image( $file_id, 'medium', false, array( 'class' => 'nano-media', 'loading' => 'lazy', 'alt' => '' ) );
					if ( ! $thumb && ! empty( $row['thumb'] ) ) {
						$thumb = wp_get_attachment_image( (int) $row['thumb'], 'medium', false, array( 'class' => 'nano-media', 'loading' => 'lazy', 'alt' => '' ) );
					}
					?>
					<li class="nano-attachment">
						<a class="nano-attachment__link" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener">
							<span class="nano-attachment__thumb<?php echo $thumb ? '' : ' nano-attachment__thumb--placeholder'; ?>">
								<?php echo $thumb ? $thumb : '<span class="nano-attachment__filetype" aria-hidden="true">PDF</span>'; // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</span>
							<span class="nano-attachment__label"><?php echo esc_html( $label ); ?></span>
							<span class="nano-attachment__meta"><?php echo esc_html( $meta ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php
	}
}
