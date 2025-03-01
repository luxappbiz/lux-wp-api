<?php
/**
 * Template Name: Explore
 */

get_header();
?>
<main id="main" class="site-main">
  <section class="container py-lg-5">
    <div class="row justify-content-center">
      <div class="col-auto text-center">
        <?php
        // Query WooCommerce products
        $args = array(
          'post_type'      => 'product',
          'posts_per_page' => 16,
          'post_status'    => 'publish',
          'exclude' => [482],
        );
        $products = new WP_Query($args);
        if ($products->have_posts()) { ?>
          <div class="lux-products-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; max-width: 1200px; margin: 0 auto;">
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
              <a href="<?php echo esc_url($product_permalink); ?>" class="lux-product-card" style="text-decoration: none; color: inherit;">
                <div class="product-image" style="width: 100%; height: 200px; overflow: hidden;">
                  <?php if ($product_image) : ?>
                    <img src="<?php echo esc_url($product_image[0]); ?>" alt="<?php echo esc_attr($product_title); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                  <?php else : ?>
                    <img src="<?php echo wc_placeholder_img_src(); ?>" alt="Placeholder" style="width: 100%; height: 100%; object-fit: cover;">
                  <?php endif; ?>
                </div>
                <h3 class="product-title" style="font-size: 16px; margin: 10px 0;"><?php echo esc_html($product_title); ?></h3>
                <div class="product-price" style="font-size: 14px; color: #777;"><?php echo $product_price; ?></div>
              </a>
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
</main>
<?php get_footer(); ?>
