
<div class="modal fade" id="membershipUnlockModal" tabindex="-1" aria-labelledby="membershipUnlockModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Membership</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p class="text-center m-0">You’re trying to access a members-only feature. Upgrade to a membership to unlock this and more!</p>

        <form id="lux-membership-form" method="post" action="<?php echo esc_url(wc_get_checkout_url()); ?>">
          <?php
          $product_id = 482;
          $product = wc_get_product($product_id);
          // Get available variations
          $variations = $product->get_available_variations();
          $attributes = $product->get_variation_attributes();
          $default_attributes = $product->get_default_attributes();
          // Output variation dropdown (assuming 'subscription_period' attribute)
          if (!empty($attributes)) {
            foreach ($attributes as $attribute_name => $options) {
              $attribute_key = 'attribute_' . sanitize_title($attribute_name);
              $selected = isset($default_attributes[sanitize_title($attribute_name)]) ? $default_attributes[sanitize_title($attribute_name)] : '';
              ?>
              <div class="form-group">
                <label for="<?php echo esc_attr($attribute_key); ?>">Choose Subscription Period</label>
                <select name="<?php echo esc_attr($attribute_key); ?>" id="<?php echo esc_attr($attribute_key); ?>" class="form-control" required>
                  <option value="">Select an option</option>
                  <?php foreach ($options as $option) : ?>
                    <option value="<?php echo esc_attr($option); ?>" <?php selected($selected, $option); ?>>
                      <?php echo esc_html(ucfirst($option)); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            <?php }
          } ?>
          <input type="hidden" name="add-to-cart" value="<?php echo esc_attr($product_id); ?>" />
          <button type="submit" class="btn btn-black btn-block">Proceed to Checkout</button>
        </form>

      </div>
      <!-- <div class="modal-footer">
        <a href="https://luxapp.biz/checkout/?add-to-cart=482" class="btn btn-black w-100">Buy Membership Now</a>
      </div> -->
    </div>
  </div>
</div>
<script>
jQuery(document).ready(function($) {
  $('#lux-membership-form').on('submit', function(e) {
    // Optional: Add custom logic before redirect
    console.log('Form submitted with variation:', $(this).serialize());
  });
});
</script>
