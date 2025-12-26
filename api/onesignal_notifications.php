<?php
function send_new_message_notification($receiver_opensignal_ids, $content, $heading,)
{
  $onesignal_push_notification_api_key = get_field('onesignal_push_notification_api_key', 'option');
  $onesignal_push_notification_app_id = get_field('onesignal_push_notification_app_id', 'option');
  if (!$onesignal_push_notification_api_key || !$onesignal_push_notification_app_id) {
    return false;
  }
  if (!$receiver_opensignal_ids || !count($receiver_opensignal_ids)) {
    return false;
  }
  $body = wp_json_encode([
    "include_aliases" => [
      "onesignal_id" => $receiver_opensignal_ids
    ],
    'target_channel' => 'push',
    'app_id' => $onesignal_push_notification_app_id,
    "headings" => [
      "en" => $heading,
    ],
    'contents' => [
      'en' => $content,
    ],
  ]);
  $response = wp_remote_post('https://api.onesignal.com/notifications', [
    'headers' => [
      'Content-Type'  => 'application/json',
      'Authorization' => 'Key ' . $onesignal_push_notification_api_key,
    ],
    'body' => $body,
    'method'  => 'POST',
  ]);
  return $response;
}


function lux_send_iap_notification_to_user($user_id, $heading, $content, $data = []) {
  $onesignal_id = get_user_meta($user_id, 'onesignal_id', true);
  lux_send_new_iap_notification([$onesignal_id], $heading, $content, $data);
}

function lux_send_new_iap_notification($receiver_opensignal_ids, $heading, $content, $data = [])
{
  $onesignal_push_notification_api_key = get_field('onesignal_push_notification_api_key', 'option');
  $onesignal_push_notification_app_id = get_field('onesignal_push_notification_app_id', 'option');
  if (!$onesignal_push_notification_api_key || !$onesignal_push_notification_app_id) {
    return false;
  }
  if (!$receiver_opensignal_ids || !count($receiver_opensignal_ids)) {
    return false;
  }
  // $receiver_onesignal_id = get_user_meta($receiver_user_id, 'onesignal_id', true);
  // if (!$receiver_onesignal_id) {
  //   return false;
  // }
  $body = wp_json_encode([
    "include_aliases" => [
      "onesignal_id" => $receiver_opensignal_ids
    ],
    'target_channel' => 'push',
    'app_id' => $onesignal_push_notification_app_id,
    "headings" => [
      "en" => $heading,
    ],
    'contents' => [
      'en' => $content,
    ],
    "data" => $data
  ]);
  $response = wp_remote_post('https://api.onesignal.com/notifications', [
    'headers' => [
      'Content-Type'  => 'application/json',
      'Authorization' => 'Key ' . $onesignal_push_notification_api_key,
    ],
    'body' => $body,
    'method'  => 'POST',
  ]);

  // Optional: handle response
  if (is_wp_error($response)) {
    error_log('OneSignal error: ' . $response->get_error_message());
  } else {
    $body = wp_remote_retrieve_body($response);
    error_log('OneSignal response: ' . $body);
  }
}
