<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Auto_Product_Filters_Renderer {
	private $discovery;
	private $filtered_product_ids_cache = array();
	private $dynamic_term_counts_cache = array();
	private $price_bounds_cache = array();
	private $current_context = array(
		'type'        => 'shop',
		'category_id' => 0,
	);

	public function __construct( $discovery ) {
		$this->discovery = $discovery;
	}

	public function render( $filters, $context ) {
		$this->current_context = is_array( $context ) ? $context : array(
			'type'        => 'shop',
			'category_id' => 0,
		);
		$this->filtered_product_ids_cache = array();
		$this->dynamic_term_counts_cache = array();
		$this->price_bounds_cache        = array();

		$settings        = fsapf_get_global_settings();
		$layout          = isset( $settings['filters_layout'] ) ? sanitize_key( $settings['filters_layout'] ) : 'stacked';
			$columns_desktop = isset( $settings['filters_columns_desktop'] ) ? absint( $settings['filters_columns_desktop'] ) : 3;
			$visible_filters = isset( $settings['visible_filters'] ) ? absint( $settings['visible_filters'] ) : 3;
			$sidebar_panel   = ! empty( $settings['sidebar_panel_enabled'] );
		$mobile_button_only = ! empty( $settings['mobile_button_only_enabled'] );
		$collapse_filters = ! empty( $settings['collapse_filters_enabled'] );
			$submit_mode      = isset( $settings['submit_mode'] ) ? sanitize_key( $settings['submit_mode'] ) : 'auto';
		$color_attributes = fsapf_get_color_attributes();
		if ( $visible_filters < 1 ) {
			$visible_filters = 3;
		}
		if ( $columns_desktop < 1 ) {
			$columns_desktop = 3;
		}
		$effective_columns_desktop = $columns_desktop + ( isset( $filters['price'] ) ? 1 : 0 );

		ob_start();
		?>
			<div class="wcapf-filters wcapf-layout-<?php echo esc_attr( $layout ); ?><?php echo $sidebar_panel ? ' wcapf-has-panel' : ''; ?><?php echo $mobile_button_only ? ' wcapf-mobile-button-only' : ''; ?>" style="--wcapf-columns-desktop:<?php echo esc_attr( (string) $effective_columns_desktop ); ?>;" data-context="<?php echo esc_attr( $context['type'] ); ?>" data-visible-filters="<?php echo esc_attr( (string) $visible_filters ); ?>" data-sidebar-panel="<?php echo $sidebar_panel ? '1' : '0'; ?>" data-mobile-button-only="<?php echo $mobile_button_only ? '1' : '0'; ?>" data-collapse-filters="<?php echo $collapse_filters ? '1' : '0'; ?>">
				<?php if ( ! $mobile_button_only ) : ?>
					<h3 class="wcapf-heading"><?php esc_html_e( 'Filters', 'finestudio-wc-filters' ); ?></h3>
				<?php endif; ?>
					<?php if ( $mobile_button_only ) : ?>
						<button type="button" class="button wcapf-open-mobile-filters"><span class="wcapf-filter-fab-icon" aria-hidden="true"></span><span class="wcapf-open-mobile-filters-label"><?php esc_html_e( 'Filter', 'finestudio-wc-filters' ); ?></span></button>
						<button type="button" class="button wcapf-open-mobile-filters-fab" aria-label="<?php esc_attr_e( 'Filter', 'finestudio-wc-filters' ); ?>">
							<span class="wcapf-filter-fab-icon" aria-hidden="true"></span>
						</button>
					<?php endif; ?>
				<form class="wcapf-form" method="get">
				<?php $this->render_non_filter_query_args(); ?>
				<div class="wcapf-fields">
				<?php $i = 0; foreach ( $filters as $key => $filter ) : ?>
					<?php ob_start(); $has_output = $this->render_field( $key, $filter ); $field_html = trim( ob_get_clean() ); ?>
					<?php if ( ! $has_output || '' === $field_html ) { continue; } ?>
					<?php $i++; ?>
					<div class="wcapf-field wcapf-field-<?php echo esc_attr( sanitize_html_class( $key ) ); ?><?php echo ( $collapse_filters && $i > $visible_filters ) ? ' wcapf-hidden-field' : ''; ?>" data-filter="<?php echo esc_attr( $key ); ?>" data-color-assigned="<?php echo in_array( $key, $color_attributes, true ) ? '1' : '0'; ?>">
						<h4 class="wcapf-title"><?php echo esc_html( $filter['label'] ); ?></h4>
						<div class="wcapf-options" style="display:block;">
							<?php echo $field_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
					</div>
				<?php endforeach; ?>
				</div>
				<?php if ( $collapse_filters && $i > $visible_filters ) : ?>
					<button type="button" class="button wcapf-show-all"><?php esc_html_e( 'Show all filters', 'finestudio-wc-filters' ); ?></button>
				<?php endif; ?>
				<?php if ( 'button' === $submit_mode ) : ?>
					<button type="submit" class="button wcapf-submit"><?php esc_html_e( 'Filter', 'finestudio-wc-filters' ); ?></button>
				<?php endif; ?>
				<a class="wcapf-reset" href="<?php echo esc_url( $this->get_reset_url() ); ?>"><?php esc_html_e( 'Reset', 'finestudio-wc-filters' ); ?></a>
			</form>
				<?php if ( $sidebar_panel || $mobile_button_only ) : ?>
					<div class="wcapf-panel-overlay" hidden></div>
					<div class="wcapf-panel" hidden>
					<button type="button" class="button wcapf-close-panel"><?php esc_html_e( 'Close', 'finestudio-wc-filters' ); ?></button>
					<form class="wcapf-form wcapf-panel-form" method="get">
						<?php $this->render_non_filter_query_args(); ?>
						<div class="wcapf-fields">
						<?php foreach ( $filters as $key => $filter ) : ?>
							<?php ob_start(); $has_output = $this->render_field( $key, $filter ); $field_html = trim( ob_get_clean() ); ?>
							<?php if ( ! $has_output || '' === $field_html ) { continue; } ?>
							<div class="wcapf-field wcapf-field-<?php echo esc_attr( sanitize_html_class( $key ) ); ?>" data-filter="<?php echo esc_attr( $key ); ?>" data-color-assigned="<?php echo in_array( $key, $color_attributes, true ) ? '1' : '0'; ?>">
								<h4 class="wcapf-title"><?php echo esc_html( $filter['label'] ); ?></h4>
								<div class="wcapf-options" style="display:block;">
									<?php echo $field_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</div>
							</div>
						<?php endforeach; ?>
						</div>
						<div class="wcapf-panel-actions wcapf-panel-actions-hidden">
							<button type="submit" class="button wcapf-apply-results"><?php esc_html_e( 'Show results', 'finestudio-wc-filters' ); ?></button>
							<a class="wcapf-reset" href="<?php echo esc_url( $this->get_reset_url() ); ?>"><?php esc_html_e( 'Reset', 'finestudio-wc-filters' ); ?></a>
						</div>
					</form>
				</div>
			<?php endif; ?>
		</div>
		<?php
		$html = ob_get_clean();
		return $html;
	}

	private function render_non_filter_query_args() {
		$preserve = array( 'orderby', 's' );
		foreach ( $preserve as $key ) {
			if ( isset( $_GET[ $key ] ) ) {
				$value = is_array( $_GET[ $key ] ) ? '' : sanitize_text_field( wp_unslash( $_GET[ $key ] ) );
				echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" />';
			}
		}
	}

	private function render_field( $key, $filter ) {
		if ( 'price' === $key ) {
			$bounds      = $this->get_price_bounds();
			$current_min = isset( $_GET['filter_min_price'] ) && '' !== $_GET['filter_min_price'] ? (float) wc_clean( wp_unslash( $_GET['filter_min_price'] ) ) : $bounds['min'];
			$current_max = isset( $_GET['filter_max_price'] ) && '' !== $_GET['filter_max_price'] ? (float) wc_clean( wp_unslash( $_GET['filter_max_price'] ) ) : $bounds['max'];
			echo '<div class="wcapf-price-slider" data-min="' . esc_attr( (string) $bounds['min'] ) . '" data-max="' . esc_attr( (string) $bounds['max'] ) . '">';
			echo '<div class="wcapf-range-row">';
			echo '<div class="wcapf-price-input-wrap">';
			echo '<input class="wcapf-price-input-min" type="number" step="1" name="filter_min_price" placeholder="' . esc_attr__( 'Min', 'finestudio-wc-filters' ) . '" value="' . esc_attr( (string) $current_min ) . '" />';
			echo '<span class="wcapf-price-currency">€</span>';
			echo '</div>';
			echo '<span class="wcapf-price-separator">-</span>';
			echo '<div class="wcapf-price-input-wrap">';
			echo '<input class="wcapf-price-input-max" type="number" step="1" name="filter_max_price" placeholder="' . esc_attr__( 'Max', 'finestudio-wc-filters' ) . '" value="' . esc_attr( (string) $current_max ) . '" />';
			echo '<span class="wcapf-price-currency">€</span>';
			echo '</div>';
			echo '</div>';
			echo '<div class="wcapf-slider-rail">';
			echo '<div class="wcapf-range-track"><span class="wcapf-range-progress"></span></div>';
			echo '<input class="wcapf-range wcapf-range-min" type="range" min="' . esc_attr( (string) $bounds['min'] ) . '" max="' . esc_attr( (string) $bounds['max'] ) . '" step="1" value="' . esc_attr( (string) $current_min ) . '" />';
			echo '<input class="wcapf-range wcapf-range-max" type="range" min="' . esc_attr( (string) $bounds['min'] ) . '" max="' . esc_attr( (string) $bounds['max'] ) . '" step="1" value="' . esc_attr( (string) $current_max ) . '" />';
			echo '</div>';
			echo '</div>';
			return true;
		}

		if ( 'stock' === $key ) {
			$checked = isset( $_GET['filter_stock'] ) && 'instock' === sanitize_text_field( wp_unslash( $_GET['filter_stock'] ) );
			echo '<label><input type="checkbox" name="filter_stock" value="instock" ' . checked( $checked, true, false ) . ' /> ' . esc_html__( 'In stock only', 'finestudio-wc-filters' ) . '</label>';
			return true;
		}

		if ( 'sale' === $key ) {
			$checked = isset( $_GET['filter_sale'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['filter_sale'] ) );
			echo '<label><input type="checkbox" name="filter_sale" value="1" ' . checked( $checked, true, false ) . ' /> ' . esc_html__( 'On sale only', 'finestudio-wc-filters' ) . '</label>';
			return true;
		}

		if ( ! empty( $filter['taxonomy'] ) ) {
			return $this->render_taxonomy_values( $key, $filter );
		}

		return false;
	}

	private function render_taxonomy_values( $key, $filter ) {
		$terms = get_terms(
			array(
				'taxonomy'   => $filter['taxonomy'],
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return false;
		}

		$param          = 'filter_' . $key;
		$selected_value = isset( $_GET[ $param ] ) ? (array) wp_unslash( $_GET[ $param ] ) : array();
		$selected_value = array_map( 'sanitize_title', $selected_value );
		$swatches       = fsapf_get_color_swatches();
		$display_type   = isset( $filter['display_type'] ) ? $filter['display_type'] : 'checkbox';
		$color_attributes = fsapf_get_color_attributes();
		$is_color_filter = in_array( $key, $color_attributes, true );
		$use_color_swatches = $is_color_filter && 'swatches' === $display_type;
		$use_text_swatches  = ! $is_color_filter && 'swatches' === $display_type;
		$has_active_filters = $this->has_filter_request_for_counts();
		$dynamic_counts     = $this->get_dynamic_term_counts( $filter['taxonomy'] );

		if ( $use_color_swatches ) {
			echo '<div class="wcapf-color-grid">';
		} elseif ( $use_text_swatches ) {
			echo '<div class="wcapf-text-swatches">';
		}

		if ( 'select' === $display_type ) {
			echo '<select name="' . esc_attr( $param ) . '">';
			echo '<option value="">' . esc_html__( 'Any', 'finestudio-wc-filters' ) . '</option>';
			foreach ( $terms as $term ) {
				echo '<option value="' . esc_attr( $term->slug ) . '" ' . selected( in_array( $term->slug, $selected_value, true ), true, false ) . '>' . esc_html( $term->name ) . '</option>';
			}
			echo '</select>';
			return true;
		}

		if ( 'radio' === $display_type ) {
			echo '<label><input type="radio" name="' . esc_attr( $param ) . '" value="" ' . checked( empty( $selected_value ), true, false ) . '/> ' . esc_html__( 'Any', 'finestudio-wc-filters' ) . '</label>';
			foreach ( $terms as $term ) {
				$term_slug = sanitize_title( $term->slug );
				$selected = in_array( $term_slug, $selected_value, true );
				$term_count = isset( $dynamic_counts[ $term_slug ] )
					? absint( $dynamic_counts[ $term_slug ] )
					: ( $has_active_filters ? 0 : absint( $term->count ) );
				$is_disabled = ( $term_count < 1 ) && ! $selected;
				$disabled_attr = $is_disabled ? ' disabled="disabled"' : '';
				$disabled_class = $is_disabled ? ' class="wcapf-option-disabled"' : '';
				echo '<label' . $disabled_class . '><input type="radio" name="' . esc_attr( $param ) . '" value="' . esc_attr( $term_slug ) . '" ' . checked( $selected, true, false ) . $disabled_attr . '/> ' . esc_html( $term->name ) . ' - <em class="wcapf-term-count">' . esc_html( (string) $term_count ) . '</em></label>';
			}
			return true;
		}

		if ( 'multiselect' === $display_type ) {
			echo '<select name="' . esc_attr( $param ) . '[]" multiple="multiple">';
			foreach ( $terms as $term ) {
				echo '<option value="' . esc_attr( $term->slug ) . '" ' . selected( in_array( $term->slug, $selected_value, true ), true, false ) . '>' . esc_html( $term->name ) . '</option>';
			}
			echo '</select>';
			return true;
		}

		foreach ( $terms as $term ) {
			$selected = in_array( $term->slug, $selected_value, true );
			$term_slug = sanitize_title( $term->slug );
			$term_count = isset( $dynamic_counts[ $term_slug ] )
				? absint( $dynamic_counts[ $term_slug ] )
				: ( $has_active_filters ? 0 : absint( $term->count ) );
			$is_disabled = ( $term_count < 1 ) && ! $selected;
			$disabled_attr = $is_disabled ? ' disabled="disabled"' : '';
			$disabled_class = $is_disabled ? ' wcapf-option-disabled' : '';

			if ( $use_color_swatches ) {
				$color = isset( $swatches[ $key ][ $term->slug ] ) ? sanitize_hex_color( $swatches[ $key ][ $term->slug ] ) : '';
				echo '<label class="wcapf-swatch' . esc_attr( $disabled_class ) . '" title="' . esc_attr( $term->name ) . '" aria-label="' . esc_attr( $term->name ) . '" tabindex="0">';
				echo '<input type="checkbox" name="' . esc_attr( $param ) . '[]" value="' . esc_attr( $term->slug ) . '" ' . checked( $selected, true, false ) . $disabled_attr . ' />';
				echo '<span style="background-color:' . esc_attr( $color ? $color : '#d1d5db' ) . '"></span>';
				echo '</label>';
			} elseif ( $use_text_swatches ) {
				echo '<label class="wcapf-text-swatch' . esc_attr( $disabled_class ) . '">';
				echo '<input type="checkbox" name="' . esc_attr( $param ) . '[]" value="' . esc_attr( $term->slug ) . '" ' . checked( $selected, true, false ) . $disabled_attr . ' />';
				echo '<span>' . esc_html( $term->name ) . ' - <em class="wcapf-term-count">' . esc_html( (string) $term_count ) . '</em></span>';
				echo '</label>';
			} else {
				echo '<label class="' . esc_attr( trim( $disabled_class ) ) . '"><input type="checkbox" name="' . esc_attr( $param ) . '[]" value="' . esc_attr( $term->slug ) . '" ' . checked( $selected, true, false ) . $disabled_attr . ' />';
				if ( $is_color_filter && 'checkbox' === $display_type ) {
					$color = isset( $swatches[ $key ][ $term->slug ] ) ? sanitize_hex_color( $swatches[ $key ][ $term->slug ] ) : '';
					echo '<span class="wcapf-inline-color" style="background-color:' . esc_attr( $color ? $color : '#d1d5db' ) . '"></span>';
				}
				echo ' ' . esc_html( $term->name ) . ' - <em class="wcapf-term-count">' . esc_html( (string) $term_count ) . '</em></label>';
			}
		}

		if ( $use_color_swatches ) {
			echo '</div>';
		} elseif ( $use_text_swatches ) {
			echo '</div>';
		}

		return true;
	}

	private function get_reset_url() {
		$kept = array();
		foreach ( $_GET as $key => $value ) {
			if ( 0 === strpos( $key, 'filter_' ) ) {
				continue;
			}

			if ( is_array( $value ) ) {
				$kept[ sanitize_key( $key ) ] = array_map( 'sanitize_text_field', wp_unslash( $value ) );
			} else {
				$kept[ sanitize_key( $key ) ] = sanitize_text_field( wp_unslash( $value ) );
			}
		}

		return add_query_arg( $kept, remove_query_arg( array_keys( $_GET ) ) );
	}

	private function get_price_bounds() {
		$category_id = isset( $this->current_context['category_id'] ) ? absint( $this->current_context['category_id'] ) : 0;
		$cache_key   = $category_id > 0 ? 'cat_' . $category_id : 'shop';
		if ( isset( $this->price_bounds_cache[ $cache_key ] ) && is_array( $this->price_bounds_cache[ $cache_key ] ) ) {
			return $this->price_bounds_cache[ $cache_key ];
		}

		global $wpdb;

		$base_from_where = "
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		";
		$where = "
			WHERE pm.meta_key = '_price'
			AND p.post_type = 'product'
			AND p.post_status = 'publish'
		";

		if ( $category_id > 0 ) {
			$base_from_where .= "
				INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
				INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
			";
			$where .= $wpdb->prepare( " AND tt.taxonomy = 'product_cat' AND tt.term_id = %d", $category_id );
		}

		$min_sql = "SELECT MIN(CAST(pm.meta_value AS DECIMAL(10,2))) {$base_from_where} {$where}";
		$max_sql = "SELECT MAX(CAST(pm.meta_value AS DECIMAL(10,2))) {$base_from_where} {$where}";

		$min = $wpdb->get_var( $min_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$max = $wpdb->get_var( $max_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery

		$min = null !== $min ? floor( (float) $min ) : 0;
		$max = null !== $max ? ceil( (float) $max ) : 1000;
		if ( $max <= $min ) {
			$max = $min + 1;
		}

		$result = array(
			'min' => $min,
			'max' => $max,
		);
		$this->price_bounds_cache[ $cache_key ] = $result;
		return $this->price_bounds_cache[ $cache_key ];
	}

	private function get_dynamic_term_counts( $taxonomy ) {
		$taxonomy = sanitize_key( (string) $taxonomy );
		if ( isset( $this->dynamic_term_counts_cache[ $taxonomy ] ) && is_array( $this->dynamic_term_counts_cache[ $taxonomy ] ) ) {
			return $this->dynamic_term_counts_cache[ $taxonomy ];
		}

		if ( ! $this->has_filter_request_for_counts() ) {
			return array();
		}

		$product_ids = $this->get_filtered_product_ids_for_counts( $taxonomy );
		if ( empty( $product_ids ) || ! taxonomy_exists( $taxonomy ) ) {
			return array();
		}

		global $wpdb;

		$counts       = array();
		$product_ids  = array_values( array_filter( array_map( 'absint', $product_ids ) ) );
		$placeholders = implode( ',', array_fill( 0, count( $product_ids ), '%d' ) );
		$sql          = "
			SELECT t.slug AS slug, COUNT(DISTINCT tr.object_id) AS term_count
			FROM {$wpdb->term_relationships} tr
			INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
			INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
			WHERE tt.taxonomy = %s
			AND tr.object_id IN ($placeholders)
			GROUP BY t.slug
		";
		$params       = array_merge( array( $taxonomy ), $product_ids );
		$rows         = $wpdb->get_results( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				if ( ! isset( $row->slug ) ) {
					continue;
				}
				$slug = sanitize_title( (string) $row->slug );
				if ( '' === $slug ) {
					continue;
				}
				$counts[ $slug ] = isset( $row->term_count ) ? absint( $row->term_count ) : 0;
			}
		}

		$this->dynamic_term_counts_cache[ $taxonomy ] = $counts;
		return $this->dynamic_term_counts_cache[ $taxonomy ];
	}

	private function has_filter_request_for_counts() {
		$category_id = isset( $this->current_context['category_id'] ) ? absint( $this->current_context['category_id'] ) : 0;
		if ( $category_id > 0 ) {
			return true;
		}

		foreach ( $_GET as $key => $value ) {
			$key = sanitize_key( $key );
			if ( 0 !== strpos( $key, 'filter_' ) ) {
				continue;
			}

			if ( 'filter_min_price' === $key || 'filter_max_price' === $key ) {
				$bounds = $this->get_price_bounds();
				$min = isset( $_GET['filter_min_price'] ) && '' !== $_GET['filter_min_price'] ? (float) wc_clean( wp_unslash( $_GET['filter_min_price'] ) ) : (float) $bounds['min'];
				$max = isset( $_GET['filter_max_price'] ) && '' !== $_GET['filter_max_price'] ? (float) wc_clean( wp_unslash( $_GET['filter_max_price'] ) ) : (float) $bounds['max'];
				if ( $min > (float) $bounds['min'] || $max < (float) $bounds['max'] ) {
					return true;
				}
				continue;
			}

			if ( is_array( $value ) ) {
				$clean = array_filter( array_map( 'sanitize_text_field', wp_unslash( $value ) ) );
				if ( ! empty( $clean ) ) {
					return true;
				}
			} else {
				$clean = sanitize_text_field( wp_unslash( $value ) );
				if ( '' !== $clean ) {
					return true;
				}
			}
		}

		return false;
	}

	private function get_filtered_product_ids_for_counts( $ignore_taxonomy = '' ) {
		$ignore_taxonomy = sanitize_key( (string) $ignore_taxonomy );
		$cache_key = '' !== $ignore_taxonomy ? $ignore_taxonomy : '__all__';
		if ( isset( $this->filtered_product_ids_cache[ $cache_key ] ) && is_array( $this->filtered_product_ids_cache[ $cache_key ] ) ) {
			return $this->filtered_product_ids_cache[ $cache_key ];
		}

		$original_get = $_GET;
		if ( '' !== $ignore_taxonomy ) {
			$param = 'filter_' . $ignore_taxonomy;
			if ( isset( $_GET[ $param ] ) ) {
				unset( $_GET[ $param ] );
			}
		}

		$query_args = array(
			'post_type'        => 'product',
			'post_status'      => 'publish',
			'fields'           => 'ids',
			'posts_per_page'   => -1,
			'no_found_rows'    => true,
			'suppress_filters' => false,
		);

		// Reuse the same WooCommerce query filter pipeline used by shortcode products,
		// so counts reflect the active filters exactly.
		$query_args = apply_filters( 'woocommerce_shortcode_products_query', $query_args, array(), 'products' );

		$category_id = isset( $this->current_context['category_id'] ) ? absint( $this->current_context['category_id'] ) : 0;
		if ( $category_id > 0 ) {
			$tax_query = isset( $query_args['tax_query'] ) && is_array( $query_args['tax_query'] ) ? $query_args['tax_query'] : array();
			$tax_query[] = array(
				'taxonomy'         => 'product_cat',
				'field'            => 'term_id',
				'terms'            => array( $category_id ),
				'include_children' => true,
				'operator'         => 'IN',
			);
			if ( count( $tax_query ) > 1 && ! isset( $tax_query['relation'] ) ) {
				$tax_query['relation'] = 'AND';
			}
			$query_args['tax_query'] = $tax_query;
		}

		$q = new WP_Query( $query_args );
		$_GET = $original_get;

		$this->filtered_product_ids_cache[ $cache_key ] = array_map( 'absint', is_array( $q->posts ) ? $q->posts : array() );
		return $this->filtered_product_ids_cache[ $cache_key ];
	}
}


