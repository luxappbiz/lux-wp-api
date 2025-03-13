<?php
$membership_subscription_id = $args['membership_subscription_id'] ?? false;
global $product;
$post_id = get_the_ID();
$categories = get_the_terms($post_id, 'product_cat');
$price = $product->get_price_html();
$author_id = get_post_field('post_author', $product->get_id());
$author_image_id = get_user_option('metronet_image_id', $author_id);
$author_image_url = wp_get_attachment_url($author_image_id);
$author_name = get_the_author_meta('display_name', $author_id);
$author_url = get_author_posts_url($author_id);
?>
<section id="single-product-details" class="py-5">
	<div class="container">
		<?php if ($categories) { ?>
			<p class="product-cat mb-2">
				<a href="<?= esc_url(get_term_link($categories[0])) ?>" class="text-black"><?= esc_attr($categories[0]->name) ?></a>
			</p>
		<?php } ?>
		<div class="mb-3">
			<h1 class="mb-1"><?= get_the_title() ?></h1>
			<h4 class="mb-0"><?= $price ?></h4>			
		</div>
		<div class="single-product-seller mb-3">
			<div class="row">
				<?php if ($author_image_url) { ?>
					<div class="col-auto">
						<a href="<?= $author_url ?>" class="d-block">
							<img src="<?= $author_image_url ?>" class="author-img" alt="<?= $author_name ?>"/>
						</a>
					</div>
				<?php } ?>
				<div class="d-flex col align-items-center justify-content-between">
					<div>
						<span>Listed by</span>
						<h3 class="fs-1.5x m-0">
							<a href="<?= $author_url ?>" class="d-block"><?= $author_name ?></a>
						</h3>
					</div>
					<div>
						<?php if ($membership_subscription_id) { ?>
							<button type="button" class="btn btn-transparent" onclick="alert('To do: navigate to messages')">
								<i class="fa-chat-bubbles" aria-hidden="true"></i>
								<span class="sr-only">Message</span>
							</button>
						<?php } else { ?>
							<button type="button" class="btn btn-transparent" data-toggle="modal" data-target="#membershipRequiredModal">
								<i class="fa-chat-bubbles" aria-hidden="true"></i>
								<span class="sr-only">Message</span>
							</button>
						<?php } ?>
					</div>
				</div>
			</div>
		</div>
		<div>
			<?php the_content(); ?>
		</div>
	</div>
</section>
