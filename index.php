<?php
/**
 * The main template file.
 */

get_header(); ?>

  <div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">

    <?php the_content(); ?>

    </main><!-- #main -->
  </div><!-- #primary -->

<?php
get_footer();
