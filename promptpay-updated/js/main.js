jQuery(document).ready(function($) {
    // Initialize QR code for all promptpay cards
    $('.ppy-card').each(function() {
        const $card = $(this);
        const promptpayId = $card.data('promptpay-id');
        const amount = parseFloat($card.data('amount')) || 0;
        const showLogo = $card.data('show-promptpay-logo') === '1';
        const showId = $card.data('show-promptpay-id') === '1';
        const accountName = $card.data('account-name');
        const shopName = $card.data('shop-name');

        // Generate QR code data
        const qrData = generatePromptPayData(promptpayId, amount);
        
        // Create QR code element
        const qrCode = $('<div class="qrcode"></div>');
        $card.prepend(qrCode);

        // Generate QR code using QRCode.js or similar library
        // Note: You'll need to include a QR code library of your choice
        new QRCode(qrCode[0], {
            text: qrData,
            width: 256,
            height: 256
        });

        // Add account info if enabled
        if (showId || accountName || shopName) {
            const accountInfo = $('<div class="account-info"></div>');
            
            if (showId && promptpayId) {
                accountInfo.append($('<p></p>').text('PromptPay ID: ' + promptpayId));
            }
            
            if (accountName) {
                accountInfo.append($('<p></p>').text('Account: ' + accountName));
            }
            
            if (shopName) {
                accountInfo.append($('<p></p>').text('Shop: ' + shopName));
            }
            
            $card.append(accountInfo);
        }

        // Add amount if specified
        if (amount > 0) {
            $card.append($('<div class="amount"></div>').text('Amount: ฿' + amount.toFixed(2)));
        }
    });

    function generatePromptPayData(id, amount) {
        // Basic PromptPay QR code data format
        // This is a simplified version - you may need to adjust based on actual requirements
        let data = '000201010211';
        
        // Add merchant ID
        if (id) {
            data += '29370016A000000677010111';
            data += ('0' + id.length).slice(-2);
            data += id;
        }
        
        // Add amount if specified
        if (amount > 0) {
            data += '5406';
            data += amount.toFixed(2);
        }
        
        // Add currency (THB)
        data += '5303764';
        
        // Add country (TH)
        data += '5802TH';
        
        // Add checksum
        data += '6304';
        
        return data;
    }
}); 