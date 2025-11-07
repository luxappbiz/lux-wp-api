<?php

add_action('rest_api_init', function () {
  /**
   * API - GET conversations
   */
  register_rest_route('lux/v1', '/conversations', array(
    'methods' => 'GET',
    'callback' => 'lux_api_get_conversations',
    'permission_callback' => function($request) {
      return is_user_logged_in();
    },
  ));

  register_rest_route('lux/v3', '/conversations', array(
    'methods' => 'GET',
    'callback' => 'lux_api_get_conversations_v3',
    'permission_callback' => function($request) {
      return is_user_logged_in();
    },
  ));
  /**
   * API - Create new conversation
   */
  register_rest_route( 'lux/v1', '/conversations', array(
    'methods' => 'POST',
    'callback' => 'lux_api_create_conversation',
    'permission_callback' => function($request) {
      return is_user_logged_in();
    },
  ) );
  /**
   * API - New Conversation Message
   */
  register_rest_route( 'lux/v1', '/conversations/(?P<conversation_id>\d+)/messages', array(
    'methods' => 'POST',
    'callback' => 'lux_api_create_conversation_message',
    'permission_callback' => function($request) {
      return is_user_logged_in();
    },
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
    'permission_callback' => function($request) {
      return is_user_logged_in();
    },
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
    'permission_callback' => function($request) {
      return is_user_logged_in();
    },
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
  /**
   * API - Conversation Message DELETE - Deleting a message (soft delete)
   */
  register_rest_route('lux/v1', '/conversations/(?P<conversation_id>\d+)/messages/(?P<message_id>\d+)', array(
    'methods' => 'DELETE',
    'callback' => 'lux_api_delete_conversation_message',
    'permission_callback' => function($request) {
      return is_user_logged_in();
    },
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
  // $user_id = $request->get_param('user_id');
  $user_id = get_current_user_id();
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
        // Unread Messages Count
        $table_messages_read = $wpdb->prefix . 'lux_messages_read';
        $sql = "SELECT COUNT(*) FROM $table_messages_read WHERE conversation_id = $conversation_id AND user_id = $user_id AND read_at IS null";
        $messages_unread_count = $wpdb->get_var($sql);
        $conversations[] = array(
          'id' => (int)$conversation_id,
          'name' => $conversation_name,
          'image_url' => $image_url,
          'conversation_user_ids' => [
            $user_id, // sender
            $other_conversation_user_id // receiver
          ],
          'messages_unread_count' => (int)$messages_unread_count,
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

function lux_api_get_conversations_v3( WP_REST_Request $request ) {
  // Get Conversations by User ID
  $user_id = get_current_user_id();
  // Pagination support
  $limit = $request->get_param('limit') ? intval($request->get_param('limit')) : 20;
  $page = $request->get_param('paged') ?? $request->get_param('page') ?? 1;
  if ($page < 1) {
    return new WP_REST_Response(array(
      'error' => 'Invalid page parameter.',
      'message' => 'The page parameter must be a positive integer (page >= 1).',
    ), 400);
  }
  $offset = ($page - 1) * $limit;
  if (!$user_id) {
    return new WP_REST_Response(array(
      'error' => 'User ID is required to get conversations.',
      'message' => 'User ID is required to get conversations.',
    ), 500);
  }
  global $wpdb;
  $table_conv_users = $wpdb->prefix . 'lux_conversation_users';
  $table_conversations = $wpdb->prefix . 'lux_conversations';
  // Check for include_deleted param
  $include_deleted = isset($request['include_deleted']) ? true : false;
  $where = "cu.user_id = %d";
  if ( ! $include_deleted ) {
    $where .= " AND c.deleted_at IS NULL";
  }
  // Get total count first
  $count_sql = $wpdb->prepare("
    SELECT COUNT(DISTINCT c.id)
    FROM $table_conversations c
    INNER JOIN $table_conv_users cu ON cu.conversation_id = c.id
    WHERE $where
  ", $user_id);
  $total_items = (int)$wpdb->get_var($count_sql);
  $total_pages = ceil($total_items / $limit);
  // Get paginated results
  $sql = $wpdb->prepare("
    SELECT c.id AS conversation_id
    FROM $table_conversations c
    INNER JOIN $table_conv_users cu ON cu.conversation_id = c.id
    WHERE $where
    GROUP BY c.id
    ORDER BY c.updated_at DESC
    LIMIT %d OFFSET %d
  ", $user_id, $limit, $offset);
  $conversation_ids_data = $wpdb->get_results($sql);
  $conversation_ids = array_map(function($row) {
    return $row->conversation_id;
  }, $conversation_ids_data);
  $conversations = array();
  if (!empty($conversation_ids)) {
    foreach ($conversation_ids as $conversation_id) {
      // Fetch the conversation data
      $table_conversations = $wpdb->prefix . 'lux_conversations';
      $sql_conversation = $wpdb->prepare("SELECT * FROM $table_conversations WHERE id = %d", $conversation_id);
      $conversation_data = $wpdb->get_row($sql_conversation);
      if ($conversation_data) {
        // Get all other users in the conversation (excluding current user)
        $table_conv_users = $wpdb->prefix . 'lux_conversations_users';
        $sql_users = $wpdb->prepare("SELECT user_id FROM $table_conv_users WHERE conversation_id = %d AND user_id != %d", $conversation_id, $user_id);
        $other_conversation_users_data = $wpdb->get_results($sql_users);
        $other_user_ids = array_map(function($row) {
          return (int)$row->user_id;
        }, $other_conversation_users_data);
        // Get all participants including current user
        $conversation_user_ids = lux_get_conversation_participants($conversation_id);
        // Generate conversation name from all users
        $conversation_name = lux_get_conversation_name_from_users($conversation_data, $other_user_ids, $user_id);
        // Get conversation image (uses first user's avatar for now)
        $image_url = lux_get_conversation_image_from_users($other_user_ids);
        // Get all participant images for group conversations
        $participant_images = lux_get_conversation_participant_images($other_user_ids, 4);
        // Build other_conversation_users array with detailed user info
        $other_conversation_users = array();
        foreach ($other_user_ids as $other_user_id) {
          $other_user = get_userdata($other_user_id);
          if ($other_user) {
            $first_name = get_user_meta($other_user_id, 'first_name', true);
            $last_name = get_user_meta($other_user_id, 'last_name', true);
            $display_name = trim($first_name . ' ' . $last_name) ?: $other_user->display_name;
            $profile_avatar = get_field('profile_avatar', 'user_' . $other_user_id);
            $profile_pic_url = $profile_avatar 
              ? wp_get_attachment_image_url($profile_avatar, 'full') 
              : get_avatar_url($other_user_id, array('size' => '512'));
            $other_conversation_users[] = array(
              'user_id' => (int)$other_user_id,
              'name' => $display_name,
              'user_nicename' => $other_user->user_nicename,
              'image_url' => $profile_pic_url,
            );
          }
        }
        // Keep first user ID for backwards compatibility
        $other_conversation_user_id = !empty($other_user_ids) ? $other_user_ids[0] : null;
        // Unread Messages Count
        $table_messages_read = $wpdb->prefix . 'lux_messages_read';
        $sql = $wpdb->prepare("SELECT COUNT(*) FROM $table_messages_read WHERE conversation_id = %d AND user_id = %d AND read_at IS null", $conversation_id, $user_id);
        $messages_unread_count = $wpdb->get_var($sql);
        // Last message preview
        $last_msg = $wpdb->get_var(
          $wpdb->prepare(
            "SELECT message FROM {$wpdb->prefix}lux_messages WHERE conversation_id = %d ORDER BY created_at DESC LIMIT 1",
            $conversation_id
          )
        );
        $last_message_preview = $last_msg ? wp_trim_words( wp_strip_all_tags( $last_msg ), 12 ) : '';
        $conversations[] = array(
          'id' => (int)$conversation_id,
          'name' => $conversation_name,
          'other_conversation_user_id' => $other_conversation_user_id, // Backwards compatibility - first other user
          'other_conversation_user_ids' => $other_user_ids, // All other user IDs (backwards compatibility)
          'other_conversation_users' => $other_conversation_users, // Detailed user info array
          'conversation_user_ids' => $conversation_user_ids, // All participants including current user
          'participant_count' => count($conversation_user_ids),
          'image_url' => $image_url,
          'participant_images' => $participant_images, // Array of image URLs for group conversations
          'messages_unread_count' => (int)$messages_unread_count,
          'last_message_preview' => $last_message_preview,
          'updated_at' => $conversation_data->updated_at,
          'created_at' => $conversation_data->created_at,
          'deleted_at' => $conversation_data->deleted_at,
        );
      }
    }
  }
  $sort = 'desc';
  // Sort the $conversations array by the 'updated_at' field
  usort($conversations, function($a, $b) use ($sort) {
    $timestamp_a = strtotime($a['updated_at']);
    $timestamp_b = strtotime($b['updated_at']);
    return $sort == 'asc' ? $timestamp_a - $timestamp_b : $timestamp_b - $timestamp_a;
  });
  return new WP_REST_Response(array(
    'sort' => $sort,
    'conversations' => $conversations,
    'pagination' => array(
      'page' => $page, // backwards compatibility - deprecated, use current_page instead
      'limit' => $limit, // backwards compatibility - deprecated, use per_page instead
      'current_page' => (int)$page,
      'per_page' => (int)$limit,
      'total_items' => $total_items,
      'total_pages' => (int)$total_pages,
    ),
  ), 200);
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
      // 'sql' => $sql,
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
    // Create Message Read (Unread) records for every other user in the conversation other than current $user_id
    $table = $wpdb->prefix . 'lux_conversation_users';
    $sql = "SELECT user_id FROM $table WHERE conversation_id = $conversation_id AND user_id != $user_id";
    $conversation_users_data = $wpdb->get_results($sql);
    if ($conversation_users_data) {
      $table_messages_read = $wpdb->prefix . 'lux_messages_read';
      foreach ($conversation_users_data as $conversation_user_data) {
        $conversation_user_id = $conversation_user_data->user_id;
        $data = array(
          'conversation_id' => $conversation_id,
          'message_id' => $message_id,
          'user_id' => $conversation_user_id, // other user id
        );
        $success = $wpdb->insert($table_messages_read, $data);
      }
    }
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
  // Select Messages
  $table = $wpdb->prefix . 'lux_messages';
  $limit = 20;
  $page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
  $offset = $page ? ($page - 1) * $limit : false;
  $sql = "SELECT * FROM $table WHERE conversation_id = $conversation_id ORDER BY created_at DESC LIMIT $limit";
  if ($offset) {
    $sql .= " OFFSET $offset";
  }
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
        'deleted_at' => $message_data->deleted_at,
        'updated_at' => $message_data->updated_at,
        'created_at' => $message_data->created_at,
      );
    }
  }
  // Mark all Unread messages as read
  $user_id = get_current_user_id();
  $messages_marked_read = 0;
  if ($user_id) {
    $table = $wpdb->prefix . 'lux_messages_read';
    $messages_marked_read = $wpdb->update(
      $table,
      array(
        'read_at' => current_time('mysql'),
      ),
      array(
        'conversation_id' => intval($conversation_id),
        'user_id' => intval($user_id),
      ),
      array('%s'),
      array('%d', '%d')
    );
  }
  return new WP_REST_Response(array(
    'user_id' => $user_id, 
    'messages' => $messages,
    'messages_marked_read' => $messages_marked_read,
    '$sql' => $sql,
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
  $message = $request->get_param('message');
  $reactions = $request->get_param('reactions');
  // Update the message
  global $wpdb;
  $table_messages = $wpdb->prefix . 'lux_messages';
  $result = $wpdb->update(
    $table_messages,
    array(
      'message' => esc_sql($message),
      'reactions' => esc_sql($reactions),
    ),
    array(
      'id' => intval($message_id) // Where clause
    ),
    array('%s','%s'),
    array('%d')
  );
  // Return response message
  return new WP_REST_Response(array(
    // 'last_query' => $wpdb->last_query,
    'result' => $result,
    'message' => 'Updated message',
    'message_id' => $message_id
  ), 200);
}

function lux_api_delete_conversation_message( WP_REST_Request $request ) {
  $conversation_id = $request['conversation_id'];
  $message_id = $request['message_id'];
  global $wpdb;
  $table_messages = $wpdb->prefix . 'lux_messages';
  // Soft Delete the message
  $deleted = $wpdb->update(
    $table_messages,
    array(
      'deleted_at' => current_time('mysql'), // Column and value to update
    ),
    array(
      'id' => $message_id // Where clause
    ),
    array(
      '%s' // Format for updated_at (string)
    ),
    array(
      '%d' // Format for id (integer)
    )
  );
  // Hard Delete the message
  // $deleted = $wpdb->delete($table_messages, array(
  //   'message_id' => $message_id, 
  //   'conversation_id' => $conversation_id
  // ), array('%d', '%d'));
  return new WP_REST_Response(array(
    'message_id' => $message_id,
    'deleted' => $deleted,
  ), 200);
}
