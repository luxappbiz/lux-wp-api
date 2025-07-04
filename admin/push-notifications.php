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

  ?>
  <div class="wrap">
    <h1>Mobile App Push Notifications</h1>
    <p>Send a push notification to your LUX mobile app users via Expo.</p>
    <form method="post" action="">
      <?php wp_nonce_field('lux_push_notification_nonce', 'lux_nonce'); ?>
      <table class="form-table">
        <tr>
          <th scope="row"><label for="lux_push_title">Title</label></th>
          <td><input type="text" name="lux_push_title" id="lux_push_title" class="regular-text" value="<?= $_POST['lux_push_title'] ?>" required /></td>
        </tr>
        <tr>
          <th scope="row"><label for="lux_push_body">Body</label></th>
          <td><input type="text" name="lux_push_body" id="lux_push_body" class="regular-text" value="<?= $_POST['lux_push_body'] ?>" required /></td>
        </tr>
        <tr>
          <th scope="row"><label for="lux_push_user_id">User Id</label></th>
          <td>
            <input type="number" name="lux_push_user_id" id="lux_push_user_id" class="regular-text" value="<?= $_POST['lux_push_user_id'] ?>" required />
            <p class="description">Enter the User Id.</p>
          </td>
        </tr>
      </table>
      <?php submit_button('Send Push Notification', 'primary', 'lux_push_submit'); ?>
    </form>
  </div>
  <?php
  // Check if form is submitted
  if (isset($_POST['lux_push_submit']) && check_admin_referer('lux_push_notification_nonce', 'lux_nonce')) {
    $title = sanitize_text_field($_POST['lux_push_title']);
    $body = sanitize_text_field($_POST['lux_push_body']);
    $lux_push_user_id = sanitize_text_field($_POST['lux_push_user_id']);
    if (!$lux_push_user_id) {
      echo '<div class="notice notice-error is-dismissible"><p>Please enter USER ID</p></div>';
      return;
    }
    $lux_push_user_id = get_user_by('id', $lux_push_user_id);
    if (!$lux_push_user_id) {
      echo '<div class="notice notice-error is-dismissible"><p>Unable to find User</p></div>';
      return;
    }
    $push_token = get_user_meta($lux_push_user_id, 'onesignal_id', true);
    if (!$push_token) {
      echo '<div class="notice notice-error is-dismissible"><p>User does not have registered onesignal id.</p></div>';
      return;
    }
    // Send the push notification
    $response = send_new_message_notification([$push_token], $body, $title);
    // Optional: handle response
    if (is_wp_error($response)) {
      echo $response->get_error_message();
    } else {
      $body = wp_remote_retrieve_body($response);
      print_r($body);
    }
  }
}