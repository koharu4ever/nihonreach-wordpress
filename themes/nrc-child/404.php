<?php get_header(); ?>
<div class="nrc-wrap nrc-empty">
	<p class="nrc-model">404 / PAGE NOT FOUND</p>
	<h1 class="nrc-title">没有找到这个页面</h1>
	<p>链接可能已经变化，也可能是尚未公开的产品。</p>
	<a class="nrc-button" href="<?php echo esc_url( home_url( '/products/' ) ); ?>">返回产品目录</a>
</div>
<?php get_footer(); ?>
