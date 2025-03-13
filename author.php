<?php
/**
 * Author/Seller Profile
 * Displays seller details and their published luxury products.
 */
get_header();

$seller = get_queried_object();
$seller_image_id = get_user_option('metronet_image_id', $seller->ID);
$seller_image_url = wp_get_attachment_url($author_image_id);
?>
<main id="main" class="site-main">
  <?php if (!is_user_logged_in()) { ?>
    <section class="container py-3 py-lg-5">
      <?php echo do_shortcode('[woocommerce_my_account]'); ?>      
    </section>
  <?php } else { ?>
  	<section>
  		<div class="container py-3 py-lg-5">
  			<div class="row">
					<?php if ($seller_image_url) { ?>
						<div class="col-auto">
							<img src="<?= $seller_image_url ?>" class="seller-img" alt="<?= $seller->display_name ?>"/>
						</div>
					<?php } ?>
  				<div class="col">
		  			<h1 class="seller-name"><?php echo esc_html($seller->display_name); ?></h1>
		        <?php if ($seller->description) : ?>
		          <div class="seller-bio">
		            <?php echo wpautop(esc_html($seller->description)); ?>
		          </div>
		        <?php endif; ?>  					
  				</div>
  			</div>
  		</div>
  	</section>
    <section>
	    <div class="container py-3 py-lg-5">
	    	<?php
        $args = array(
          'post_type'      => 'product',
          'posts_per_page' => 16, // 4x4 grid, adjust as needed
          'post_status'    => 'publish',
          'author'         => $seller->ID, // Filter by this author/seller
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
          echo '<p>No luxury items published.</p>';
        }
        ?>
	    </div>
    </section>
  <?php } ?>
</main>
<?php get_footer(); ?>
