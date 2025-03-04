<?php

function lux_api_helper_get_total_unread_messages_count($user_id = false) {
  if (!$user_id) {
    return false;
  }
  global $wpdb;
  $table = $wpdb->prefix . 'lux_conversation_users';
  $sql = "SELECT * FROM $table WHERE user_id = $user_id ORDER BY created_at ASC";
  $conversation_users_data = $wpdb->get_results($sql);
  $total_unread_messages_count = 0;
  if ($conversation_users_data && !empty($conversation_users_data)) {
    foreach ($conversation_users_data as $conversation_user_data) {
      $conversation_id = $conversation_user_data->conversation_id;
      $user_id = $conversation_user_data->user_id;
      $table = $wpdb->prefix . 'lux_conversations';
      $sql = "SELECT * FROM $table WHERE id = $conversation_id";
      // Get Conversation Data
      $conversation_data = $wpdb->get_row($sql);
      if ($conversation_data) {
        $table_messages_read = $wpdb->prefix . 'lux_messages_read';
        $sql = "SELECT COUNT(*) FROM $table_messages_read WHERE conversation_id = $conversation_id AND user_id = $user_id AND read_at IS null";
        $total_unread_messages_count += (int)$wpdb->get_var($sql);
      }
    }
  }
  return $total_unread_messages_count;
}
