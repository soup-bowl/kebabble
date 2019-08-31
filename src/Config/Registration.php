<?php
/**
 * Food ordering management system for WordPress.
 *
 * @package kebabble
 * @author soup-bowl <code@soupbowl.io>
 * @license MIT
 */

namespace Kebabble\Config;

/**
 * Configures the objects and terms with WordPress.
 */
class Registration {
	/**
	 * Registers WordPress hooks.
	 *
	 * @return void
	 */
	public function hook_registration() {
		add_action( 'init', [ &$this, 'orders' ], 0 );
	}

	/**
	 * Creates a new custom post type for orders, and a accompanying company tax.
	 *
	 * @return void Registers with the WordPress CPT/Tax API.
	 */
	public function orders():void {
		register_post_type(
			'kebabble_orders',
			[
				'label'        => __( 'Orders', 'text_domain' ),
				'description'  => __( 'Order list', 'text_domain' ),
				'labels'       => $this->dymo( 'Order', 'Orders' ),
				'supports'     => false,
				'taxonomies'   => [ 'kebabble_company' ],
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => true,
				// base64_encode used for logo display purposes.
				// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions
				'menu_icon'    => 'data:image/svg+xml;base64,' . base64_encode( file_get_contents( __DIR__ . '/../../assets/menu-silhouette.svg' ) ),
				// phpcs:enable
				'rewrite'      => [
					'slug'       => 'kebabble_orders',
					'with_front' => true,
					'pages'      => true,
					'feeds'      => true,
				],
			]
		);

		register_taxonomy(
			'kebabble_company',
			[ 'kebabble_orders' ],
			[
				'label'             => __( 'Company', 'text_domain' ),
				'description'       => __( 'Company list', 'text_domain' ),
				'labels'            => $this->dymo( 'Company', 'Companies' ),
				'supports'          => false,
				'public'            => false,
				'show_ui'           => true,
				'show_in_menu'      => true,
				'meta_box_cb'       => false,
				'show_admin_column' => true,
			]
		);

		register_taxonomy(
			'kebabble_collector',
			[ 'kebabble_orders' ],
			[
				'label'             => __( 'Collector', 'text_domain' ),
				'description'       => __( 'Collector list', 'text_domain' ),
				'labels'            => $this->dymo( 'Collector', 'Collectors' ),
				'supports'          => false,
				'public'            => false,
				'show_ui'           => true,
				'show_in_menu'      => true,
				'meta_box_cb'       => false,
			]
		);
	}

	/**
	 * Generates tax/cpt labels.
	 *
	 * @param string $singular Singular name.
	 * @param string $plural   Pluralised name (blank will append an 's').
	 * @return string[]
	 */
	private function dymo( string $singular, string $plural = null ):array {
		$plural = ( empty( $plural ) ) ? "{$singular}s" : $plural;

		return [
			'name'          => _x( $plural, 'Post Type General Name', 'text_domain' ),
			'singular_name' => _x( $singular, 'Post Type Singular Name', 'text_domain' ),
			'add_new_item'  => __( "Add new {$singular}", 'text_domain' ),
			'edit_item'     => __( "Edit {$singular}", 'text_domain' ),
			'update_item'   => __( "Update {$singular}", 'text_domain' ),
			'view_item'     => __( "View {$singular}", 'text_domain' ),
			'search_items'  => __( "Search all {$plural}", 'text_domain' ),
		];
	}
}
