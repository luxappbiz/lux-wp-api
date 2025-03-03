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
?>

<?php
get_header('shop');
// Get the current product category term
$term = get_queried_object();
?>
asfsafasfasfasfsf
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
<div class="lux-products-grid-container">
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
  */ ?>
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
    <?php if (woocommerce_product_loop()) { ?>
      <section class="container py-3 py-lg-5">
        <?php
        // Custom query to ensure 16 products (4x4 grid)
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
              <div class="col-xl-3 col-lg-4">
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
  asfsafasfasfasfsf
</div>
<?php
get_footer('shop');
?>
