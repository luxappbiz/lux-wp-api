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

  register_rest_route('lux/v3', '/login', array(
    'methods' => 'POST',
    'callback' => 'lux_api_login_v3',
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


  register_rest_route('lux/v1', '/create-checkout-session', array(
    'methods' => 'POST',
    'callback' => 'lux_create_checkout_session',
    'permission_callback' => '__return_true', // We handle auth in callback
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

function lux_api_login(WP_REST_Request $request) {
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
    // 'token' => $generated_token,
    'token' => $application_password[0],
    'application_password' => $application_password[0],
  ]);
}

function lux_get_user_object_from_user_id($user_id){
  $user = get_user_by('ID', $user_id);
  if (!$user) {
    return null;
  }
  $user->data->user_pass = null;
  $user->data->image = false;
  $profile_avatar = get_field('profile_avatar', 'user_' . $user->ID);
  if ($profile_avatar) {
    $user->data->image = wp_get_attachment_image_url($profile_avatar, 'full');
  } else {
    $user->data->image = get_avatar_url($user->ID, array('size' => '512'));
  }
  $user->data->first_name = get_user_meta($user->ID, 'first_name', true);
  $user->data->last_name = get_user_meta($user->ID, 'last_name', true);
  $user->data->affiliate_id = false;
  if (function_exists('gigfiliate_is_active_affiliate') && gigfiliate_is_active_affiliate($user->ID)) {
    $user->data->affiliate_id = (int)get_user_meta($user->ID, 'v_affiliate_id', true);
  }
  // $user->data->phone = get_user_meta($user->ID, 'phone', true);
  $user->data->phone = get_field('phone', 'user_' . $user->ID);
  $user->data->instagram = get_field('instagram', 'user_' . $user->ID);
  $user->data->occupation = get_field('occupation', 'user_' . $user->ID);
  $user->data->gender = get_user_meta($user->ID, 'gender', true);
  $user->data->total_unread_messages_count = lux_api_helper_get_total_unread_messages_count($user->ID);
  $user->data->balances = [
    'incoming' => get_user_meta($user->ID, 'lux_incoming_balance', true),
    'available' => get_user_meta($user->ID, 'lux_available_balance', true),
  ];
  $user->data->capabilities = get_field('capabilities', 'user_' . $user->ID);
  $user->data->is_verified = get_user_meta($user->ID, 'email_verified', true);
  return $user->data;
}

function lux_api_login_v3( WP_REST_Request $request ) {
  $body = $request->get_body();
  $body = json_decode($body, true);
  $login = $body['login'];
  $password = $body['password'];
  $user = false;
  // $user = get_user_by( 'email', $email );
  if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
    $user = get_user_by('email', $login);
  } else {
    $user = get_user_by('login', $login);
  }
  if ( !$user || !wp_check_password( $password, $user->data->user_pass, $user->ID ) ) {
    wp_send_json([
      'success'=>false,
      'error'=>'Invalid Credentials'
    ]);
  }
  // Get user agent for application password name
  $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown App';
  $app_name = !empty($user_agent) ? substr($user_agent, 0, 65) : 'mobile-app'; 
  $application_password = WP_Application_Passwords::create_new_application_password($user->ID, array('name' => $app_name));
  $user = lux_get_user_object_from_user_id($user->ID);
  wp_send_json([
    'success' => true,
    'user' => $user,
    'token' => $application_password[0],
    'application_password' => $application_password[0],
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

function lux_api_register_v2(WP_REST_Request $request)
{

  $api_validation = vires_validate_api_key($request, ['auth_method' => 'header']);

  if (is_wp_error($api_validation)) {
    return $api_validation;
  }

  return lux_api_register($request);
}

function lux_api_login_v2(WP_REST_Request $request)
{

  $api_validation = vires_validate_api_key($request, ['auth_method' => 'header']);

  if (is_wp_error($api_validation)) {
    return $api_validation;
  }

  return lux_api_login($request);
}


function lux_create_checkout_session(WP_REST_Request $request)
{
  lux_rest_api_cors_headers();

  // Manual auth check
  $user_id = get_current_user_id();
  if (!$user_id) {
    $auth_header = $request->get_header('authorization');
    if ($auth_header && preg_match('/Basic (.+)/', $auth_header, $matches)) {
      $credentials = base64_decode($matches[1]);
      list($username, $app_password) = explode(':', $credentials);

      $user = wp_authenticate_application_password(null, $username, $app_password);
      if (!is_wp_error($user) && $user) {
        $user_id = $user->ID;
        wp_set_current_user($user_id);
      }
    }
  }

  if (!$user_id) {
    return wp_send_json([
      'success' => false,
      'error' => 'User not authenticated'
    ]);
  }

  $body = json_decode($request->get_body(), true);
  $plan_type = sanitize_text_field($body['plan_type'] ?? 'annual');

  if (!in_array($plan_type, ['monthly', 'annual'])) {
    return wp_send_json([
      'success' => false,
      'error' => 'Invalid plan type'
    ]);
  }

  // Generate session token
  $session_token = wp_generate_password(32, false);
  $session_key = 'lux_checkout_session_' . $session_token;

  // Store session data
  $session_data = array(
    'user_id' => $user_id,
    'plan_type' => $plan_type,
    'variation_id' => $plan_type === 'monthly' ? 841 : 842,
    'product_id' => 482,
    'created_at' => current_time('timestamp'),
    'used' => false
  );

  set_transient($session_key, $session_data, 600); // 10 minutes

  // Build auto-login URL
  $auto_login_url = home_url('/lux-auto-login') . '?' . http_build_query(array(
    'session' => $session_token,
    'plan' => $plan_type
  ));

  return wp_send_json(array(
    'success' => true,
    'session_token' => $session_token,
    'checkout_url' => $auto_login_url,
    'expires_in' => 600
  ));
}

// 3. Auto-login handler
add_action('init', 'lux_handle_auto_login', 1);
function lux_handle_auto_login() {
  if (!isset($_GET['session']) || strpos($_SERVER['REQUEST_URI'], '/lux-auto-login') === false) {
    return;
  }
  $session_token = sanitize_text_field($_GET['session']);
  $session_key = 'lux_checkout_session_' . $session_token;
  $session_data = get_transient($session_key);
  if (!$session_data) {
    wp_die('Session expired or invalid. Please try again from the app.', 'Session Error');
  }
  if ($session_data['used']) {
    wp_die('This session has already been used.', 'Session Error');
  }
  // Mark as used
  $session_data['used'] = true;
  set_transient($session_key, $session_data, 300);
  // Log user in
  $user = get_user_by('id', $session_data['user_id']);
  if (!$user) {
    wp_die('User not found.', 'Error');
  }
  wp_clear_auth_cookie();
  wp_set_current_user($session_data['user_id']);
  wp_set_auth_cookie($session_data['user_id'], true);
  // Store session info for redirect handling
  if (!session_id()) {
    session_start();
  }
  $_SESSION['lux_app_return'] = true;
  // Redirect to cart with product
  $cart_url = add_query_arg(array(
    'add-to-cart' => $session_data['product_id'],
    'variation_id' => $session_data['variation_id']
  ), wc_get_cart_url());
  wp_redirect($cart_url);
  exit;
}

// 4. Rewrite rules
add_action('init', 'lux_add_auto_login_rewrite');
function lux_add_auto_login_rewrite() {
  add_rewrite_rule('^lux-auto-login/?', 'index.php?lux_auto_login=1', 'top');
}

add_filter('query_vars', 'lux_add_auto_login_query_var');
function lux_add_auto_login_query_var($vars) {
  $vars[] = 'lux_auto_login';
  return $vars;
}
