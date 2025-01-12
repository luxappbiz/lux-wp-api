
<?php
$these_args = [
  'customer_id' => $args['current_user_id'],
  'subscription_status' => ['wc-active', 'wc-cancelled', 'wc-on-hold'],
  'subscriptions_per_page' => 3
];
$subscriptions = wcs_get_subscriptions($these_args);
$site_url = get_site_url();
if ($subscriptions) : ?>
  <div class="row mb-3">
    <div class="col">
      <div class="recent-subscriptions_heading-container">
        <h3 class="recent-subscriptions_heading">My Recent Subscriptions</h3>
        <a href="<?= $site_url ?>/account/subscriptions" aria-label="link to view all subscriptions" class="link-underline link-underline_darker-gray">View All</a>
      </div>
      <div class="recent-subscriptions">
        <div class="list-group list-group-flush">
          <?php foreach ($subscriptions as $subscription) :
            $is_order_for_customer = get_post_meta($subscription->get_ID(), 'gig_ordered_by', true);
            $status = wcs_get_subscription_status_name( $subscription->get_status() );
            ?>
            <a href="<?= $site_url ?>/account/view-subscription/<?= $subscription->get_ID() ?>/" aria-label="link to subscription number <?= $subscription->get_ID(); ?>" class="list-group-item">
              <div class="list-group-item_row">
                <div class="order-col">
                  <strong>Sub #</strong>
                  <br/>
                  <?= $subscription->get_ID() ?>
                </div>
                <div class="order-col">
                  <strong>Sent to</strong>
                  <?php if ($is_order_for_customer) { ?>
                    <br/>
                    <span class="badge badge-info">Ordered For Customer</span>
                  <?php } ?>
                  <br/>
                  <?= $subscription->get_billing_email() ?>
                </div>
                <div class="order-col">
                  <strong>Total</strong>
                  <br/>
                  <?= $subscription->get_formatted_order_total() ?>
                </div>
                <div class="order-col order-status-col">
                  <span class="badge badge-<?= str_replace(' ', '-', strtolower( $status )) ?> mr-2"><?= $status ?></span>
                  <i class="fa fa-angle-right" aria-hidden="true"></i>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>
