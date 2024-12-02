<?php

add_action( 'rest_api_init', function () {
  add_action( 'rest_pre_serve_request', function () {
    header('Access-Control-Allow-Methods: GET, POST');
    header('Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Wpml-Language');
    header("Access-Control-Allow-Origin: *");
  });
  register_rest_route('lux/v1', '/favourites', array(
    'methods' => 'GET',
    'callback' => 'lux_get_favorites',
  ));

  register_rest_route('lux/v1', '/favourites', array(
    'methods' => 'POST',
    'callback' => 'lux_add_favorite',
  ));

  register_rest_route('lux/v1', '/favourites', array(
    'methods' => 'DELETE',
    'callback' => 'lux_remove_favorite',
  ));
});

function lux_get_favorites( WP_REST_Request $request ) {
  $user_id = get_current_user_id();
  $favourite_products = get_user_meta($user_id, 'favourite_products', true);
  if (!$favourite_products) {
    $favourite_products = [];
  } else {
    $favourite_products = json_decode($favourite_products, true);
  }
  $favorites = [];
  if (!empty($favourite_products)) {
    foreach ($favourite_products as $product_id) {
      $product = wc_get_product($product_id);
      $gallery_image_ids = $product->get_gallery_image_ids();
      $gallery_image_urls = [];
      $featured_image_id = get_post_thumbnail_id( $product_id );
      if ( $featured_image_id ) {
        $featured_image_url = wp_get_attachment_image_url( $featured_image_id, 'full' );
        $gallery_image_urls[] = $featured_image_url;
      }
      foreach ( $gallery_image_ids as $image_id ) {
        $gallery_image_urls[] = wp_get_attachment_url( $image_id );
      }
      $product_data = $product->get_data();
      $product_data['images'] = $gallery_image_urls;
      $favorites[] = $product_data;
    }
  }
  wp_send_json([
    'success' => true,
    'favourite_products' => $favorites
  ]);
}

function lux_add_favorite( WP_REST_Request $request ) {
  $data = $request->get_body();
  $data = json_decode($data, true);
  if (!isset($data['product_id']) || $data['product_id'] == null) {
    wp_send_json([
      'success' => false,
      'error' => 'Product id missing.',
    ]);
  }
  $user_id = get_current_user_id();
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
  
  $favorites = [];
  if (!empty($favourite_products)) {
    foreach ($favourite_products as $product_id) {
      $product = wc_get_product($product_id);
      $gallery_image_ids = $product->get_gallery_image_ids();
      $gallery_image_urls = [];
      $featured_image_id = get_post_thumbnail_id( $product_id );
      if ( $featured_image_id ) {
        $featured_image_url = wp_get_attachment_image_url( $featured_image_id, 'full' );
        $gallery_image_urls[] = $featured_image_url;
      }
      foreach ( $gallery_image_ids as $image_id ) {
        $gallery_image_urls[] = wp_get_attachment_url( $image_id );
      }
      $product_data = $product->get_data();
      $product_data['images'] = $gallery_image_urls;
      $favorites[] = $product_data;
    }
  }

  update_user_meta($user_id, 'favourite_products', json_encode($favourite_products));
  wp_send_json([
    'success' => true,
    'favourite_products' => $favorites
  ]);
}

function lux_remove_favorite( WP_REST_Request $request ) {
  $data = $request->get_body();
  $data = json_decode($data, true);

  if (!isset($data['product_id']) || $data['product_id'] == null) {
    wp_send_json([
      'success'=>false,
      'error' => 'Please provide product id',
    ]);
  }
  $product_id_to_remove = $data['product_id'];
  $user_id = get_current_user_id();
  $favourite_products = get_user_meta($user_id, 'favourite_products', true);
  if (!$favourite_products) {
    $favourite_products = [];
  } else {
    $favourite_products = json_decode($favourite_products, true);
  }
  if (($key = array_search($product_id_to_remove, $favourite_products)) !== false) {
    unset($favourite_products[$key]);
  }

  $favorites = [];
  if (!empty($favourite_products)) {
    foreach ($favourite_products as $product_id) {
      $product = wc_get_product($product_id);
      $gallery_image_ids = $product->get_gallery_image_ids();
      $gallery_image_urls = [];
      $featured_image_id = get_post_thumbnail_id( $product_id );
      if ( $featured_image_id ) {
        $featured_image_url = wp_get_attachment_image_url( $featured_image_id, 'full' );
        $gallery_image_urls[] = $featured_image_url;
      }
      foreach ( $gallery_image_ids as $image_id ) {
        $gallery_image_urls[] = wp_get_attachment_url( $image_id );
      }
      $product_data = $product->get_data();
      $product_data['images'] = $gallery_image_urls;
      $favorites[] = $product_data;
    }
  }

  update_user_meta($user_id, 'favourite_products', json_encode($favourite_products));
  wp_send_json([
    'success' => true,
    'favourite_products' => $favorites
  ]);
}
