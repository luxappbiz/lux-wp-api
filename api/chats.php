<?php

add_action( 'rest_api_init', function () {
  add_action( 'rest_pre_serve_request', function () {
    header('Access-Control-Allow-Methods: GET, POST');
    header('Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Wpml-Language');
    header("Access-Control-Allow-Origin: *");
  });
  register_rest_route('lux/v1', '/chats', array(
    'methods' => 'GET',
    'callback' => 'lux_api_get_chat_messages',
    'permission_callback' => '__return_true',
  ));

  register_rest_route('lux/v1', '/chats', array(
    'methods' => 'POST',
    'callback' => 'lux_api_add_chat_message',
    'permission_callback' => '__return_true',
  ));

  // register_rest_route('lux/v1', '/chats', array(
  //   'methods' => 'DELETE',
  //   'callback' => 'lux_api_delete_chat_message',
  //   'permission_callback' => '__return_true',
  // ));
});

function lux_api_get_chat_messages( WP_REST_Request $request ) {
  header('Access-Control-Allow-Origin: *');
  header('Access-Control-Allow-Methods: GET');
  header("Access-Control-Allow-Headers: X-Requested-With");
  // $data = $request->get_body();
  // $data = json_decode($data, true);
  // if (!isset($data['receiver_user_id']) || $data['receiver_user_id'] == null) {
  //   wp_send_json([
  //     'success' => false,
  //     'error' => 'Receiver user id missing.',
  //   ]);
  // }
  // $sender_user_id = isset($data['sender_user_id']) ? (int)$data['sender_user_id'] : get_current_user_id();
  // $sender_user_id = get_current_user_id(); // not working
  if (!isset($_GET['sender_user_id']) || !isset($_GET['receiver_user_id'])) {
    wp_send_json([
      'success' => false,
      'error' => 'Sender & receiver user id missing.',
    ]);
  }
  global $wpdb;
  $table = $wpdb->prefix . 'lux_chats';
  $sender_user_id = (int)$_GET['sender_user_id']; // dont use
  // $receiver_user_id = (int)$data['receiver_user_id']; // live
  $receiver_user_id = (int)$_GET['receiver_user_id']; // dont use
  $sql = "SELECT * FROM $table WHERE sender_user_id = $sender_user_id AND receiver_user_id = $receiver_user_id";
  $chat_messages = $wpdb->get_results($sql);
  if ($chat_messages) {
    if (!empty($chat_messages)) {
      foreach($chat_messages as $key => $chat_message) {
        $user_id = (int)$chat_message->sender_user_id;
        $chat_message->{'first_name'} = get_user_meta($user_id, 'first_name', true);
        $chat_message->{'last_name'} = get_user_meta($user_id, 'first_name', true);
      }
    }
    wp_send_json([
      'success' => true,
      'messages' => $chat_messages
    ]);    
  } else {
    wp_send_json([
      'success' => false,
      'messages' => [],
      'error' => 'Something went wrong.'
    ]);
  }
}

function lux_api_add_chat_message( WP_REST_Request $request ) {
  $data = $request->get_body();
  $data = json_decode($data, true);
  if (!isset($data['receiver_user_id']) || $data['receiver_user_id'] == null) {
    wp_send_json([
      'success' => false,
      'error' => 'Receiver user id missing.',
    ]);
  }
  // $user_id = isset($data['sender_user_id']) ? (int)$data['sender_user_id'] : get_current_user_id();
  $sender_user_id = get_current_user_id();
  global $wpdb;
  $table_name = $wpdb->prefix . 'lux_chats';
  $data = array(
    'sender_user_id' => $sender_user_id,
    'receiver_user_id' => $data['receiver_user_id'],
    'message' => $data['message'],
    // 'reactions' => $data['reactions'], // Assuming this is already in JSON format or NULL
    'reactions' => null,
    'updated_at' => current_time('mysql', 1),
    'created_at' => current_time('mysql', 1)
  );
  // The format for each value in the data array
  $format = array('%d', '%d', '%s', '%s', '%s', '%s');
  $wpdb->insert($table_name, $data, $format);
  // Check for errors
  if ($wpdb->insert_id) {
    wp_send_json([
      'success' => true,
      'message_id' => $wpdb->insert_id
    ]);
  } else {
    error_log("Error inserting chat message: " . $wpdb->last_error);
    wp_send_json([
      'success' => false,
      'message_id' => null,
      'error' => "Error inserting chat message: " . $wpdb->last_error
    ]);
  }
}

// To do: Edit Chat Message
function lux_api_edit_chat_message( WP_REST_Request $request ) {
}
