<?php

add_action('admin_init', function () {
  if (get_option('lux_conversations_table_created_at')) {
    return;
  }
  global $wpdb;
  $table_name = $wpdb->prefix . 'lux_conversations';
  if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
      id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      owner_user_id INT(11) UNSIGNED NOT NULL,
      name TEXT NOT NULL,
      deleted_at DATETIME DEFAULT NULL,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    // $wpdb->query("ALTER TABLE $table_name ADD INDEX (sender_user_id, receiver_user_id);");
    add_option('lux_conversations_table_created_at', current_datetime()->format('Y-m-d H:i:s'));
  }
  if (get_option('lux_conversation_users_table_created_at')) {
    return;
  }
  global $wpdb;
  $table_name = $wpdb->prefix . 'lux_conversation_users';
  if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
      id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      conversation_id INT(11) UNSIGNED NOT NULL,
      user_id INT(11) UNSIGNED NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    add_option('lux_conversation_users_table_created_at', current_datetime()->format('Y-m-d H:i:s'));
  }
  if (get_option('lux_messages_table_created_at')) {
    return;
  }
  global $wpdb;
  $table_name = $wpdb->prefix . 'lux_messages';
  if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
      id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      conversation_id INT(11) UNSIGNED NOT NULL,
      user_id INT(11) UNSIGNED NOT NULL,
      message TEXT NOT NULL,
      attachment_id INT(11) UNSIGNED NOT NULL,
      reactions JSON DEFAULT NULL,
      deleted_at DATETIME DEFAULT NULL,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    add_option('lux_messages_table_created_at', current_datetime()->format('Y-m-d H:i:s'));
  }
});
