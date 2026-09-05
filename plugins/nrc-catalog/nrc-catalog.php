<?php
/**
 * Plugin Name: NRC 产品目录
 * Description: NihonReach 演示产品、分类与型号验证。产品使用经典编辑器。
 * Version: 1.0.0
 * License: GPL-2.0-or-later
 * Text Domain: nrc-catalog
 */

defined( 'ABSPATH' ) || exit;

/** Register product business objects independently of the theme. */
function nrc_register() {
	register_post_type(
		'nrc_product',
		array(
			'labels'          => array(
				'name'          => '产品',
				'singular_name' => '产品',
				'add_new_item'  => '新增产品',
				'edit_item'     => '编辑产品',
				'all_items'     => '所有产品',
			),
			'public'          => true,
			'has_archive'     => 'products',
			'rewrite'         => array( 'slug' => 'product' ),
			'menu_icon'       => 'dashicons-products',
			'supports'        => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'author' ),
			'capability_type' => 'post',
			'map_meta_cap'    => true,
			'show_in_rest'    => false,
		)
	);
	register_taxonomy(
		'nrc_category',
		'nrc_product',
		array(
			'labels'            => array(
				'name'          => '产品分类',
				'singular_name' => '产品分类',
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'product-category' ),
		)
	);
	register_post_meta(
		'nrc_product',
		'_nrc_model',
		array(
			'type'          => 'string',
			'single'        => true,
			'show_in_rest'  => false,
			'auth_callback' => function ( $allowed, $key, $post_id ) {
				return current_user_can( 'edit_post', $post_id );
			},
		)
	);
}
add_action( 'init', 'nrc_register' );
add_filter(
	'use_block_editor_for_post_type',
	function ( $use, $type ) {
		return 'nrc_product' === $type ? false : $use;
	},
	10,
	2
);

/** Products use a classic meta box with visible post-redirect validation feedback. */
function nrc_model_box( $post ) {
	wp_nonce_field( 'nrc_model_' . $post->ID, 'nrc_model_nonce' );
	echo '<label for="nrc-model-input">产品型号</label><br><input class="widefat" id="nrc-model-input" name="nrc_model" type="text" value="' . esc_attr( get_post_meta( $post->ID, '_nrc_model', true ) ) . '">';
	echo '<p>可留空；非空时仅允许大写字母、数字和横杠，最多 32 个字符。型号不是唯一库存编码。</p>';
}
add_action(
	'add_meta_boxes_nrc_product',
	function () {
		add_meta_box( 'nrc-model', '产品型号', 'nrc_model_box', 'nrc_product', 'normal', 'high' );
	}
);

/** Validate a submitted model without changing content. Missing and empty are distinct. */
function nrc_save_model( $post_id ) {
	if (
		'nrc_product' !== get_post_type( $post_id )
		|| wp_is_post_revision( $post_id )
		|| wp_is_post_autosave( $post_id )
		|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
	) {
		return;
	}
	if (
		! current_user_can( 'edit_post', $post_id )
		|| ! isset( $_POST['nrc_model_nonce'] )
		|| ! is_string( $_POST['nrc_model_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nrc_model_nonce'] ) ), 'nrc_model_' . $post_id )
	) {
		return;
	}
	if ( ! array_key_exists( 'nrc_model', $_POST ) ) {
		return;
	}
	// Preserve raw input so invalid characters are rejected rather than silently removed; allowlist below.
	$value = is_string( $_POST['nrc_model'] ) ? wp_unslash( $_POST['nrc_model'] ) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Strict full-string allowlist below.
	if ( null === $value || ( '' !== $value && ! preg_match( '/\A[A-Z0-9-]{1,32}\z/D', $value ) ) ) {
		set_transient( 'nrc_error_' . get_current_user_id() . '_' . $post_id, 1, 300 );
		return;
	}
	delete_transient( 'nrc_error_' . get_current_user_id() . '_' . $post_id );
	if ( '' === $value ) {
		delete_post_meta( $post_id, '_nrc_model' );
	} else {
		update_post_meta( $post_id, '_nrc_model', $value );
	}
}
add_action( 'save_post_nrc_product', 'nrc_save_model' );

/** Errors belong to the submitting user and the edited product. */
function nrc_model_notice() {
	$screen = get_current_screen();
	$id     = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
	if ( ! $screen || 'nrc_product' !== $screen->post_type || ! current_user_can( 'edit_post', $id ) ) {
		return;
	}
	$key = 'nrc_error_' . get_current_user_id() . '_' . $id;
	if ( get_transient( $key ) ) {
		delete_transient( $key );
		echo '<div class="notice notice-error"><p>型号未保存：仅允许大写字母、数字和横杠，最多 32 个字符（也可留空）。原型号已保留；标题和正文可能已经保存，请分别检查。</p></div>';
	}
}
add_action( 'admin_notices', 'nrc_model_notice' );

/** Public listings exclude password-protected content, including search. */
function nrc_public_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() || $query->is_singular() ) {
		return;
	}
	if ( $query->is_post_type_archive( 'nrc_product' ) || $query->is_tax( 'nrc_category' ) ) {
		$query->set( 'posts_per_page', 9 );
		$query->set( 'post_status', 'publish' );
		$query->set( 'has_password', false );
		$query->set( 'orderby', 'title' );
		$query->set( 'order', 'ASC' );
	}
	if ( $query->is_search() || $query->is_feed() ) {
		$query->set( 'has_password', false );
	}
}
add_action( 'pre_get_posts', 'nrc_public_query' );
register_activation_hook(
	__FILE__,
	function () {
		nrc_register();
		flush_rewrite_rules();
	}
);
register_deactivation_hook(
	__FILE__,
	function () {
		unregister_post_type( 'nrc_product' );
		unregister_taxonomy( 'nrc_category' );
		flush_rewrite_rules();
	}
);
