const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
const { createElement } = window.wp.element;
const { __ } = window.wp.i18n;

const settings = window.wc.wcSettings.getSetting('thailand_promptpay_data', {});

const Label = (props) => {
    const { PaymentMethodLabel } = props.components;
    
    return createElement(PaymentMethodLabel, {
        text: settings.title || __('PromptPay', 'thailand-promptpay'),
        icon: settings.icon
    });
};

const Content = (props) => {
    return createElement(
        'div',
        {
            className: 'thailand-promptpay-payment-method-content'
        },
        settings.description || __('Pay using Thailand PromptPay QR code.', 'thailand-promptpay')
    );
};

const ThailandPromptPayPaymentMethod = {
    name: 'thailand_promptpay',
    label: createElement(Label),
    content: createElement(Content),
    edit: createElement(Content),
    canMakePayment: () => true,
    ariaLabel: settings.title || __('PromptPay', 'thailand-promptpay'),
    supports: {
        features: settings.supports || []
    }
};

registerPaymentMethod(ThailandPromptPayPaymentMethod); 