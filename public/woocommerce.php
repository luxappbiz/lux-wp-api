<?php
add_theme_support('woocommerce');
add_filter('woocommerce_account_menu_items', function ($items) {
  unset($items['downloads']); // Remove downloads item
  $items['dashboard'] = __('Account', 'lux');
  return $items;
}, 99, 1);

// Handle membership purchase
add_action('woocommerce_order_status_completed', 'lux_handle_membership_purchase');
add_action('woocommerce_order_status_processing', 'lux_handle_membership_purchase');
add_action('woocommerce_payment_complete', 'lux_handle_membership_purchase');

function lux_handle_membership_purchase($order_id)
{
  $order = wc_get_order($order_id);
  $user_id = $order->get_user_id();

  // Check if this order contains membership product
  foreach ($order->get_items() as $item) {
    $product_id = $item->get_product_id();

    // Check if this is the membership product (ID: 482)
    if ($product_id == 482) {
      // Log successful purchase
      update_user_meta($user_id, 'lux_last_membership_purchase', current_time('mysql'));

      // Store order ID for reference
      update_user_meta($user_id, 'lux_membership_order_id', $order_id);

      break;
    }
  }
}

// Handle redirect after purchase - Priority 1 to run early
add_action('woocommerce_thankyou', 'lux_handle_post_purchase_redirect', 1, 1);

function lux_handle_post_purchase_redirect($order_id)
{
  if (!$order_id) {
    return;
  }

  // Check if this came from the app - check multiple sources
  $is_from_app = false;

  // Check WooCommerce session
  if (WC()->session && WC()->session->get('lux_app_return')) {
    $is_from_app = true;
    WC()->session->set('lux_app_return', false);
  }

  // Check PHP session
  if (!$is_from_app) {
    if (!session_id()) {
      session_start();
    }
    if (isset($_SESSION['lux_app_return']) && $_SESSION['lux_app_return']) {
      $is_from_app = true;
      unset($_SESSION['lux_app_return']);
    }
  }

  // Check user meta as fallback
  if (!$is_from_app) {
    $user_id = get_current_user_id();
    if ($user_id && get_user_meta($user_id, 'lux_app_checkout', true)) {
      $is_from_app = true;
      delete_user_meta($user_id, 'lux_app_checkout');
    }
  }

  if (!$is_from_app) {
    return;
  }

  $order = wc_get_order($order_id);
  if (!$order) {
    return;
  }

  // Check if order contains membership
  $has_membership = false;
  foreach ($order->get_items() as $item) {
    if ($item->get_product_id() == 482) {
      $has_membership = true;
      break;
    }
  }

  if ($has_membership) {
    // Output redirect script
    ?>
    <!DOCTYPE html>
    <html>

    <head>
      <meta charset="UTF-8">
      <title>Redirecting to LUX App...</title>
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <style>
        body {
          font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
          display: flex;
          justify-content: center;
          align-items: center;
          min-height: 100vh;
          margin: 0;
          background-color: #f5f5f5;
        }

        .container {
          text-align: center;
          padding: 40px;
          background: white;
          border-radius: 10px;
          box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
          max-width: 400px;
        }

        h1 {
          color: #4CAF50;
        }

        .loader {
          border: 3px solid #f3f3f3;
          border-top: 3px solid #4CAF50;
          border-radius: 50%;
          width: 40px;
          height: 40px;
          animation: spin 1s linear infinite;
          margin: 20px auto;
        }

        @keyframes spin {
          0% {
            transform: rotate(0deg);
          }

          100% {
            transform: rotate(360deg);
          }
        }

        .manual-link {
          display: inline-block;
          margin-top: 20px;
          padding: 12px 24px;
          background-color: #4CAF50;
          color: white;
          text-decoration: none;
          border-radius: 5px;
          font-weight: bold;
        }

        .manual-link:hover {
          background-color: #45a049;
        }
      </style>
    </head>

    <body>
      <div class="container">
        <h1>✓ Purchase Successful!</h1>
        <p>Redirecting you back to the LUX app...</p>
        <div class="loader"></div>
        <div id="manual-return" style="display: none;">
          <p>If the app doesn't open automatically:</p>
          <a href="luxapp://membership-success?order_id=<?php echo $order_id; ?>&status=success" class="manual-link">
            Return to LUX App
          </a>
        </div>
      </div>

      <script type="text/javascript">
        console.log('Attempting to redirect to LUX app...');

        // Try multiple redirect methods
        var redirectUrl = 'luxapp://membership-success?order_id=<?php echo $order_id; ?>&status=success';

        // Method 1: Direct location change
        window.location.href = redirectUrl;

        // Method 2: Location replace after small delay
        setTimeout(function () {
          window.location.replace(redirectUrl);
        }, 100);

        // Method 3: Create and click hidden link
        setTimeout(function () {
          var link = document.createElement('a');
          link.href = redirectUrl;
          link.style.display = 'none';
          document.body.appendChild(link);
          link.click();
        }, 500);

        // Show manual link after 3 seconds
        setTimeout(function () {
          document.getElementById('manual-return').style.display = 'block';
        }, 3000);

        // Also try to redirect using meta tag
        var meta = document.createElement('meta');
        meta.httpEquiv = 'refresh';
        meta.content = '0; url=' + redirectUrl;
        document.head.appendChild(meta);
      </script>
    </body>

    </html>
    <?php
    exit; // Stop WordPress from outputting anything else
  }
}

// Add fallback redirect handler
add_action('woocommerce_before_thankyou', 'lux_add_app_redirect_script', 5);

function lux_add_app_redirect_script($order_id)
{
  // Check if we need to redirect
  $should_redirect = false;

  if (WC()->session && WC()->session->get('lux_app_return')) {
    $should_redirect = true;
  }

  if (!$should_redirect) {
    $user_id = get_current_user_id();
    if ($user_id && get_user_meta($user_id, 'lux_app_checkout', true)) {
      $should_redirect = true;
    }
  }

  if ($should_redirect) {
    // Add script to footer
    add_action('wp_footer', function () use ($order_id) {
      ?>
      <script>
        // Backup redirect in footer
        (function () {
          var redirectUrl = 'luxapp://membership-success?order_id=<?php echo $order_id; ?>&status=success';

          // Check if we're on thank you page
          if (window.location.href.indexOf('order-received') > -1) {
            console.log('On thank you page, attempting redirect...');

            // Try redirect after page loads
            window.addEventListener('load', function () {
              setTimeout(function () {
                window.location.href = redirectUrl;
              }, 1000);
            });
          }
        })();
      </script>
      <?php
    });
  }
}

// Also store flag in user meta when checkout session is created
add_action('woocommerce_checkout_process', 'lux_mark_app_checkout');

function lux_mark_app_checkout()
{
  if (WC()->session && WC()->session->get('lux_app_return')) {
    $user_id = get_current_user_id();
    if ($user_id) {
      update_user_meta($user_id, 'lux_app_checkout', true);
    }
  }
}