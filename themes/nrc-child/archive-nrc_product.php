<?php
/** Product archive and category listing. */
get_header(); ?>
<div class="nrc-wrap"><p class="nrc-model">NIHONREACH / COLLECTION</p><h1 class="nrc-title"><?php echo is_tax( 'nrc_category' ) ? esc_html( single_term_title( '', false ) ) : '产品目录'; ?></h1><p class="nrc-lead">按加工用途探索精密工具。以下均为虚构演示产品，每页展示 9 款。</p>
<?php nrc_categories(); ?>
<?php if ( have_posts() ) : ?><div class="nrc-grid"><?php while ( have_posts() ) { the_post(); nrc_card(); } ?></div><?php the_posts_pagination( array( 'prev_text' => '上一页', 'next_text' => '下一页' ) ); ?>
<?php else : ?><div class="nrc-empty">这个分类还没有公开产品。<a href="<?php echo esc_url( home_url( '/products/' ) ); ?>">查看全部产品</a></div><?php endif; ?>
<aside class="nrc-note">Portfolio Demo · 图片及参数仅用于开发演示，不作为真实采购依据。</aside></div>
<?php get_footer(); ?>
