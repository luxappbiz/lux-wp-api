<?php

// Hook into the admin_menu action to add our page
add_action('admin_menu', function () {
  // Add a new top-level menu (if you want a submenu use add_submenu_page instead)
  add_menu_page(
    'Chats',    // Page title
    'Chats',    // Menu title
    'manage_options',      // Capability required
    'chats',      // Menu slug
    'lux_admin_chats_page_content', // The function to display the page content
    'dashicons-admin-comments', // Icon URL or Dashicon (you can change this to any dashicon)
    6 // Position in the menu
  );
});

add_action('admin_init', function () {
  if (get_option('lux_chats_table_created_at')) {
    return;
  }
  global $wpdb;
  $table_name = $wpdb->prefix . 'lux_chats';
  if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
      id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      sender_user_id INT(11) UNSIGNED NOT NULL,
      receiver_user_id INT(11) UNSIGNED NOT NULL,
      message TEXT NOT NULL,
      reactions JSON DEFAULT NULL,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    // $wpdb->query("ALTER TABLE $table_name ADD INDEX (sender_user_id, receiver_user_id);");
    add_option('lux_chats_table_created_at', current_datetime()->format('Y-m-d H:i:s'));
  }
});

function lux_admin_chats_page_content() {
  $site_url = get_site_url();
  ?>
  <style>
  .lux-admin-chats ul {
    list-style: initial;
    margin-left: 1rem;
  }
  </style>
  <div class="wrap lux-admin-chats">
    <h1>Chats</h1>
    <?php
    global $wpdb;
    $table = $wpdb->prefix . 'lux_chats';
    $sql = "SELECT * FROM $table";
    $chat_messages = $wpdb->get_results($sql);
    if ($chat_messages && !empty($chat_messages)) { ?>
      <div class="wp-list-table-wrap">
        <table class="wp-list-table widefat fixed striped">
          <thead>
            <tr>
              <th>ID</th>
              <th>Sender User ID</th>
              <th>Receiver User ID</th>
              <th>Message</th>
              <th>Reactions</th>
              <th>Updated At</th>
              <th>Created At</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($chat_messages as $key => $chat_message) { ?>
              <tr>
                <td><?= $chat_message->id ?></td>
                <td><?= $chat_message->sender_user_id ?></td>
                <td><?= $chat_message->receiver_user_id ?></td>
                <td><?= $chat_message->message ?></td>
                <td><?= $chat_message->reactions ?></td>
                <td><?= $chat_message->updated_at ?></td>
                <td><?= $chat_message->created_at ?></td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    <?php } ?>
  </div>
  <?php
}
