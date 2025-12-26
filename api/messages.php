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
  register_rest_route('lux/v2', '/conversations', array(
    'methods' => 'GET',
    'callback' => 'lux_api_get_conversations_v2',
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
   * Register the REST API route for creating a new conversation.
   */
  register_rest_route( 'lux/v1', '/conversations', array(
    'methods' => 'POST',
    'callback' => 'lux_api_create_conversation',
    'permission_callback' => function($request) {
      return is_user_logged_in();
    },
  ) );
  /**
   * Register the REST API route for DELETING a conversation. (Soft Delete)
   */
  register_rest_route( 'lux/v1', '/conversations/(?P<conversation_id>\d+)', array(
    'methods' => 'DELETE',
    'callback' => 'lux_api_delete_conversation',
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
    'media_file' => array(
      'required' => false,
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

/**
 * Helper function to generate conversation name from multiple users
 */
function lux_get_conversation_name_from_users($conversation_data, $other_user_ids, $current_user_id) {
  // If conversation has a custom name, use it
  if (!empty($conversation_data->name)) {
    return $conversation_data->name;
  }
  // Build name from user names
  $user_names = array();
  foreach ($other_user_ids as $user_id) {
    $first_name = get_user_meta($user_id, 'first_name', true);
    $last_name = get_user_meta($user_id, 'last_name', true);
    $display_name = trim($first_name . ' ' . $last_name);
    if (empty($display_name)) {
      $user = get_userdata($user_id);
      $display_name = $user ? $user->display_name : 'User ' . $user_id;
    }
    $user_names[] = $display_name;
  }
  // Format based on number of users
  if (count($user_names) == 1) {
    return $user_names[0];
  } else if (count($user_names) == 2) {
    return $user_names[0] . ' and ' . $user_names[1];
  } else {
    $last_name = array_pop($user_names);
    return implode(', ', $user_names) . ', and ' . $last_name;
  }
}

/**
 * Helper function to get conversation image URL from multiple users
 * Returns first user's avatar for now, can be extended to group avatars later
 */
function lux_get_conversation_image_from_users($other_user_ids) {
  if (empty($other_user_ids)) {
    return '';
  }
  // Use first user's avatar for now
  $first_user_id = $other_user_ids[0];
  $profile_avatar = get_field('profile_avatar', 'user_' . $first_user_id);
  if ($profile_avatar) {
    return wp_get_attachment_image_url($profile_avatar, 'full');
  } else {
    return get_avatar_url($first_user_id, array('size' => '512'));
  }
}

/**
 * Helper function to get all participant images for group conversations
 * Returns array of image URLs for all other users (excluding current user)
 */
function lux_get_conversation_participant_images($other_user_ids, $max_images = 4) {
  if (empty($other_user_ids)) {
    return array();
  }
  $images = array();
  $user_ids_to_show = array_slice($other_user_ids, 0, $max_images);
  foreach ($user_ids_to_show as $user_id) {
    $profile_avatar = get_field('profile_avatar', 'user_' . $user_id);
    if ($profile_avatar) {
      $images[] = wp_get_attachment_image_url($profile_avatar, 'full');
    } else {
      $images[] = get_avatar_url($user_id, array('size' => '512'));
    }
  }
  return $images;
}

/**
 * Helper function to get all participants in a conversation
 */
function lux_get_conversation_participants($conversation_id) {
  global $wpdb;
  $table = $wpdb->prefix . 'lux_conversation_users';
  $participants = array();
  $sql = $wpdb->prepare("SELECT user_id FROM $table WHERE conversation_id = %d", $conversation_id);
  $user_ids_data = $wpdb->get_results($sql);
  foreach ($user_ids_data as $row) {
    $participants[] = (int)$row->user_id;
  }
  return $participants;
}

function lux_api_get_conversations( WP_REST_Request $request ) {
  // Get Conversations by User ID
  // $user_id = $request->get_param('user_id');
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
  $table = $wpdb->prefix . 'lux_conversation_users';
  $sql = $wpdb->prepare("SELECT * FROM $table WHERE user_id = %d ORDER BY created_at ASC LIMIT %d OFFSET %d", $user_id, $limit, $offset);
  $conversation_users_data = $wpdb->get_results($sql);
  $conversations = array();
  if ($conversation_users_data && !empty($conversation_users_data)) {
    foreach ($conversation_users_data as $conversation_user_data) {
      $conversation_id = $conversation_user_data->conversation_id;
      $user_id = $conversation_user_data->user_id;
      $table = $wpdb->prefix . 'lux_conversations';
      $sql = $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $conversation_id);
      // Get Conversation Data
      $conversation_data = $wpdb->get_row($sql);
      if ($conversation_data) {
        // Get all other users in the conversation (excluding current user)
        $table = $wpdb->prefix . 'lux_conversation_users';
        $sql = $wpdb->prepare("SELECT user_id FROM $table WHERE conversation_id = %d AND user_id != %d", $conversation_id, $user_id);
        $other_conversation_users_data = $wpdb->get_results($sql);
        $other_user_ids = array_map(function($row) {
          return (int)$row->user_id;
        }, $other_conversation_users_data);
        // Get all participants including current user
        $conversation_user_ids = lux_get_conversation_participants($conversation_id);
        // Generate conversation name from all users
        $conversation_name = lux_get_conversation_name_from_users($conversation_data, $other_user_ids, $user_id);
        // Get conversation image (uses first user's avatar for now)
        $image_url = lux_get_conversation_image_from_users($other_user_ids);
        // Keep first user ID for backwards compatibility
        $other_conversation_user_id = !empty($other_user_ids) ? $other_user_ids[0] : null;
        // Unread Messages Count
        $table_messages_read = $wpdb->prefix . 'lux_messages_read';
        $sql = $wpdb->prepare("SELECT COUNT(*) FROM $table_messages_read WHERE conversation_id = %d AND user_id = %d AND read_at IS null", $conversation_id, $user_id);
        $messages_unread_count = $wpdb->get_var($sql);
        $conversations[] = array(
          'id' => (int)$conversation_id,
          'name' => $conversation_name,
          'other_conversation_user_id' => $other_conversation_user_id, // Backwards compatibility - first other user
          'other_conversation_user_ids' => $other_user_ids, // All other users
          'conversation_user_ids' => $conversation_user_ids, // All participants including current user
          'participant_count' => count($conversation_user_ids),
          'image_url' => $image_url,
          'messages_unread_count' => (int)$messages_unread_count,
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
    return $sort == 'asc' ? $timestamp_a - $timestamp_b : $timestamp_b - $timestamp_a; // Ascending order, change to $timestampB - $timestampA for descending
  });
  return new WP_REST_Response(array(
    'sort' => $sort,
    'conversations' => $conversations,
    'pagination' => array(
      'page' => $page,
      'limit' => $limit,
      // 'total' => count($conversations), // To do: update to return total number of conversations available, query the total count separately before applying LIMIT/OFFSET.
    ),
  ), 200);
}

function lux_api_get_conversations_v2( WP_REST_Request $request ) {
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
        $table_conv_users = $wpdb->prefix . 'lux_conversation_users';
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
  $existing_conversation = null;
  if ($conversation_user_ids) {
    $table = $wpdb->prefix . 'lux_conversation_users';
    // Prepare the placeholders for SQL IN clause
    $placeholders = implode(', ', array_fill(0, count($conversation_user_ids), '%d'));
    $sql = $wpdb->prepare("SELECT conversation_id FROM $table WHERE user_id IN ($placeholders) GROUP BY conversation_id HAVING COUNT(DISTINCT user_id) = %d LIMIT 1", array_merge($conversation_user_ids, [count($conversation_user_ids)]));
    $existing_conversation_id = $wpdb->get_var($sql);
    if ($existing_conversation_id) {
      // Fetch the conversation row to check deleted_at
      $table_conv = $wpdb->prefix . 'lux_conversations';
      $existing_conversation = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_conv WHERE id = %d", $existing_conversation_id));
    }
  }
  // If conversation exists, restore if soft deleted
  if (!empty($existing_conversation)) {
    if (!empty($existing_conversation->deleted_at)) {
      $wpdb->update(
        "{$wpdb->prefix}lux_conversations",
        [ 'deleted_at' => null ],
        [ 'id' => $existing_conversation->id ],
        [ '%s' ],
        [ '%d' ]
      );
      // Refresh the object so it no longer has deleted_at set
      $existing_conversation->deleted_at = null;
    }
    // Get full conversation data for existing conversation
    $current_user_id = get_current_user_id();
    $other_user_ids = array_filter($conversation_user_ids, function($id) use ($current_user_id) {
      return $id != $current_user_id;
    });
    $other_user_ids = array_values($other_user_ids);
    $conversation_name = lux_get_conversation_name_from_users($existing_conversation, $other_user_ids, $current_user_id);
    $image_url = lux_get_conversation_image_from_users($other_user_ids);
    $participant_images = lux_get_conversation_participant_images($other_user_ids, 4);
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
    return new WP_REST_Response(array(
      'conversation_id' => $existing_conversation->id,
      'message' => 'Conversation already exists.',
      'conversation' => array(
        'id' => (int)$existing_conversation->id,
        'name' => $conversation_name,
        'other_conversation_user_id' => !empty($other_user_ids) ? $other_user_ids[0] : null,
        'other_conversation_user_ids' => $other_user_ids,
        'other_conversation_users' => $other_conversation_users,
        'participant_count' => count($conversation_user_ids),
        'image_url' => $image_url,
        'participant_images' => $participant_images,
      )
    ), 200);
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
      foreach ($conversation_user_ids as $conv_user_id) {
        $table = $wpdb->prefix . 'lux_conversation_users';
        $data = array(
          'conversation_id' => $conversation_id,
          'user_id' => (int)$conv_user_id,
        );
        $success = $wpdb->insert($table, $data);
      }
    }
    // Get full conversation data for newly created conversation
    $current_user_id = get_current_user_id();
    $other_user_ids = array_filter($conversation_user_ids, function($id) use ($current_user_id) {
      return $id != $current_user_id;
    });
    $other_user_ids = array_values($other_user_ids);
    $conversation_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}lux_conversations WHERE id = %d", $conversation_id));
    $conversation_name = lux_get_conversation_name_from_users($conversation_data, $other_user_ids, $current_user_id);
    $image_url = lux_get_conversation_image_from_users($other_user_ids);
    $participant_images = lux_get_conversation_participant_images($other_user_ids, 4);
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
    return new WP_REST_Response(array(
      'conversation_id' => $conversation_id,
      'message' => 'Conversation created success.',
      'conversation' => array(
        'id' => (int)$conversation_id,
        'name' => $conversation_name,
        'other_conversation_user_id' => !empty($other_user_ids) ? $other_user_ids[0] : null,
        'other_conversation_user_ids' => $other_user_ids,
        'other_conversation_users' => $other_conversation_users,
        'participant_count' => count($conversation_user_ids),
        'image_url' => $image_url,
        'participant_images' => $participant_images,
      )
    ), 201);
  } else {
    return new WP_REST_Response(array(
      'error' => $wpdb->last_error,
      'message' => 'Conversation creaetion failed.', 
    ), 500);
  }
}

function lux_api_delete_conversation( WP_REST_Request $request ) {
    global $wpdb;
    $conversation_id = intval( $request['conversation_id'] );
    if ( ! $conversation_id ) {
        return new WP_REST_Response(
            array(
                'success' => false,
                'message' => 'Invalid conversation_id',
            ),
            400
        );
    }
    $table = "{$wpdb->prefix}lux_conversations";
    $conversation = $wpdb->get_row(
        $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $conversation_id )
    );
    if ( ! $conversation ) {
        return new WP_REST_Response(
            array(
                'success' => false,
                'message' => 'Conversation not found',
            ),
            404
        );
    }
    $updated = $wpdb->update(
        $table,
        array( 'deleted_at' => current_time( 'mysql', 1 ) ), // UTC timestamp
        array( 'id' => $conversation_id ),
        array( '%s' ),
        array( '%d' )
    );
    if ( false === $updated ) {
        return new WP_REST_Response(
            array(
                'success' => false,
                'message' => 'Failed to delete conversation',
            ),
            500
        );
    }
    return new WP_REST_Response(
        array(
            'success' => true,
            'message' => 'Conversation deleted successfully',
            'conversation_id' => $conversation_id,
        ),
        200
    );
}

function lux_api_create_conversation_message( WP_REST_Request $request ) {
  $conversation_id = $request['conversation_id'];
  $user_id = $request->get_param('user_id');
  $message = $request->get_param('message');
  global $wpdb;
  // Check if the conversation exists
  $table_conversations = $wpdb->prefix . 'lux_conversations';
  $conversation_exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_conversations WHERE id = %d", $conversation_id));
  if (!$conversation_exists) {
    return new WP_REST_Response(array(
      'error' => $wpdb->last_error,
      'message' => 'Conversation not found.'
    ), 404);
  }
  $attachment_id = null;
  if (!empty($_FILES['media_file'])) {
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    // Handle the upload and attach to a dummy post ID 0 (or actual if available)
    $attachment_id = media_handle_upload('media_file', 0);
    if (is_wp_error($attachment_id)) {
      return new WP_REST_Response(array(
        'error' => $attachment_id->get_error_message(),
        'message' => 'Media upload failed.'
      ), 500);
    }
  }
  $table_messages = $wpdb->prefix . 'lux_messages';
  $data = array(
    'conversation_id' => $conversation_id,
    'user_id' => $user_id,
    'message' => $message,
    'attachment_id' => $attachment_id ?? 0,
  );
  $success = $wpdb->insert($table_messages, $data);
  if ($success) {
    send_notifications_to_conversation_users($conversation_id, $user_id, 'New Message', $message);
    $message_id = $wpdb->insert_id;
    // Update Conversation updated_at
    $result = $wpdb->update(
      $table_conversations, // Table name
      array(
        'updated_at' => current_time('mysql'), // Columns to update
      ),
      array(
        'id' => $conversation_id // WHERE clause
      ),
      array(
        '%s' // Format for updated_at (string / datetime)
      ),
      array(
        '%d' // Format for id (integer)
      )
    );
    // Create Message Read (Unread) records for every other user in the conversation other than current $user_id
    $table = $wpdb->prefix . 'lux_conversation_users';
    $sql = $wpdb->prepare("SELECT user_id FROM $table WHERE conversation_id = %d AND user_id != %d", $conversation_id, $user_id);
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
      'message' => 'Conversation message created success.',
      'attachment_id' => $attachment_id,
      'image_url' => $attachment_id ? wp_get_attachment_url($attachment_id) : null,
    ), 201);
  } else {
    return new WP_REST_Response(array(
      'error' => $wpdb->last_error,
      'message' => 'Conversation message creation failed.', 
    ), 500);
  }
}

function lux_api_get_conversation_messages( WP_REST_Request $request ) {
  $conversation_id = $request['conversation_id'];
  global $wpdb;
  // Check if the conversation exists
  $table = $wpdb->prefix . 'lux_conversations';
  $conversation_exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE id = %d", $conversation_id));
  if (!$conversation_exists) {
    return new WP_REST_Response(array(
      'error' => $wpdb->last_error,
      'message' => 'Conversation not found.'
    ), 404);
  }
  // Select Messages
  $table = $wpdb->prefix . 'lux_messages';
  $limit = 20;
  $page = isset($_GET['page']) ? absint($_GET['page']) : 1;
  $offset = $page ? ($page - 1) * $limit : 0;
  $sql = $wpdb->prepare("SELECT * FROM $table WHERE conversation_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d", $conversation_id, $limit, $offset);
  $messages_data = $wpdb->get_results($sql);
  $messages = array();
  if (!empty($messages_data)) {
    foreach ($messages_data as $message_data) {
      $image_url = $message_data->attachment_id ? wp_get_attachment_url($message_data->attachment_id) : null;
      $messages[] = array(
        'id' => $message_data->id,
        'conversation_id' => $message_data->conversation_id,
        'user_id' => $message_data->user_id,
        'message' => $message_data->message,
        'attachment_id' => $message_data->attachment_id,
        'image_url' => $image_url,
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
  ), 200);
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

function send_notifications_to_conversation_users($conversation_id, $current_user_id, $heading, $content) {
  $heading = 'New message from ' . get_user_meta($current_user_id, 'first_name', true) .  ' ' . get_user_meta($current_user_id, 'last_name', true);
  global $wpdb;
  $table = $wpdb->prefix . 'lux_conversation_users';
  $sql = $wpdb->prepare("SELECT user_id FROM $table WHERE conversation_id = %d AND user_id != %d", $conversation_id, $current_user_id);
  $conversation_users_data = $wpdb->get_results($sql);
  $open_signal_ids = [];
  foreach ($conversation_users_data as $conversation_user_data) {
    $conversation_user_id = $conversation_user_data->user_id;
    if ($onesignal_id = get_user_meta($conversation_user_id, 'onesignal_id', true)) {
      $open_signal_ids[] = $onesignal_id;
    }
  }
  $conversation_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}lux_conversations WHERE id = %d", $conversation_id));
  if (function_exists('lux_send_new_iap_notification')) {
    lux_send_new_iap_notification($open_signal_ids, $content, $heading, [
        'screen'=>'ConversationScreen',
        'conversation_id'=>$conversation_id,
        'conversation'=>$conversation_data
      ]);
  }
}
