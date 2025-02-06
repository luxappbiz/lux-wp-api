<?php

add_action('admin_init', function () {
  $current_datetime = current_datetime()->format('Y-m-d H:i:s');
  global $wpdb;
  if (!get_option('lux_conversations_table_created_at')) {
    $table_name = $wpdb->prefix . 'lux_conversations';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
      $charset_collate = $wpdb->get_charset_collate();
      $sql = "CREATE TABLE $table_name (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        owner_user_id INT(11) UNSIGNED NOT NULL,
        name TEXT NOT NULL,
        deleted_at TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      ) $charset_collate;";
      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($sql);
      // $wpdb->query("ALTER TABLE $table_name ADD INDEX (sender_user_id, receiver_user_id);");
      add_option('lux_conversations_table_created_at', $current_datetime);
    }
  }
  if (!get_option('lux_conversation_users_table_created_at')) {
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
      add_option('lux_conversation_users_table_created_at', $current_datetime);
    }
  }
  if (!get_option('lux_messages_table_created_at')) {
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
        reactions TEXT DEFAULT NULL,
        deleted_at TIMESTAMP DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      ) $charset_collate;";
      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($sql);
      add_option('lux_messages_table_created_at', $current_datetime);
    }
  }
  if (!get_option('lux_messages_read_table_created_at')) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'lux_messages_read';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
      $charset_collate = $wpdb->get_charset_collate();
      $sql = "CREATE TABLE $table_name (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        conversation_id INT(11) UNSIGNED NOT NULL,
        message_id INT(11) UNSIGNED NOT NULL,
        user_id INT(11) UNSIGNED NOT NULL,
        read_at TIMESTAMP
      ) $charset_collate;";
      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($sql);
      add_option('lux_messages_read_table_created_at', $current_datetime);
    }
  }
});

add_action('admin_menu', function () {
  add_menu_page(
    'Conversations',
    'Conversations',
    'manage_options',
    'messages',
    'lux_admin_conversations_page_content',
    'dashicons-admin-comments',
    6 // Position in the menu
  );
});

