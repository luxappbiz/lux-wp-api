<h1>Conversations</h1>
<form method="get" style="margin: 8px 0;">
  <input type="hidden" name="page" value="messages" />
  <input type="hidden" name="tab" value="conversations" />
  <label for="filter_user_id" style="margin-right:6px;">Filter by User ID:</label>
  <input type="number" name="filter_user_id" id="filter_user_id" value="<?php echo isset($_GET['filter_user_id']) ? intval($_GET['filter_user_id']) : ''; ?>" style="width:140px;" />
  <button class="button">Apply</button>
  <?php if (isset($_GET['filter_user_id'])) { ?>
    <a href="<?php echo esc_url( admin_url('admin.php?page=messages&tab=conversations') ); ?>" class="button button-secondary" style="margin-left:6px;">Clear</a>
  <?php } ?>
</form>
<?php
$table = $wpdb->prefix . 'lux_conversations';
$cu_table = $wpdb->prefix . 'lux_conversation_users';
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$items_per_page = 50;
$offset = ($paged - 1) * $items_per_page;
$filter_user_id = isset($_GET['filter_user_id']) ? intval($_GET['filter_user_id']) : 0;
// Build query parts
$select = "SELECT SQL_CALC_FOUND_ROWS c.*";
$from   = " FROM $table c";
$join   = "";
$where  = " WHERE 1=1";
$order  = " ORDER BY c.updated_at DESC, c.created_at DESC";
$limit  = $wpdb->prepare(" LIMIT %d OFFSET %d", $items_per_page, $offset);
if ($filter_user_id > 0) {
  $join  .= " INNER JOIN $cu_table cu ON cu.conversation_id = c.id";
  $where .= $wpdb->prepare(" AND cu.user_id = %d", $filter_user_id);
}
$sql = $select . $from . $join . $where . $order . $limit;
// Debug: Show the SQL query when filtering
/*
if ($filter_user_id > 0) {
  echo '<div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ccc;">';
  echo '<strong>Debug Information:</strong><br>';
  echo '<strong>Filter User ID:</strong> ' . esc_html($filter_user_id) . '<br>';
  echo '<strong>SQL Query:</strong><br>';
  echo '<code>' . esc_html($sql) . '</code><br>';
  // Check if user exists
  $user = get_userdata($filter_user_id);
  if ($user) {
    echo '<strong>User Found:</strong> ' . esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')<br>';
  } else {
    echo '<strong>User Not Found:</strong> User ID ' . esc_html($filter_user_id) . ' does not exist<br>';
  }
  // Check how many conversations this user is in
  $user_conversation_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $cu_table WHERE user_id = %d",
    $filter_user_id
  ));
  echo '<strong>User Conversation Count:</strong> ' . intval($user_conversation_count) . '<br>';
  echo '</div>';
}
*/
$conversations = $wpdb->get_results($sql);
// Total items using FOUND_ROWS() for exact count under filter
$total_items = (int) $wpdb->get_var("SELECT FOUND_ROWS()");
$total_pages = ceil($total_items / $items_per_page);
if ($conversations && !empty($conversations)) { ?>
  <div class="wp-list-table-wrap">
    <table class="wp-list-table widefat fixed striped">
      <thead>
        <tr>
          <th>ID</th>
          <th>Participants</th>
          <th>Name</th>
          <th>Last Message</th>
          <th>Unread Count</th>
          <th>Updated At</th>
          <th>Created At</th>
          <th>Deleted At</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($conversations as $key => $conversation) { ?>
          <tr>
            <td>
              <a href="<?= $site_url ?>/wp-admin/admin.php?page=messages&conversation_id=<?= $conversation->id ?>">
                <?= $conversation->id ?>           
              </a>
            </td>
            <td>
              <?php
                $user_rows = $wpdb->get_results(
                  $wpdb->prepare(
                    "SELECT user_id FROM {$wpdb->prefix}lux_conversation_users WHERE conversation_id = %d",
                    $conversation->id
                  )
                );
                $user_links = array_map(function($row) {
                  $user = get_userdata($row->user_id);
                  $first = get_user_meta($row->user_id, 'first_name', true);
                  $last = get_user_meta($row->user_id, 'last_name', true);
                  $name = trim("$first $last") ?: ($user->display_name ?? 'User ' . $row->user_id);
                  $url  = admin_url('user-edit.php?user_id=' . intval($row->user_id));
                  return '<a href="' . esc_url($url) . '">' . esc_html($name) . '</a>';
                }, $user_rows);
                echo implode(', ', $user_links);
              ?>
            </td>
            <td><?= $conversation->name ?></td>
            <td>
              <?php
                $last_msg = $wpdb->get_var(
                  $wpdb->prepare(
                    "SELECT message FROM {$wpdb->prefix}lux_messages WHERE conversation_id = %d ORDER BY created_at DESC LIMIT 1",
                    $conversation->id
                  )
                );
                echo $last_msg ? esc_html(wp_trim_words($last_msg, 12)) : '—';
              ?>
            </td>
            <td>
              <?php
                $unread_count = $wpdb->get_var(
                  $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}lux_messages_read WHERE conversation_id = %d AND read_at IS NULL",
                    $conversation->id
                  )
                );
                echo intval($unread_count);
              ?>
            </td>
            <td><?= $conversation->updated_at ?></td>
            <td><?= $conversation->created_at ?></td>
            <td><?= $conversation->deleted_at ?? '—' ?></td>
            <td>
              <?php if (!$conversation->deleted_at) { ?>
                <button 
                  type="button" 
                  class="button button-link-delete" 
                  onclick="if(confirm('Are you sure you want to delete this conversation? This action cannot be undone.')) { deleteConversation(<?= $conversation->id ?>); }"
                  style="color: #d63638;"
                >
                  Delete Conversation
                </button>
              <?php } else { ?>
                <span style="color: #666;">Already deleted</span>
              <?php } ?>
            </td>
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
  ?>
<?php } ?>
<script>
function deleteConversation(conversationId) {
  if (!conversationId) {
    alert('Invalid conversation ID');
    return;
  }
  // Show loading state
  const button = event.target;
  const originalText = button.textContent;
  button.textContent = 'Deleting...';
  button.disabled = true;
  // Make AJAX request
  fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: new URLSearchParams({
      action: 'delete_conversation',
      conversation_id: conversationId,
      nonce: '<?php echo wp_create_nonce('delete_conversation_nonce'); ?>'
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Remove the row from the table
      const row = button.closest('tr');
      if (row) {
        row.style.opacity = '0.5';
        row.style.textDecoration = 'line-through';
        button.textContent = 'Deleted';
        button.style.color = '#666';
        // Optionally reload the page after a short delay
        setTimeout(() => {
          location.reload();
        }, 1000);
      }
    } else {
      alert('Error deleting conversation: ' + (data.data || 'Unknown error'));
      button.textContent = originalText;
      button.disabled = false;
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Error deleting conversation. Please try again.');
    button.textContent = originalText;
    button.disabled = false;
  });
}
</script>
