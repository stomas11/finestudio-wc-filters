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
			<?php $this->render_active_filters( $filters ); ?>
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

	private function render_active_filters( $filters ) {
		$badges   = $this->get_active_filter_badges( $filters );
		$is_empty = empty( $badges );

		echo '<div class="wcapf-active-filters' . ( $is_empty ? ' wcapf-active-filters-empty' : '' ) . '" data-active-count="' . esc_attr( (string) count( $badges ) ) . '"' . ( $is_empty ? ' hidden' : '' ) . '>';

		if ( ! $is_empty ) {
			echo '<span class="wcapf-active-filters-label">' . esc_html__( 'Active filters', 'finestudio-wc-filters' ) . '</span>';
			echo '<div class="wcapf-active-filter-list">';

			foreach ( $badges as $badge ) {
				$label      = isset( $badge['label'] ) ? (string) $badge['label'] : '';
				$remove_url = isset( $badge['remove_url'] ) ? (string) $badge['remove_url'] : '';
				$param      = isset( $badge['param'] ) ? (string) $badge['param'] : '';
				$value      = isset( $badge['value'] ) ? (string) $badge['value'] : '';
				$type       = isset( $badge['type'] ) ? (string) $badge['type'] : 'filter';

				echo '<a class="wcapf-active-filter-badge" href="' . esc_url( $remove_url ) . '" data-filter-param="' . esc_attr( $param ) . '" data-filter-value="' . esc_attr( $value ) . '" data-filter-type="' . esc_attr( $type ) . '" aria-label="' . esc_attr( sprintf( __( 'Remove filter: %s', 'finestudio-wc-filters' ), $label ) ) . '">';
				echo '<span class="wcapf-active-filter-text">' . esc_html( $label ) . '</span>';
				echo '<span class="wcapf-active-filter-x" aria-hidden="true">x</span>';
				echo '</a>';
			}

			echo '</div>';
		}

		echo '</div>';
	}

	private function get_active_filter_badges( $filters ) {
		$badges = array();

		foreach ( $filters as $key => $filter ) {
			if ( 'price' === $key ) {
				$price_badge = $this->get_active_price_badge( $filter );
				if ( ! empty( $price_badge ) ) {
					$badges[] = $price_badge;
				}
				continue;
			}

			if ( 'stock' === $key ) {
				if ( isset( $_GET['filter_stock'] ) && 'instock' === sanitize_text_field( wp_unslash( $_GET['filter_stock'] ) ) ) {
					$badges[] = array(
						'label'      => sprintf( '%1$s: %2$s', $filter['label'], __( 'In stock only', 'finestudio-wc-filters' ) ),
						'remove_url' => $this->get_remove_filter_url( 'filter_stock' ),
						'param'      => 'filter_stock',
						'value'      => 'instock',
						'type'       => 'single',
					);
				}
				continue;
			}

			if ( 'sale' === $key ) {
				if ( isset( $_GET['filter_sale'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['filter_sale'] ) ) ) {
					$badges[] = array(
						'label'      => sprintf( '%1$s: %2$s', $filter['label'], __( 'On sale only', 'finestudio-wc-filters' ) ),
						'remove_url' => $this->get_remove_filter_url( 'filter_sale' ),
						'param'      => 'filter_sale',
						'value'      => '1',
						'type'       => 'single',
					);
				}
				continue;
			}

			if ( ! empty( $filter['taxonomy'] ) ) {
				$badges = array_merge( $badges, $this->get_active_taxonomy_badges( $key, $filter ) );
			}
		}

		return $badges;
	}

	private function get_active_price_badge( $filter ) {
		if ( ! isset( $_GET['filter_min_price'] ) && ! isset( $_GET['filter_max_price'] ) ) {
			return array();
		}

		$bounds           = $this->get_price_bounds();
		$currency         = fsapf_get_price_filter_currency();
		$request_currency = fsapf_get_submitted_price_filter_currency( $currency );
		$current_min      = $this->get_price_display_request_amount( 'filter_min_price', $bounds['min'], $request_currency, $currency, 'min' );
		$current_max      = $this->get_price_display_request_amount( 'filter_max_price', $bounds['max'], $request_currency, $currency, 'max' );

		if ( $current_min <= (float) $bounds['min'] && $current_max >= (float) $bounds['max'] ) {
			return array();
		}

		return array(
			'label'      => sprintf(
				'%1$s: %2$s - %3$s',
				$filter['label'],
				$this->format_price_amount( $current_min, $currency ),
				$this->format_price_amount( $current_max, $currency )
			),
			'remove_url' => $this->get_remove_filter_url( 'price' ),
			'param'      => 'price',
			'value'      => '',
			'type'       => 'price',
		);
	}

	private function get_active_taxonomy_badges( $key, $filter ) {
		$param = 'filter_' . $key;
		if ( empty( $_GET[ $param ] ) ) {
			return array();
		}

		$raw_values = (array) wp_unslash( $_GET[ $param ] );
		$values     = array_values( array_unique( array_filter( array_map( 'sanitize_title', $raw_values ) ) ) );
		if ( empty( $values ) ) {
			return array();
		}

		$badges = array();
		foreach ( $values as $value ) {
			$term_name = $value;
			$term      = get_term_by( 'slug', $value, $filter['taxonomy'] );
			if ( $term instanceof WP_Term ) {
				$term_name = $term->name;
			}

			$badges[] = array(
				'label'      => sprintf( '%1$s: %2$s', $filter['label'], $term_name ),
				'remove_url' => $this->get_remove_filter_url( $param, $value ),
				'param'      => $param,
				'value'      => $value,
				'type'       => 'term',
			);
		}

		return $badges;
	}

	private function format_price_amount( $amount, $currency ) {
		if ( function_exists( 'wc_price' ) ) {
			$price = wp_strip_all_tags( wc_price( (float) $amount, array( 'currency' => $currency ) ) );
			$price = html_entity_decode( $price, ENT_QUOTES, get_bloginfo( 'charset' ) ? get_bloginfo( 'charset' ) : 'UTF-8' );
			return trim( str_replace( "\xc2\xa0", ' ', $price ) );
		}

		$formatted = function_exists( 'number_format_i18n' ) ? number_format_i18n( (float) $amount, 0 ) : (string) $amount;
		return trim( $formatted . ' ' . fsapf_get_price_filter_currency_symbol( $currency ) );
	}

	private function render_field( $key, $filter ) {
		if ( 'price' === $key ) {
			$bounds           = $this->get_price_bounds();
			$currency         = fsapf_get_price_filter_currency();
			$request_currency = fsapf_get_submitted_price_filter_currency( $currency );
			$current_min      = $this->get_price_display_request_amount( 'filter_min_price', $bounds['min'], $request_currency, $currency, 'min' );
			$current_max      = $this->get_price_display_request_amount( 'filter_max_price', $bounds['max'], $request_currency, $currency, 'max' );
			$currency_symbol  = fsapf_get_price_filter_currency_symbol( $currency );
			echo '<div class="wcapf-price-slider" data-min="' . esc_attr( (string) $bounds['min'] ) . '" data-max="' . esc_attr( (string) $bounds['max'] ) . '" data-currency="' . esc_attr( $currency ) . '">';
			echo '<input type="hidden" name="wcapf_price_currency" value="' . esc_attr( $currency ) . '" />';
			echo '<div class="wcapf-range-row">';
			echo '<div class="wcapf-price-input-wrap">';
			echo '<input class="wcapf-price-input-min" type="number" step="1" name="filter_min_price" placeholder="' . esc_attr__( 'Min', 'finestudio-wc-filters' ) . '" value="' . esc_attr( (string) $current_min ) . '" />';
			echo '<span class="wcapf-price-currency">' . esc_html( $currency_symbol ) . '</span>';
			echo '</div>';
			echo '<span class="wcapf-price-separator">-</span>';
			echo '<div class="wcapf-price-input-wrap">';
			echo '<input class="wcapf-price-input-max" type="number" step="1" name="filter_max_price" placeholder="' . esc_attr__( 'Max', 'finestudio-wc-filters' ) . '" value="' . esc_attr( (string) $current_max ) . '" />';
			echo '<span class="wcapf-price-currency">' . esc_html( $currency_symbol ) . '</span>';
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

	private function get_price_request_amount( $key, $default ) {
		if ( ! isset( $_GET[ $key ] ) || '' === $_GET[ $key ] || is_array( $_GET[ $key ] ) ) {
			return $default;
		}

		$value = wc_clean( wp_unslash( $_GET[ $key ] ) );
		if ( function_exists( 'wc_format_decimal' ) ) {
			$value = wc_format_decimal( $value );
		}

		return (float) $value;
	}

	private function get_price_display_request_amount( $key, $default, $request_currency, $display_currency, $bound_type ) {
		if ( ! isset( $_GET[ $key ] ) || '' === $_GET[ $key ] || is_array( $_GET[ $key ] ) ) {
			return $default;
		}

		$amount = $this->get_price_request_amount( $key, $default );
		if ( $request_currency === $display_currency ) {
			return $amount;
		}

		$database_amount = fsapf_price_to_database_currency( $amount, $request_currency );
		$display_amount  = fsapf_price_to_display_currency( $database_amount, $display_currency );

		return 'max' === $bound_type ? ceil( $display_amount ) : floor( $display_amount );
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
			if ( 'wcapf_price_currency' === $key ) {
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

	private function get_remove_filter_url( $param, $remove_value = null ) {
		$param       = sanitize_key( (string) $param );
		$remove_slug = null !== $remove_value ? sanitize_title( (string) $remove_value ) : null;
		$kept        = array();

		foreach ( $_GET as $key => $value ) {
			$clean_key = sanitize_key( $key );
			if ( '' === $clean_key || in_array( $clean_key, array( 'paged', 'product-page' ), true ) ) {
				continue;
			}

			if ( 'price' === $param && in_array( $clean_key, array( 'filter_min_price', 'filter_max_price', 'wcapf_price_currency' ), true ) ) {
				continue;
			}

			if ( $clean_key === $param ) {
				if ( null === $remove_slug ) {
					continue;
				}

				$values = is_array( $value ) ? array_map( 'sanitize_text_field', wp_unslash( $value ) ) : array( sanitize_text_field( wp_unslash( $value ) ) );
				$values = array_values(
					array_filter(
						$values,
						function( $item ) use ( $remove_slug ) {
							return sanitize_title( $item ) !== $remove_slug && '' !== trim( (string) $item );
						}
					)
				);

				if ( ! empty( $values ) ) {
					$kept[ $clean_key ] = $values;
				}
				continue;
			}

			if ( is_array( $value ) ) {
				$clean_value = array_values( array_filter( array_map( 'sanitize_text_field', wp_unslash( $value ) ) ) );
				if ( ! empty( $clean_value ) ) {
					$kept[ $clean_key ] = $clean_value;
				}
			} else {
				$clean_value = sanitize_text_field( wp_unslash( $value ) );
				if ( '' !== $clean_value ) {
					$kept[ $clean_key ] = $clean_value;
				}
			}
		}

		return add_query_arg( $kept, remove_query_arg( array_keys( $_GET ) ) );
	}

	private function get_price_bounds() {
		$category_id = isset( $this->current_context['category_id'] ) ? absint( $this->current_context['category_id'] ) : 0;
		$currency    = fsapf_get_price_filter_currency();
		$cache_key   = ( $category_id > 0 ? 'cat_' . $category_id : 'shop' ) . '_' . $currency;
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

		$raw_result = array(
			'min' => $min,
			'max' => $max,
		);
		$result = fsapf_get_price_display_bounds( $raw_result );
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
				$min = $this->get_price_request_amount( 'filter_min_price', (float) $bounds['min'] );
				$max = $this->get_price_request_amount( 'filter_max_price', (float) $bounds['max'] );
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


