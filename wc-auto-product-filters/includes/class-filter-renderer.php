<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Auto_Product_Filters_Renderer {
	private $discovery;

	public function __construct( $discovery ) {
		$this->discovery = $discovery;
	}

	public function render( $filters, $context ) {
		$settings        = wcapf_get_global_settings();
		$layout          = isset( $settings['filters_layout'] ) ? sanitize_key( $settings['filters_layout'] ) : 'stacked';
		$columns_desktop = isset( $settings['filters_columns_desktop'] ) ? absint( $settings['filters_columns_desktop'] ) : 3;
		$visible_filters = isset( $settings['visible_filters'] ) ? absint( $settings['visible_filters'] ) : 3;
		$sidebar_panel   = ! empty( $settings['sidebar_panel_enabled'] );
		$collapse_filters = ! empty( $settings['collapse_filters_enabled'] );
		$submit_mode      = isset( $settings['submit_mode'] ) ? sanitize_key( $settings['submit_mode'] ) : 'auto';
		$color_attributes = wcapf_get_color_attributes();
		if ( $visible_filters < 1 ) {
			$visible_filters = 3;
		}
		if ( $columns_desktop < 1 ) {
			$columns_desktop = 3;
		}
		$effective_columns_desktop = $columns_desktop + ( isset( $filters['price'] ) ? 1 : 0 );

		ob_start();
		?>
		<div class="wcapf-filters wcapf-layout-<?php echo esc_attr( $layout ); ?><?php echo $sidebar_panel ? ' wcapf-has-panel' : ''; ?>" style="--wcapf-columns-desktop:<?php echo esc_attr( (string) $effective_columns_desktop ); ?>;" data-context="<?php echo esc_attr( $context['type'] ); ?>" data-visible-filters="<?php echo esc_attr( (string) $visible_filters ); ?>" data-sidebar-panel="<?php echo $sidebar_panel ? '1' : '0'; ?>">
			<h3 class="wcapf-heading"><?php esc_html_e( 'Filters', 'wc-auto-product-filters' ); ?></h3>
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
					<button type="button" class="button wcapf-show-all"><?php esc_html_e( 'Show all filters', 'wc-auto-product-filters' ); ?></button>
				<?php endif; ?>
				<?php if ( 'button' === $submit_mode ) : ?>
					<button type="submit" class="button wcapf-submit"><?php esc_html_e( 'Filter', 'wc-auto-product-filters' ); ?></button>
				<?php endif; ?>
				<a class="wcapf-reset" href="<?php echo esc_url( $this->get_reset_url() ); ?>"><?php esc_html_e( 'Reset', 'wc-auto-product-filters' ); ?></a>
			</form>
			<?php if ( $sidebar_panel ) : ?>
				<div class="wcapf-panel-overlay" hidden></div>
				<div class="wcapf-panel" hidden>
					<button type="button" class="button wcapf-close-panel"><?php esc_html_e( 'Close', 'wc-auto-product-filters' ); ?></button>
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
							<button type="submit" class="button wcapf-apply-results"><?php esc_html_e( 'Show results', 'wc-auto-product-filters' ); ?></button>
							<a class="wcapf-reset" href="<?php echo esc_url( $this->get_reset_url() ); ?>"><?php esc_html_e( 'Reset', 'wc-auto-product-filters' ); ?></a>
						</div>
					</form>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
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
			echo '<input class="wcapf-price-input-min" type="number" step="1" name="filter_min_price" placeholder="' . esc_attr__( 'Min', 'wc-auto-product-filters' ) . '" value="' . esc_attr( (string) $current_min ) . '" />';
			echo '<span class="wcapf-price-currency">€</span>';
			echo '</div>';
			echo '<span class="wcapf-price-separator">-</span>';
			echo '<div class="wcapf-price-input-wrap">';
			echo '<input class="wcapf-price-input-max" type="number" step="1" name="filter_max_price" placeholder="' . esc_attr__( 'Max', 'wc-auto-product-filters' ) . '" value="' . esc_attr( (string) $current_max ) . '" />';
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
			echo '<label><input type="checkbox" name="filter_stock" value="instock" ' . checked( $checked, true, false ) . ' /> ' . esc_html__( 'In stock only', 'wc-auto-product-filters' ) . '</label>';
			return true;
		}

		if ( 'sale' === $key ) {
			$checked = isset( $_GET['filter_sale'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['filter_sale'] ) );
			echo '<label><input type="checkbox" name="filter_sale" value="1" ' . checked( $checked, true, false ) . ' /> ' . esc_html__( 'On sale only', 'wc-auto-product-filters' ) . '</label>';
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
				'hide_empty' => true,
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return false;
		}

		$param          = 'filter_' . $key;
		$selected_value = isset( $_GET[ $param ] ) ? (array) wp_unslash( $_GET[ $param ] ) : array();
		$selected_value = array_map( 'sanitize_title', $selected_value );
		$swatches       = wcapf_get_color_swatches();
		$display_type   = isset( $filter['display_type'] ) ? $filter['display_type'] : 'checkbox';
		$color_attributes = wcapf_get_color_attributes();
		$is_color_filter = in_array( $key, $color_attributes, true );
		$use_color_swatches = $is_color_filter && 'swatches' === $display_type;
		$use_text_swatches  = ! $is_color_filter && 'swatches' === $display_type;

		if ( $use_color_swatches ) {
			echo '<div class="wcapf-color-grid">';
		} elseif ( $use_text_swatches ) {
			echo '<div class="wcapf-text-swatches">';
		}

		if ( 'select' === $display_type ) {
			echo '<select name="' . esc_attr( $param ) . '">';
			echo '<option value="">' . esc_html__( 'Any', 'wc-auto-product-filters' ) . '</option>';
			foreach ( $terms as $term ) {
				echo '<option value="' . esc_attr( $term->slug ) . '" ' . selected( in_array( $term->slug, $selected_value, true ), true, false ) . '>' . esc_html( $term->name ) . '</option>';
			}
			echo '</select>';
			return true;
		}

		if ( 'radio' === $display_type ) {
			echo '<label><input type="radio" name="' . esc_attr( $param ) . '" value="" ' . checked( empty( $selected_value ), true, false ) . '/> ' . esc_html__( 'Any', 'wc-auto-product-filters' ) . '</label>';
			foreach ( $terms as $term ) {
				echo '<label><input type="radio" name="' . esc_attr( $param ) . '" value="' . esc_attr( $term->slug ) . '" ' . checked( in_array( $term->slug, $selected_value, true ), true, false ) . '/> ' . esc_html( $term->name ) . '</label>';
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

			if ( $use_color_swatches ) {
				$color = isset( $swatches[ $key ][ $term->slug ] ) ? sanitize_hex_color( $swatches[ $key ][ $term->slug ] ) : '';
				echo '<label class="wcapf-swatch" title="' . esc_attr( $term->name ) . '" aria-label="' . esc_attr( $term->name ) . '" tabindex="0">';
				echo '<input type="checkbox" name="' . esc_attr( $param ) . '[]" value="' . esc_attr( $term->slug ) . '" ' . checked( $selected, true, false ) . ' />';
				echo '<span style="background-color:' . esc_attr( $color ? $color : '#d1d5db' ) . '"></span>';
				echo '</label>';
			} elseif ( $use_text_swatches ) {
				echo '<label class="wcapf-text-swatch">';
				echo '<input type="checkbox" name="' . esc_attr( $param ) . '[]" value="' . esc_attr( $term->slug ) . '" ' . checked( $selected, true, false ) . ' />';
				echo '<span>' . esc_html( $term->name ) . ' - <em class="wcapf-term-count">' . esc_html( (string) absint( $term->count ) ) . '</em></span>';
				echo '</label>';
			} else {
				echo '<label><input type="checkbox" name="' . esc_attr( $param ) . '[]" value="' . esc_attr( $term->slug ) . '" ' . checked( $selected, true, false ) . ' />';
				if ( $is_color_filter && 'checkbox' === $display_type ) {
					$color = isset( $swatches[ $key ][ $term->slug ] ) ? sanitize_hex_color( $swatches[ $key ][ $term->slug ] ) : '';
					echo '<span class="wcapf-inline-color" style="background-color:' . esc_attr( $color ? $color : '#d1d5db' ) . '"></span>';
				}
				echo ' ' . esc_html( $term->name ) . ' - <em class="wcapf-term-count">' . esc_html( (string) absint( $term->count ) ) . '</em></label>';
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
		global $wpdb;

		$min = $wpdb->get_var(
			"SELECT MIN(CAST(pm.meta_value AS DECIMAL(10,2)))
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE pm.meta_key = '_price'
			AND p.post_type = 'product'
			AND p.post_status = 'publish'"
		); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery

		$max = $wpdb->get_var(
			"SELECT MAX(CAST(pm.meta_value AS DECIMAL(10,2)))
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE pm.meta_key = '_price'
			AND p.post_type = 'product'
			AND p.post_status = 'publish'"
		); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery

		$min = null !== $min ? floor( (float) $min ) : 0;
		$max = null !== $max ? ceil( (float) $max ) : 1000;
		if ( $max <= $min ) {
			$max = $min + 1;
		}

		return array(
			'min' => $min,
			'max' => $max,
		);
	}
}
