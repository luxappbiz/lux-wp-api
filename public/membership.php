<?php

function lux_get_membership_subscription_id($user_id) {
  if (!$user_id) {
    return [
    	'success' => false,
    	'id' => false,
    	'msg' => 'Membership subscription not found.'
    ];
  }
  $membership_product_id = 482;
  $these_args = [
    'customer_id' => $user_id,
    'subscription_status' => ['wc-active', 'wc-cancelled', 'wc-on-hold'],
    'subscriptions_per_page' => -1 // This might cause the page to load very slowly, so we'll want to think of a more scalable method
  ];
  $subscriptions = wcs_get_subscriptions($these_args);
  if (!$subscriptions || empty($subscriptions)) {
    return [
    	'success' => false,
    	'id' => false,
    	'msg' => 'Membership subscription not found.'
    ];
  }
  foreach ($subscriptions as $subscription) {
    $items = $subscription->get_items();
    if ($items) {
      foreach ($subscription->get_items() as $item) {
        // If Subscription is Membership then return true
        if ($membership_product_id == $item['product_id']) {
          return [
          	'success' => true,
            'id' => $subscription->get_ID(),
            'status' => wcs_get_subscription_status_name( $subscription->get_status() ),
            'msg' => 'Membership subscription found.'
          ];
        }
      }
    }
  }
  return [
  	'success' => false,
  	'id' => false,
  	'msg' => 'Membership subscription not found.'
  ];
}
