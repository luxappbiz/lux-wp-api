<h1>Messages</h1>
<style>
.messages-table tbody tr {
  position: relative;
}

.messages-table tbody tr:hover .row-actions {
  opacity: 1;
}

.row-actions {
  position: absolute;
  right: 0;
  top: 50%;
  transform: translateY(-50%);
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 4px;
  padding: 4px 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  opacity: 0;
  transition: opacity 0.2s ease;
  z-index: 10;
}

.row-actions a {
  color: #0073aa;
  text-decoration: none;
  font-size: 12px;
  margin: 0 4px;
}

.row-actions a:hover {
  color: #005177;
}

.row-actions a.delete {
  color: #d63638;
}

.row-actions a.delete:hover {
  color: #b32d2e;
}
</style>
<form method="get" style="margin-bottom: 1em;">
  <input type="hidden" name="page" value="messages" />
  <input type="hidden" name="tab" value="messages" />
  <input type="text" name="s" value="<?= esc_attr($_GET['s'] ?? '') ?>" placeholder="Search messages..." />
  <button type="submit" class="button">Search</button>
</form>
<?php
// Handle delete message action
if (isset($_POST['action']) && $_POST['action'] === 'delete_message' && isset($_POST['delete_message_id'])) {
  // Verify nonce
  if (!wp_verify_nonce($_POST['delete_message_nonce'], 'delete_message_nonce')) {
    wp_die('Security check failed');
  }
  // Check user permissions
  if (!current_user_can('manage_options')) {
    wp_die('Insufficient permissions');
  }
  global $wpdb;
  $message_id = intval($_POST['delete_message_id']);
  if ($message_id > 0) {
    $table = $wpdb->prefix . 'lux_messages';
    $result = $wpdb->delete(
      $table,
      array('id' => $message_id),
      array('%d')
    );
    if ($result !== false) {
      echo '<div class="notice notice-success is-dismissible"><p>Message deleted successfully.</p></div>';
    } else {
      echo '<div class="notice notice-error is-dismissible"><p>Failed to delete message.</p></div>';
    }
  } else {
    echo '<div class="notice notice-error is-dismissible"><p>Invalid message ID.</p></div>';
  }
}
$table = $wpdb->prefix . 'lux_messages';
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$items_per_page = 100;
$offset = ($paged - 1) * $items_per_page;
$search_filter = isset($_GET['s']) ? trim($_GET['s']) : '';
$search_sql = '';
if ($search_filter !== '') {
  $search_sql = $wpdb->prepare("WHERE message LIKE %s", '%' . $wpdb->esc_like($search_filter) . '%');
}
$sql = "SELECT * FROM $table $search_sql ORDER BY created_at DESC LIMIT $items_per_page OFFSET $offset";
$messages = $wpdb->get_results($sql);
$total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table $search_sql");
$total_pages = ceil($total_items / $items_per_page);

// Show search results info
if ($search_filter !== '') {
  echo '<div class="notice notice-info"><p>Search results for: <strong>"' . esc_html($search_filter) . '"</strong> - Found ' . intval($total_items) . ' message(s)</p></div>';
}

if ($messages && !empty($messages)) { ?>
  <div class="wp-list-table-wrap">
    <table class="wp-list-table widefat fixed striped messages-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Conversation ID</th>
          <th>Sender</th>
          <th>Message</th>
          <th>Attachment ID</th>
          <th>Reactions</th>
          <th>Updated At</th>
          <th>Created At</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($messages as $key => $message) { ?>
          <tr>
            <td>
              <?= $message->id ?>
              <div class="row-actions">
                <a href="#" class="delete" onclick="deleteMessage(<?= $message->id ?>); return false;">Delete</a>
              </div>
            </td>
            <td>
              <a href="<?= $site_url ?>/wp-admin/admin.php?page=messages&conversation_id=<?= $message->conversation_id ?>">
                <?= $message->conversation_id ?>
              </a>
            </td>
            <?php
            $user = get_userdata($message->user_id);
            $first_name = get_user_meta($message->user_id, 'first_name', true);
            $last_name = get_user_meta($message->user_id, 'last_name', true);
            $display_name = $user ? $user->display_name : 'Unknown';
            $user_name = trim("$first_name $last_name") ?: $display_name;
            ?>
            <td>
              <a href="<?= $site_url . '/wp-admin/user-edit.php?user_id=' . $message->user_id ?>">
                <?= esc_html($user_name) ?>
              </a>
            </td>
            <td><?= $message->message ?></td>
            <td>
              <?= $message->attachment_id ? wp_get_attachment_link($message->attachment_id) : '-' ?>
            </td>
            <td><?= $message->reactions ?></td>
            <td><?= $message->updated_at ?></td>
            <td><?= $message->created_at ?></td>
          </tr>
        <?php } ?>
      </tbody>
    </table>
  </div>
  <?php
  echo paginate_links(array(
    'base' => add_query_arg('paged', '%#%'),
    'format' => '',
    'prev_text' => __('« Prev'),
    'next_text' => __('Next »'),
    'total' => $total_pages,
    'current' => $paged
  ));
} else {
  // No messages found
  if ($search_filter !== '') {
    echo '<div class="notice notice-warning"><p><strong>No messages found</strong> for search term: "' . esc_html($search_filter) . '"</p></div>';
    echo '<p>Try a different search term or <a href="' . esc_url(admin_url('admin.php?page=messages&tab=messages')) . '">view all messages</a>.</p>';
  } else {
    echo '<div class="notice notice-info"><p><strong>No messages found.</strong> There are currently no messages in the system.</p></div>';
  }
}
?>
<script>
function deleteMessage(messageId) {
  if (confirm("Are you sure?")) {
    // Create a form and submit it using jQuery
    var $form = $('<form>', {
      method: 'POST',
      action: window.location.href
    });
    // Add hidden inputs
    $form.append($('<input>', {
      type: 'hidden',
      name: 'delete_message_id',
      value: messageId
    }));
    $form.append($('<input>', {
      type: 'hidden',
      name: 'action',
      value: 'delete_message'
    }));
    $form.append($('<input>', {
      type: 'hidden',
      name: 'delete_message_nonce',
      value: '<?php echo wp_create_nonce('delete_message_nonce'); ?>'
    }));
    // Submit the form
    $('body').append($form);
    $form.submit();
  }
}
</script>