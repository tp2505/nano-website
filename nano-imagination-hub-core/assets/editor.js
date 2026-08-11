/**
 * Editor-side registration for the Nano server-rendered blocks.
 *
 * The blocks are defined in PHP (block.json + render.php) and render on the
 * server, which is all the front end needs. The block editor, however, is a
 * JavaScript app with its own block registry: a block that is never passed to
 * wp.blocks.registerBlockType() client-side is reported as "Your site doesn't
 * include support for this block", even though the server renders it fine.
 *
 * The block list is injected from the PHP registry (see
 * nano_core_enqueue_editor_assets), so block.json stays the single source of
 * truth; the server also bootstraps each block's full settings (title, icon,
 * category, attributes, supports), so all we add here is the edit view — a
 * live server-side preview of the real render.php output.
 */
( function ( wp ) {
	'use strict';

	var blocks = ( window.nanoCoreEditor && window.nanoCoreEditor.blocks ) || [];
	var el = wp.element.createElement;

	blocks.forEach( function ( block ) {
		wp.blocks.registerBlockType( block.name, {
			edit: function ( props ) {
				var blockProps = wp.blockEditor.useBlockProps();

				// Preview through the REST block-renderer so editors see the
				// real server output (same render.php as the front end).
				if ( wp.serverSideRender ) {
					return el(
						'div',
						blockProps,
						el( wp.serverSideRender, {
							block: block.name,
							attributes: props.attributes,
						} )
					);
				}

				// If the preview component is unavailable (e.g. REST blocked
				// on the host), fall back to an inert labelled placeholder so
				// the block remains selectable and saves untouched.
				return el(
					'div',
					blockProps,
					el(
						'div',
						{ className: 'nano-block-placeholder' },
						block.title + ' — ' + 'rendered on the published page.'
					)
				);
			},

			// Server-rendered: nothing is serialised beyond the attributes.
			save: function () {
				return null;
			},
		} );
	} );
} )( window.wp );
