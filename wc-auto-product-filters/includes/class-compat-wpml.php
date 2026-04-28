<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Auto_Product_Filters_WPML {
	public function translate_term_id( $term_id, $taxonomy ) {
		if ( function_exists( 'wpml_object_id_filter' ) ) {
			$translated = wpml_object_id_filter( $term_id, $taxonomy, true );
			if ( $translated ) {
				return (int) $translated;
			}
		}
		return (int) $term_id;
	}
}
