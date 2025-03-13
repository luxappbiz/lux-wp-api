<?php
/**
 * Template Name: Explore
 */
get_header();
?>
<style type="text/css">
.lux-product-card .badge-sold {
  position: absolute;
  top: 0.25rem;
  right: 1.25rem;
  display: block;
  background: red;
  color: white;
}
</style>
<main id="main" class="site-main">
  <div><? the_content(); ?></div>
  <?php if (!is_user_logged_in()) { ?>
    <section class="container py-3 py-lg-5">
      <?php echo do_shortcode('[woocommerce_my_account]'); ?>      
    </section>
  <?php } else { ?>
    <section>
      <div class="container py-3 py-lg-5">
        <?php
        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
        $args = array(
          'post_type'      => 'product',
          'posts_per_page' => 16,
          'post_status'    => 'publish',
          'post__not_in'   => [482],
          'paged'          => $paged,
        );
        $products = new WP_Query($args);
        if ($products->have_posts()) { ?>
          <div class="lux-products-grid row">
            <?php while ($products->have_posts()) {
              $products->the_post();
              ?>
              <div class="col-xl-3 col-lg-4">
                <?php get_template_part('partials/product', 'card'); ?>
              </div>
            <?php } ?>
          </div>
          <?php
          // Pagination
          if ($products->max_num_pages > 1) { ?>
            <div class="lux-pagination" style="margin-top: 1rem; text-align: center;">
              <?php echo paginate_links(array(
                'total'   => $products->max_num_pages,
                'current' => max(1, get_query_var('paged')),
                'format'  => '?paged=%#%',
              )); ?>
            </div>
          <?php }
          // Reset post data
          wp_reset_postdata();
        } else {
          echo '<p>No luxury items found.</p>';
        }
        ?>
      </div>
    </section>
  <?php } ?>
</main>
<?php get_footer(); ?>
