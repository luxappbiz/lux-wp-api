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

get_header( 'shop' ); ?>
	<style>
	.woocommerce .woocommerce-breadcrumb {
		padding-left: 1rem;
		padding-right: 1rem;
	}
	#single-product-details .author-img {
	  height: 5rem;
	  width: 5rem;
		object-fit: cover;
		border-radius: 0.25rem;
	}
	.single-product-seller {
		padding: 0.25rem;
		border-radius: 0.25rem;
		border: 1px solid silver;
	}
	</style>
	<main class="lux-single-product-container">
		<?php
		/**
		 * woocommerce_before_main_content hook.
		 *
		 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
		 * @hooked woocommerce_breadcrumb - 20
		 */
		do_action( 'woocommerce_before_main_content' );
		?>
	  <?php if (!is_user_logged_in()) { ?>
	    <?php get_template_part('partials/account', 'login'); ?>
	  <?php } else {
	  	$current_user_id = get_current_user_id();
	  	$membership_subscription_id = lux_get_membership_subscription_id($current_user_id);
	  	while ( have_posts() ) :
	  		the_post();
	  		?>
				<?php // wc_get_template_part( 'content', 'single-product' ); ?>
			  <?php get_template_part('partials/product/carousel'); ?>
			  <?php get_template_part('partials/product/details', null, [
			  	'membership_subscription_id' => $membership_subscription_id
			  ]);
			  if (!$membership_subscription_id) { ?>
					<?php get_template_part('partials/modal-membership-unlock'); ?>
				<?php } ?>
			<?php endwhile;
		} ?>
		<?php
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
