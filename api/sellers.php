<?php

add_action('rest_api_init', function () {
  register_rest_route('lux/v1', '/sellers', array(
    'methods' => 'GET',
    'callback' => 'lux_api_get_sellers',
    // 'permission_callback' => '__return_true',
    'permission_callback' => function($request) {
      return is_user_logged_in();
    },
  ));
  // register_rest_route('lux/v1', '/sellers/(?P<seller_id>\d+)', array(
  //   'methods' => 'GET',
  //   'callback' => 'lux_api_get_seller',
  //   // 'permission_callback' => '__return_true',
  //   'permission_callback' => function($request) {
  //     return is_user_logged_in();
  //   },
  // ));
  /*
   * GET Seller Blocked
   */
  register_rest_route('lux/v1', '/sellers/(?P<seller_id>\d+)/blocked', array(
    'methods' => 'GET',
    'callback' => 'lux_api_get_seller_blocked',
    // 'permission_callback' => '__return_true',
    'permission_callback' => function($request) {
      return is_user_logged_in();
    },
    'args' => array(
      'seller_id' => array(
        'validate_callback' => function($param, $request, $key) {
          return is_numeric($param);
        }
      )
    ),
  ));
  /*
   * PUT/UPDATE Seller Blocked
   */
  register_rest_route('lux/v1', '/sellers/(?P<seller_id>\d+)/blocked', array(
    'methods' => 'PUT',
    'callback' => 'lux_api_update_seller_blocked',
    // 'permission_callback' => '__return_true',
    'permission_callback' => function($request) {
      return is_user_logged_in();
    },
    'args' => array(
      'seller_id' => array(
        'validate_callback' => function($param, $request, $key) {
          return is_numeric($param);
        }
      )
    ),
  ));
});

function lux_api_get_sellers( WP_REST_Request $request ) {
  // $page_id = 66;
  // $include = array();
  // $exclude_members = get_field('exclude_members', $page_id) ?? array();
  $exclude = (isset($_GET['exclude'])) ? explode(',', $_GET['exclude']) : [];
  // if (!empty($exclude_members)) {
  //   foreach ($exclude_members as $user_id) {
  //     $exclude[] = $user_id;
  //   }
  // }
  $search = (isset($_GET['search'])) ? $_GET['search'] : '';
  $orderby = $orderby_filter = (isset($_GET['orderby'])) ? $_GET['orderby'] : 'registered';
  $order = (isset($_GET['order'])) ? strtoupper($_GET['order']) : 'DESC';
  if ($orderby === 'registered' || $orderby === 'email_verified' || $orderby === 'display_name_desc') {
    $order = 'DESC';
  } else if ($orderby === 'display_name_asc') {
    $order = 'ASC';
  }
  $per_page = 12;
  // $per_page = ($per_page !== 12) ? intval($per_page) : $per_page;
  $paged = get_query_var('paged');
  $offset = (!empty($paged)) ? ($paged - 1) * $per_page : 0;
  if ($orderby === 'display_name_asc' || $orderby === 'display_name_desc') {
    $orderby = 'display_name';
  }
  $args = array(
    'role'         => '',
    // 'include'      => $include,
    'exclude'      => $exclude,
    'orderby'      => $orderby === 'email_verified' ? 'registered' : $orderby,
    'order'        => $order,
    'offset'       => $offset,
    'search'       => '*' . $search . '*',
    'number'       => $per_page,
    'count_total'  => true,
    // 'meta_query' => [
    //   [
    //     'key' => 'approved_at',
    //     'value' => false,
    //     'compare' => '!='
    //   ],
    // ],
  );
  if ($orderby === 'email_verified') {
    $args['meta_query'] = [
      [
        'key' => 'email_verified',
        'value' => true,
        'compare' => '='
      ],
    ];
  }
  $users = get_users($args);
  $users_count = count($users);
  $members = array();
  foreach ($users as $user) {
    $image_url = '';
    $profile_avatar = get_field('profile_avatar', 'user_' . $user->ID);
    if ($profile_avatar) {
    //   $image_url = wp_get_attachment_image_url($profile_avatar, 'full');
      $image_url = $profile_avatar['url'];
    } else {
      $image_url = get_avatar_url($user->ID, array('size' => '512'));
    }
    $full_name = get_user_meta($user->ID, 'first_name', true) . ' ' . get_user_meta($user->ID, 'last_name', true);
    $members[] = array(
      'id' => $user->ID,
      'name' => $full_name && $full_name !== ' ' ? $full_name : $user->display_name,
      'email' => $user->user_email,
      // 'occupation' => get_field('occupation', 'user_' . $user->ID),
      'image_url' => $image_url,
    );
  }
  wp_send_json([
    'success' => true,
    'args' => $args,
    'sellers' => $members,
    'count' => $users_count,
  ]);
}

// Get Seller's Blocked Users
function lux_api_get_seller_blocked( WP_REST_Request $request ) {
  $seller_id = (int)$request['seller_id'];
  if (!$seller_id) {
    return new WP_REST_Response(array(
      'success' => false,
    ), 500);
  }
  $blocked = get_user_meta($seller_id, 'blocked', true); // comma separated string of user_ids
  return new WP_REST_Response(array(
    'success' => true,
    'blocked' => $blocked,
  ), 200);  
}

// Update Seller's Blocked Users
function lux_api_update_seller_blocked( WP_REST_Request $request ) {
  $seller_id = (int)$request['seller_id'];
  if (!$seller_id) {
    return new WP_REST_Response(array(
      'success' => false,
    ), 500);
  }
  $user_id_to_block = $request->get_param('user_id_to_block');
  $blocked_str = get_user_meta($seller_id, 'blocked', true);
  if ($blocked_str) {
    $blocked_ar = explode(',', $blocked_str);
    $blocked_ar[] = $user_id_to_block
    update_user_meta($seller_id, 'blocked', implode(',', $blocked_ar));
  } else {
    update_user_meta($seller_id, 'blocked', $user_id_to_block);
  }
  return new WP_REST_Response(array(
    'success' => true,
  ), 200);  
}
