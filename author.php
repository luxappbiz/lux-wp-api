<?php
/**
 * Author/Seller Profile
 * Displays seller details and their published luxury products.
 */
get_header();

$seller = get_queried_object();
$seller_image_id = get_user_option('metronet_image_id', $seller->ID);
$seller_image_url = wp_get_attachment_url($seller_image_id);
?>
<style>
.seller-img {
	height: 6rem;
	width: 6rem;
	border-radius: 50%;
}
</style>
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
  				<div class="col d-flex align-items-center">
  					<div>
			  			<h1 class="seller-name fs-1.5x"><?php echo esc_html($seller->display_name); ?></h1>
			        <?php if ($seller->description) : ?>
			          <div class="seller-bio">
			            <?php echo wpautop(esc_html($seller->description)); ?>
			          </div>
			        <?php endif; ?>  						
  					</div>
  				</div>
  			</div>
  		</div>
  	</section>
    <section>
	    <div class="container py-3 py-lg-5">
	    	<div class="row justify-content-center mb-3">
	    		<div class="col-auto">
						<ul id="lux-products-filters" class="nav nav-pills">
						  <li class="nav-item">
						    <a class="nav-link active" href="#" filter="all">All</a>
						  </li>
						  <li class="nav-item">
						    <a class="nav-link" href="#" filter="draft">In Review</a>
						  </li>
						  <li class="nav-item">
						    <a class="nav-link" href="#" filter="sold">Sold</a>
						  </li>
						</ul>	    			
	    		</div>
	    	</div>
	    	<?php
        $args = array(
          'post_type'      => 'product',
          'posts_per_page' => 16,
          'post_status'    => 'publish',
          'author'         => $seller->ID, // Filter by this author/seller
          'post__not_in'   => [482],
        );
        $products = new WP_Query($args);
        if ($products->have_posts()) { ?>
          <div class="lux-products-grid row">
            <?php while ($products->have_posts()) {
              $products->the_post();
              ?>
              <div class="lux-product-col col-xl-3 col-lg-4">
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
<script type="text/javascript">
(function($) {
$(document).ready(function() {
$('#lux-products-filters a.nav-link').on('click', function() {
	const $btn = $(this)
	if ($btn.hasClass('active')) {
		return
	}
	$('#lux-products-filters a').removeClass('active')
	const filter_status = $btn.attr('filter')
	if (filter_status == 'all') {
		$('.lux-product-col').show()
	} else {
		$('.lux-product-card').each(function() {
			const status = $(this).attr('data-status')
			if (filter_status == status) {
				$(this).parent().show()
			} else {
				$(this).parent().hide()
			}
		})		
	}
	$btn.addClass('active')
})
})
})(jQuery)
</script>
<?php get_footer(); ?>
