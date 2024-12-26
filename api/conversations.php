<?php

add_action('rest_api_init', function () {
  register_rest_route('lux/v1', '/conversations', array(
    'methods' => 'GET',
    'callback' => 'lux_api_get_conversations',
    'permission_callback' => '__return_true'
  ));
  /**
   * Register the REST API route for creating a new conversation.
   */
  register_rest_route( 'lux/v1', '/conversations', array(
    'methods' => 'POST',
    'callback' => 'lux_api_create_conversation',
    'permission_callback' => '__return_true'
    // 'permission_callback' => function () {
    //   return current_user_can( 'edit_posts' ); // Adjust permissions as needed
    // }
  ) );
  /**
   * API - New Conversation Message
   */
  register_rest_route( 'lux/v1', '/conversations/(?P<conversation_id>\d+)/messages', array(
    'methods' => 'POST',
    'callback' => 'lux_api_create_conversation_message',
    // 'permission_callback' => function () {
    //   return current_user_can( 'edit_posts' ); // Adjust permissions as needed
    // },
    'permission_callback' => '__return_true',
    'args' => array(
      'conversation_id' => array(
        'validate_callback' => function($param, $request, $key) {
          return is_numeric($param);
        }
      )
    ),
  ) );
  register_rest_route('lux/v1', '/conversations/(?P<conversation_id>\d+)/messages', array(
    'methods' => 'GET',
    'callback' => 'lux_api_get_conversation_messages',
    'permission_callback' => '__return_true',
    'args' => array(
      'conversation_id' => array(
        'validate_callback' => function($param, $request, $key) {
          return is_numeric($param);
        }
      )
    ),
  ));
  /**
   * API - Conversation Message PUT - Updating a message
   */
  register_rest_route('lux/v1', '/conversations/(?P<conversation_id>\d+)/messages/(?P<message_id>\d+)', array(
    'methods' => 'PUT',
    'callback' => 'lux_api_update_conversation_message',
    'permission_callback' => '__return_true',
    'args' => array(
      'conversation_id' => array(
        'validate_callback' => function($param, $request, $key) {
          return is_numeric($param);
        }
      ),
      'message_id' => array(
        'validate_callback' => function($param, $request, $key) {
          return is_numeric($param);
        }
      )
    )
  ));
});

function lux_api_get_conversations( WP_REST_Request $request ) {
  // Get Conversations by User ID
  $user_id = $request->get_param('user_id');
  if (!$user_id) {
    return new WP_REST_Response(array(
      'error' => 'User ID is required to get conversations.',
      'message' => 'User ID is required to get conversations.', 
    ), 500);
  }
  global $wpdb;
  $table = $wpdb->prefix . 'lux_conversation_users';
  $sql = "SELECT * FROM $table WHERE user_id = $user_id ORDER BY created_at ASC";
  $conversation_users_data = $wpdb->get_results($sql);
  $conversations = array();
  if ($conversation_users_data && !empty($conversation_users_data)) {
    foreach ($conversation_users_data as $conversation_user_data) {
      $conversation_id = $conversation_user_data->conversation_id;
      $user_id = $conversation_user_data->user_id;
      $table = $wpdb->prefix . 'lux_conversations';
      $sql = "SELECT * FROM $table WHERE id = $conversation_id";
      // Get Conversation Data
      $conversation_data = $wpdb->get_row($sql);
      if ($conversation_data) {
        // Set Conversation Image & Name to be NOT the current user
        $table = $wpdb->prefix . 'lux_conversation_users';
        $sql = "SELECT * FROM $table WHERE conversation_id = $conversation_id AND user_id != $user_id";
        $other_conversation_users_data = $wpdb->get_results($sql);
        $other_conversation_user_id = $other_conversation_users_data[0]->user_id;
        // todo use all conversation users to make the conversation name and the image, for now we'll just use first found
        $image_url = '';
        $profile_avatar = get_field('profile_avatar', 'user_' . $other_conversation_user_id);
        if ($profile_avatar) {
          // $image_url = wp_get_attachment_image_url($profile_avatar, 'full');
          $image_url = $profile_avatar['url'];
        } else {
          $image_url = get_avatar_url($other_conversation_user_id, array('size' => '512'));
        }
        $conversation_name = $conversation_data->name;
        if ($conversation_name == '') {
          $conversation_name = get_user_meta($other_conversation_user_id, 'first_name', true) . ' ' . get_user_meta($other_conversation_user_id, 'last_name', true);
        }
        $conversations[] = array(
          'id' => $conversation_id,
          'name' => $conversation_name,
          'image_url' => $image_url,
          'updated_at' => $conversation_data->updated_at,
          'created_at' => $conversation_data->created_at,
        );
      }
    }
  }
  $sort = 'asc';
  // Sort the $conversations array by the 'updated_at' field
  usort($conversations, function($a, $b) {
    $timestamp_a = strtotime($a['updated_at']);
    $timestamp_b = strtotime($b['updated_at']);
    return $sort == 'asc' ? $timestamp_a - $timestamp_b : $timestamp_b - $timestamp_a; // Ascending order, change to $timestampB - $timestampA for descending
  });
  return new WP_REST_Response(array(
    'sort' => $sort,
    'conversations' => $conversations, 
  ), 201);
}

