<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Auto_Product_Filters_Shortcode {
	private $discovery;
	private $renderer;
	private static $did_render = false;

	public function __construct( $discovery, $renderer ) {
		$this->discovery = $discovery;
		$this->renderer  = $renderer;
	}

	public function init() {
		add_shortcode( 'fs_product_filters', array( $this, 'render_shortcode' ) );
	}

	public function render_shortcode( $atts ) {
		fsapf_profiler_begin();
		self::$did_render = true;

		$atts = shortcode_atts(
			array(
				'context'  => 'auto',
				'category' => '',
			),
			$atts,
			'fs_product_filters'
		);

		$context = $this->discovery->get_context( $atts );
		$filters = $this->discovery->discover_filters( $context );
		$html = $this->renderer->render( $filters, $context );
		fsapf_profiler_end();
		return $html;
	}

	public static function did_render() {
		return self::$did_render;
	}
}


