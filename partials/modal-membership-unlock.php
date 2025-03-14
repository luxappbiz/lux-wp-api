
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
        <form id="lux-membership-form" method="GET" action="<?php echo esc_url(wc_get_checkout_url()); ?>">
          <?php
          $product_id = 482;
          $product = wc_get_product($product_id);
          $variations = $product->get_available_variations();
          $attributes = $product->get_variation_attributes();
          $default_attributes = $product->get_default_attributes();
          if (!empty($attributes)) {
            foreach ($attributes as $attribute_name => $options) {
              $attribute_key = 'attribute_' . sanitize_title($attribute_name);
              $selected = isset($default_attributes[sanitize_title($attribute_name)]) ? $default_attributes[sanitize_title($attribute_name)] : '';
              ?>
              <div class="form-group">
                <label for="membership_plan">Membership Plan</label>
                <select id="membership_plan" class="form-control" required>
                  <option value="">Select an option</option>
                  <?php foreach ($variations as $key => $variation) : 
                    $variation_id = $variation['variation_id'];
                    $period = isset($attributes['Membership Plan']) ? $attributes['Membership Plan'][$key] : 'Unknown';
                    ?>
                    <option value="<?php echo esc_attr($variation_id); ?>">
                      <?php echo esc_html($period); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            <?php }
          } ?>
          <input type="hidden" name="add-to-cart" value="<?php echo esc_attr($product_id); ?>" />
          <input type="hidden" name="variation_id" value="" class="lux-variation-id" />
          <button type="submit" class="btn btn-black btn-block">Proceed to Checkout</button>
        </form>
      </div>
      <!-- <div class="modal-footer">
        <a href="https://luxapp.biz/checkout/?add-to-cart=482" class="btn btn-black w-100">Buy Membership Now</a>
      </div> -->
    </div>
  </div>
</div>
<script type="text/javascript">
jQuery(document).ready(function($) {
  const $form = $('#lux-membership-form');
  const $select = $form.find('#membership_plan');
  const $submitBtn = $form.find('.lux-submit-btn');
  const $variationIdInput = $form.find('.lux-variation-id');
  $select.on('change', function() {
    const variationId = $(this).val();
    if (variationId) {
      $variationIdInput.val(variationId); // Update hidden variation_id input
      $submitBtn.prop('disabled', false); // Enable submit button
    } else {
      $variationIdInput.val(''); // Clear variation_id
      $submitBtn.prop('disabled', true); // Disable submit button
    }
  });
});
</script>
