
<?php
$product = wc_get_product(get_the_ID());
$featured_image_id = $product->get_image_id();
$gallery_image_ids = $product->get_gallery_image_ids();
// Combine featured image with gallery images
$all_image_ids = array_filter(array_merge(array($featured_image_id), $gallery_image_ids));
if (empty($all_image_ids)) {
  return; // No images to display
}
?>
<section id="single-product-carousel">
	<div id="productCarousel" class="carousel slide" data-ride="carousel">
	  <ol class="carousel-indicators">
	  	<?php foreach ($all_image_ids as $index => $image_id) : 
        $active_class = $index === 0 ? 'active' : '';
        ?>
		    <li data-target="#productCarousel" data-slide-to="<?= $index ?>" class="<?= $active_class ?>"></li>
	    <?php endforeach; ?>
	  </ol>
	  <div class="carousel-inner">
	  	<?php  foreach ($all_image_ids as $index => $image_id) : 
        $active_class = $index === 0 ? 'active' : '';
        $full_size_image = wp_get_attachment_image_src($image_id, 'full');
        $thumbnail = wp_get_attachment_image_src($image_id, 'woocommerce_single');
        ?>
		    <div class="carousel-item <?php echo $active_class; ?>">
					<div class="container">
			    	<div class="carousel-item_bg-img" style="background-image: url('<?= esc_url($full_size_image[0]) ?>');"></div>
			    	<div class="overlay"></div>
			      <img src="<?= esc_url($full_size_image[0]) ?>" class="d-block w-100" alt="<?= esc_attr(get_the_title($image_id)) ?>"
			      	data-fullsrc="<?= esc_url($full_size_image[0]) ?>"/>
					</div>
		    </div>
	    <?php endforeach; ?>
	  </div>
	  <?php if (count($all_image_ids) > 1) : ?>
		  <button class="carousel-control-prev" type="button" data-target="#productCarousel" data-slide="prev">
		    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
		    <span class="sr-only">Previous</span>
		  </button>
		  <button class="carousel-control-next" type="button" data-target="#productCarousel" data-slide="next">
		    <span class="carousel-control-next-icon" aria-hidden="true"></span>
		    <span class="sr-only">Next</span>
		  </button>
	  <?php endif; ?>
	</div>
</section>
