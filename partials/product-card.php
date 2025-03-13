<?php
global $product;
$product_permalink = get_permalink();
$product_title = get_the_title();
$product_price = $product->get_price_html();
$product_image = wp_get_attachment_image_src(get_post_thumbnail_id(), 'full');
$is_sold_out = !$product->is_in_stock();
?>
<a href="<?php echo esc_url($product_permalink); ?>" class="lux-product-card">
  <?php if ($is_sold_out) { ?>
    <span class="badge badge-sold">SOLD</span>
  <?php } ?>
  <div class="product-image">
    <?php if ($product_image) : ?>
      <img src="<?php echo esc_url($product_image[0]); ?>" alt="<?php echo esc_attr($product_title); ?>"/>
    <?php else : ?>
      <img src="<?php echo wc_placeholder_img_src(); ?>" alt="<?= the_title_attribute() ?>"/>
    <?php endif; ?>
  </div>
  <div class="lux-product-card_body">
    <h3 class="product-title"><?php echo esc_html($product_title); ?></h3>
    <div class="product-price"><?php echo $product_price; ?></div>                      
  </div>
</a>
