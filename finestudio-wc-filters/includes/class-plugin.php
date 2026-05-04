<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Auto_Product_Filters_Plugin {
	private $discovery;
	private $renderer;
	private $query;
	private $shortcode;
	private $ajax;
	private $admin;

	public function init() {
		load_plugin_textdomain( 'finestudio-wc-filters', false, dirname( plugin_basename( FSAPF_FILE ) ) . '/languages' );

		$this->discovery = new WC_Auto_Product_Filters_Discovery();
		$this->renderer  = new WC_Auto_Product_Filters_Renderer( $this->discovery );
		$this->query     = new WC_Auto_Product_Filters_Query();
		$this->shortcode = new WC_Auto_Product_Filters_Shortcode( $this->discovery, $this->renderer );
		$this->ajax      = new WC_Auto_Product_Filters_Ajax( $this->discovery, $this->renderer );
		$this->admin     = new WC_Auto_Product_Filters_Admin( $this->discovery );

		$this->shortcode->init();
		$this->query->init();
		$this->admin->init();

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'woocommerce_no_products_found', array( $this, 'render_filters_on_no_results' ), 1 );
		add_filter( 'wp_kses_allowed_html', array( $this, 'allow_filter_form_tags' ), 20, 2 );
		add_action( 'save_post_product', 'fsapf_invalidate_discovery_cache' );
		add_action( 'created_term', 'fsapf_invalidate_discovery_cache' );
		add_action( 'edited_term', 'fsapf_invalidate_discovery_cache' );
		add_action( 'delete_term', 'fsapf_invalidate_discovery_cache' );
	}

	public function enqueue_frontend_assets() {
		$css_file = FSAPF_PATH . 'assets/css/frontend.css';
		$js_file  = FSAPF_PATH . 'assets/js/frontend.js';
		$css_ver  = file_exists( $css_file ) ? (string) filemtime( $css_file ) : FSAPF_VERSION;
		$js_ver   = file_exists( $js_file ) ? (string) filemtime( $js_file ) : FSAPF_VERSION;

		wp_enqueue_style( 'wcapf-frontend', FSAPF_URL . 'assets/css/frontend.css', array(), $css_ver );

		$settings = fsapf_get_global_settings();
		wp_enqueue_script( 'wcapf-frontend', FSAPF_URL . 'assets/js/frontend.js', array( 'jquery' ), $js_ver, true );
		wp_localize_script(
			'wcapf-frontend',
			'wcapfData',
			array(
				'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
				'nonce'           => wp_create_nonce( 'fsapf_ajax_filter' ),
				'ajaxEnabled'     => (int) $settings['ajax_enabled'],
				'autoSubmit'      => (int) $settings['auto_submit'],
				'updateBrowserUrl'=> (int) $settings['update_browser_url'],
				'productsSelector'=> $settings['products_selector'],
				'productsContainerId' => $settings['products_container_id'],
				'submitMode'      => $settings['submit_mode'],
				'strings'         => array(
					'showMoreOptions'  => __( 'Show more options', 'finestudio-wc-filters' ),
					'showingOneResult' => __( 'Showing 1 result', 'finestudio-wc-filters' ),
					'showingResults'   => __( 'Showing %d results', 'finestudio-wc-filters' ),
					'showAllFilters'   => __( 'Show all filters', 'finestudio-wc-filters' ),
				),
			)
		);
	}

	public function allow_filter_form_tags( $allowed_tags, $context ) {
		if ( 'post' !== $context ) {
			return $allowed_tags;
		}

		$allowed_tags['form'] = array(
			'class'  => true,
			'method' => true,
			'action' => true,
		);
		$allowed_tags['input'] = array(
			'type'        => true,
			'name'        => true,
			'value'       => true,
			'placeholder' => true,
			'checked'     => true,
			'class'       => true,
			'step'        => true,
			'min'         => true,
			'max'         => true,
			'id'          => true,
		);
		$allowed_tags['select'] = array(
			'name'     => true,
			'class'    => true,
			'multiple' => true,
			'id'       => true,
		);
		$allowed_tags['option'] = array(
			'value'    => true,
			'selected' => true,
		);
			$allowed_tags['button'] = array(
				'type'  => true,
				'class' => true,
				'aria-label' => true,
			);
			$allowed_tags['span'] = array(
				'class' => true,
				'aria-hidden' => true,
				'style' => true,
			);
			$allowed_tags['svg'] = array(
				'viewbox'    => true,
				'role'       => true,
				'focusable'  => true,
				'aria-hidden'=> true,
				'class'      => true,
				'width'      => true,
				'height'     => true,
				'fill'       => true,
				'stroke'     => true,
			);
			$allowed_tags['path'] = array(
				'd'               => true,
				'fill'            => true,
				'stroke'          => true,
				'stroke-width'    => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
			);

			return $allowed_tags;
		}

	public function render_filters_on_no_results() {
		if ( ! ( is_shop() || is_product_taxonomy() || is_search() ) ) {
			return;
		}

		if ( WC_Auto_Product_Filters_Shortcode::did_render() ) {
			return;
		}

		echo do_shortcode( '[fs_product_filters]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

}


