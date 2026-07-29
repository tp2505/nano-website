<?php
/**
 * Archive block — a chronological (newest-first) mixed grid of Events and News,
 * plus a Classes listing grouped by term (current → past). Four combinable,
 * URL-settable filters drive it: Type (All/News/Events/Classes), Initiative
 * (All/the four/General) and Year. Filtering is client-side (assets/js/nano.js
 * → initArchive); the dropdowns' initial state is set from URL parameters
 * (?ftype=&finit=&fyear=) so links can deep-link into a pre-filtered view.
 *
 * Because Classes are Pedagogies-only, selecting Type=Classes constrains the
 * Initiative filter to Pedagogies and disables that control — the impossible
 * "Classes + some other Initiative" combination is prevented, not just tolerated.
 * Any empty result still shows a clean, readable empty state.
 *
 * @package Nano\ImaginationHubCore
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner content (unused).
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

// Gather Events + News into one chronological list (Classes are listed
// separately, grouped by term, further down).
$items = array();
foreach ( array( 'event', 'news' ) as $pt ) {
	$q = new WP_Query(
		array(
			'post_type'      => $pt,
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		)
	);
	while ( $q->have_posts() ) {
		$q->the_post();
		$id        = get_the_ID();
		$date_raw  = function_exists( 'nano_field' ) ? (string) nano_field( 'nano_date', $id ) : '';
		$init_id   = function_exists( 'nano_field' ) ? (int) nano_field( 'nano_initiative', $id ) : 0;
		$init_slug = $init_id ? get_post_field( 'post_name', $init_id ) : 'general';
		$year      = $date_raw ? substr( $date_raw, 0, 4 ) : get_the_date( 'Y', $id );
		$items[]   = array(
			'id'   => $id,
			'type' => ( 'event' === $pt ) ? 'events' : 'news',
			'date' => $date_raw ? $date_raw : '00000000',
			'init' => $init_slug,
			'year' => (string) $year,
		);
	}
	wp_reset_postdata();
}

// Newest first.
usort(
	$items,
	function ( $a, $b ) {
		return strcmp( $b['date'], $a['date'] );
	}
);

// Classes, grouped by term (newest term first). Pedagogies-only.
$class_items = function_exists( 'nano_get_classes' ) ? nano_get_classes() : array();
$class_groups = array();
foreach ( $class_items as $c ) {
	$label = '' !== $c['term'] ? $c['term'] : __( 'Undated', 'nano' );
	if ( ! isset( $class_groups[ $label ] ) ) {
		$class_groups[ $label ] = array(
			'key'   => $c['key'],
			'year'  => $c['year'],
			'items' => array(),
		);
	}
	$class_groups[ $label ]['items'][] = $c['id'];
}

if ( ! $items && ! $class_groups ) {
	return;
}

// Filter options: the four Initiatives (in order) and the years present across
// Events, News and Classes.
$initiatives = get_posts(
	array(
		'post_type'   => 'initiative',
		'numberposts' => -1,
		'post_status' => 'publish',
		'meta_key'    => 'nano_order', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		'orderby'     => 'meta_value_num',
		'order'       => 'ASC',
	)
);
$years = array();
foreach ( $items as $it ) {
	if ( $it['year'] ) {
		$years[ $it['year'] ] = true;
	}
}
foreach ( $class_groups as $g ) {
	if ( $g['year'] ) {
		$years[ $g['year'] ] = true;
	}
}
krsort( $years );
$years = array_keys( $years );

// Initial dropdown state from the URL (sanitised). Keys are namespaced
// (ftype/finit/fyear) so they don't collide with WordPress' own public query
// vars — `initiative` (the CPT) and `year` (core date query) would otherwise
// hijack /archive/ and route to a different template.
$sel_type = isset( $_GET['ftype'] ) ? sanitize_key( wp_unslash( $_GET['ftype'] ) ) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification
if ( ! in_array( $sel_type, array( 'all', 'news', 'events', 'classes' ), true ) ) {
	$sel_type = 'all';
}
$sel_init = isset( $_GET['finit'] ) ? sanitize_key( wp_unslash( $_GET['finit'] ) ) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification
$sel_year = isset( $_GET['fyear'] ) ? preg_replace( '/[^0-9]/', '', wp_unslash( $_GET['fyear'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
if ( '' === $sel_year ) {
	$sel_year = 'all';
}

// Type=Classes forces (and disables) the Initiative filter onto Pedagogies.
$is_classes    = ( 'classes' === $sel_type );
$init_disabled = $is_classes;
$init_value    = $is_classes ? 'pedagogies' : $sel_init;

// Initial visibility (the JS re-applies on load; this just avoids a flash).
$show_chrono  = ! $is_classes;                                  // News + Events grid.
$show_classes = ( 'all' === $sel_type || $is_classes );         // Classes listing.

$wrapper = get_block_wrapper_attributes( array( 'class' => 'nano-archive nano-archive--mixed' ) );
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?> data-nano-archive>
	<div class="nano-archive__filters">
		<label class="nano-archive__filter">
			<span class="nano-archive__filter-label"><?php esc_html_e( 'Type', 'nano' ); ?></span>
			<select data-nano-archive-type>
				<option value="all" <?php selected( $sel_type, 'all' ); ?>><?php esc_html_e( 'All', 'nano' ); ?></option>
				<option value="news" <?php selected( $sel_type, 'news' ); ?>><?php esc_html_e( 'News', 'nano' ); ?></option>
				<option value="events" <?php selected( $sel_type, 'events' ); ?>><?php esc_html_e( 'Events', 'nano' ); ?></option>
				<option value="classes" <?php selected( $sel_type, 'classes' ); ?>><?php esc_html_e( 'Classes', 'nano' ); ?></option>
			</select>
		</label>
		<label class="nano-archive__filter">
			<span class="nano-archive__filter-label"><?php esc_html_e( 'Initiative', 'nano' ); ?></span>
			<select data-nano-archive-initiative <?php echo $init_disabled ? 'disabled aria-disabled="true"' : ''; ?>>
				<option value="all" <?php selected( $init_value, 'all' ); ?>><?php esc_html_e( 'All', 'nano' ); ?></option>
				<?php foreach ( $initiatives as $init ) : ?>
					<option value="<?php echo esc_attr( $init->post_name ); ?>" <?php selected( $init_value, $init->post_name ); ?>><?php echo esc_html( get_the_title( $init ) ); ?></option>
				<?php endforeach; ?>
				<option value="general" <?php selected( $init_value, 'general' ); ?>><?php esc_html_e( 'General', 'nano' ); ?></option>
			</select>
			<span class="nano-archive__filter-note" data-nano-archive-note <?php echo $is_classes ? '' : 'hidden'; ?>><?php esc_html_e( 'Classes are part of Pedagogies.', 'nano' ); ?></span>
		</label>
		<label class="nano-archive__filter">
			<span class="nano-archive__filter-label"><?php esc_html_e( 'Year', 'nano' ); ?></span>
			<select data-nano-archive-year>
				<option value="all" <?php selected( $sel_year, 'all' ); ?>><?php esc_html_e( 'All', 'nano' ); ?></option>
				<?php foreach ( $years as $y ) : ?>
					<option value="<?php echo esc_attr( $y ); ?>" <?php selected( $sel_year, $y ); ?>><?php echo esc_html( $y ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
	</div>

	<ul class="nano-cards nano-cards--sm nano-archive__grid" data-nano-archive-grid role="list" <?php echo $show_chrono ? '' : 'hidden'; ?>>
		<?php
		foreach ( $items as $it ) {
			nano_render_card(
				$it['id'],
				array(
					'show_type' => true,
					'static'    => true,
					'data'      => array(
						'type'       => $it['type'],
						'initiative' => $it['init'],
						'year'       => $it['year'],
					),
				)
			);
		}
		?>
	</ul>

	<?php if ( $class_groups ) : ?>
		<div class="nano-archive__classes" data-nano-archive-classes <?php echo $show_classes ? '' : 'hidden'; ?>>
			<?php foreach ( $class_groups as $label => $group ) : ?>
				<div class="nano-archive__termgroup" data-nano-archive-termgroup data-year="<?php echo esc_attr( $group['year'] ); ?>">
					<h3 class="nano-archive__term"><?php echo esc_html( $label ); ?></h3>
					<ul class="nano-cards nano-cards--sm" role="list">
						<?php
						foreach ( $group['items'] as $cid ) {
							nano_render_card(
								$cid,
								array(
									'show_type' => true,
									'static'    => true,
									'data'      => array(
										'type'       => 'classes',
										'initiative' => 'pedagogies',
										'year'       => $group['year'],
									),
								)
							);
						}
						?>
					</ul>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<p class="nano-archive__empty" hidden
		data-empty-generic="<?php esc_attr_e( 'No entries match those filters.', 'nano' ); ?>"
		data-empty-classes="<?php esc_attr_e( 'No classes match these filters.', 'nano' ); ?>">
		<?php echo esc_html( $is_classes ? __( 'No classes match these filters.', 'nano' ) : __( 'No entries match those filters.', 'nano' ) ); ?>
	</p>
</div>
<?php
