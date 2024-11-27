<?php
/**
 * The main template file.
 */

get_header(); ?>

  <div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">

      <h1><?php the_title(); ?></h1>
      <div id="content">
        <?php the_content(); ?>        
      </div>

    </main><!-- #main -->
  </div><!-- #primary -->

<?php
get_footer();
