<?php

add_action('rest_api_init', function () {
  register_rest_route('lux/v1', '/register', array(
    'methods' => 'POST',
    'callback' => 'lux_api_register',
    'permission_callback' => '__return_true',
  ));

  register_rest_route('lux/v1', '/login', array(
    'methods' => 'POST',
    'callback' => 'lux_api_login',
    'permission_callback' => '__return_true',
  ));

  register_rest_route('lux/v1', '/user', array(
    'methods' => 'GET',
    'callback' => 'lux_mobile_app_get_user_func',
    'permission_callback' => function ($request) {
      return is_user_logged_in();
    },
  ));

  register_rest_route('lux/v1', '/user', array(
    'methods' => 'POST',
    'callback' => 'lux_mobile_app_update_user_func',
    'permission_callback' => function ($request) {
      return is_user_logged_in();
    },
  ));

  register_rest_route('lux/v1', '/user/notification', array(
    'methods' => 'POST',
    'callback' => 'lux_api_register_user_notification_token_func',
    'permission_callback' => function ($request) {
      return is_user_logged_in();
    },
  ));



  register_rest_route('lux/v1', '/forgot-password', [
    'methods' => 'POST',
    'callback' => 'lux_api_handle_forgot_password',
    'permission_callback' => '__return_true',
  ]);

  register_rest_route('lux/v1', '/validate-password', [
    'methods' => 'POST',
    'callback' => 'lux_api_validate_password',
    'permission_callback' => '__return_true',
  ]);


  register_rest_route('lux/v2', '/register', array(
    'methods' => 'POST',
    'callback' => 'lux_api_register_v2',
    'permission_callback' => '__return_true',
  ));

  register_rest_route('lux/v2', '/login', array(
    'methods' => 'POST',
    'callback' => 'lux_api_login_v2',
    'permission_callback' => '__return_true',
  ));

});

