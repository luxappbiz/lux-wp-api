<?php
/**
 * The Template for displaying all single products
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
global $product;
$author_id = get_post_field('post_author', $product->get_id());
get_header( 'shop' ); ?>
	<main class="lux-single-product-container">
		<?php
		/**
		 * woocommerce_before_main_content hook.
		 *
		 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
		 * @hooked woocommerce_breadcrumb - 20
		 */
		do_action( 'woocommerce_before_main_content' );
		if (!is_user_logged_in()) {
	  	get_template_part('partials/account', 'login');
	  } else {
	  	$current_user_id = get_current_user_id();
	  	$membership_subscription_id = lux_get_membership_subscription_id($current_user_id);
	  	$membership_subscription_id = ($membership_subscription_id['success']) ? $membership_subscription_id['id'] : false;
	  	while ( have_posts() ) :
	  		the_post();
	  		get_template_part('partials/product/carousel');
	  		get_template_part('partials/product/details', null, [
			  	'membership_subscription_id' => $membership_subscription_id
			  ]);
			  if (!$membership_subscription_id) {
			  	get_template_part('partials/modal-membership-unlock');
			  }
			endwhile;
			if ($author_id) {
	  		get_template_part('partials/product/related', null, [
	  			'product_id' => $product->get_id(),
	  			'author_id' => $author_id,
	  		]);				
			}
		}
		/**
		 * woocommerce_after_main_content hook.
		 *
		 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
		 */
		do_action( 'woocommerce_after_main_content' );
		?>
	</main>
<?php
get_footer( 'shop' );

/* Omit closing PHP tag at the end of PHP files to avoid "headers already sent" issues. */
