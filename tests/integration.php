<?php
/** Real WordPress integration assertions; only the dedicated test installation. */
if ( 'http://127.0.0.1:8083' !== get_option( 'home' ) ) { WP_CLI::error( 'Refusing to run outside the dedicated 8083 test database.' ); }
$GLOBALS['nrc_checks'] = 0;
function nrc_assert( $condition, $message ) {
	if ( ! $condition ) { WP_CLI::error( 'FAIL: ' . $message ); }
	++$GLOBALS['nrc_checks'];
	WP_CLI::log( 'PASS: ' . $message );
}
nrc_assert( post_type_exists( 'nrc_product' ), 'CPT registered' );
nrc_assert( is_object_in_taxonomy( 'nrc_product', 'nrc_category' ), 'taxonomy connected' );
$admin = get_user_by( 'login', getenv( 'NRC_ADMIN_USER' ) );
wp_set_current_user( $admin->ID );
$id = wp_insert_post( array( 'post_type' => 'nrc_product', 'post_title' => 'Integration temporary', 'post_status' => 'draft', 'post_author' => $admin->ID ) );
$nonce = wp_create_nonce( 'nrc_model_' . $id );
function nrc_submit_test( $id, $nonce, $value, $present = true ) {
	$_POST = array( 'nrc_model_nonce' => $nonce );
	if ( $present ) { $_POST['nrc_model'] = $value; }
	wp_update_post( array( 'ID' => $id, 'post_content' => 'Body saved separately' ) );
	$_POST = array();
}
nrc_submit_test( $id, $nonce, 'VALID-01' );
nrc_assert( 'VALID-01' === get_post_meta( $id, '_nrc_model', true ), 'valid model persisted through save_post' );
nrc_submit_test( $id, 'invalid', 'EVIL' );
nrc_assert( 'VALID-01' === get_post_meta( $id, '_nrc_model', true ), 'invalid nonce cannot modify' );
nrc_submit_test( $id, $nonce, 'bad value' );
nrc_assert( 'VALID-01' === get_post_meta( $id, '_nrc_model', true ), 'invalid format preserves original' );
nrc_assert( (bool) get_transient( 'nrc_error_' . $admin->ID . '_' . $id ), 'invalid value queues user-scoped feedback' );
require_once ABSPATH . 'wp-admin/includes/screen.php';
set_current_screen( 'nrc_product' ); $_GET['post'] = $id;
ob_start(); nrc_model_notice(); $notice = ob_get_clean();
nrc_assert( str_contains( $notice, '标题和正文可能已经保存' ), 'notice truthfully describes partial save' );
set_current_screen( 'front' ); $_GET = array();
nrc_submit_test( $id, $nonce, array( 'bad' ) );
nrc_assert( 'VALID-01' === get_post_meta( $id, '_nrc_model', true ), 'array input rejected' );
nrc_submit_test( $id, $nonce, str_repeat( 'A', 33 ) );
nrc_assert( 'VALID-01' === get_post_meta( $id, '_nrc_model', true ), 'overlength rejected' );
nrc_submit_test( $id, $nonce, null, false );
nrc_assert( 'VALID-01' === get_post_meta( $id, '_nrc_model', true ), 'missing field preserves original (quick edit)' );
nrc_submit_test( $id, $nonce, '' );
nrc_assert( ! metadata_exists( 'post', $id, '_nrc_model' ), 'explicit empty clears field' );
$users = array();
foreach ( array( 'subscriber', 'contributor', 'author', 'editor' ) as $role ) {
	$uid = wp_insert_user( array( 'user_login' => 'test-' . $role . '-' . wp_generate_password( 6, false ), 'user_pass' => wp_generate_password( 30 ), 'role' => $role ) );
	$users[] = $uid; wp_set_current_user( $uid );
	nrc_assert( current_user_can( 'edit_posts' ) === ( 'subscriber' !== $role ), $role . ' edit capability' );
	nrc_assert( current_user_can( 'publish_posts' ) === in_array( $role, array( 'author', 'editor' ), true ), $role . ' publish capability' );
	nrc_assert( current_user_can( 'delete_post', $id ) === ( 'editor' === $role ), $role . ' delete someone else product capability' );
	if ( 'subscriber' === $role ) {
		nrc_submit_test( $id, wp_create_nonce( 'nrc_model_' . $id ), 'DENIED' );
		nrc_assert( '' === get_post_meta( $id, '_nrc_model', true ), 'valid nonce without capability rejected' );
	}
}
wp_set_current_user( $admin->ID );
nrc_submit_test( $id, wp_create_nonce( 'nrc_model_' . $id ), 'KEEP-01' );
require_once ABSPATH . 'wp-admin/includes/plugin.php';
deactivate_plugins( 'nrc-catalog/nrc-catalog.php' );
nrc_assert( 'KEEP-01' === get_post_meta( $id, '_nrc_model', true ), 'deactivation preserves data' );
activate_plugin( 'nrc-catalog/nrc-catalog.php' ); nrc_register(); flush_rewrite_rules();
nrc_assert( 'KEEP-01' === get_post_meta( $id, '_nrc_model', true ), 'reactivation preserves data' );
wp_set_current_user( 0 );
$response = wp_remote_get( 'http://web/products/', array( 'redirection' => 0, 'headers' => array( 'Host' => '127.0.0.1:8083' ) ) );
$body = wp_remote_retrieve_body( $response );
nrc_assert( 200 === wp_remote_retrieve_response_code( $response ), 'public archive HTTP 200' );
nrc_assert( 9 === substr_count( $body, 'class="nrc-card"' ), 'archive first page has 9 public products' );
nrc_assert( ! str_contains( $body, 'HIDDEN-' ) && ! str_contains( $body, '测试产品' ), 'restricted products excluded' );
foreach ( array( 'draft', 'private', 'publish' ) as $status ) {
	$response = wp_remote_get( 'http://web/product/restricted-' . $status . '/', array( 'redirection' => 0, 'headers' => array( 'Host' => '127.0.0.1:8083' ) ) );
	$body = wp_remote_retrieve_body( $response );
	nrc_assert( ! str_contains( $body, 'HIDDEN-' ) && ! str_contains( $body, '仅供访问权限检查的非公开正文' ), $status . ' detail cannot disclose model/body' );
	nrc_assert( ( 'publish' === $status ? 200 : 404 ) === wp_remote_retrieve_response_code( $response ), $status . ' correct HTTP status' );
}
$response = wp_remote_get( 'http://web/products/page/2/', array( 'redirection' => 0, 'headers' => array( 'Host' => '127.0.0.1:8083' ) ) );
nrc_assert( 3 === substr_count( wp_remote_retrieve_body( $response ), 'class="nrc-card"' ), 'second page contains remaining 3 products' );
wp_set_current_user( $admin->ID ); wp_delete_post( $id, true );
require_once ABSPATH . 'wp-admin/includes/user.php';
foreach ( $users as $uid ) { wp_delete_user( $uid ); }
WP_CLI::success( $GLOBALS['nrc_checks'] . ' assertions passed in isolated test database.' );