function lux_api_create_conversation( WP_REST_Request $request ) {
  $user_id = $request->get_param('owner_user_id');
  if (!$user_id || empty($user_id)) {
    return new WP_REST_Response(array('message' => 'User ID missing.'), 400);
  }
  $conversation_users = $request->get_param('conversation_users');
  if (!$conversation_users || empty($conversation_users)) {
    return new WP_REST_Response(array('message' => 'Conversaion users missing.'), 400);
  }
  $conversation_user_ids = explode(',', $conversation_users);
  global $wpdb;
  // if Conversation ID already exist
  $sql = '';
  $existing_conversation_id = false;
  if ($conversation_user_ids) {
    $table = $wpdb->prefix . 'lux_conversation_users';
    // Prepare the placeholders for SQL IN clause
    $placeholders = implode(', ', array_fill(0, count($conversation_user_ids), '%d'));
    $sql = $wpdb->prepare("SELECT conversation_id FROM $table WHERE user_id IN ($placeholders) GROUP BY conversation_id HAVING COUNT(DISTINCT user_id) = %d LIMIT 1", array_merge($conversation_user_ids, [count($conversation_user_ids)]));
    $existing_conversation_id = $wpdb->get_var($sql);
  }
  if ($existing_conversation_id) {
    return new WP_REST_Response(array(
      'sql' => $sql,
      'conversation_id' => $existing_conversation_id,
      'message' => 'Conversation already exists.'
    ), 201);
  }
  // else Create Conversation
  $table = $wpdb->prefix . 'lux_conversations';
  $data = array(
    'owner_user_id' => $user_id,
  );
  $success = $wpdb->insert($table, $data);
  if ($success) {
    $conversation_id = $wpdb->insert_id;
    // Save Conversation Users
    if ($conversation_user_ids) {
      foreach ($conversation_user_ids as $user_id) {
        $table = $wpdb->prefix . 'lux_conversation_users';
        $data = array(
          'conversation_id' => $conversation_id,
          'user_id' => (int)$user_id,
        );
        $success = $wpdb->insert($table, $data);
      }
    }
    return new WP_REST_Response(array(
      'conversation_id' => $conversation_id,
      'message' => 'Conversation created success.'
    ), 201);
  } else {
    return new WP_REST_Response(array(
      'error' => $wpdb->last_error,
      'message' => 'Conversation creaetion failed.', 
    ), 500);
  }
}

function lux_api_create_conversation_message( WP_REST_Request $request ) {
  $conversation_id = $request['conversation_id'];
  $user_id = $request->get_param('user_id');
  $message = $request->get_param('message');
  global $wpdb;
  // Check if the conversation exists
  $table_conversations = $wpdb->prefix . 'lux_conversations';
  $conversation_exists = $wpdb->get_var("SELECT id FROM $table_conversations WHERE id = $conversation_id");
  if (!$conversation_exists) {
    return new WP_REST_Response(array(
      'error' => $wpdb->last_error,
      'message' => 'Conversation not found.'
    ), 404);
  }
  $table_messages = $wpdb->prefix . 'lux_messages';
  $data = array(
    'conversation_id' => $conversation_id,
    'user_id' => $user_id,
    'message' => $message,
  );
  $success = $wpdb->insert($table_messages, $data);
  if ($success) {
    $message_id = $wpdb->insert_id;
    // Update Conversation updated_at
    $result = $wpdb->update(
      $table_conversations,
      array(
        'updated_at' => current_time('mysql'), // Column and value to update
      ),
      array(
        'id' => $conversation_id // Where clause
      ),
      array(
        '%s' // Format for updated_at (string)
      ),
      array(
        '%d' // Format for id (integer)
      )
    );
    return new WP_REST_Response(array(
      'message_id' => $message_id,
      'message' => 'Conversation message created success.'
    ), 201);
  } else {
    return new WP_REST_Response(array(
      'error' => $wpdb->last_error,
      'message' => 'Conversation message creaetion failed.', 
    ), 500);
  }
}

function lux_api_get_conversation_messages( WP_REST_Request $request ) {
  $conversation_id = $request['conversation_id'];
  global $wpdb;
  // Check if the conversation exists
  $table = $wpdb->prefix . 'lux_conversations';
  $conversation_exists = $wpdb->get_var("SELECT id FROM $table WHERE id = $conversation_id");
  if (!$conversation_exists) {
    return new WP_REST_Response(array(
      'error' => $wpdb->last_error,
      'message' => 'Conversation not found.'
    ), 404);
  }
  $table = $wpdb->prefix . 'lux_messages';
  $sql = "SELECT * FROM $table WHERE conversation_id = $conversation_id ORDER BY created_at ASC";
  $messages_data = $wpdb->get_results($sql);
  $messages = array();
  if (!empty($messages_data)) {
    foreach ($messages_data as $message_data) {
      $messages[] = array(
        'id' => $message_data->id,
        'conversation_id' => $message_data->conversation_id,
        'user_id' => $message_data->user_id,
        'message' => $message_data->message,
        'attachment_id' => $message_data->attachment_id,
        'reactions' => $message_data->reactions,
        'updated_at' => $message_data->updated_at,
        'created_at' => $message_data->created_at,
      );
    }
  }
  return new WP_REST_Response(array(
    'messages' => $messages, 
  ), 201); 
}

function lux_api_update_conversation_message( WP_REST_Request $request ) {
  $conversation_id = $request['conversation_id'];
  $message_id = $request['message_id'];
  if (!$conversation_id || !$message_id) {
    return new WP_REST_Response(array(
      'message' => 'Conversation or message not found.'
    ), 404);
  }
  // Check if the message exists in the specified conversation
  // Update the message
  // Return response message
  return new WP_REST_Response(array(
    'message' => 'To do: under construction',
    'message_id' => $message_id
  ), 200);
}
