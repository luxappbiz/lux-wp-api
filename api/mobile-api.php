<?php

add_action( 'rest_api_init', function () {
  add_action( 'rest_pre_serve_request', function () {
    header('Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Wpml-Language', true );
    header("Access-Control-Allow-Origin: *");
    // header('Access-Control-Allow-Methods: GET, POST');
    // header("Access-Control-Allow-Headers: X-Requested-With");
  });
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

  register_rest_route('lux/v1', '/forgot-password', [
    'methods' => 'POST',
    'callback' => 'lux_api_handle_forgot_password',
  ]);

  register_rest_route('lux/v1', '/validate-password', [
    'methods' => 'POST',
    'callback' => 'lux_api_validate_password',
  ]);
});

function lux_api_register(WP_REST_Request $request) {
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
      'error' => 'Creating new user failed. ' . $user_id->get_error_message()
    ]);
  }
  $user = get_user_by('ID', $user_id);
  if (!$user || !wp_check_password($password, $user->data->user_pass, $user->ID)) {
    wp_send_json([
      'success' => false,
      'error' => 'Invalid Credentials'
    ]);
  }
  $generated_token = lux_generate_random_string(60);
  $generated_token .= $user_id . $user->data->user_pass . $email . time();
  $generated_token = md5($generated_token);
  $user->data->user_pass = null;
  add_user_meta($user->ID, 'app_access_tokens', $generated_token);
  $application_password = WP_Application_Passwords::create_new_application_password($user_id, array('name' => 'lux-mobile-app'));
  wp_send_json([
    'success' => true,
    'user' => $user->data,
    'token' => $generated_token,
    'application_password' => $application_password[0]
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

function lux_api_login(WP_REST_Request $request)
{
  header('Access-Control-Allow-Origin: *');
  header('Access-Control-Allow-Methods: GET, POST');
  header("Access-Control-Allow-Headers: X-Requested-With");
  $body = $request->get_body();
  $body = json_decode($body, true);
  $login = $body['login'];
  $password = $body['password'];
  if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
    $user = get_user_by('email', $login);
  } else {
    $user = get_user_by('login', $login);
  }
  if (!$user || !wp_check_password($password, $user->data->user_pass, $user->ID)) {
    wp_send_json([
      'success' => false,
      'error' => 'Invalid Credentials'
    ]);
  }
  $generated_token = lux_generate_random_string(60);
  $generated_token .= $user->ID . $user->data->user_pass . $login . time();
  $generated_token = md5($generated_token);
  $user->data->user_pass = null;
  add_user_meta($user->ID, 'app_access_tokens', $generated_token);
  $application_password = WP_Application_Passwords::create_new_application_password($user->ID, array('name' => 'lux-mobile-app'));

  wp_send_json([
    'success' => true,
    'user' => $user->data,
    'token' => $generated_token,
    'application_password' => $application_password[0]
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

function lux_api_handle_forgot_password(WP_REST_Request $request) {
  $email = sanitize_email($request->get_param('email'));

  if (!is_email($email)) {
    return wp_send_json([
      'message' => 'The provided email address is not valid.',
      'success' => false,
    ], 404);
  }

  $user = get_user_by('email', $email);
  if (!$user) {
    return wp_send_json([
      'message' => 'No user found with this email address.',
      'success' => false,
    ], 404);
  }

  $reset_key = get_password_reset_key($user);

  if (is_wp_error($reset_key)) {
    return wp_send_json([
      'message' => 'Failed to generate password reset link.',
      'success' => false,
    ], 500);
  }

  $reset_url = network_site_url("wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode($user->user_login));

  $message = sprintf(
    "Hello,\n\nYou requested a password reset for your account on %s.\n\nTo reset your password, click the link below:\n\n%s\n\nIf you did not request this, please ignore this email.",
    get_bloginfo('name'),
    $reset_url
  );

  $mail_sent = wp_mail($email, 'Password Reset Request', $message);

  if (!$mail_sent) {
    return wp_send_json([
      'message' => 'Failed to send the password reset email.',
      'success' => false,
    ], 500);
  }

  return wp_send_json([
    'message' => 'User password reset email sent successfully.',
    'success' => true,
  ]);
}

function lux_api_validate_password(WP_REST_Request $request)
{
  header('Access-Control-Allow-Origin: *');
  header('Access-Control-Allow-Methods: GET, POST');
  header("Access-Control-Allow-Headers: X-Requested-With");
  $body = $request->get_body();
  $body = json_decode($body, true);
  $user_id = $body['id'];
  $password = $body['password'];
  $user = get_user_by('ID', $user_id);

  if (!$user || !wp_check_password($password, $user->data->user_pass, $user_id)) {
    wp_send_json([
      'success' => false,
      'error' => 'Invalid Password'
    ]);
  }
  WP_Application_Passwords::delete_all_application_passwords($user_id);
  $application_password = WP_Application_Passwords::create_new_application_password($user_id, array('name' => 'lux-mobile-app'));
  wp_send_json([
    'success' => true,
    'application_password' => $application_password[0]
  ]);
}
