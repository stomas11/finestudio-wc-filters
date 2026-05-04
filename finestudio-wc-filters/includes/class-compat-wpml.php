<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Auto_Product_Filters_WPML {
	const STRING_CONTEXT = 'Finestudio WC Filters';

	public function translate_term_id( $term_id, $taxonomy ) {
		if ( function_exists( 'wpml_object_id_filter' ) ) {
			$translated = wpml_object_id_filter( $term_id, $taxonomy, true );
			if ( $translated ) {
				return (int) $translated;
			}
		}
		return (int) $term_id;
	}

	public static function register_filter_label_strings() {
		$settings = fsapf_get_filter_settings();
		foreach ( $settings as $filter_key => $filter_settings ) {
			if ( empty( $filter_settings['label'] ) ) {
				continue;
			}

			self::register_string(
				self::global_filter_label_name( $filter_key ),
				sanitize_text_field( $filter_settings['label'] )
			);
		}

		$overrides = fsapf_get_category_overrides();
		foreach ( $overrides as $category_id => $override ) {
			if ( empty( $override['label'] ) || ! is_array( $override['label'] ) ) {
				continue;
			}

			foreach ( $override['label'] as $filter_key => $label ) {
				$label = sanitize_text_field( $label );
				if ( '' === trim( $label ) ) {
					continue;
				}

				self::register_string(
					self::category_filter_label_name( $category_id, $override, $filter_key ),
					$label
				);
			}
		}
	}

	public static function translate_filter_label( $filter_key, $label ) {
		$label = (string) $label;
		if ( '' === $label ) {
			return $label;
		}

		return apply_filters(
			'wpml_translate_single_string',
			$label,
			self::STRING_CONTEXT,
			self::global_filter_label_name( $filter_key )
		);
	}

	public static function translate_category_filter_label( $category_id, $override, $filter_key, $label ) {
		$label = (string) $label;
		if ( '' === $label ) {
			return $label;
		}

		return apply_filters(
			'wpml_translate_single_string',
			$label,
			self::STRING_CONTEXT,
			self::category_filter_label_name( $category_id, $override, $filter_key )
		);
	}

	private static function register_string( $name, $value ) {
		$value = (string) $value;
		if ( '' === trim( $value ) ) {
			return;
		}

		do_action( 'wpml_register_single_string', self::STRING_CONTEXT, $name, $value );
	}

	private static function global_filter_label_name( $filter_key ) {
		return 'Filter label - ' . sanitize_key( $filter_key );
	}

	private static function category_filter_label_name( $category_id, $override, $filter_key ) {
		$category_ref = 'category-' . absint( $category_id );
		if ( is_array( $override ) && ! empty( $override['category_slug'] ) ) {
			$category_ref = 'category-' . sanitize_title( $override['category_slug'] );
		}

		return 'Category override label - ' . $category_ref . ' - ' . sanitize_key( $filter_key );
	}
}


