
<section>
	<div class="container py-5">
  	<?php
    $args = array(
      'post_type'      => 'product',
      'posts_per_page' => 4,
      'post_status'    => 'publish',
      'author'         => $args['author_id'],
      'post__not_in'   => [$args['product_id'],482],
    );
    $products = new WP_Query($args);
    if ($products->have_posts()) { ?>
			<div class="row">
        <?php while ($products->have_posts()) {
          $products->the_post();
          ?>
	        <div class="lux-product-col col-xl-3 col-lg-4">
	          <?php get_template_part('partials/product', 'card'); ?>
	        </div>
	      <?php } ?>
			</div>
    <?php } ?>
	</div>
</section>
