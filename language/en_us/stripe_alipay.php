<?php

$lang['StripeAlipay.name'] = 'Alipay via Stripe';
$lang['StripeAlipay.description'] = 'One of largest third-party mobile and online payment platforms in China. Process Alipay payments through Stripe.';

// Errors
$lang['StripeAlipay.!error.auth'] = 'Authentication failed at the gateway.';
$lang['StripeAlipay.!error.secret_key.empty'] = 'You must enter a Secret Key.';
$lang['StripeAlipay.!error.webhook_secret.empty'] = 'You must enter a WebHook Secret.';
$lang['StripeAlipay.!error.secret_key.valid'] = 'Connection to the Stripe API with the provided Secret Key was unsuccessful.';
$lang['StripeAlipay.!error.currency.length'] = 'The currency must be 3 characters in length.';

$lang['StripeAlipay.!error.invalid_request_error'] = 'An error occurred while processing your request through the payment gateway.';

// Payment Status
$lang['StripeAlipay.!error.payment_canceled'] = 'The gateway reports that this transaction was canceled. Please proceed to checkout again. If you are already completed the payment, please wait for system validation.  If your payment has not been validated within 10 minutes, please contact support.';
$lang['StripeAlipay.!error.payment_error_with_message'] = 'The gateway reports an error with the message: ';
$lang['StripeAlipay.!error.payment_unkown_error'] = 'The gateway has reported an unknown error. If you have already completed the payment, please wait for system validation. If your payment has not been validated within 10 minutes, please contact support.';
$lang['StripeAlipay.!error.payment_intent_id_missing'] = 'Return URL missing session ID. If you have already completed the payment, please wait for system validation. If your payment has not been validated within 10 minutes, please contact support.';
$lang['StripeAlipay.!error.metadata_missing'] = 'Payment gateway error, please open a support ticket and tell us it missing  metadata for this transaction.';

// Settings
$lang['StripeAlipay.secret_key'] = 'API Secret Key';
$lang['StripeAlipay.test_key_detected'] = 'Test Key Deteced! Change to Live key once you are ready.';
$lang['StripeAlipay.tooltip_secret_key'] = 'API Secret Key can generate from your Stripe account, DO NOT TELL ANYONE ELSE';

$lang['StripeAlipay.webhook_secret'] = 'Webhook Secret';
$lang['StripeAlipay.tooltip_webhook_secret'] = 'When set, Gateway will try to verify the webhook request using the given secret.';

$lang['StripeAlipay.currency'] = 'Currency (Just for test)';
$lang['StripeAlipay.tooltip_currency'] = 'The currency to use for transactions. (ISO 4217)';
$lang['StripeAlipay.tooltip_fee_currency'] = 'Note: The currency must be supported by your Stripe account.';

$lang['StripeAlipay.fee_fix'] = 'Fixed fee';
$lang['StripeAlipay.tooltip_fee_fix'] = 'The amount to charge on each transaction.';
$lang['StripeAlipay.fee_percent'] = 'Percent fee (NOT WORK DO NOT SET IT) ';
$lang['StripeAlipay.tooltip_fee_percent'] = 'The percentage to charge on each transaction. (NOT WORK DO NOT SET IT)';
$lang['StripeAlipay.fee'] = 'Setting about transaction Fee, for example if stripe charge 2.9% + 30 cents per transaction, you should set 0.30 for fee_fix and 2.9 for fee_percent.';

$lang['StripeAlipay.fee_choice'] = 'Fee Calculation';
$lang['StripeAlipay.payment_fee'] = 'On Payment';
$lang['StripeAlipay.refund_fee'] = 'On Refund';
$lang['StripeAlipay.no_fee'] = 'No Fee';

$lang['StripeAlipay.test_on_save'] = 'Test when you save settings, it will try to make a test payment to verify your settings.';

$lang['StripeAlipay.webhook'] = 'Stripe Webhook';

$lang['StripeAlipay.webhook_note'] = 'Use this link as the webhook for `payment_intent.succeeded`, `payment_intent.canceled`, and `payment_intent.succeeded` events in your Stripe account, and fill in the webhook_secret.';
