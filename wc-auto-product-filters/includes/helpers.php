<?php
/**
 * Helper functions.
 *
 * @package WCAPF
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function wcapf_get_global_settings() {
	$defaults = array(
		'ajax_enabled'       => 0,
		'auto_submit'        => 1,
		'update_browser_url' => 1,
		'products_selector'  => '.woocommerce ul.products',
		'products_container_id' => '',
		'submit_mode'        => 'auto',
		'color_attributes'   => array(),
		'filters_layout'     => 'stacked',
		'filters_columns_desktop' => 3,
		'visible_filters'    => 3,
		'sidebar_panel_enabled' => 0,
		'collapse_filters_enabled' => 1,
	);

	$settings = get_option( 'wcapf_global_settings', array() );
	return wp_parse_args( is_array( $settings ) ? $settings : array(), $defaults );
}

function wcapf_get_filter_settings() {
	$settings = get_option( 'wcapf_filter_settings', array() );
	return is_array( $settings ) ? $settings : array();
}

function wcapf_get_category_overrides() {
	$overrides = get_option( 'wcapf_category_overrides', array() );
	return is_array( $overrides ) ? $overrides : array();
}

function wcapf_get_color_swatches() {
	$swatches = get_option( 'wcapf_color_swatches', array() );
	return is_array( $swatches ) ? $swatches : array();
}

function wcapf_sanitize_display_type( $type ) {
	$allowed = array( 'checkbox', 'radio', 'select', 'multiselect', 'range', 'swatches' );
	$type    = sanitize_key( $type );
	return in_array( $type, $allowed, true ) ? $type : 'checkbox';
}

function wcapf_get_color_attributes() {
	$settings = wcapf_get_global_settings();
	$raw      = isset( $settings['color_attributes'] ) ? $settings['color_attributes'] : array();
	if ( is_string( $raw ) ) {
		$raw = explode( ',', $raw );
	}
	if ( ! is_array( $raw ) ) {
		return array();
	}

	$clean = array();
	foreach ( $raw as $taxonomy ) {
		$taxonomy = sanitize_key( trim( (string) $taxonomy ) );
		if ( '' !== $taxonomy ) {
			$clean[] = $taxonomy;
		}
	}

	return array_values( array_unique( $clean ) );
}

function wcapf_get_term_translated_id( $term_id, $taxonomy ) {
	$term_id = (int) $term_id;

	if ( function_exists( 'wpml_object_id_filter' ) ) {
		$translated = wpml_object_id_filter( $term_id, $taxonomy, true );
		if ( $translated ) {
			return (int) $translated;
		}
	}

	if ( function_exists( 'pll_get_term' ) ) {
		$translated = pll_get_term( $term_id );
		if ( $translated ) {
			return (int) $translated;
		}
	}

	return $term_id;
}

function wcapf_get_context_category_id( $category_id ) {
	if ( ! $category_id ) {
		return 0;
	}

	$translated = wcapf_get_term_translated_id( (int) $category_id, 'product_cat' );
	return (int) $translated;
}

function wcapf_invalidate_discovery_cache() {
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wcapf_discovery_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_wcapf_discovery_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
}

function wcapf_profiler_init() {
	if ( ! isset( $GLOBALS['wcapf_profiler'] ) || ! is_array( $GLOBALS['wcapf_profiler'] ) ) {
		$GLOBALS['wcapf_profiler'] = array(
			'depth'   => 0,
			'queries' => 0,
		);
	}
}

function wcapf_profiler_begin() {
	wcapf_profiler_init();
	$GLOBALS['wcapf_profiler']['depth']++;
}

function wcapf_profiler_end() {
	wcapf_profiler_init();
	$GLOBALS['wcapf_profiler']['depth'] = max( 0, (int) $GLOBALS['wcapf_profiler']['depth'] - 1 );
}

function wcapf_profiler_capture_query() {
	wcapf_profiler_init();
	if ( (int) $GLOBALS['wcapf_profiler']['depth'] > 0 ) {
		$GLOBALS['wcapf_profiler']['queries']++;
	}
}

function wcapf_profiler_get_queries_count() {
	wcapf_profiler_init();
	return (int) $GLOBALS['wcapf_profiler']['queries'];
}
