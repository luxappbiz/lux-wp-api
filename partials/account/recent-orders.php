
<?php
$site_url = get_site_url();
$order_args = [
  'posts_per_page' => 3,
  'customer_id' => $args['current_user_id'],
  'post_type' => wc_get_order_types(),
  'post_status' => array_keys(wc_get_order_statuses()),
];
$orders = wc_get_orders($order_args);
// $my_account = get_field('my_account', 'option');
?>
<div class="recent-orders_heading-container">
  <h3 class="recent-orders_heading">My Recent Orders</h3>
  <?php if ($orders) { ?>
    <a href="<?= $site_url ?>/account/orders" class="link-underline link-underline_darker-gray">View All</a>
  <?php } ?>
</div>
<?php if ($orders) : ?>
  <div class="recent-orders">
    <div class="list-group list-group-flush">
      <?php foreach ($orders as $order) :
        $order_id = $order->get_id();
        $wc_order = wc_get_order($order_id);
        $status_name = wc_get_order_status_name($wc_order->get_status());
        $status_name = str_replace(' payment', '', $status_name);
        $is_order_for_customer = get_post_meta($order_id, 'gigfilliatewp_ordered_by', true);
        ?>
        <a href="<?= $site_url ?>/account/view-order/<?= $order_id ?>/" class="list-group-item">
          <div class="list-group-item_row">
            <div class="order-col order-number-col">
              <strong>Order #</strong>
              <br/>
              <?= $wc_order->get_order_number() ?>
            </div>
            <div class="order-col order-customer-billing-col">
              <strong>Customer</strong>
              <br/>
              <?php if ($is_order_for_customer) : ?>
                <span class="badge badge-info">Ordered For Customer</span>
                <br/>
              <?php endif; ?>
              <?= $wc_order->get_billing_email() ?>
            </div>
            <div class="order-col order-customer-total-col">
              <strong>Total</strong>
              <br/>
              <?= $wc_order->get_formatted_order_total() ?>
            </div>
            <div class="order-col order-status-col">
              <strong>Status</strong>
              <br/>
              <span class="badge badge-<?= str_replace(' ', '-', strtolower($status_name)) ?> mr-2">
                <?= $status_name ?>
              </span>
              <i class="fa fa-angle-right" aria-hidden="true"></i>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
<?php else : ?>
  <p>No orders yet.</p>
  <?php /* if (isset($my_account['no_orders_button']) && $my_account['no_orders_button']) { ?>
    <a href="<?= $my_account['no_orders_button']['url'] ?>" class="btn btn-primary"><?= $my_account['no_orders_button']['title'] ?></a>
  <?php } */ ?>
<?php endif; ?>
