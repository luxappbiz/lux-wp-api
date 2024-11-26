<?php

add_action( 'rest_api_init', function () {
  register_rest_route('lux/v1', '/favourites', array(
    'methods' => 'GET',
    'callback' => 'mobile_app_get_favourites',
  ));

  register_rest_route('lux/v1', '/favourites', array(
    'methods' => 'POST',
    'callback' => 'mobile_app_add_to_favourites',
  ));

  register_rest_route('lux/v1', '/favourites', array(
    'methods' => 'DELETE',
    'callback' => 'mobile_app_remove_from_favourites',
  ));
});

function mobile_app_get_favourites( WP_REST_Request $request ) {
  header('Access-Control-Allow-Origin: *');
  header('Access-Control-Allow-Methods: GET, POST');
  header("Access-Control-Allow-Headers: X-Requested-With");

  $authorization = $request->get_header_as_array('authorization');
  if (!$authorization) {
    wp_send_json([
      'success' => false,
      'error' => 'Invalid auth code',
    ]);
  }
  $user = get_users(array(
    'meta_key' => 'app_access_tokens',
    'meta_value' => $authorization
  ));
  if (!$user) {
    wp_send_json([
      'success' => false,
      'error' => 'Invalid auth code',
    ]);
  }
  $user_id = $user[0]->ID;
  $favourite_products = get_user_meta($user_id, 'favourite_products', true);
  if (!$favourite_products) {
    $favourite_products = [];
  } else {
    $favourite_products = json_decode($favourite_products, true);
  }
  $favorites = [];
  if (!empty($favourite_products)) {
    foreach ($favourite_products as $product_id) {
      // $product = wc_get_product($product_id)->get_data();
      $product = wc_get_product($product_id);
      // var_dump($product_id, $product);
      // wp_die();
      $gallery_image_ids = $product->get_gallery_image_ids();
      $gallery_image_urls = array();
      // if (isset($product['image_id']) && $product['image_id']) {
        // $product['images'] = wp_get_attachment_metadata($product['image_id']);
      // }
      $featured_image_id = get_post_thumbnail_id( $object['id'] );
      if ( $featured_image_id ) {
        $featured_image_url = wp_get_attachment_image_url( $featured_image_id, 'full' );
        $gallery_image_urls = array($featured_image_url);
      }
      foreach ( $gallery_image_ids as $image_id ) {
        $gallery_image_urls[] = wp_get_attachment_url( $image_id );
      }
      $product['images'] = $gallery_image_urls;
      $favorites[] = $product;
    }
  }
  wp_send_json([
    'success' => true,
    'favourite_products' => $favorites
  ]);
}

function mobile_app_add_to_favourites( WP_REST_Request $request ) {
  header('Access-Control-Allow-Origin: *');
  header('Access-Control-Allow-Methods: GET, POST');
  header("Access-Control-Allow-Headers: X-Requested-With");
  $authorization = $request->get_header_as_array('authorization');
  if (!$authorization) {
    wp_send_json([
      'success' => false,
      'error' => 'Invalid auth code',
    ]);
  }
  $user = get_users(array(
    'meta_key' => 'app_access_tokens',
    'meta_value' => $authorization
  ));
  if (!$user) {
    wp_send_json([
      'success' => false,
      'error' => 'Invalid auth code',
    ]);
  }
  $data = $request->get_body();
  $data = json_decode($data, true);
  if (!isset($data['product_id']) || $data['product_id'] == null) {
    wp_send_json([
      'success' => false,
      'error' => 'Product id missing.',
    ]);
  }
  $user_id = $user[0]->ID;
  $favourite_products = get_user_meta($user_id, 'favourite_products', true);
  if (!$favourite_products) {
    $favourite_products = [];
    array_push($favourite_products, $data['product_id']);
  } else {
    $favourite_products = json_decode($favourite_products, true);
    if (!in_array($data['product_id'], $favourite_products, true)) {
      array_push($favourite_products, $data['product_id']);
    }
  }
  update_user_meta($user_id, 'favourite_products', json_encode($favourite_products));
  wp_send_json([
    'success' => true,
    'favourite_products' => $favourite_products
  ]);
}

function mobile_app_remove_from_favourites( WP_REST_Request $request ) {
  header('Access-Control-Allow-Origin: *');
  header('Access-Control-Allow-Methods: GET, POST');
  header("Access-Control-Allow-Headers: X-Requested-With");
  $authorization = $request->get_header_as_array('authorization');
  if (!$authorization) {
    wp_send_json([
      'success'=>false,
      'error' => 'Invalid auth code',
    ]);
  }
  $user = get_users(array(
    'meta_key' => 'app_access_tokens',
    'meta_value' => $authorization
  ));
  if (!$user) {
    wp_send_json([
      'success'=>false,
      'error' => 'Invalid auth code',
    ]);
  }
  $body = $request->get_body();
  $body = json_decode($body, true);

  if (!isset($body['product_id']) || $body['product_id'] == null) {
    wp_send_json([
      'success'=>false,
      'error' => 'Please provide product id',
    ]);
  }
  $product_id_to_remove = $body['product_id'];
  $user_id = $user[0]->ID;
  $favourite_products = get_user_meta($user_id, 'favourite_products', true);
  if (!$favourite_products) {
    $favourite_products = [];
  } else {
    $favourite_products = json_decode($favourite_products, true);
  }
  if (($key = array_search($product_id_to_remove, $favourite_products)) !== false) {
    unset($favourite_products[$key]);
  }

  update_user_meta($user_id, 'favourite_products', json_encode($favourite_products));
  wp_send_json([
    'success'=>true,
    'favourite_products' => $favourite_products
  ]);
}
