<?php
/** Static front page template. */
get_header();
$products = post_type_exists( 'nrc_product' ) ? new WP_Query(
	array(
		'post_type'      => 'nrc_product',
		'post_status'    => 'publish',
		'has_password'   => false,
		'posts_per_page' => 3,
		'orderby'        => 'title',
		'order'          => 'ASC',
	)
) : null;
?>
<div class="nrc-wrap">
	<section class="nrc-hero">
		<div>
			<span class="nrc-kicker">NihonReach / Portfolio Demo</span>
			<h1>精密工具，<br>清晰呈现。</h1>
			<p>从铣削到孔加工，探索一个可浏览、可维护的精密工具演示目录。每一款产品都承载一次 WordPress 开发练习。</p>
			<a class="nrc-button" href="<?php echo esc_url( home_url( '/products/' ) ); ?>">探索产品目录 →</a>
		</div>
		<div>
			<?php
			if ( $products && $products->have_posts() ) {
				echo get_the_post_thumbnail( $products->posts[0]->ID, 'large', array( 'alt' => '精密切削工具演示图' ) );
			}
			?>
		</div>
	</section>
	<div class="nrc-section-head">
		<div>
			<p class="nrc-model">01 / PRODUCT COLLECTION</p>
			<h2>从这里认识我们的演示产品</h2>
		</div>
		<a href="<?php echo esc_url( home_url( '/products/' ) ); ?>">查看全部产品 →</a>
	</div>
	<div class="nrc-grid">
		<?php
		if ( $products ) {
			while ( $products->have_posts() ) {
				$products->the_post();
				nrc_card();
			}
			wp_reset_postdata();
		}
		?>
	</div>
	<aside class="nrc-note">Portfolio Demo · 本站为求职作品。品牌、产品与规格均为虚构演示资料，不提供销售、支付或真实业务服务。</aside>
</div>
<?php get_footer(); ?>
