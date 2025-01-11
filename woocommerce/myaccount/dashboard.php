<?php
/**
 * My Account Dashboard
 *
 * Shows the first intro screen on the account dashboard.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/dashboard.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 4.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

$site_url = get_site_url();
$template_directory_uri = get_template_directory_uri();
$current_user_id = $current_user->ID;
$customer = new WC_Customer($current_user_id);
$shipping_address = $customer->get_shipping();
$saved_payment_methods = wc_get_customer_saved_methods_list( $current_user_id );
$payment_method = null;
foreach ($saved_payment_methods as $saved_payment_method) {
  foreach ($saved_payment_method as $saved_payment) {
    if ($saved_payment['is_default']) {
      $payment_method = $saved_payment;
    }
  }
}
$first_name = get_user_meta($current_user_id, 'first_name', true);
$last_name = get_user_meta($current_user_id, 'last_name', true);
?>
<style>
.account-welcome-title {
  font-size: 2rem;
  text-transform: capitalize;
}
</style>
<div class="row mb-3">
  <div class="col-lg-8">
    <h1 class="account-welcome-title mb-3">
      Welcome, <?= $first_name && $last_name ? $first_name . ' ' . $last_name : $current_user->display_name ?>
    </h1>
    <?php
    /**
     * My Account dashboard.
     *
     * @since 2.6.0
     */
    do_action( 'woocommerce_account_dashboard' );

    /**
     * Deprecated woocommerce_before_my_account action.
     *
     * @deprecated 2.6.0
     */
    do_action( 'woocommerce_before_my_account' );

    /**
     * Deprecated woocommerce_after_my_account action.
     *
     * @deprecated 2.6.0
     */
    do_action( 'woocommerce_after_my_account' );
    if (class_exists('Gigfiliate_WP_Public')) {
      $active_affiliate = gigfiliate_is_active_affiliate( $current_user_id );
      if (!$active_affiliate) {
        echo do_shortcode('[gigfiliate_your_shopping_with]');
      }
    }
    get_template_part('partials/account/recent-orders', null, [
      'current_user_id' => $current_user_id
    ]);
    ?>
  </div>
</div>
<?php
/* Omit closing PHP tag at the end of PHP files to avoid "headers already sent" issues. */
