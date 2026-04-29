<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Auto_Product_Filters_Query {
	private $price_bounds_cache = array();
	private $attribute_match_cache = array();

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

		if ( $this->should_apply_price_filter() ) {
			$bounds = $this->get_price_bounds();
			$min = isset( $_GET['filter_min_price'] ) && '' !== $_GET['filter_min_price'] ? (float) wc_clean( wp_unslash( $_GET['filter_min_price'] ) ) : (float) $bounds['min'];
			$max = isset( $_GET['filter_max_price'] ) && '' !== $_GET['filter_max_price'] ? (float) wc_clean( wp_unslash( $_GET['filter_max_price'] ) ) : (float) $bounds['max'];
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
			$sale_ids = wc_get_product_ids_on_sale();
			$current_post_in = $query->get( 'post__in' );
			if ( is_array( $current_post_in ) && ! empty( $current_post_in ) ) {
				$sale_ids = array_values( array_intersect( $sale_ids, $current_post_in ) );
			}
			$query->set( 'post__in', ! empty( $sale_ids ) ? $sale_ids : array( 0 ) );
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
		fsapf_profiler_begin();
		if ( ! $this->has_filter_request() ) {
			fsapf_profiler_end();
			return $tax_query;
		}
		// Attribute filtering is handled via post__in fallback in apply_wc_query_flags(),
		// because some shops store usable terms mainly on variations.
		fsapf_profiler_end();
		return $tax_query;
	}

	public function filter_wc_meta_query( $meta_query, $wc_query ) {
		fsapf_profiler_begin();
		if ( ! $this->has_filter_request() ) {
			fsapf_profiler_end();
			return $meta_query;
		}

		if ( $this->should_apply_price_filter() ) {
			$bounds = $this->get_price_bounds();
			$min = isset( $_GET['filter_min_price'] ) && '' !== $_GET['filter_min_price'] ? (float) wc_clean( wp_unslash( $_GET['filter_min_price'] ) ) : (float) $bounds['min'];
			$max = isset( $_GET['filter_max_price'] ) && '' !== $_GET['filter_max_price'] ? (float) wc_clean( wp_unslash( $_GET['filter_max_price'] ) ) : (float) $bounds['max'];
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

		fsapf_profiler_end();
		return $meta_query;
	}

	public function apply_wc_query_flags( $query ) {
		fsapf_profiler_begin();
		if ( ! $this->has_filter_request() ) {
			fsapf_profiler_end();
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
			$sale_ids = wc_get_product_ids_on_sale();
			$current_post_in = $query->get( 'post__in' );
			if ( is_array( $current_post_in ) && ! empty( $current_post_in ) ) {
				$sale_ids = array_values( array_intersect( $sale_ids, $current_post_in ) );
			}
			$query->set( 'post__in', ! empty( $sale_ids ) ? $sale_ids : array( 0 ) );
		}
		fsapf_profiler_end();
	}

	public function filter_wc_shortcode_products_query( $query_args, $atts, $type ) {
		fsapf_profiler_begin();
		if ( ! $this->has_filter_request() ) {
			fsapf_profiler_end();
			return $query_args;
		}

		$tax_query  = isset( $query_args['tax_query'] ) && is_array( $query_args['tax_query'] ) ? $query_args['tax_query'] : array();
		$meta_query = isset( $query_args['meta_query'] ) && is_array( $query_args['meta_query'] ) ? $query_args['meta_query'] : array();

		if ( $this->should_apply_price_filter() ) {
			$bounds = $this->get_price_bounds();
			$min = isset( $_GET['filter_min_price'] ) && '' !== $_GET['filter_min_price'] ? (float) wc_clean( wp_unslash( $_GET['filter_min_price'] ) ) : (float) $bounds['min'];
			$max = isset( $_GET['filter_max_price'] ) && '' !== $_GET['filter_max_price'] ? (float) wc_clean( wp_unslash( $_GET['filter_max_price'] ) ) : (float) $bounds['max'];
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

		fsapf_profiler_end();
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

		$cache_key = md5( wp_json_encode( $attribute_map ) );
		if ( isset( $this->attribute_match_cache[ $cache_key ] ) && is_array( $this->attribute_match_cache[ $cache_key ] ) ) {
			return $this->attribute_match_cache[ $cache_key ];
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

		$result = array_map( 'absint', $all_ids );
		$this->attribute_match_cache[ $cache_key ] = $result;
		return $result;
	}

	private function should_apply_price_filter() {
		if ( ! isset( $_GET['filter_min_price'] ) && ! isset( $_GET['filter_max_price'] ) ) {
			return false;
		}

		$bounds = $this->get_price_bounds();
		$min = isset( $_GET['filter_min_price'] ) && '' !== $_GET['filter_min_price'] ? (float) wc_clean( wp_unslash( $_GET['filter_min_price'] ) ) : (float) $bounds['min'];
		$max = isset( $_GET['filter_max_price'] ) && '' !== $_GET['filter_max_price'] ? (float) wc_clean( wp_unslash( $_GET['filter_max_price'] ) ) : (float) $bounds['max'];

		return ( $min > (float) $bounds['min'] ) || ( $max < (float) $bounds['max'] );
	}

	private function get_price_bounds() {
		$category_id = $this->get_context_category_id();
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

	private function get_context_category_id() {
		$category_id = 0;

		if ( is_product_category() ) {
			$queried = get_queried_object();
			if ( $queried instanceof WP_Term ) {
				$category_id = (int) $queried->term_id;
			}
		}

		if ( $category_id < 1 ) {
			$qv = get_query_var( 'product_cat' );
			if ( is_string( $qv ) && '' !== trim( $qv ) ) {
				$term = get_term_by( 'slug', sanitize_title( $qv ), 'product_cat' );
				if ( $term instanceof WP_Term ) {
					$category_id = (int) $term->term_id;
				}
			}
		}

		return $category_id > 0 ? (int) fsapf_get_context_category_id( $category_id ) : 0;
	}
}


