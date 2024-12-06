<?php

// Hook into the admin_menu action to add our page
add_action('admin_menu', function () {
  // Add a new top-level menu (if you want a submenu use add_submenu_page instead)
  add_menu_page(
    'Chats',    // Page title
    'Chats',    // Menu title
    'manage_options',      // Capability required
    'chats',      // Menu slug
    'lux_admin_chats_page_content', // The function to display the page content
    'dashicons-admin-comments', // Icon URL or Dashicon (you can change this to any dashicon)
    6 // Position in the menu
  );
});

function lux_admin_chats_page_content() {
  $site_url = get_site_url();
  ?>
  <style>
  .lux-admin-chats ul {
    list-style: initial;
    margin-left: 1rem;
  }
  </style>
  <div class="wrap lux-admin-chats">
    <h1>Chats</h1>
    <div class="wp-list-table-wrap">
      <table class="wp-list-table widefat fixed striped">
        <thead>
          <tr>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
  <?php
}
