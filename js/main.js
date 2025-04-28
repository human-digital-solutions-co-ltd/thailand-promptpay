/**
 * PromptPay QR Code Generator
 * Handles the generation and display of PromptPay QR codes
 */
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

        // Generate QR code using QRCode.js library
        new QRCode(qrCode[0], {
            text: qrData,
            width: 256,
            height: 256,
            colorDark: '#000000',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.H
        });

        // Add PromptPay logo if enabled
        if (showLogo) {
            const logo = $('<img/>', {
                src: '../image/promptpay.jpg',
                alt: 'PromptPay',
                class: 'promptpay-logo'
            });
            $card.prepend(logo);
        }

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

    /**
     * Generates PromptPay QR code data according to the EMVCo QR Code Specification
     * @param {string} id - PromptPay ID (phone number or e-wallet ID)
     * @param {number} amount - Payment amount
     * @returns {string} EMVCo QR Code data
     */
    function generatePromptPayData(id, amount) {
        // EMVCo Merchant Presented QR Code format
        let data = '000201'; // Payload Format Indicator
        data += '010211'; // Point of Initiation Method

        // Merchant Account Information
        if (id) {
            data += '29370016A000000677010111';
            data += ('0' + id.length).slice(-2);
            data += id;
        }
        
        // Transaction Amount
        if (amount > 0) {
            data += '54'; // Transaction Amount
            const amountStr = amount.toFixed(2);
            data += ('0' + amountStr.length).slice(-2);
            data += amountStr;
        }
        
        // Currency Code (THB)
        data += '5303764';
        
        // Country Code (TH)
        data += '5802TH';
        
        // Checksum (CRC16)
        data += '6304';
        data += calculateCRC16(data);
        
        return data;
    }

    /**
     * Calculates CRC16 checksum for QR code data
     * @param {string} data - QR code data
     * @returns {string} CRC16 checksum
     */
    function calculateCRC16(data) {
        // Placeholder for actual CRC16 implementation
        // In production, implement proper CRC16-CCITT calculation
        return '0000';
    }

    // Copy PromptPay ID to clipboard when clicked
    $('.thailand-promptpay-id').click(function() {
        var $temp = $("<input>");
        $("body").append($temp);
        $temp.val($(this).text()).select();
        document.execCommand("copy");
        $temp.remove();
        
        // Show copied message
        var $this = $(this);
        var originalText = $this.text();
        $this.text('Copied!');
        setTimeout(function() {
            $this.text(originalText);
        }, 1500);
    });

    // Refresh QR code if needed
    function refreshQRCode() {
        var $qrCode = $('.thailand-promptpay-qr img');
        if ($qrCode.length) {
            var currentSrc = $qrCode.attr('src');
            $qrCode.attr('src', currentSrc + '&t=' + new Date().getTime());
        }
    }

    // Add refresh button functionality
    $('.thailand-promptpay-refresh').click(function(e) {
        e.preventDefault();
        refreshQRCode();
    });
}); 