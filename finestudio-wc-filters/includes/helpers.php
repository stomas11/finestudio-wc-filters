<?php
/**
 * Helper functions.
 *
 * @package WCAPF
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function fsapf_get_option_with_legacy( $new_key, $legacy_key, $default = array() ) {
	$value = get_option( $new_key, null );
	if ( null !== $value ) {
		return $value;
	}

	$legacy = get_option( $legacy_key, null );
	if ( null !== $legacy ) {
		update_option( $new_key, $legacy );
		return $legacy;
	}

	return $default;
}

function fsapf_get_global_settings() {
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
		'mobile_button_only_enabled' => 0,
	);

	$settings = fsapf_get_option_with_legacy( 'fsapf_global_settings', 'wcapf_global_settings', array() );
	return wp_parse_args( is_array( $settings ) ? $settings : array(), $defaults );
}

function fsapf_get_filter_settings() {
	$settings = fsapf_get_option_with_legacy( 'fsapf_filter_settings', 'wcapf_filter_settings', array() );
	return is_array( $settings ) ? $settings : array();
}

function fsapf_get_category_overrides() {
	$overrides = fsapf_get_option_with_legacy( 'fsapf_category_overrides', 'wcapf_category_overrides', array() );
	return is_array( $overrides ) ? $overrides : array();
}

function fsapf_get_color_swatches() {
	$swatches = fsapf_get_option_with_legacy( 'fsapf_color_swatches', 'wcapf_color_swatches', array() );
	return is_array( $swatches ) ? $swatches : array();
}

function fsapf_sanitize_display_type( $type ) {
	$allowed = array( 'checkbox', 'radio', 'select', 'multiselect', 'range', 'swatches' );
	$type    = sanitize_key( $type );
	return in_array( $type, $allowed, true ) ? $type : 'checkbox';
}

function fsapf_get_color_attributes() {
	$settings = fsapf_get_global_settings();
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

function fsapf_get_term_translated_id( $term_id, $taxonomy ) {
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

function fsapf_get_context_category_id( $category_id ) {
	if ( ! $category_id ) {
		return 0;
	}

	$translated = fsapf_get_term_translated_id( (int) $category_id, 'product_cat' );
	return (int) $translated;
}

function fsapf_invalidate_discovery_cache() {
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_fsapf_discovery_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_fsapf_discovery_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
}

function fsapf_profiler_init() {
	if ( ! isset( $GLOBALS['fsapf_profiler'] ) || ! is_array( $GLOBALS['fsapf_profiler'] ) ) {
		$GLOBALS['fsapf_profiler'] = array(
			'depth'   => 0,
			'queries' => 0,
		);
	}
}

function fsapf_profiler_begin() {
	fsapf_profiler_init();
	$GLOBALS['fsapf_profiler']['depth']++;
}

function fsapf_profiler_end() {
	fsapf_profiler_init();
	$GLOBALS['fsapf_profiler']['depth'] = max( 0, (int) $GLOBALS['fsapf_profiler']['depth'] - 1 );
}

function fsapf_profiler_capture_query() {
	fsapf_profiler_init();
	if ( (int) $GLOBALS['fsapf_profiler']['depth'] > 0 ) {
		$GLOBALS['fsapf_profiler']['queries']++;
	}
}

function fsapf_profiler_get_queries_count() {
	fsapf_profiler_init();
	return (int) $GLOBALS['fsapf_profiler']['queries'];
}


