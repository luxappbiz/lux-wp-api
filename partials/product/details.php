<?php
$post_id = get_the_ID();
$categories = get_the_terms($post_id, 'product_cat');
$author_id = get_post_field('post_author', $product->get_id());
$author_name = get_the_author_meta('display_name', $author_id);
$author_url = get_author_posts_url($author_id);
?>
<style>
#single-product-details .author-img {
	height: 2rem;
	width: 2rem;
	object-fit: cover;
	border-radius: 0.25rem;
}
.single-product-seller {
	border-radius: 0.25rem;
	border: 1px solid silver;
}
</style>
<section id="single-product-details" class="py-5">
	<div class="container">
		<?php if ($categories) { ?>
			<p class="product-cat">
				<a href="<?= esc_url(get_term_link($categories[0])) ?>" class="text-black"><?= esc_attr($categories[0]->name) ?></a>
			</p>
		<?php } ?>
		<h1 class="mb-3"><?= get_the_title() ?></h1>
		<div class="single-product-seller">
			<div class="row">
				<div class="col-auto">
					<img src="https://placebear.com/400/400" class="author-img" alt="<?= $author_name ?>"/>
				</div>
				<div class="col">
					<span>Listed by</span>
					<h3><?= $author_name ?></h3>
				</div>
			</div>
		</div>
		<div>
			<?php the_content(); ?>
		</div>
	</div>
</section>