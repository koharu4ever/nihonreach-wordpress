<?php
/** Child theme presentation; product storage belongs to nrc-catalog. */
defined( 'ABSPATH' ) || exit;
add_action(
	'wp_enqueue_scripts',
	function () {
		// Parent 2.9 enqueues only its own CSS; load child once, after that handle.
		wp_enqueue_style(
			'nrc-child',
			get_stylesheet_uri(),
			array( 'twenty-twenty-one-style' ),
			(string) filemtime( get_stylesheet_directory() . '/style.css' )
		);
	},
	20
);

function nrc_categories() {
	if ( ! taxonomy_exists( 'nrc_category' ) ) {
		return;
	}
	echo '<nav class="nrc-categories" aria-label="产品分类"><a href="' . esc_url( get_post_type_archive_link( 'nrc_product' ) ) . '">全部产品</a>';
	$terms = get_terms(
		array(
			'taxonomy'   => 'nrc_category',
			'hide_empty' => true,
		)
	);
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			echo '<a ' . ( is_tax( 'nrc_category', $term->term_id ) ? 'aria-current="page" ' : '' ) . 'href="' . esc_url( get_term_link( $term ) ) . '">' . esc_html( $term->name ) . '</a>';
		}
	}
	echo '</nav>';
}

function nrc_card() {
	if (
		'publish' !== get_post_status()
		|| post_password_required()
		|| '' !== get_post_field( 'post_password', get_the_ID() )
	) {
		return;
	}
	?>
	<article class="nrc-card">
		<a href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><?php the_post_thumbnail( 'medium_large' ); ?></a>
		<div class="nrc-card-body">
			<span class="nrc-model"><?php echo esc_html( get_post_meta( get_the_ID(), '_nrc_model', true ) ?: '型号待补充' ); ?></span>
			<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
			<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 24 ) ); ?></p>
		</div>
	</article>
	<?php
}
