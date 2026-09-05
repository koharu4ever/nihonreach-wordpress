<?php
/** Idempotent insert-only seed; never overwrite subsequent user changes. */
if ( get_option( 'nrc_seed_v1' ) ) { WP_CLI::success( 'Seed already applied; existing edits preserved.' ); return; }
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
$upload_check = wp_upload_dir();
if ( $upload_check['error'] ) { WP_CLI::error( $upload_check['error'] ); }
$categories = array();
foreach ( array( '铣削工具' => 'milling', '孔加工工具' => 'drilling', '车削工具' => 'turning', '待上新系列' => 'coming-soon' ) as $name => $slug ) {
	$term = term_exists( $slug, 'nrc_category' );
	if ( ! $term ) { $term = wp_insert_term( $name, 'nrc_category', array( 'slug' => $slug ) ); }
	$categories[ $slug ] = (int) $term['term_id'];
}
$items = array(
	array( '四刃平底立铣刀 6mm', 'EM-06-4F', 'milling', 'nr-demo-4-flute-end-mill-6mm.webp' ),
	array( '球头立铣刀 R3', 'BM-R3', 'milling', 'nr-demo-ball-end-mill-r3.webp' ),
	array( '硬质合金钻头 8mm', 'DR-08', 'drilling', 'nr-demo-carbide-drill-8mm.webp' ),
	array( '机用铰刀 10mm', 'RM-10', 'drilling', 'nr-demo-machine-reamer-10mm.webp' ),
	array( '方肩铣刀 50mm', 'SM-50', 'milling', 'nr-demo-shoulder-cutter-50mm.webp' ),
	array( '外圆车刀杆', 'TH-20', 'turning', 'nr-demo-external-turning-holder.webp' ),
	array( '精加工立铣刀 4mm', 'EM-04-F', 'milling', 'nr-demo-4-flute-end-mill-6mm.webp' ),
	array( '球头立铣刀 R2', 'BM-R2', 'milling', 'nr-demo-ball-end-mill-r3.webp' ),
	array( '短刃钻头 6mm', 'DR-06-S', 'drilling', 'nr-demo-carbide-drill-8mm.webp' ),
	array( '精密铰刀 8mm', 'RM-08', 'drilling', 'nr-demo-machine-reamer-10mm.webp' ),
	array( '轻切削铣刀 40mm', 'SM-40', 'milling', 'nr-demo-shoulder-cutter-50mm.webp' ),
	array( '精加工车刀杆', 'TH-16-F', 'turning', 'nr-demo-external-turning-holder.webp' ),
);
$media = array();
foreach ( $items as $item ) {
	$slug = strtolower( $item[1] );
	$existing = get_page_by_path( $slug, OBJECT, 'nrc_product' );
	if ( $existing ) { continue; }
	$id = wp_insert_post( array( 'post_type' => 'nrc_product', 'post_status' => 'publish', 'post_title' => $item[0], 'post_name' => $slug, 'post_excerpt' => '面向精密加工场景的虚构工具，用于展示产品信息与分类维护。', 'post_content' => '<p>这是一款 NihonReach 原创演示产品，用于说明工具目录的内容结构。</p><p>演示应用：通用精密加工。规格仅用于页面和后台验证，不能作为真实选型依据。</p>' ) );
	update_post_meta( $id, '_nrc_model', $item[1] );
	wp_set_object_terms( $id, array( $categories[ $item[2] ] ), 'nrc_category' );
	if ( ! isset( $media[ $item[3] ] ) ) {
		$tmp = wp_tempnam( $item[3] ); copy( '/work/assets/' . $item[3], $tmp );
		$attachment = media_handle_sideload( array( 'name' => $item[3], 'tmp_name' => $tmp ), $id, '原创虚构工具演示图' );
		if ( is_wp_error( $attachment ) ) { WP_CLI::error( $attachment->get_error_message() ); }
		update_post_meta( $attachment, '_wp_attachment_image_alt', $item[0] . '演示图片' );
		$media[ $item[3] ] = $attachment;
	}
	set_post_thumbnail( $id, $media[ $item[3] ] );
}
foreach ( array( 'draft' => '草稿测试产品', 'private' => '私有测试产品', 'publish' => '密码保护测试产品' ) as $status => $title ) {
	$slug = 'restricted-' . $status;
	if ( get_page_by_path( $slug, OBJECT, 'nrc_product' ) ) { continue; }
	$id = wp_insert_post( array( 'post_type' => 'nrc_product', 'post_status' => $status, 'post_title' => $title, 'post_name' => $slug, 'post_content' => '仅供访问权限检查的非公开正文。', 'post_password' => 'publish' === $status ? wp_generate_password( 24, false ) : '' ) );
	update_post_meta( $id, '_nrc_model', 'HIDDEN-' . strtoupper( $status ) );
}
$pages = array( 'home' => array( '首页', '欢迎浏览 NihonReach Portfolio Demo。' ), 'about' => array( '关于项目', '<p>NihonReach WordPress Companion 是独立的求职作品，展示子主题开发、产品插件、安全保存与备份恢复。</p><p>本站与 Laravel NihonReach 使用相同的虚构精密工具场景，各自拥有独立代码、数据库与账号，没有实时同步。</p><p>Portfolio Demo：没有真实客户、企业经营或流量成果声明。</p>' ), 'contact' => array( '联系说明', '<p>本站是 Portfolio Demo，不接受订单，也不提供真实销售服务。</p><p>联系表单、邮件、支付均未启用。请通过求职材料中提供的个人联系方式沟通，不要在演示站填写个人资料。</p>' ) );
$menu_id = wp_create_nav_menu( '主导航' );
foreach ( $pages as $slug => $page ) {
	$existing = get_page_by_path( $slug );
	$id = $existing ? $existing->ID : wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $page[0], 'post_content' => $page[1] ) );
	if ( 'home' === $slug ) { update_option( 'page_on_front', $id ); }
	if ( ! is_wp_error( $menu_id ) ) { wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-object-id' => $id, 'menu-item-object' => 'page', 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) ); }
}
if ( ! is_wp_error( $menu_id ) ) {
	wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-title' => '产品目录', 'menu-item-url' => home_url( '/products/' ), 'menu-item-status' => 'publish', 'menu-item-position' => 2 ) );
	set_theme_mod( 'nav_menu_locations', array( 'primary' => $menu_id ) );
}
update_option( 'show_on_front', 'page' ); update_option( 'blogdescription', '精密工具目录 · Portfolio Demo' ); update_option( 'blog_public', 0 );
update_option( 'sidebars_widgets', array( 'wp_inactive_widgets' => array(), 'sidebar-1' => array() ) );
update_option( 'default_comment_status', 'closed' );
update_option( 'permalink_structure', '/%postname%/' ); update_option( 'nrc_seed_v1', 1 ); flush_rewrite_rules();
WP_CLI::success( 'Created demo content, media and navigation.' );
