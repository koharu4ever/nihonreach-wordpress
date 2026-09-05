<?php
/** Public-safe data fingerprint for comparing dev with a restored copy. */
$snapshot = array( 'products' => array(), 'categories' => array(), 'media' => array(), 'theme' => get_option( 'stylesheet' ), 'menu' => get_theme_mod( 'nav_menu_locations' ), 'front' => get_option( 'page_on_front' ) );
foreach ( get_posts( array( 'post_type' => 'nrc_product', 'post_status' => array( 'publish', 'private', 'draft' ), 'numberposts' => -1, 'orderby' => 'ID', 'order' => 'ASC' ) ) as $post ) {
	$snapshot['products'][] = array( 'id' => $post->ID, 'title' => $post->post_title, 'status' => $post->post_status, 'protected' => '' !== $post->post_password, 'model' => get_post_meta( $post->ID, '_nrc_model', true ), 'terms' => wp_get_object_terms( $post->ID, 'nrc_category', array( 'fields' => 'slugs' ) ), 'thumbnail' => get_post_thumbnail_id( $post->ID ) );
}
foreach ( get_terms( array( 'taxonomy' => 'nrc_category', 'hide_empty' => false ) ) as $term ) { $snapshot['categories'][] = array( $term->slug, $term->name, $term->count ); }
foreach ( get_posts( array( 'post_type' => 'attachment', 'numberposts' => -1, 'orderby' => 'ID', 'order' => 'ASC' ) ) as $post ) { $snapshot['media'][] = array( 'id' => $post->ID, 'sha256' => hash_file( 'sha256', get_attached_file( $post->ID ) ) ); }
echo wp_json_encode( $snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n";
