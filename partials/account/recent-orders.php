
<?php
$site_url = get_site_url();
$order_args = [
  'numberposts' => '3',
  'meta_key' => '_customer_user',
  'meta_value' => $args['current_user_id'],
  'orderby' => 'date',
  'order' => 'DESC',
  'post_type' => wc_get_order_types(),
  'post_status' => array_keys(wc_get_order_statuses()),
];
$orders = get_posts($order_args);
$my_account = get_field('my_account', 'option');
?>
<style>
.recent-orders .list-group-item {
  background-color: transparent;
  color: white !important;
  border: 1px solid rgba(255, 255, 255, 0.25);
}
</style>
<div class="row mb-3">
  <div class="col">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h3 class="mb-0">My Recent Orders</h3>
      <?php if ($orders) { ?>
        <a href="<?= $site_url ?>/account/orders" class="link-underline link-underline_darker-gray">View All</a>
      <?php } ?>
    </div>
    <?php if ($orders) : ?>
      <div class="recent-orders">
        <div class="list-group list-group-flush">
          <?php foreach ($orders as $order) :
            $wc_order = wc_get_order($order->ID);
            $status_name = wc_get_order_status_name( $wc_order->get_status() );
            $status_name = str_replace(' payment', '', $status_name);
            $is_order_for_customer = get_post_meta($order->ID, 'gigfilliatewp_ordered_by', true);
            ?>
            <a href="<?= $site_url ?>/account/view-order/<?= $order->ID ?>/" class="list-group-item recent-orders_order p-3 text-decoration-none">
              <div class="row align-items-center" style="row-gap: 0.5rem">
                <div class="col-2">
                  <strong>Order #</strong>
                  <br/>
                  <?= $wc_order->get_order_number() ?>
                </div>
                <div class="col-6 overflow-hidden text-truncate">
                  <strong>Customer</strong>
                  <br/>
                  <?php if ($is_order_for_customer) : ?>
                    <span class="badge badge-info">Ordered For Customer</span>
                    <br/>
                  <?php endif; ?>
                  <?= $wc_order->get_billing_email() ?>
                </div>
                <div class="col-2 col-md-6 col-lg-2">
                  <strong>Total</strong>
                  <br/>
                  <?= $wc_order->get_formatted_order_total() ?>
                </div>
                <div class="col-2 d-flex align-items-center justify-content-end">
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
      <?php if (isset($my_account['no_orders_button']) && $my_account['no_orders_button']) { ?>
        <a href="<?= $my_account['no_orders_button']['url'] ?>" class="btn btn-primary"><?= $my_account['no_orders_button']['title'] ?></a>
      <?php } ?>
    <?php endif; ?>
  </div>
</div>
