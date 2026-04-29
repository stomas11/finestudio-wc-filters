<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Auto_Product_Filters_Polylang {
	public function translate_term_id( $term_id, $taxonomy ) {
		if ( function_exists( 'pll_get_term' ) ) {
			$translated = pll_get_term( $term_id );
			if ( $translated ) {
				return (int) $translated;
			}
		}
		return (int) $term_id;
	}
}