function lux_api_register(WP_REST_Request $request)
{
  lux_rest_api_cors_headers();
  $body = $request->get_body();
  $body = json_decode($body, true);
  $email = $body['email'];
  if (email_exists($email)) {
    wp_send_json([
      'success' => false,
      'error' => 'Email address already exists, use a different email or login.',
    ]);
  }
  $password = $body['password'];
  $username = lux_create_unique_username_by_email($email);
  $user_id = wp_create_user($username, $password, $email);
  if (is_wp_error($user_id)) {
    wp_send_json([
      'success' => false,
      'error' => 'Creating new user failed. ' . $user_id->get_error_message()
    ]);
  }
  $sent_verification_email = lux_api_send_verification_email($user_id);
  add_user_meta($user->ID, 'terms_agreed_at', current_time('mysql'));
  $user = new WP_User($user_id);
  $user->set_role('customer');
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

function lux_create_unique_username_by_email($email)
{
  $username = '';
  // If @ is found and it's not the last character, return substring before @
  $atPosition = strpos($email, '@');
  if ($atPosition !== false && $atPosition < strlen($email) - 1) {
    $username = substr($email, 0, $atPosition);
  }
  $username = strtolower($username);
  $i = 2;
  while (username_exists($username)) {
    $username = $username . $i;
    $i++;
  }
  return $username;
}

function lux_createRandStr($strLength)
{
  //specify characters to be used in generating random string, do not specify any characters that wordpress does not allow in the creation.
  $characters = "0123456789ABCDEFGHIJKLMNPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz_[]{}!@$%^*().,>=-;|:?";
  //get the total length of specified characters to be used in generating random string
  $charactersLength = strlen($characters);
  //declare a string that we will use to create the random string 
  $randomString = '';
  for ($i = 0; $i < $strLength; $i++) {
    //generate random characters
    $randomCharacter = $characters[rand(0, $charactersLength - 1)];
    //add the random characters to the random string
    $randomString .= $randomCharacter;
  }
  ;
  //sanitize_user, just in case 
  $sanRandomString = sanitize_user($randomString);
  //check that random string contains Uppercase/Lowercase/Intergers/Special Char and that it is the correct length
  if ((preg_match('([a-zA-Z].*[0-9]|[0-9].*[a-zA-Z].*[_\W])', $sanRandomString) == 1) && (strlen($sanRandomString) == $strLength)) {
    //return the random string if it meets the complexity criteria 
    return $sanRandomString;
  } else {
    // if the random string does not meet minimium criteria call function again 
    return call_user_func("lux_createRandStr", ($strLength));
  }
}

function lux_api_send_verification_email($user_id)
{
  if (!$user_id) {
    $user_id = get_current_user_id();
  }
  $user = get_userdata($user_id);
  $user_data = $user->data;
  $code = lux_createRandStr(42); // check does func exist?
  update_user_meta($user_id, 'email_verified', false);
  update_user_meta($user_id, 'email_verification_code', $code);
  update_user_meta($user_id, 'email_verification_code_generation_time', time());
  $email_code_verification_page_link = get_field('email_code_verification_page_link', 'option');
  $url = $email_code_verification_page_link . '?code=' . base64_encode(serialize($code));
  $body = get_field('verification_email_template', 'option');
  $tags_to_replace = [
    '[user_name]' => $user_data->display_name,
    '[email]' => $user_data->user_email,
    '[verification_link]' => "<a href='$url'>$url</a>"
  ];
  foreach ($tags_to_replace as $key => $value) {
    $body = str_replace($key, $value, $body);
  }
  $headers = array('Content-Type: text/html; charset=UTF-8');
  return wp_mail($user_data->user_email, 'LUX Email Verification', $body, $headers);
}

function lux_api_login(WP_REST_Request $request)
{
  lux_rest_api_cors_headers();
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
  $user->data->unread_messages_count = lux_api_helper_get_total_unread_messages_count($user->ID);
  $user->data->has_active_membership = lux_user_has_active_membership($user->ID);
  wp_send_json([
    'success' => true,
    'user' => $user->data,
    'token' => $generated_token,
    'application_password' => $application_password[0]
  ]);
}

function lux_generate_random_string($length = 10)
{
  $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $charactersLength = strlen($characters);
  $randomString = '';
  for ($i = 0; $i < $length; $i++) {
    $randomString .= $characters[random_int(0, $charactersLength - 1)];
  }
  return $randomString;
}

function lux_mobile_app_get_user_func(WP_REST_Request $request)
{
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
  wp_send_json([
    'success' => true,
    'user' => $user[0]->data
  ]);
}

function lux_mobile_app_update_user_func(WP_REST_Request $request)
{
  lux_rest_api_cors_headers();
  $user_id = get_current_user_id();
  $new_display_name = sanitize_text_field($request->get_param('display_name'));
  $media_id = null;
  if (!$user_id) {
    return new WP_Error('mpp_no_user', __('User not found.', ''), array('status' => 403));
  }
  // if (! current_user_can('upload_files', $user_id)) {
  //   return new WP_Error('mpp_insufficient_privs', __('You must be able to upload files.', ''), array('status' => 403));
  // }
  $fileParams = $request->get_file_params();
  $post_id = lux_get_media_post_id($user_id);
  // Save user meta.
  update_user_option($user_id, 'metronet_post_id', $post_id);
  if ($image = $fileParams['profile_image']) {
    $upload = wp_handle_upload($image, ['test_form' => false]);
    if (isset($upload['error'])) {
      return new WP_Error('image_upload_failed', $upload['error'], ['status' => 500]);
    }
    $media_id = lux_create_attachment($upload['file'], $upload['url'], $post_id);
  }
  update_user_option($user_id, 'metronet_image_id', $media_id);
  set_post_thumbnail($post_id, $media_id);
  $user_data = [
    'ID' => $user_id,
    'display_name' => $new_display_name,
  ];
  // Update the user
  $result = wp_update_user($user_data);
  if (is_wp_error($result)) {
    wp_send_json([
      'success' => false,
      'error' => $result
    ]);
  }
  wp_send_json([
    'success' => true,
  ]);
}

function lux_get_media_post_id($user_id = 0)
{
  $user = get_user_by('id', $user_id);
  // Get/Create Profile Picture Post.
  $post_args = array(
    'post_type' => 'mt_pp',
    'author' => $user_id,
    'post_status' => 'publish',
  );
  $posts = get_posts($post_args);
  if (!$posts) {
    $post_id = wp_insert_post(
      array(
        'post_author' => $user_id,
        'post_type' => 'mt_pp',
        'post_status' => 'publish',
        'post_title' => $user->data->display_name,
      )
    );
  } else {
    $post = end($posts);
    $post_id = $post->ID;
  }
  return $post_id;
}

function lux_api_handle_forgot_password(WP_REST_Request $request)
{
  lux_rest_api_cors_headers();
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

function lux_api_register_user_notification_token_func(WP_REST_Request $request)
{
  if (!is_user_logged_in()) {
    return;
  }
  $body = $request->get_body();
  $body = json_decode($body, true);
  update_user_meta(get_current_user_id(), 'external_id', $body['externalId']);
  update_user_meta(get_current_user_id(), 'onesignal_id', $body['onesignalId']);
  return wp_send_json(array(
    'success' => true,
  ));
}

function lux_api_register_v2_simple(WP_REST_Request $request)
{

  $api_validation = vires_validate_api_key($request, ['auth_method' => 'header']);

  if (is_wp_error($api_validation)) {
    return $api_validation;
  }

  return lux_api_register($request);
}

function lux_api_login_v2_simple(WP_REST_Request $request)
{

  $api_validation = vires_validate_api_key($request, ['auth_method' => 'header']);

  if (is_wp_error($api_validation)) {
    return $api_validation;
  }

  return lux_api_login($request);
}


