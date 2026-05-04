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

function fsapf_get_price_filter_currency() {
	$currency = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : get_option( 'woocommerce_currency', '' );
	$currency = apply_filters( 'wcml_price_currency', $currency );
	$currency = is_string( $currency ) ? strtoupper( sanitize_text_field( $currency ) ) : '';

	if ( '' === $currency ) {
		$currency = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : get_option( 'woocommerce_currency', '' );
		$currency = is_string( $currency ) ? strtoupper( sanitize_text_field( $currency ) ) : '';
	}

	return $currency;
}

function fsapf_get_price_filter_currency_symbol( $currency = '' ) {
	$currency = '' !== $currency ? $currency : fsapf_get_price_filter_currency();

	if ( function_exists( 'get_woocommerce_currency_symbol' ) ) {
		return get_woocommerce_currency_symbol( $currency );
	}

	return $currency;
}

function fsapf_get_submitted_price_filter_currency( $fallback = '' ) {
	$currency = '' !== $fallback ? strtoupper( sanitize_text_field( $fallback ) ) : fsapf_get_price_filter_currency();

	if ( isset( $_GET['wcapf_price_currency'] ) && ! is_array( $_GET['wcapf_price_currency'] ) ) {
		$submitted_currency = strtoupper( sanitize_text_field( wp_unslash( $_GET['wcapf_price_currency'] ) ) );
		if ( '' !== $submitted_currency ) {
			$currency = $submitted_currency;
		}
	}

	return $currency;
}

function fsapf_get_wcml_multi_currency() {
	global $woocommerce_wpml;

	if ( isset( $woocommerce_wpml->multi_currency ) && is_object( $woocommerce_wpml->multi_currency ) ) {
		return $woocommerce_wpml->multi_currency;
	}

	if ( function_exists( 'WPML\\Container\\make' ) && class_exists( 'WCML_Multi_Currency' ) ) {
		try {
			$multi_currency = \WPML\Container\make( 'WCML_Multi_Currency' );
			if ( is_object( $multi_currency ) ) {
				return $multi_currency;
			}
		} catch ( Exception $e ) {
			return null;
		}
	}

	return null;
}

function fsapf_get_price_filter_default_currency() {
	$currency       = get_option( 'woocommerce_currency', '' );
	$multi_currency = fsapf_get_wcml_multi_currency();

	if ( is_object( $multi_currency ) && is_callable( array( $multi_currency, 'get_default_currency' ) ) ) {
		$default_currency = $multi_currency->get_default_currency();
		if ( is_string( $default_currency ) && '' !== $default_currency ) {
			$currency = $default_currency;
		}
	}

	return is_string( $currency ) ? strtoupper( sanitize_text_field( $currency ) ) : '';
}

function fsapf_price_to_display_currency( $price, $currency = '' ) {
	$price    = (float) $price;
	$currency = '' !== $currency ? $currency : fsapf_get_price_filter_currency();

	$converted = apply_filters( 'wcml_raw_price_amount', $price, $currency );
	if ( is_numeric( $converted ) ) {
		return (float) $converted;
	}

	return $price;
}

function fsapf_price_to_database_currency( $price, $currency = '' ) {
	$price            = (float) $price;
	$currency         = '' !== $currency ? strtoupper( sanitize_text_field( $currency ) ) : fsapf_get_price_filter_currency();
	$default_currency = fsapf_get_price_filter_default_currency();

	if ( '' !== $currency && '' !== $default_currency && $currency === $default_currency ) {
		return $price;
	}

	$multi_currency = fsapf_get_wcml_multi_currency();
	if ( is_object( $multi_currency ) && isset( $multi_currency->prices ) && is_object( $multi_currency->prices ) && is_callable( array( $multi_currency->prices, 'unconvert_price_amount' ) ) ) {
		$converted = $multi_currency->prices->unconvert_price_amount( $price, $currency );
		if ( is_numeric( $converted ) ) {
			return (float) $converted;
		}
	}

	$exchange_rates = apply_filters( 'wcml_exchange_rates', array() );
	if ( is_array( $exchange_rates ) && isset( $exchange_rates[ $currency ] ) && (float) $exchange_rates[ $currency ] > 0 ) {
		return $price / (float) $exchange_rates[ $currency ];
	}

	return $price;
}

function fsapf_get_price_display_bounds( $bounds, $currency = '' ) {
	$raw_min = isset( $bounds['min'] ) ? (float) $bounds['min'] : 0;
	$raw_max = isset( $bounds['max'] ) ? (float) $bounds['max'] : 1000;
	$min     = floor( fsapf_price_to_display_currency( $raw_min, $currency ) );
	$max     = ceil( fsapf_price_to_display_currency( $raw_max, $currency ) );

	if ( $max <= $min ) {
		$max = $min + 1;
	}

	return array(
		'min' => $min,
		'max' => $max,
	);
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


