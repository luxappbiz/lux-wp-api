<?php
/**
 * The Template for displaying products in a product category. Simply includes the archive template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/taxonomy-product-cat.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     4.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

get_header('shop');

$term = get_queried_object();
?>
<main class="lux-products-grid-container">
  <?php /*
  <header class="woocommerce-products-header">
    <?php if (apply_filters('woocommerce_show_page_title', true)) : ?>
      <h1 class="woocommerce-products-header__title page-title"><?php woocommerce_page_title(); ?></h1>
    <?php endif; ?>
    <?php
    // Display category description if available
    do_action('woocommerce_archive_description');
    ?>
  </header>
  <div><? the_content(); ?></div>
  */ ?>
  <?php get_template_part('partials/taxonomy-page-header'); ?>
  <?php if (!is_user_logged_in()) { ?>
    <?php get_template_part('partials/account', 'login'); ?>
  <?php } else { ?>
    <?php if (woocommerce_product_loop()) { ?>
      <section class="container py-3 py-lg-5">
        <?php
        $args = array(
          'post_type'      => 'product',
          'posts_per_page' => 16,
          'post_status'    => 'publish',
          'tax_query'      => array(
            array(
              'taxonomy' => 'product_cat',
              'field'    => 'term_id',
              'terms'    => $term->term_id,
            ),
          ),
        );
        $products = new WP_Query($args);
        if ($products->have_posts()) { ?>
          <div class="lux-products-grid row">
            <?php while ($products->have_posts()) {
              $products->the_post();
              ?>
              <div class="col-xl-3 col-lg-4 col-md-6">
                <?php get_template_part('partials/product', 'card'); ?>
              </div>
            <?php } ?>
          </div>
          <?php
          // Pagination (optional)
          woocommerce_pagination();
          // Reset post data
          wp_reset_postdata();
        } else {
          echo '<p>No luxury items found in this category.</p>';
        }
        do_action('woocommerce_after_shop_loop');
        ?>
      </section>
    <?php } else {
      do_action('woocommerce_no_products_found');
    } ?>
  <?php } ?>
</main>
<?php
get_footer('shop');
?>
