<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Auto_Product_Filters_Discovery {
	public function discover_all_filters_for_admin() {
		$context = array(
			'type'        => 'shop',
			'category_id' => 0,
		);

		$filters = $this->build_base_filters();
		return $this->apply_admin_settings( $filters, $context, true );
	}

	public function get_context( $atts = array() ) {
		$context = array(
			'type'        => 'shop',
			'category_id' => 0,
		);

		return $context;
	}

	public function discover_filters( $context ) {
		$cache_key = 'wcapf_discovery_' . md5( wp_json_encode( $context ) );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$filters = $this->build_base_filters();

		$filters = $this->apply_admin_settings( $filters, $context );
		set_transient( $cache_key, $filters, HOUR_IN_SECONDS );

		return $filters;
	}

	private function build_base_filters() {
		$filters = array(
			'price' => array(
				'type'         => 'core',
				'label'        => __( 'Price', 'wc-auto-product-filters' ),
				'display_type' => 'range',
				'order'        => 10,
			),
			'stock' => array(
				'type'         => 'core',
				'label'        => __( 'Availability', 'wc-auto-product-filters' ),
				'display_type' => 'checkbox',
				'order'        => 20,
			),
			'sale'  => array(
				'type'         => 'core',
				'label'        => __( 'On sale', 'wc-auto-product-filters' ),
				'display_type' => 'checkbox',
				'order'        => 30,
			),
		);

		$taxonomies = wc_get_attribute_taxonomy_names();
		$order      = 40;
		foreach ( $taxonomies as $taxonomy ) {
			if ( ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}

			$filters[ $taxonomy ] = array(
				'type'         => 'taxonomy',
				'taxonomy'     => $taxonomy,
				'label'        => wc_attribute_label( $taxonomy ),
				'display_type' => $this->default_display_type_for_taxonomy( $taxonomy ),
				'order'        => $order,
			);
			$order += 10;
		}

		return $filters;
	}

	private function taxonomy_has_terms_in_category( $taxonomy, $category_id ) {
		$term = get_term( $category_id, 'product_cat' );
		if ( ! ( $term instanceof WP_Term ) ) {
			return false;
		}

		$product_ids = wc_get_products(
			array(
				'limit'    => -1,
				'return'   => 'ids',
				'status'   => 'publish',
				'category' => array( $term->slug ),
			)
		);

		if ( empty( $product_ids ) ) {
			return false;
		}

		$terms = wp_get_object_terms( $product_ids, $taxonomy, array( 'fields' => 'ids' ) );
		return ! is_wp_error( $terms ) && ! empty( $terms );
	}

	private function default_display_type_for_taxonomy( $taxonomy ) {
		$manual_color_attributes = wcapf_get_color_attributes();
		if ( in_array( $taxonomy, $manual_color_attributes, true ) ) {
			return 'swatches';
		}

		return 'checkbox';
	}

	private function apply_admin_settings( $filters, $context, $keep_disabled = false ) {
		$settings  = wcapf_get_filter_settings();

		foreach ( $filters as $key => $filter ) {
			$filter_settings = isset( $settings[ $key ] ) ? $settings[ $key ] : array();
			if ( ! $keep_disabled && isset( $filter_settings['enabled'] ) && 0 === (int) $filter_settings['enabled'] ) {
				unset( $filters[ $key ] );
				continue;
			}

			if ( ! empty( $filter_settings['label'] ) ) {
				$filters[ $key ]['label'] = $filter_settings['label'];
			}

			if ( ! empty( $filter_settings['display_type'] ) ) {
				$filters[ $key ]['display_type'] = wcapf_sanitize_display_type( $filter_settings['display_type'] );
			}

			if ( isset( $filter_settings['order'] ) ) {
				$filters[ $key ]['order'] = absint( $filter_settings['order'] );
			}
		}

		uasort(
			$filters,
			function( $a, $b ) {
				return (int) $a['order'] <=> (int) $b['order'];
			}
		);

		if ( ! $keep_disabled ) {
			$filters = $this->apply_category_overrides( $filters, $context );
		}

		return $filters;
	}

	private function apply_category_overrides( $filters, $context ) {
		$category_id = isset( $context['category_id'] ) ? absint( $context['category_id'] ) : 0;
		if ( ! $category_id ) {
			return $filters;
		}

		$overrides = wcapf_get_category_overrides();
		if ( empty( $overrides ) ) {
			return $filters;
		}

		$override = $this->get_category_override( $overrides, $category_id );
		if ( empty( $override ) || ! is_array( $override ) ) {
			return $filters;
		}

		$hidden_filters = isset( $override['hidden_filters'] ) && is_array( $override['hidden_filters'] ) ? $override['hidden_filters'] : array();
		foreach ( $hidden_filters as $hidden_key ) {
			$hidden_key = sanitize_key( $hidden_key );
			if ( isset( $filters[ $hidden_key ] ) ) {
				unset( $filters[ $hidden_key ] );
			}
		}

		if ( ! empty( $override['label'] ) && is_array( $override['label'] ) ) {
			foreach ( $override['label'] as $key => $label ) {
				$key = sanitize_key( $key );
				if ( isset( $filters[ $key ] ) && '' !== trim( (string) $label ) ) {
					$filters[ $key ]['label'] = sanitize_text_field( $label );
				}
			}
		}

		if ( ! empty( $override['display_type'] ) && is_array( $override['display_type'] ) ) {
			foreach ( $override['display_type'] as $key => $display_type ) {
				$key = sanitize_key( $key );
				if ( isset( $filters[ $key ] ) && '' !== trim( (string) $display_type ) ) {
					$filters[ $key ]['display_type'] = wcapf_sanitize_display_type( $display_type );
				}
			}
		}

		if ( ! empty( $override['order'] ) && is_array( $override['order'] ) ) {
			foreach ( $override['order'] as $key => $order ) {
				$key = sanitize_key( $key );
				if ( isset( $filters[ $key ] ) ) {
					$filters[ $key ]['order'] = absint( $order );
				}
			}
		}

		uasort(
			$filters,
			function( $a, $b ) {
				return (int) $a['order'] <=> (int) $b['order'];
			}
		);

		return $filters;
	}

	private function get_category_override( $overrides, $category_id ) {
		if ( isset( $overrides[ $category_id ] ) ) {
			return $overrides[ $category_id ];
		}

		$term = get_term( $category_id, 'product_cat' );
		if ( $term instanceof WP_Term && ! empty( $term->slug ) ) {
			foreach ( $overrides as $override ) {
				if ( ! empty( $override['category_slug'] ) && $override['category_slug'] === $term->slug ) {
					return $override;
				}
			}
		}

		return array();
	}
}
