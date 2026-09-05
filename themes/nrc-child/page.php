<?php
/** Simple informational pages; retain the parent's header and footer hooks. */
get_header(); ?>
<div class="nrc-wrap nrc-prose"><?php while ( have_posts() ) : the_post(); ?><h1 class="nrc-title"><?php the_title(); ?></h1><div class="entry-content"><?php the_content(); ?></div><?php endwhile; ?></div>
<?php get_footer(); ?>
