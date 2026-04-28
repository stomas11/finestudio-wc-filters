<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Auto_Product_Filters_Ajax {
	private $discovery;
	private $renderer;

	public function __construct( $discovery, $renderer ) {
		$this->discovery = $discovery;
		$this->renderer  = $renderer;
	}

	public function init() {
		add_action( 'wp_ajax_wcapf_filter_products', array( $this, 'handle' ) );
		add_action( 'wp_ajax_nopriv_wcapf_filter_products', array( $this, 'handle' ) );
	}

	public function handle() {
		check_ajax_referer( 'wcapf_ajax_filter', 'nonce' );

		parse_str( isset( $_POST['query'] ) ? wp_unslash( $_POST['query'] ) : '', $query_vars );
		if ( ! is_array( $query_vars ) ) {
			$query_vars = array();
		}

		foreach ( $query_vars as $key => $value ) {
			$_GET[ sanitize_key( $key ) ] = $value;
		}

		$paged = isset( $query_vars['paged'] ) ? absint( $query_vars['paged'] ) : 1;
		if ( $paged < 1 ) {
			$paged = 1;
		}

		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => (int) apply_filters( 'loop_shop_per_page', wc_get_default_products_per_row() * wc_get_default_product_rows_per_page() ),
			'paged'          => $paged,
		);

		$query = new WP_Query( $args );

		ob_start();
		if ( $query->have_posts() ) {
			woocommerce_product_loop_start();
			while ( $query->have_posts() ) {
				$query->the_post();
				wc_get_template_part( 'content', 'product' );
			}
			woocommerce_product_loop_end();
		} else {
			do_action( 'woocommerce_no_products_found' );
		}
		$products_html = ob_get_clean();

		ob_start();
		echo paginate_links(
			array(
				'total'   => $query->max_num_pages,
				'current' => $paged,
				'format'  => '?paged=%#%',
				'type'    => 'list',
			)
		);
		$pagination_html = ob_get_clean();

		wp_reset_postdata();

		wp_send_json_success(
			array(
				'productsHtml'   => $products_html,
				'paginationHtml' => $pagination_html,
				'resultCount'    => (int) $query->found_posts,
			)
		);
	}
}
