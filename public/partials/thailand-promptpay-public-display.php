<?php
/**
 * Provide a public-facing view for the plugin
 */
?>

<div class="thailand-promptpay-container">
    <div class="thailand-promptpay-form">
        <h2><?php _e('PromptPay Payment', 'thailand-promptpay'); ?></h2>
        <div class="thailand-promptpay-qr">
            <img src="<?php echo plugin_dir_url(__FILE__) . '../../image/promptpay.jpg'; ?>" alt="PromptPay QR Code">
        </div>
        <div class="thailand-promptpay-details">
            <p><?php _e('Scan the QR code above to make a payment using PromptPay', 'thailand-promptpay'); ?></p>
            <p class="thailand-promptpay-amount"><?php _e('Amount:', 'thailand-promptpay'); ?> <span id="thailand-promptpay-amount-value">0.00</span> THB</p>
        </div>
    </div>
</div> 