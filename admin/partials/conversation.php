<?php
// Fetch all participants in this conversation
$participants_table = $wpdb->prefix . 'lux_conversation_users';
$participants_sql = $wpdb->prepare("SELECT user_id FROM $participants_table WHERE conversation_id = %d", $conversation_id);
$participants_data = $wpdb->get_results($participants_sql);
$participant_ids = array_map(function($row) {
  return (int)$row->user_id;
}, $participants_data);
$participant_count = count($participant_ids);
// Get conversation data
$conversations_table = $wpdb->prefix . 'lux_conversations';
$conversation_sql = $wpdb->prepare("SELECT * FROM $conversations_table WHERE id = %d", $conversation_id);
$conversation_data = $wpdb->get_row($conversation_sql);
?>
<style>
.participant-card {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  background: #f6f7f7;
  border: 1px solid #dcdcde;
  border-radius: 6px;
  text-decoration: none;
  color: #2271b1;
  transition: all 0.2s;
}
.participant-card:hover {
  background: #fff;
  border-color: #2271b1;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  transform: translateY(-1px);
}
.participant-card img {
  border-radius: 50%;
  border: 2px solid #fff;
}
.conversation-header {
  background: #fff;
  border: 1px solid #ccd0d4;
  box-shadow: 0 1px 1px rgba(0,0,0,.04);
  padding: 20px;
  margin: 20px 0;
  border-radius: 4px;
}
.message-card {
  margin-bottom: 12px;
  border-radius: 8px;
  padding: 12px;
  transition: box-shadow 0.2s;
}
.message-card:hover {
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
</style>
<p><a href="<?= $site_url ?>/wp-admin/admin.php?page=messages&tab=conversations" class="button">← Back to Conversations</a></p>
<div class="conversation-header">
  <h1 style="margin: 0 0 15px 0;">Conversation #<?= $conversation_id ?></h1>
  <div style="margin-bottom: 20px;">
    <h3 style="margin: 0 0 10px 0; color: #666;">
      👥 Participants (<?= $participant_count ?>)
      <?php if ($participant_count > 2) { ?>
        <span style="background: #2271b1; color: #fff; padding: 2px 8px; border-radius: 12px; font-size: 12px; font-weight: 600; margin-left: 8px;">Group Chat</span>
      <?php } ?>
    </h3>
    <div style="display: flex; flex-wrap: wrap; gap: 12px;">
      <?php foreach ($participant_ids as $user_id) {
        $user = get_userdata($user_id);
        if (!$user) continue;
        $first_name = get_user_meta($user_id, 'first_name', true);
        $last_name = get_user_meta($user_id, 'last_name', true);
        $display_name = trim("$first_name $last_name") ?: $user->display_name;
        $avatar_url = get_avatar_url($user_id, array('size' => 48));
        $profile_url = admin_url('user-edit.php?user_id=' . $user_id);
        $is_owner = ($conversation_data && $conversation_data->owner_user_id == $user_id);
        ?>
        <a href="<?= esc_url($profile_url) ?>" class="participant-card" title="View user profile">
          <img src="<?= esc_url($avatar_url) ?>" width="32" height="32">
          <div>
            <div style="font-weight: 600;">
              <?= esc_html($display_name) ?>
              <?php if ($is_owner) { ?>
                <span style="color: #d63638; font-size: 11px;">★ Owner</span>
              <?php } ?>
            </div>
            <div style="font-size: 12px; color: #646970;">@<?= esc_html($user->user_login) ?></div>
          </div>
        </a>
      <?php } ?>
    </div>
  </div>
  <?php if ($conversation_data) { ?>
    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #dcdcde; font-size: 13px; color: #646970;">
      <div><strong>Created:</strong> <?= esc_html($conversation_data->created_at) ?></div>
      <div><strong>Last Updated:</strong> <?= esc_html($conversation_data->updated_at) ?></div>
      <?php if ($conversation_data->deleted_at) { ?>
        <div style="color: #d63638;"><strong>Deleted:</strong> <?= esc_html($conversation_data->deleted_at) ?></div>
      <?php } ?>
    </div>
  <?php } ?>
</div>
<h2>Messages (<?= count($messages) ?>)</h2>
<?php if ($messages) { ?>
  <?php foreach ($messages as $message) {
    $user = get_userdata($message->user_id);
    $first_name = get_user_meta($message->user_id, 'first_name', true);
    $last_name = get_user_meta($message->user_id, 'last_name', true);
    $display_name = $user ? $user->display_name : '';
    $name = trim("$first_name $last_name") ?: $display_name;
    $avatar_url = get_avatar_url($message->user_id, array('size' => 40));
    $is_deleted = !empty($message->deleted_at);
    ?>
    <div class="message-card" style="background-color: <?= $is_deleted ? '#fee' : '#f9f9f9' ?>; border: 1px solid <?= $is_deleted ? '#fcc' : '#dcdcde' ?>; <?= $is_deleted ? 'opacity: 0.6;' : '' ?>">
      <div style="display: flex; align-items: flex-start; gap: 10px;">
        <img src="<?= esc_url($avatar_url) ?>" width="40" height="40" style="border-radius: 50%; border: 2px solid #fff;">
        <div style="flex: 1;">
          <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
            <strong style="color: #2271b1;"><?= esc_html($name) ?></strong>
            <span style="color: #646970; font-size: 12px;">@<?= esc_html($user ? $user->user_login : 'unknown') ?></span>
            <?php if ($is_deleted) { ?>
              <span style="background: #d63638; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: 600;">DELETED</span>
            <?php } ?>
          </div>
          <?php if ($message->message) { ?>
            <div style="margin-bottom: 8px; color: #1d2327; line-height: 1.5;">
              <?= nl2br(esc_html($message->message)) ?>
            </div>
          <?php } ?>
          <?php if ($message->attachment_id): ?>
            <div style="margin-bottom: 8px; padding: 8px; background: #fff; border: 1px solid #dcdcde; border-radius: 4px; display: inline-block;">
              <span style="margin-right: 6px;">📎</span>
              <?= wp_get_attachment_link($message->attachment_id) ?>
            </div>
          <?php endif; ?>
          <?php if ($message->reactions): ?>
            <div style="margin-bottom: 8px;">
              <?php
              try {
                $reactions = json_decode($message->reactions, true);
                if ($reactions && is_array($reactions)) {
                  echo '<div style="display: flex; gap: 4px; flex-wrap: wrap;">';
                  foreach ($reactions as $reaction) {
                    $reactor_name = get_user_meta($reaction['user_id'], 'first_name', true);
                    echo '<span style="background: #f0f0f1; padding: 4px 8px; border-radius: 12px; font-size: 14px;" title="' . esc_attr($reactor_name) . '">' . esc_html($reaction['emoji']) . '</span>';
                  }
                  echo '</div>';
                }
              } catch (Exception $e) {
                // Ignore JSON parse errors
              }
              ?>
            </div>
          <?php endif; ?>
          <div style="display: flex; gap: 12px; font-size: 12px; color: #646970;">
            <span>📅 <?= esc_html($message->created_at) ?></span>
            <?php if ($message->updated_at != $message->created_at) { ?>
              <span>✏️ Edited: <?= esc_html($message->updated_at) ?></span>
            <?php } ?>
            <?php if ($is_deleted) { ?>
              <span>🗑️ Deleted: <?= esc_html($message->deleted_at) ?></span>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>
  <?php } ?>
<?php } else { ?>
  <p>No messages.</p>
<?php } ?>