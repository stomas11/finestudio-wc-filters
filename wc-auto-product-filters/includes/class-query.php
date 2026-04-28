<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Auto_Product_Filters_Query {
	public function init() {
		add_filter( 'woocommerce_product_query_tax_query', array( $this, 'filter_wc_tax_query' ), 20, 2 );
		add_filter( 'woocommerce_product_query_meta_query', array( $this, 'filter_wc_meta_query' ), 20, 2 );
		add_action( 'woocommerce_product_query', array( $this, 'apply_wc_query_flags' ), 20, 1 );
		add_filter( 'woocommerce_shortcode_products_query', array( $this, 'filter_wc_shortcode_products_query' ), 20, 3 );
	}

	public function apply_filters_to_query( $query ) {
		if ( is_admin() || ! ( $query instanceof WP_Query ) ) {
			return;
		}

		if ( ! $this->has_filter_request() ) {
			return;
		}

		if ( ! $this->query_targets_products( $query ) ) {
			return;
		}

		$tax_query  = (array) $query->get( 'tax_query', array() );
		$meta_query = (array) $query->get( 'meta_query', array() );

		if ( isset( $_GET['filter_min_price'] ) || isset( $_GET['filter_max_price'] ) ) {
			$min = isset( $_GET['filter_min_price'] ) && '' !== $_GET['filter_min_price'] ? (float) wc_clean( wp_unslash( $_GET['filter_min_price'] ) ) : 0;
			$max = isset( $_GET['filter_max_price'] ) && '' !== $_GET['filter_max_price'] ? (float) wc_clean( wp_unslash( $_GET['filter_max_price'] ) ) : 999999999;
			$meta_query[] = array(
				'key'     => '_price',
				'value'   => array( $min, $max ),
				'compare' => 'BETWEEN',
				'type'    => 'NUMERIC',
			);
		}

		if ( isset( $_GET['filter_stock'] ) && 'instock' === sanitize_text_field( wp_unslash( $_GET['filter_stock'] ) ) ) {
			$meta_query[] = array(
				'key'   => '_stock_status',
				'value' => 'instock',
			);
		}

		if ( isset( $_GET['filter_sale'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['filter_sale'] ) ) ) {
			$query->set( 'post__in', wc_get_product_ids_on_sale() );
		}

		foreach ( $_GET as $key => $raw_value ) {
			$key = sanitize_key( $key );
			if ( 0 !== strpos( $key, 'filter_pa_' ) ) {
				continue;
			}

			$taxonomy = str_replace( 'filter_', '', $key );

			if ( ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}

			$values = is_array( $raw_value ) ? $raw_value : array( $raw_value );
			$values = array_filter( array_map( 'sanitize_title', wp_unslash( $values ) ) );
			if ( empty( $values ) ) {
				continue;
			}

			$tax_query[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => $values,
				'operator' => 'IN',
			);
		}

		if ( count( $tax_query ) > 1 ) {
			$tax_query['relation'] = 'AND';
		}

		if ( count( $meta_query ) > 1 ) {
			$meta_query['relation'] = 'AND';
		}

		$query->set( 'tax_query', $tax_query );
		$query->set( 'meta_query', $meta_query );
	}

	private function has_filter_request() {
		foreach ( $_GET as $key => $value ) {
			$key = sanitize_key( $key );
			if ( 0 === strpos( $key, 'filter_' ) ) {
				if ( is_array( $value ) ) {
					$value = array_filter( array_map( 'sanitize_text_field', wp_unslash( $value ) ) );
					if ( ! empty( $value ) ) {
						return true;
					}
				} else {
					$value = sanitize_text_field( wp_unslash( $value ) );
					if ( '' !== $value ) {
						return true;
					}
				}
			}
		}
		return false;
	}

	private function query_targets_products( $query ) {
		$post_type = $query->get( 'post_type' );

		if ( 'product' === $post_type ) {
			return true;
		}

		if ( is_array( $post_type ) && in_array( 'product', $post_type, true ) ) {
			return true;
		}

		if ( is_shop() || is_product_taxonomy() ) {
			return true;
		}

		return false;
	}

	public function filter_wc_tax_query( $tax_query, $wc_query ) {
		if ( ! $this->has_filter_request() ) {
			return $tax_query;
		}
		// Attribute filtering is handled via post__in fallback in apply_wc_query_flags(),
		// because some shops store usable terms mainly on variations.
		return $tax_query;
	}

	public function filter_wc_meta_query( $meta_query, $wc_query ) {
		if ( ! $this->has_filter_request() ) {
			return $meta_query;
		}

		if ( isset( $_GET['filter_min_price'] ) || isset( $_GET['filter_max_price'] ) ) {
			$min = isset( $_GET['filter_min_price'] ) && '' !== $_GET['filter_min_price'] ? (float) wc_clean( wp_unslash( $_GET['filter_min_price'] ) ) : 0;
			$max = isset( $_GET['filter_max_price'] ) && '' !== $_GET['filter_max_price'] ? (float) wc_clean( wp_unslash( $_GET['filter_max_price'] ) ) : 999999999;
			$meta_query[] = array(
				'key'     => '_price',
				'value'   => array( $min, $max ),
				'compare' => 'BETWEEN',
				'type'    => 'NUMERIC',
			);
		}

		if ( isset( $_GET['filter_stock'] ) && 'instock' === sanitize_text_field( wp_unslash( $_GET['filter_stock'] ) ) ) {
			$meta_query[] = array(
				'key'   => '_stock_status',
				'value' => 'instock',
			);
		}

		if ( count( $meta_query ) > 1 && ! isset( $meta_query['relation'] ) ) {
			$meta_query['relation'] = 'AND';
		}

		return $meta_query;
	}

	public function apply_wc_query_flags( $query ) {
		if ( ! $this->has_filter_request() ) {
			return;
		}

		$attribute_map = $this->get_attribute_filter_map();
		if ( ! empty( $attribute_map ) ) {
			$matched_ids = $this->get_product_ids_matching_attributes( $attribute_map );
			if ( empty( $matched_ids ) ) {
				$query->set( 'post__in', array( 0 ) );
			} else {
				$current_post_in = $query->get( 'post__in' );
				if ( is_array( $current_post_in ) && ! empty( $current_post_in ) ) {
					$matched_ids = array_values( array_intersect( $matched_ids, $current_post_in ) );
				}
				$query->set( 'post__in', $matched_ids );
			}
		}

		if ( isset( $_GET['filter_sale'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['filter_sale'] ) ) ) {
			$query->set( 'post__in', wc_get_product_ids_on_sale() );
		}
	}

	public function filter_wc_shortcode_products_query( $query_args, $atts, $type ) {
		if ( ! $this->has_filter_request() ) {
			return $query_args;
		}

		$tax_query  = isset( $query_args['tax_query'] ) && is_array( $query_args['tax_query'] ) ? $query_args['tax_query'] : array();
		$meta_query = isset( $query_args['meta_query'] ) && is_array( $query_args['meta_query'] ) ? $query_args['meta_query'] : array();

		if ( isset( $_GET['filter_min_price'] ) || isset( $_GET['filter_max_price'] ) ) {
			$min = isset( $_GET['filter_min_price'] ) && '' !== $_GET['filter_min_price'] ? (float) wc_clean( wp_unslash( $_GET['filter_min_price'] ) ) : 0;
			$max = isset( $_GET['filter_max_price'] ) && '' !== $_GET['filter_max_price'] ? (float) wc_clean( wp_unslash( $_GET['filter_max_price'] ) ) : 999999999;
			$meta_query[] = array(
				'key'     => '_price',
				'value'   => array( $min, $max ),
				'compare' => 'BETWEEN',
				'type'    => 'NUMERIC',
			);
		}

		if ( isset( $_GET['filter_stock'] ) && 'instock' === sanitize_text_field( wp_unslash( $_GET['filter_stock'] ) ) ) {
			$meta_query[] = array(
				'key'   => '_stock_status',
				'value' => 'instock',
			);
		}

		if ( count( $meta_query ) > 1 && ! isset( $meta_query['relation'] ) ) {
			$meta_query['relation'] = 'AND';
		}

		$query_args['tax_query']  = $tax_query;
		$query_args['meta_query'] = $meta_query;

		$attribute_map = $this->get_attribute_filter_map();
		if ( ! empty( $attribute_map ) ) {
			$matched_ids = $this->get_product_ids_matching_attributes( $attribute_map );
			$query_args['post__in'] = ! empty( $matched_ids ) ? $matched_ids : array( 0 );
		}

		if ( isset( $_GET['filter_sale'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['filter_sale'] ) ) ) {
			$sale_ids = wc_get_product_ids_on_sale();
			if ( isset( $query_args['post__in'] ) && is_array( $query_args['post__in'] ) ) {
				$query_args['post__in'] = array_values( array_intersect( $query_args['post__in'], $sale_ids ) );
				if ( empty( $query_args['post__in'] ) ) {
					$query_args['post__in'] = array( 0 );
				}
			} else {
				$query_args['post__in'] = $sale_ids;
			}
		}

		return $query_args;
	}

	private function get_attribute_filter_map() {
		$map = array();
		foreach ( $_GET as $key => $raw_value ) {
			$key = sanitize_key( $key );
			if ( 0 !== strpos( $key, 'filter_pa_' ) ) {
				continue;
			}
			$taxonomy = str_replace( 'filter_', '', $key );
			if ( ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}

			$values = is_array( $raw_value ) ? $raw_value : array( $raw_value );
			$values = array_filter( array_map( 'sanitize_title', wp_unslash( $values ) ) );
			if ( ! empty( $values ) ) {
				$map[ $taxonomy ] = array_values( array_unique( $values ) );
			}
		}
		return $map;
	}

	private function get_product_ids_matching_attributes( $attribute_map ) {
		if ( empty( $attribute_map ) ) {
			return array();
		}

		$tax_query = array( 'relation' => 'AND' );
		foreach ( $attribute_map as $taxonomy => $values ) {
			$tax_query[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => $values,
				'operator' => 'IN',
			);
		}

		$parent_ids_from_terms = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'tax_query'      => $tax_query,
				'suppress_filters' => false,
			)
		);

		$variation_meta_query = array( 'relation' => 'AND' );
		foreach ( $attribute_map as $taxonomy => $values ) {
			$variation_meta_query[] = array(
				'key'     => 'attribute_' . $taxonomy,
				'value'   => $values,
				'compare' => 'IN',
			);
		}

		$variation_ids = get_posts(
			array(
				'post_type'      => 'product_variation',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => $variation_meta_query,
				'suppress_filters' => false,
			)
		);

		$parent_ids_from_variations = array();
		foreach ( $variation_ids as $variation_id ) {
			$parent_id = (int) wp_get_post_parent_id( $variation_id );
			if ( $parent_id > 0 ) {
				$parent_ids_from_variations[] = $parent_id;
			}
		}

		$all_ids = array_values(
			array_unique(
				array_merge(
					is_array( $parent_ids_from_terms ) ? $parent_ids_from_terms : array(),
					$parent_ids_from_variations
				)
			)
		);

		return array_map( 'absint', $all_ids );
	}
}
