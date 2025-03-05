<?php

// Register the admin menu page
add_action('admin_menu', function () {
  add_menu_page(
    'Mobile App Push Notifications',       // Page title
    'Push Notifications',                  // Menu title
    'manage_options',                      // Capability required
    'mobile-app-push-notifications',       // Menu slug
    'lux_render_push_notifications_page',  // Callback function to render the page
    'dashicons-bell',                      // Icon (WordPress dashicon)
    30                                     // Menu position
  );
});

// Render the admin page
function lux_render_push_notifications_page() {
  // Check if form is submitted
  if (isset($_POST['lux_push_submit']) && check_admin_referer('lux_push_notification_nonce', 'lux_nonce')) {
    $title = sanitize_text_field($_POST['lux_push_title']);
    $body = sanitize_text_field($_POST['lux_push_body']);
    $push_token = sanitize_text_field($_POST['lux_push_token']);
    // Validate push token format (basic check)
    if (empty($push_token) || strpos($push_token, 'ExponentPushToken[') !== 0) {
      echo '<div class="notice notice-error is-dismissible"><p>Please enter a valid ExponentPushToken (e.g., ExponentPushToken[xxx]).</p></div>';
    } else {
      // Send the push notification
      $response = lux_send_push_notification($title, $body, $push_token);
      // Display success or error message
      if (!is_wp_error($response)) {
        $response_body = wp_remote_retrieve_body($response);
        echo '<div class="notice notice-success is-dismissible"><p>Push notification sent successfully! Response: ' . esc_html($response_body) . '</p></div>';
      } else {
        echo '<div class="notice notice-error is-dismissible"><p>Error sending push notification: ' . esc_html($response->get_error_message()) . '</p></div>';
      }
    }
  }
  ?>
  <div class="wrap">
    <h1>Mobile App Push Notifications</h1>
    <p>Send a push notification to your LUX mobile app users via Expo.</p>
    <form method="post" action="">
      <?php wp_nonce_field('lux_push_notification_nonce', 'lux_nonce'); ?>
      <table class="form-table">
        <tr>
          <th scope="row"><label for="lux_push_title">Title</label></th>
          <td><input type="text" name="lux_push_title" id="lux_push_title" class="regular-text" required /></td>
        </tr>
        <tr>
          <th scope="row"><label for="lux_push_body">Body</label></th>
          <td><input type="text" name="lux_push_body" id="lux_push_body" class="regular-text" required /></td>
        </tr>
        <tr>
          <th scope="row"><label for="lux_push_token">Push Token</label></th>
          <td>
            <input type="text" name="lux_push_token" id="lux_push_token" class="regular-text" required placeholder="ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]" />
            <p class="description">Enter the Expo push token for the recipient device.</p>
          </td>
        </tr>
      </table>
      <?php submit_button('Send Push Notification', 'primary', 'lux_push_submit'); ?>
    </form>
  </div>
  <?php
}

// Function to send the push notification via Expo API
function lux_send_push_notification($title, $body, $push_token) {
  $api_url = 'https://exp.host/--/api/v2/push/send';
  $request_body = wp_json_encode(array(
    'to'    => $push_token,
    'title' => $title,
    'body'  => $body,
  ));
  $args = array(
    'method'      => 'POST',
    'headers'     => array(
      'Content-Type' => 'application/json',
    ),
    'body'        => $request_body,
    'timeout'     => 30,
  );
  return wp_remote_post($api_url, $args);
}
