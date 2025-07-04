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
