<?php
/**
 * Template Name: Explore
 */
get_header();
?>
<style>
/*.lux-products-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
  max-width: 1200px;
  margin: 0 auto;
}*/
.lux-products-grid {
  row-gap: 1rem;
}
.lux-product-card {
  display: block;
  text-decoration: none;
  color: inherit;
  border: 1px solid silver;
  border-radius: 0.25rem;
}
.lux-product-card .product-image {
  width: 100%;
  height: 12rem;
  overflow: hidden;
  border-top-left-radius: 0.25rem;
  border-top-right-radius: 0.25rem;
}
.lux-product-card .product-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  margin-bottom: 0.5rem;
}
.lux-product-card_body {
  padding: 0.5rem;
}
.product-title {
  font-size: 1rem;
  font-family: "Playfair Display", Sans-serif;
  margin-bottom: 0.5rem;
}
.product-price {
  font-size: 0.75rem;
  color: silver;
}
</style>
<main id="main" class="site-main">
  <div><? the_content(); ?></div>
  <?php if (!is_user_logged_in()) { ?>
    <style>
    .woocommerce .col2-set .col-1,
    .woocommerce-page .col2-set .col-1 {
      float: left;
      width: 48%;
      max-width: initial;
    }
    .woocommerce .col2-set .col-2,
    .woocommerce-page .col2-set .col-2 {
      float: right;
      width: 48%;
      max-width: initial;
    }
    .woocommerce form .form-row .password-input {
      width: 100%;
    }
    .woocommerce form .form-row {
      display: flex;
      flex-direction: column;
    }
    .woocommerce-form .woocommerce-button {
      display: block;
      width: 100%;
      margin: 0;
      background-color: black !important;
      color: white !important;
      font-family: "Playfair Display", Sans-serif;
    }
    </style>
    <section class="container py-3 py-lg-5">
      <?php echo do_shortcode('[woocommerce_my_account]'); ?>      
    </section>
  <?php } else { ?>
    <section class="container py-3 py-lg-5">
      <div class="row justify-content-center">
        <div class="col-auto text-center">
          <?php
          // Query WooCommerce products
          $args = array(
            'post_type'      => 'product',
            'posts_per_page' => 16,
            'post_status'    => 'publish',
            'post__not_in' => [482],
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
            // Reset post data
            wp_reset_postdata();
          } else {
            echo '<p>No luxury items found.</p>';
          }
          ?>
        </div>
      </div> 
    </section>
  <?php } ?>
</main>
<?php get_footer(); ?>
