<?php namespace kebabble\config\taxonomy;

defined( 'ABSPATH' ) or die( 'Operation not permitted.' );

class orders extends taxonomy {
	/**
	 * Creates a new custom post type for orders.
	 * @return void
	 */
	public static function orders() {
		register_post_type( 'kebabble_orders', [
			'label'               => __( 'Order', 'text_domain' ),
			'description'         => __( 'Order list', 'text_domain' ),
			'labels'              => [
				'name'                => _x( 'Orders', 'Post Type General Name', 'text_domain' ),
				'singular_name'       => _x( 'Order', 'Post Type Singular Name', 'text_domain' ),
				'menu_name'           => __( 'Orders', 'text_domain' ),
				'name_admin_bar'      => __( 'Orders', 'text_domain' ),
				'parent_item_colon'   => __( 'Parent order:', 'text_domain' ),
				'all_items'           => __( 'All orders', 'text_domain' ),
				'add_new_item'        => __( 'Add new order', 'text_domain' ),
				'add_new'             => __( 'New order', 'text_domain' ),
				'new_item'            => __( 'New order', 'text_domain' ),
				'edit_item'           => __( 'Edit order', 'text_domain' ),
				'update_item'         => __( 'Update order', 'text_domain' ),
				'view_item'           => __( 'View order', 'text_domain' ),
				'search_items'        => __( 'Search orders', 'text_domain' ),
				'not_found'           => __( 'Not found', 'text_domain' ),
				'not_found_in_trash'  => __( 'Not found in Trash', 'text_domain' ),
			],
			'supports'            => false,
			'taxonomies'          => ['kebabble_orders'],
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 25,
			'menu_icon'           => plugin_dir_url( __FILE__ ) . "../../../resource/1f959.png",
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'rewrite'             => [
				'slug'                => 'kebabble_orders',
				'with_front'          => true,
				'pages'               => true,
				'feeds'               => true,
			],
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
		] );
	}
}