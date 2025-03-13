<?php

// change_author_base_to_seller
add_action('init', function () {
  global $wp_rewrite;
  // Change the author base from 'author' to 'seller'
  $wp_rewrite->author_base = 'seller';
  // Flush rewrite rules (only needed once, can be commented out after first run)
  flush_rewrite_rules();
});