function lux_admin_conversations_page_content() {
  $site_url = get_site_url();
  $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'conversations';
  ?>
  <style>
  li.conversation-owner {
    text-align: right;
  }
  </style>
  <div class="wrap lux-admin-conversations">
    <div class="nav-tab-wrapper">
      <a href="<?= $site_url ?>/wp-admin/admin.php?page=messages&tab=conversations" class="nav-tab <?= $active_tab === 'conversations' ? 'nav-tab-active' : '' ?>">Conversations</a>
      <a href="<?= $site_url ?>/wp-admin/admin.php?page=messages&tab=messages" class="nav-tab <?= $active_tab === 'messages' ? 'nav-tab-active' : '' ?>">Messages</a>
    </div>
    <?php
    global $wpdb;
    $conversation_id = isset($_GET['conversation_id']) ? $_GET['conversation_id'] : false;
    if ($conversation_id) {
      $table_conversations = $wpdb->prefix . 'lux_conversations';
      $sql = "SELECT * FROM $table_conversations WHERE id = $conversation_id";
      $conversation = $wpdb->get_results($sql);
      $conversation = $conversation ? $conversation[0] : false;
      $table_messages = $wpdb->prefix . 'lux_messages';
      $sql = "SELECT * FROM $table_messages WHERE conversation_id = $conversation_id ORDER BY created_at ASC";
      $messages = $wpdb->get_results($sql);
      ?>
      <div class="wrap">
        <h1>Conversation #<?= $conversation_id ?> <?= $conversation->name ?> Messages</h1>
        <?php if ($messages) { ?>
          <ul>
            <?php foreach ($messages as $message) {
              $is_owner = $message->user_id == $conversation->owner_user_id;
              $first_name = get_user_meta($message->user_id, 'first_name', true);
              $reactions = $message->reactions != '' ? json_decode(
                str_replace('\"', '"', $message->reactions), true
              ) : false;
              $reactions_str = '';
              if ($reactions) {
                foreach ($reactions as $reaction) {
                  $reactions_str .= $reactions_str != '' ? ', ' . $reaction['emoji'] : $reaction['emoji'];
                }
              }
              ?>
              <li class="<?= $is_owner ? 'conversation-owner' : '' ?>">
                <p>"<?= $message->message ?>" sent by <?= $first_name ?> on <?= $message->created_at ?><?= $reactions_str != '' ? ' with reactions ' . $reactions_str : '' ?></p>
              </li>
            <?php } ?>
          </ul>
        <?php } else { ?>
          <p>No messages.</p>
        <?php } ?>
      </div>
      <?php
    } else {
      if ($active_tab === 'conversations') { ?>
        <h1>Conversations</h1>
        <?php
        $table = $wpdb->prefix . 'lux_conversations';
        $sql = "SELECT * FROM $table ORDER BY updated_at DESC";
        $conversations = $wpdb->get_results($sql);
        if ($conversations && !empty($conversations)) { ?>
          <div class="wp-list-table-wrap">
            <table class="wp-list-table widefat fixed striped">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Owner User ID</th>
                  <th>Name</th>
                  <th>Updated At</th>
                  <th>Created At</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($conversations as $key => $conversation) {
                  $owner_name = get_user_meta($conversation->owner_user_id, 'first_name', true);
                  $owner_name .= ' ' . get_user_meta($conversation->owner_user_id, 'last_name', true);
                  ?>
                  <tr>
                    <td>
                      <a href="<?= $site_url ?>/wp-admin/admin.php?page=messages&conversation_id=<?= $conversation->id ?>">
                        <?= $conversation->id ?>
                      </a>
                    </td>
                    <td>
                      <a href="<?= $site_url . '/wp-admin/user-edit.php?user_id=' . $conversation->owner_user_id ?>">
                        <?= $$conversation->owner_user_id ?> <?= $owner_name ?>  
                      </a>
                    </td>
                    <td><?= $conversation->name ?></td>
                    <td><?= $conversation->updated_at ?></td>
                    <td><?= $conversation->created_at ?></td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        <?php } ?>
      <?php } else if ($active_tab === 'messages') { ?>
        <h1>Messages</h1>
        <?php
        $table = $wpdb->prefix . 'lux_messages';
        $sql = "SELECT * FROM $table ORDER BY created_at DESC";
        $messages = $wpdb->get_results($sql);
        if ($messages && !empty($messages)) { ?>
          <div class="wp-list-table-wrap">
            <table class="wp-list-table widefat fixed striped">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Conversation ID</th>
                  <th>User ID</th>
                  <th>Message</th>
                  <th>Attachment ID</th>
                  <th>Reactions</th>
                  <th>Deleted At</th>
                  <th>Updated At</th>
                  <th>Created At</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($messages as $key => $message) {
                  $user_name = get_user_meta($message->user_id, 'first_name', true);
                  $user_name .= ' ' . get_user_meta($message->user_id, 'last_name', true);
                  ?>
                  <tr>
                    <td><?= $message->id ?></td>
                    <td>
                      <a href="<?= $site_url ?>/wp-admin/admin.php?page=messages&conversation_id=<?= $message->conversation_id ?>">
                        <?= $message->conversation_id ?>
                      </a>
                    </td>
                    <td>
                      <a href="<?= $$site_url . '/wp-admin/user-edit.php?user_id=' . $message->user_id ?>">
                        <?= $message->user_id ?> <? $user_name ?>
                      </a>
                    </td>
                    <td><?= $message->message ?></td>
                    <td><?= $message->attachment_id ?></td>
                    <td><?= $message->reactions ?></td>
                    <td><?= $message->deleted_at ?></td>
                    <td><?= $message->updated_at ?></td>
                    <td><?= $message->created_at ?></td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        <?php }
      }
    }
    ?>
  </div>
<?php }
