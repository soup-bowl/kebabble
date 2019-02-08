<?php
add_action( 'login_enqueue_scripts', function() { ?>
<style type="text/css">
	#login h1 a, .login h1 a {
		background-image: url( <?php echo trailingslashit( get_stylesheet_directory_uri() ); ?>kebabbleman.png);
		height: 70px;
		width: 100px;
		padding-bottom: 30px;
	}
	#backtoblog { display:none; }
</style>
<?php });

add_action( 'admin_menu', function() {
    remove_menu_page( 'edit.php' );
	remove_menu_page( 'edit.php?post_type=page' );
	remove_menu_page( 'edit-comments.php' );
	remove_menu_page( 'upload.php' );
});

add_action( 'wp_dashboard_setup', function() {
	global $wp_meta_boxes;

	unset( $wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press'] );
	unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_right_now'] );
	unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_activity'] );
});
