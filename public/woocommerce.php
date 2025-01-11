<?php

add_filter( 'woocommerce_account_menu_items', function($items) {
  unset($items['downloads']); // Remove downloads item
  $items['dashboard'] = __('Account', 'lux');
  return $items;
}, 99, 1 );
