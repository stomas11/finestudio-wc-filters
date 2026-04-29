<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Auto_Product_Filters_Admin {
	private $discovery;

	public function __construct( $discovery ) {
		$this->discovery = $discovery;
	}

	public function init() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function register_menu() {
		add_menu_page( __( 'Product Filters', 'finestudio-wc-filters' ), __( 'Product Filters', 'finestudio-wc-filters' ), 'manage_woocommerce', 'wcapf-filters', array( $this, 'render_filters_page' ), 'dashicons-filter', 58 );
		add_submenu_page( 'wcapf-filters', __( 'Filter Overview', 'finestudio-wc-filters' ), __( 'Filter Overview', 'finestudio-wc-filters' ), 'manage_woocommerce', 'wcapf-filters', array( $this, 'render_filters_page' ) );
		add_submenu_page( 'wcapf-filters', __( 'Attribute Colors', 'finestudio-wc-filters' ), __( 'Attribute Colors', 'finestudio-wc-filters' ), 'manage_woocommerce', 'wcapf-colors', array( $this, 'render_colors_page' ) );
		add_submenu_page( 'wcapf-filters', __( 'Settings', 'finestudio-wc-filters' ), __( 'Settings', 'finestudio-wc-filters' ), 'manage_woocommerce', 'wcapf-settings', array( $this, 'render_settings_page' ) );
	}

	public function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, 'wcapf' ) ) {
			return;
		}

		wp_enqueue_style( 'wcapf-admin', FSAPF_URL . 'assets/css/admin.css', array(), FSAPF_VERSION );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'wcapf-admin', FSAPF_URL . 'assets/js/admin.js', array( 'jquery', 'jquery-ui-sortable' ), FSAPF_VERSION, true );
	}

	public function handle_save() {
		if ( empty( $_POST['fsapf_action'] ) || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		check_admin_referer( 'fsapf_save_settings' );

		$action = sanitize_key( wp_unslash( $_POST['fsapf_action'] ) );
		if ( 'save_filters' === $action ) {
			$this->save_filter_settings();
		} elseif ( 'save_colors' === $action ) {
			$this->save_color_settings();
		} elseif ( 'save_global' === $action ) {
			$this->save_global_settings();
		}

		fsapf_invalidate_discovery_cache();
	}

	private function save_filter_settings() {
		$filters = isset( $_POST['filters'] ) ? (array) wp_unslash( $_POST['filters'] ) : array();
		$clean   = array();
		foreach ( $filters as $key => $row ) {
			$key = sanitize_key( $key );
			$clean[ $key ] = array(
				'enabled'      => isset( $row['enabled'] ) ? 1 : 0,
				'label'        => sanitize_text_field( $row['label'] ?? '' ),
				'display_type' => fsapf_sanitize_display_type( $row['display_type'] ?? 'checkbox' ),
				'order'        => absint( $row['order'] ?? 999 ),
			);
		}
		update_option( 'fsapf_filter_settings', $clean );

		$all_filters = $this->discovery->discover_all_filters_for_admin();
		$all_keys    = array_keys( $all_filters );
		$overrides = array();
		$cats      = isset( $_POST['overrides'] ) ? (array) wp_unslash( $_POST['overrides'] ) : array();
		foreach ( $cats as $cat_id => $config ) {
			$cat_id = absint( $cat_id );
			if ( ! $cat_id ) {
				continue;
			}

			$hidden_csv   = sanitize_text_field( $config['hidden_filters_csv'] ?? '' );
			$hidden       = array_filter( array_map( 'sanitize_key', array_map( 'trim', explode( ',', $hidden_csv ) ) ) );
			$enabled_map  = ! empty( $config['enabled'] ) && is_array( $config['enabled'] ) ? $config['enabled'] : array();
			foreach ( $all_keys as $filter_key ) {
				if ( ! isset( $enabled_map[ $filter_key ] ) ) {
					$hidden[] = sanitize_key( $filter_key );
				}
			}
			$hidden = array_values( array_unique( $hidden ) );

			$order = array();
			if ( ! empty( $config['order'] ) && is_array( $config['order'] ) ) {
				foreach ( $config['order'] as $filter_key => $value ) {
					$order[ sanitize_key( $filter_key ) ] = absint( $value );
				}
			}

			$label = array();
			if ( ! empty( $config['label'] ) && is_array( $config['label'] ) ) {
				foreach ( $config['label'] as $filter_key => $value ) {
					$label[ sanitize_key( $filter_key ) ] = sanitize_text_field( $value );
				}
			}

			$display_type = array();
			if ( ! empty( $config['display_type'] ) && is_array( $config['display_type'] ) ) {
				foreach ( $config['display_type'] as $filter_key => $value ) {
					$display_type[ sanitize_key( $filter_key ) ] = fsapf_sanitize_display_type( $value );
				}
			}

			$term = get_term( $cat_id, 'product_cat' );
			$overrides[ $cat_id ] = array(
				'category_slug' => $term instanceof WP_Term ? sanitize_title( $term->slug ) : '',
				'hidden_filters'=> $hidden,
				'order'         => $order,
				'label'         => $label,
				'display_type'  => $display_type,
			);
		}

		update_option( 'fsapf_category_overrides', $overrides );
	}

	private function save_color_settings() {
		$colors = isset( $_POST['colors'] ) ? (array) wp_unslash( $_POST['colors'] ) : array();
		$clean  = array();
		foreach ( $colors as $tax => $pairs ) {
			$tax = sanitize_key( $tax );
			foreach ( (array) $pairs as $slug => $hex ) {
				$slug = sanitize_title( $slug );
				$hex  = sanitize_hex_color( $hex );
				if ( $hex ) {
					$clean[ $tax ][ $slug ] = $hex;
				}
			}
		}
		update_option( 'fsapf_color_swatches', $clean );
	}

	private function save_global_settings() {
		$submit_mode = sanitize_key( wp_unslash( $_POST['submit_mode'] ?? 'auto' ) );
		if ( ! in_array( $submit_mode, array( 'auto', 'button' ), true ) ) {
			$submit_mode = 'auto';
		}

		$color_attributes_raw = sanitize_text_field( wp_unslash( $_POST['color_attributes'] ?? '' ) );
		$color_attributes     = array_filter(
			array_map(
				'sanitize_key',
				array_map( 'trim', explode( ',', $color_attributes_raw ) )
			)
		);

		$filters_layout = sanitize_key( wp_unslash( $_POST['filters_layout'] ?? 'stacked' ) );
		if ( ! in_array( $filters_layout, array( 'stacked', 'columns' ), true ) ) {
			$filters_layout = 'stacked';
		}

		$visible_filters = absint( wp_unslash( $_POST['visible_filters'] ?? 3 ) );
		if ( $visible_filters < 1 ) {
			$visible_filters = 3;
		}

		$filters_columns_desktop = absint( wp_unslash( $_POST['filters_columns_desktop'] ?? 3 ) );
		if ( $filters_columns_desktop < 1 ) {
			$filters_columns_desktop = 3;
		}
		if ( $filters_columns_desktop > 6 ) {
			$filters_columns_desktop = 6;
		}

			$settings = array(
			'ajax_enabled'       => isset( $_POST['ajax_enabled'] ) ? 1 : 0,
			'auto_submit'        => isset( $_POST['auto_submit'] ) ? 1 : 0,
			'update_browser_url' => isset( $_POST['update_browser_url'] ) ? 1 : 0,
			'products_selector'  => sanitize_text_field( wp_unslash( $_POST['products_selector'] ?? '.woocommerce ul.products' ) ),
			'products_container_id' => sanitize_key( wp_unslash( $_POST['products_container_id'] ?? '' ) ),
			'submit_mode'        => $submit_mode,
			'color_attributes'   => array_values( array_unique( $color_attributes ) ),
			'filters_layout'     => $filters_layout,
			'filters_columns_desktop' => $filters_columns_desktop,
			'visible_filters'    => $visible_filters,
				'sidebar_panel_enabled' => isset( $_POST['sidebar_panel_enabled'] ) ? 1 : 0,
				'collapse_filters_enabled' => isset( $_POST['collapse_filters_enabled'] ) ? 1 : 0,
				'mobile_button_only_enabled' => isset( $_POST['mobile_button_only_enabled'] ) ? 1 : 0,
			);
		update_option( 'fsapf_global_settings', $settings );
	}

	public function render_filters_page() {
		$context  = array( 'type' => 'shop', 'category_id' => 0 );
		$filters  = $this->discovery->discover_all_filters_for_admin();
		$cats     = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
		$override = fsapf_get_category_overrides();
		$saved    = fsapf_get_filter_settings();
		$types    = array( 'checkbox', 'radio', 'select', 'multiselect', 'range', 'swatches' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Product Filters', 'finestudio-wc-filters' ); ?></h1>
			<form method="post">
				<?php wp_nonce_field( 'fsapf_save_settings' ); ?>
				<input type="hidden" name="fsapf_action" value="save_filters" />
				<table class="widefat striped wcapf-sortable-table">
					<thead><tr><th><?php esc_html_e( 'Order', 'finestudio-wc-filters' ); ?></th><th><?php esc_html_e( 'Filter', 'finestudio-wc-filters' ); ?></th><th><?php esc_html_e( 'Enabled', 'finestudio-wc-filters' ); ?></th><th><?php esc_html_e( 'Label', 'finestudio-wc-filters' ); ?></th><th><?php esc_html_e( 'Type', 'finestudio-wc-filters' ); ?></th></tr></thead>
					<tbody id="wcapf-global-sortable">
						<?php foreach ( $filters as $key => $filter ) : ?>
							<?php $display_type = $saved[ $key ]['display_type'] ?? $filter['display_type']; ?>
							<?php $row_order = isset( $saved[ $key ]['order'] ) ? (int) $saved[ $key ]['order'] : (int) $filter['order']; ?>
							<tr data-filter-key="<?php echo esc_attr( $key ); ?>">
								<td><input class="wcapf-order" type="number" name="filters[<?php echo esc_attr( $key ); ?>][order]" value="<?php echo esc_attr( (string) $row_order ); ?>" /></td>
								<td><strong><?php echo esc_html( $key ); ?></strong></td>
								<td><input type="checkbox" name="filters[<?php echo esc_attr( $key ); ?>][enabled]" value="1" <?php checked( isset( $saved[ $key ]['enabled'] ) ? (int) $saved[ $key ]['enabled'] : 1, 1 ); ?> /></td>
								<td><input type="text" name="filters[<?php echo esc_attr( $key ); ?>][label]" value="<?php echo esc_attr( $saved[ $key ]['label'] ?? $filter['label'] ); ?>" /></td>
								<td>
									<select name="filters[<?php echo esc_attr( $key ); ?>][display_type]">
										<?php foreach ( $types as $type ) : ?>
											<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $display_type, $type ); ?>><?php echo esc_html( $type ); ?></option>
										<?php endforeach; ?>
									</select>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<h2><?php esc_html_e( 'Category Overrides', 'finestudio-wc-filters' ); ?></h2>
				<?php foreach ( $cats as $cat ) : ?>
					<?php $cat_override = isset( $override[ $cat->term_id ] ) ? $override[ $cat->term_id ] : array(); ?>
					<div class="wcapf-override-box">
						<h3><?php echo esc_html( $cat->name ); ?></h3>
						<p>
							<label><?php esc_html_e( 'Hidden filter keys (comma separated)', 'finestudio-wc-filters' ); ?></label>
							<input type="text" name="overrides[<?php echo esc_attr( (string) $cat->term_id ); ?>][hidden_filters_csv]" value="<?php echo esc_attr( isset( $cat_override['hidden_filters'] ) ? implode( ',', $cat_override['hidden_filters'] ) : '' ); ?>" />
						</p>
						<table class="widefat striped">
							<thead><tr><th><?php esc_html_e( 'Order', 'finestudio-wc-filters' ); ?></th><th><?php esc_html_e( 'Filter', 'finestudio-wc-filters' ); ?></th><th><?php esc_html_e( 'Enabled', 'finestudio-wc-filters' ); ?></th><th><?php esc_html_e( 'Label', 'finestudio-wc-filters' ); ?></th><th><?php esc_html_e( 'Type', 'finestudio-wc-filters' ); ?></th></tr></thead>
							<tbody>
							<?php foreach ( $filters as $key => $filter ) : ?>
								<tr>
									<td><input type="number" name="overrides[<?php echo esc_attr( (string) $cat->term_id ); ?>][order][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( isset( $cat_override['order'][ $key ] ) ? (string) $cat_override['order'][ $key ] : '' ); ?>" /></td>
									<td><?php echo esc_html( $key ); ?></td>
									<td>
										<input type="checkbox" name="overrides[<?php echo esc_attr( (string) $cat->term_id ); ?>][enabled][<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( ! in_array( $key, $cat_override['hidden_filters'] ?? array(), true ), true ); ?> />
									</td>
									<td><input type="text" name="overrides[<?php echo esc_attr( (string) $cat->term_id ); ?>][label][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $cat_override['label'][ $key ] ?? '' ); ?>" /></td>
									<td>
										<select name="overrides[<?php echo esc_attr( (string) $cat->term_id ); ?>][display_type][<?php echo esc_attr( $key ); ?>]">
											<option value=""><?php esc_html_e( 'Default', 'finestudio-wc-filters' ); ?></option>
											<?php foreach ( $types as $type ) : ?>
												<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $cat_override['display_type'][ $key ] ?? '', $type ); ?>><?php echo esc_html( $type ); ?></option>
											<?php endforeach; ?>
										</select>
									</td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endforeach; ?>

				<?php submit_button( __( 'Save filters', 'finestudio-wc-filters' ) ); ?>
			</form>
		</div>
		<?php
	}

	public function render_colors_page() {
		$swatches   = fsapf_get_color_swatches();
		$taxonomies = fsapf_get_color_attributes();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Attribute Colors', 'finestudio-wc-filters' ); ?></h1>
			<?php if ( empty( $taxonomies ) ) : ?>
				<p><?php esc_html_e( 'No color attributes configured. Set them in Settings -> Color attributes (taxonomy keys, comma separated).', 'finestudio-wc-filters' ); ?></p>
			<?php endif; ?>
			<form method="post">
				<?php wp_nonce_field( 'fsapf_save_settings' ); ?>
				<input type="hidden" name="fsapf_action" value="save_colors" />
				<?php foreach ( $taxonomies as $taxonomy ) : $terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) ); ?>
					<h2><?php echo esc_html( wc_attribute_label( $taxonomy ) ); ?> (<?php echo esc_html( $taxonomy ); ?>)</h2>
					<table class="widefat striped">
						<tbody>
						<?php foreach ( $terms as $term ) : ?>
							<tr>
								<td><?php echo esc_html( $term->name ); ?></td>
								<td><input type="color" name="colors[<?php echo esc_attr( $taxonomy ); ?>][<?php echo esc_attr( $term->slug ); ?>]" value="<?php echo esc_attr( $swatches[ $taxonomy ][ $term->slug ] ?? '#d1d5db' ); ?>" /></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endforeach; ?>
				<?php submit_button( __( 'Save colors', 'finestudio-wc-filters' ) ); ?>
			</form>
		</div>
		<?php
	}

	public function render_settings_page() {
		$settings = fsapf_get_global_settings();
		$color_attributes_value = implode( ',', fsapf_get_color_attributes() );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Settings', 'finestudio-wc-filters' ); ?></h1>
			<form method="post">
				<?php wp_nonce_field( 'fsapf_save_settings' ); ?>
				<input type="hidden" name="fsapf_action" value="save_global" />
				<p><label><input type="checkbox" name="ajax_enabled" value="1" <?php checked( (int) $settings['ajax_enabled'], 1 ); ?> /> <?php esc_html_e( 'Enable AJAX filtering', 'finestudio-wc-filters' ); ?></label></p>
				<p><label><input type="checkbox" name="auto_submit" value="1" <?php checked( (int) $settings['auto_submit'], 1 ); ?> /> <?php esc_html_e( 'Auto submit on change', 'finestudio-wc-filters' ); ?></label></p>
				<p><label><input type="checkbox" name="update_browser_url" value="1" <?php checked( (int) $settings['update_browser_url'], 1 ); ?> /> <?php esc_html_e( 'Update browser URL', 'finestudio-wc-filters' ); ?></label></p>
				<p><label><?php esc_html_e( 'Submit mode', 'finestudio-wc-filters' ); ?>
					<select name="submit_mode">
						<option value="auto" <?php selected( $settings['submit_mode'], 'auto' ); ?>><?php esc_html_e( 'Automatic', 'finestudio-wc-filters' ); ?></option>
						<option value="button" <?php selected( $settings['submit_mode'], 'button' ); ?>><?php esc_html_e( 'Button only', 'finestudio-wc-filters' ); ?></option>
					</select>
				</label></p>
				<p><label><?php esc_html_e( 'Products container selector', 'finestudio-wc-filters' ); ?> <input type="text" name="products_selector" value="<?php echo esc_attr( $settings['products_selector'] ); ?>" /></label></p>
				<p><label><?php esc_html_e( 'Products container ID (without #)', 'finestudio-wc-filters' ); ?> <input type="text" name="products_container_id" value="<?php echo esc_attr( $settings['products_container_id'] ); ?>" placeholder="products-list" /></label></p>
				<p><label><?php esc_html_e( 'Color attributes (taxonomy keys, comma separated)', 'finestudio-wc-filters' ); ?> <input type="text" name="color_attributes" value="<?php echo esc_attr( $color_attributes_value ); ?>" placeholder="pa_farba,pa_color" /></label></p>
				<p><label><?php esc_html_e( 'Filters layout', 'finestudio-wc-filters' ); ?>
					<select name="filters_layout">
						<option value="stacked" <?php selected( $settings['filters_layout'], 'stacked' ); ?>><?php esc_html_e( 'Stacked', 'finestudio-wc-filters' ); ?></option>
						<option value="columns" <?php selected( $settings['filters_layout'], 'columns' ); ?>><?php esc_html_e( 'Columns on desktop', 'finestudio-wc-filters' ); ?></option>
					</select>
				</label></p>
				<p><label><?php esc_html_e( 'Columns count on desktop', 'finestudio-wc-filters' ); ?> <input type="number" min="1" max="6" step="1" name="filters_columns_desktop" value="<?php echo esc_attr( (string) $settings['filters_columns_desktop'] ); ?>" /></label></p>
					<p><label><input type="checkbox" name="sidebar_panel_enabled" value="1" <?php checked( (int) $settings['sidebar_panel_enabled'], 1 ); ?> /> <?php esc_html_e( 'Show filters in sidebar panel (desktop + mobile)', 'finestudio-wc-filters' ); ?></label></p>
					<p><label><input type="checkbox" name="mobile_button_only_enabled" value="1" <?php checked( (int) $settings['mobile_button_only_enabled'], 1 ); ?> /> <?php esc_html_e( 'On mobile show only "Filter" button and open filters panel on click', 'finestudio-wc-filters' ); ?></label></p>
					<p><label><input type="checkbox" name="collapse_filters_enabled" value="1" <?php checked( (int) $settings['collapse_filters_enabled'], 1 ); ?> /> <?php esc_html_e( 'Collapse to first filters + Show all button', 'finestudio-wc-filters' ); ?></label></p>
				<p><label><?php esc_html_e( 'Visible filters before "Show all"', 'finestudio-wc-filters' ); ?> <input type="number" min="1" step="1" name="visible_filters" value="<?php echo esc_attr( (string) $settings['visible_filters'] ); ?>" /></label></p>
				<?php submit_button( __( 'Save settings', 'finestudio-wc-filters' ) ); ?>
			</form>
		</div>
		<?php
	}
}


