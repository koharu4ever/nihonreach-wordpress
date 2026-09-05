<?php
/** Protect all custom fields and images behind WordPress's password check. */
get_header();
?>
<div class="nrc-wrap">
	<?php while ( have_posts() ) : ?>
		<?php the_post(); ?>
		<a href="<?php echo esc_url( home_url( '/products/' ) ); ?>">← 返回产品目录</a>
		<h1 class="nrc-title"><?php the_title(); ?></h1>
		<?php if ( post_password_required() ) : ?>
			<?php echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WordPress core generates and escapes the password form. ?>
		<?php else : ?>
			<div class="nrc-detail">
				<div>
					<?php
					if ( has_post_thumbnail() ) {
						the_post_thumbnail( 'large' );
					} else {
						echo '<div class="nrc-empty">暂无产品图片</div>';
					}
					?>
				</div>
				<div>
					<span class="nrc-model">型号：<?php echo esc_html( get_post_meta( get_the_ID(), '_nrc_model', true ) ?: '待补充' ); ?></span>
					<?php echo get_the_term_list( get_the_ID(), 'nrc_category', '<p>分类：', ' / ', '</p>' ); ?>
					<div class="nrc-content"><?php the_content(); ?></div>
					<a class="nrc-button" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">联系说明 →</a>
				</div>
			</div>
		<?php endif; ?>
	<?php endwhile; ?>
	<aside class="nrc-note">Portfolio Demo · 虚构产品，无真实交易功能。</aside>
</div>
<?php get_footer(); ?>
