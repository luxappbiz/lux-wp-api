<?php

add_action( 'rest_api_init', function () {
  register_rest_route('lux/v1', '/register', array(
    'methods' => 'POST',
    'callback' => 'lux_api_register',
  ));

  register_rest_route('lux/v1', '/login', array(
    'methods' => 'POST',
    'callback' => 'lux_api_login',
  ));

  register_rest_route('lux/v1', '/user', array(
    'methods' => 'GET',
    'callback' => 'lux_mobile_app_get_user_func',
  ));

  register_rest_route('lux/v1', '/favourites', array(
    'methods' => 'GET',
    'callback' => 'mobile_app_get_favourites',
  ));

  register_rest_route('lux/v1', '/favourites', array(
    'methods' => 'post',
    'callback' => 'mobile_app_add_to_favourites',
  ));

  register_rest_route('lux/v1', '/favourites', array(
    'methods' => 'delete',
    'callback' => 'mobile_app_remove_from_favourites',
  ));
});

function lux_api_register( WP_REST_Request $request ) {
  header('Access-Control-Allow-Origin: *');
  header('Access-Control-Allow-Methods: GET, POST');
  header("Access-Control-Allow-Headers: X-Requested-With");
  $body = $request->get_body();
  $body = json_decode($body, true);
  $email = $body['email'];
  $password = $body['password'];
  $username = lux_create_unique_username_by_email($email);
  $user_id = wp_create_user($username, $password, $email);
  if (is_wp_error($user_id)) {
    wp_send_json([
      'success' => false,
      'error'=> 'Creating new user failed. '. $user_id->get_error_message()
    ]);
  }
  $user = get_user_by('ID', $user_id);
  if (!$user || !wp_check_password( $password, $user->data->user_pass, $user->ID )) {
    wp_send_json([
      'success' => false,
      'error' => 'Invalid Credentials'
    ]);
  }
  $generated_token = lux_generate_random_string(60);
  $generated_token .= $user_id . $user->data->user_pass . $email.time();
  $generated_token = md5($generated_token);
  $user->data->user_pass = null;
  add_user_meta($user->ID, 'app_access_tokens', $generated_token);
  wp_send_json([
    'success' => true,
    'user' => $user->data,
    'token' => $generated_token
  ]);  
}

function lux_create_unique_username_by_email($email) {
  $username = '';
  // If @ is found and it's not the last character, return substring before @
  $atPosition = strpos($email, '@');
  if ($atPosition !== false && $atPosition < strlen($email) - 1) {
    $username = substr($email, 0, $atPosition);
  }
  $username = strtolower($username);
  $i = 2;
  while (username_exists( $username )) {
    $username = $username . $i;
    $i++;
  }
  return $username;
}

function lux_api_login( WP_REST_Request $request ) {
  header('Access-Control-Allow-Origin: *');
  header('Access-Control-Allow-Methods: GET, POST');
  header("Access-Control-Allow-Headers: X-Requested-With");
  $body = $request->get_body();
  $body = json_decode($body, true);
  $login = $body['login'];
  $password = $body['password'];
  if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
    $user = get_user_by( 'email', $login );
  } else {
    $user = get_user_by( 'login', $login );
  }
  if ( !$user || !wp_check_password( $password, $user->data->user_pass, $user->ID ) ) {
    wp_send_json([
      'success' => false,
      'error' => 'Invalid Credentials'
    ]);
  }
  $generated_token = lux_generate_random_string(60);
  $generated_token .= $user->ID . $user->data->user_pass . $login.time();
  $generated_token = md5($generated_token);
  $user->data->user_pass = null;
  add_user_meta($user->ID, 'app_access_tokens', $generated_token);
  wp_send_json([
    'success' => true,
    'user' => $user->data,
    'token' => $generated_token
  ]);
}

function lux_generate_random_string($length = 10) {
  $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $charactersLength = strlen($characters);
  $randomString = '';
  for ($i = 0; $i < $length; $i++) {
    $randomString .= $characters[random_int(0, $charactersLength - 1)];
  }
  return $randomString;
}

function lux_mobile_app_get_user_func( WP_REST_Request $request ) {
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
  wp_send_json([
    'success'=>true,
    'user' => $user[0]->data
  ]);
}

function mobile_app_get_favourites( WP_REST_Request $request ) {
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
  $user_id = $user[0]->ID;
  $favourite_products = get_user_meta($user_id, 'favourite_products', true);
  if (!$favourite_products) {
    $favourite_products = [];
  } else {
    $favourite_products = json_decode($favourite_products, true);
  }
  $favourite_products_to_send = [];
  foreach ($favourite_products as $value) {
    $product_data = wc_get_product($value)->get_data();
    // if (isset($product_data['image_id']) && $product_data['image_id']) {
      $product_data['testing_param'] = 'test';
      $product_data['images'] = wp_get_attachment_metadata($product_data['image_id']);
    // }
    $favourite_products_to_send[] = $product_data;
  }
  wp_send_json([
    'success'=>true,
    'favourite_products' => $favourite_products_to_send
  ]);
}

function mobile_app_add_to_favourites( WP_REST_Request $request ) {
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
  $user_id = $user[0]->ID;
  $favourite_products = get_user_meta($user_id, 'favourite_products', true);
  if (!$favourite_products) {
    $favourite_products = [];
  } else {
    $favourite_products = json_decode($favourite_products, true);
  }
  array_push($favourite_products, $body['product_id']);
  update_user_meta($user_id, 'favourite_products', json_encode($favourite_products));
  wp_send_json([
    'success'=>true,
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



