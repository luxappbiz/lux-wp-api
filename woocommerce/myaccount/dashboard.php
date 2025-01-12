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
.dashboard-container {
  display: flex;
  margin-right: -1rem;
  margin-left: -1rem;
}
@media (max-width: 991px) {
  .dashboard-container {
    flex-wrap: wrap;
  }
}
@media (min-width: 992px) {
  .dashboard-main-col {
    position: relative;
    width: 100%;
    padding-right: 1rem;
    padding-left: 1rem;
    flex: 0 0 66.66667%;
    max-width: 66.66667%;
  }
}
@media (min-width: 992px) {
  .dashboard-sidebar-col {
    position: relative;
    width: 100%;
    padding-right: 1rem;
    padding-left: 1rem;
    flex: 0 0 33.3333%;
    max-width: 33.3333%;
  }
}
.recent-orders_heading-container,
.recent-subscriptions_heading-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}
.recent-orders_heading,
.recent-subscriptions_heading {
  margin-bottom: 0;
}
.recent-orders .list-group a {
  color: black;
}
.list-group-item_row {
  display: flex;
  margin-right: -1rem;
  margin-left: -1rem;
}
.order-col {
  position: relative;
  width: 100%;
  padding-right: 1rem;
  padding-left: 1rem;
  flex: 0 0 25%;
  max-width: 25%; 
}
.order-status-col {
  align-items: center;
  justify-content: flex-end;
}
.list-group {
  display: flex;
  flex-direction: column;
  padding-left: 0;
  margin-bottom: 0;
  border-radius: .25rem;
}
.list-group-item {
  position: relative;
  display: block;
  padding: 1rem;
  text-decoration: none;
  background-color: #fff;
  border: 1px solid rgba(0, 0, 0, 0.25);
  margin-bottom: -1px;
}
.list-group .list-group-item:first-child {
  border-top-left-radius: .25rem !important;
  border-top-right-radius: .25rem !important;
}
.list-group .list-group-item:last-child {
  border-bottom-left-radius: .25rem !important;
  border-bottom-right-radius: .25rem !important;
}
.card {
  padding: 1rem;
  border: 1px solid rgba(0, 0, 0, 0.25);
  margin-bottom: 1rem;
}
.card-header-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}
.card-title {
  margin: 0;
}
</style>
<div class="dashboard-container">
  <div class="dashboard-main-col">
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
    get_template_part('partials/account/recent-subscriptions', null, [
      'current_user_id' => $current_user_id
    ]);
    ?>
  </div>
  <div class="dashboard-sidebar-col">
    <h3 class="mb-3">My Settings</h3>
    <div class="card">
      <div class="card-body">
        <div class="card-header-container">
          <h4 class="card-title">Account Info</h4>
          <a href="<?= wc_get_endpoint_url('edit-account') ?>" title="Edit Account Details">Edit</a>
        </div>
        <strong class="mb-0"><?= $current_user->display_name ?></strong>
        <p class="card-text"><?= $current_user->user_email ?></p>
      </div>
    </div>
    <div class="card">
      <div class="card-body">
        <div class="card-header-container">
          <h4 class="card-title">Primary Shipping Address</h4>
          <a href="<?php echo wc_get_endpoint_url('edit-address/shipping'); ?>" title="Edit Address">Edit</a>
        </div>
        <?php if ($shipping_address['first_name'] && $shipping_address['last_name']) { ?>
          <strong class="mb-0"><?= $shipping_address['first_name'] ?> <?= $shipping_address['last_name'] ?></strong>
        <?php } ?>
        <p class="card-text">
          <?php if ($shipping_address['address_1']) { ?>
            <?php echo $shipping_address['address_1']; ?>, <?php echo $shipping_address['address_2'] ? $shipping_address['address_2'] . ' ,' : ''; ?>
            <?php echo $shipping_address['postcode']; ?>, <?php echo $shipping_address['city']; ?>, <?php echo $shipping_address['state']; ?>, <?php echo $shipping_address['country']; ?>
            <br/>
          <?php } ?>
          <?php if (isset($shipping_address['email'])) : ?>
            <i class="fa fa-envelope" aria-hidden="true"></i> <?php echo $shipping_address['email']; ?><br/>
          <?php endif; ?>
          <?php if ($shipping_address['phone']) : ?>
            <i class="fa fa-phone" aria-hidden="true"></i> <?php echo $shipping_address['phone']; ?>
          <?php endif; ?>
        </p>
      </div>
    </div>
    <?php if ($payment_method) : ?>
      <div class="card mb-3">
        <div class="card-body">
          <div class="card-header-container">
            <h4 class="card-title">Default Primary Method</h4>
            <a href="<?php echo wc_get_endpoint_url('payment-methods'); ?>" title="Edit Payment Methods">Edit</a>
          </div>
          <div class="d-flex px-1">
            <div class="col-2 d-flex justify-content-center align-items-center px-0">
              <img src="<?= $template_directory_uri ?>/assets/images/cards/<?= str_replace(' ', '-', strtolower($payment_method['method']['brand'])) ?>.svg" alt="<?php echo $payment_method['method']['brand']; ?>" class="w-75"/>
            </div>
            <div class="col-10">
              <?php if (!empty( $payment_method['method']['last4'])) { ?>
                <p class="mb-1"><b><?php echo $payment_method['method']['brand']; ?> ****</b> <?php echo $payment_method['method']['last4']; ?></p>
              <?php } else { ?>
                <?php echo esc_html( wc_get_credit_card_type_label( $payment_method['method']['brand'] ) ); ?>
              <?php } ?>
              <?php if (!empty( $payment_method['expires'])) : ?>
                <p class="mb-0">Expires <?php echo $payment_method['expires']; ?></p>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php
/* Omit closing PHP tag at the end of PHP files to avoid "headers already sent" issues. */
