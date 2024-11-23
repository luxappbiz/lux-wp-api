<?php

add_action('rest_api_init', function () {
  register_rest_field('store', 'products', array(
    'get_callback' => function ($object, $field_name, $request) {
      var_dump('test');
      // Assuming $object contains the store data where 'author' is the user_id
      $user_id = $object['id'];
      // Query for products by the user
      $args = array(
        'author' => $user_id,
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => -1, // Fetch all products
        'fields' => 'ids', // Only fetch product IDs for efficiency
      );
      $query = new WP_Query($args);
      $product_ids = $query->posts;
      // Fetch product data for each ID
      $products = array();
      foreach ($product_ids as $product_id) {
        $product = wc_get_product($product_id);
        if ($product) {
          // Here you can decide what product data to include
          $products[] = array(
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'permalink' => $product->get_permalink(),
            // Add more fields as needed
          );
        }
      }
      return $products;
    },
    'schema' => null,
    /*
    'schema' => array(
      'description' => __('List of products published by the store owner.', 'dokan'),
      'type' => 'array',
      'items' => array(
        'type' => 'object',
        'properties' => array(
          'id' => array(
            'description' => __('Product ID.', 'dokan'),
            'type' => 'integer',
          ),
          'name' => array(
            'description' => __('Product name.', 'dokan'),
            'type' => 'string',
          ),
          'permalink' => array(
            'description' => __('Product URL.', 'dokan'),
            'type' => 'string',
            'format' => 'uri',
          ),
        ),
      ),
    ),
    */
  ));
});
