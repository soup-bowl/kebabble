<?php
/**
 * Food ordering management system for WordPress.
 *
 * @package kebabble
 * @author soup-bowl <code@revive.today>
 * @license MIT
 */

namespace Kebabble\Config;

/**
 * Configures the objects and terms with WordPress.
 */
class Registration {
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
				'labels'       => [
					'name'          => _x( 'Orders', 'Post Type General Name', 'text_domain' ),
					'singular_name' => _x( 'Order', 'Post Type Singular Name', 'text_domain' ),
					'add_new_item'  => __( 'Add new order', 'text_domain' ),
					'edit_item'     => __( 'Edit order', 'text_domain' ),
					'update_item'   => __( 'Update order', 'text_domain' ),
					'view_item'     => __( 'View order', 'text_domain' ),
					'search_items'  => __( 'Search orders', 'text_domain' ),
				],
				'supports'     => false,
				'taxonomies'   => [ 'kebabble_company' ],
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => true,
				'menu_icon'    => plugin_dir_url( __FILE__ ) . '../../resource/kebab-s.png',
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
				'label'        => __( 'Company', 'text_domain' ),
				'description'  => __( 'Company list', 'text_domain' ),
				'labels'       => [
					'name'          => _x( 'Company', 'Post Type General Name', 'text_domain' ),
					'singular_name' => _x( 'Company', 'Post Type Singular Name', 'text_domain' ),
					'add_new_item'  => __( 'Add new company', 'text_domain' ),
					'edit_item'     => __( 'Edit company', 'text_domain' ),
					'update_item'   => __( 'Update company', 'text_domain' ),
					'view_item'     => __( 'View company', 'text_domain' ),
					'search_items'  => __( 'Search all companies', 'text_domain' ),
				],
				'supports'     => false,
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => true,
			]
		);
	}
}
