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
  text-decoration: none; color: inherit;
}
.lux-product-card .product-image {
  width: 100%;
  height: 12rem;
  overflow: hidden;
}
.lux-product-card .product-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  margin-bottom: 0.5rem;
}
.product-title {
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
                global $product;
                // Get product details
                $product_permalink = get_permalink();
                $product_title = get_the_title();
                $product_price = $product->get_price_html();
                $product_image = wp_get_attachment_image_src(get_post_thumbnail_id(), 'medium'); // Medium size for featured image
                // Output product card
                ?>
                <div class="col-xl-3 col-lg-4">
                  <a href="<?php echo esc_url($product_permalink); ?>" class="lux-product-card" style="text-decoration: none; color: inherit;">
                    <div class="product-image">
                      <?php if ($product_image) : ?>
                        <img src="<?php echo esc_url($product_image[0]); ?>" alt="<?php echo esc_attr($product_title); ?>"/>
                      <?php else : ?>
                        <img src="<?php echo wc_placeholder_img_src(); ?>" alt="<?= the_title_attribute() ?>"/>
                      <?php endif; ?>
                    </div>
                    <h3 class="product-title"><?php echo esc_html($product_title); ?></h3>
                    <div class="product-price"><?php echo $product_price; ?></div>
                  </a>
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
