<?php

/**
 * Stripe Alipay processing gateway. Redirect user to Stripe pre-build page
 *
 * The Stripe API can be found at: https://stripe.com/docs/api
 */
class StripeAlipay extends NonmerchantGateway
{
    /**
     * @var array An array of meta data for this gateway
     */
    private $meta;

    private $base_url = 'https://api.stripe.com/v1/';

    /**
     * Construct a new merchant gateway
     */
    public function __construct()
    {
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');
        Loader::loadComponents($this, ['Input']);
        Language::loadLang('stripe_alipay', null, dirname(__FILE__) . DS . 'language' . DS);
        Configure::load('stripe_alipay', dirname(__FILE__) . DS . 'config' . DS);
    }

    /**
     * {@inheritdoc}
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * {@inheritdoc}
     */
    public function getSettings(array $meta = null)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('settings', 'default');
        $this->view->setDefaultView('components' . DS . 'gateways' . DS . 'nonmerchant' . DS . 'stripe_alipay' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);
        $this->view->set('meta', $meta);

        return $this->view->fetch();
    }

    /**
     * {@inheritdoc}
     */
    public function editSettings(array $meta)
    {
        // Validate the given meta data to ensure it meets the requirements
        $rules = [
            'secret_key' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('StripeAlipay.!error.secret_key.empty', true),
                ],
                'valid' => [
                    'rule' => [[$this, 'validateConnection'], $meta['currency']],
                    'message' => Language::_('StripeAlipay.!error.currency.valid', true),
                ],
            ],
            'webhook_secret' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('StripeAlipay.!error.webhook_secret.empty', true),
                ],
            ],
            'currency' => [
                'valid' => [
                    'rule' => ['betweenLength', 3, 3],
                    'message' => Language::_('StripeAlipay.!error.currency.length', true),
                ],
            ],
        ];

        $this->Input->setRules($rules);

        if ($meta["test_on_save"] == true) {
            $this->Input->validates($meta);
        }

        return $meta;
    }

    /**
     * {@inheritdoc}
     */
    public function encryptableFields()
    {
        return ['secret_key', 'webhook_secret'];
    }

    /**
     * {@inheritdoc}
     */
    public function setMeta(array $meta = null)
    {
        $this->meta = $meta;
        // TODO: Find a way to ensure that the percentage fee does not cause a discrepancy between payment and receipt amounts.
        $this->meta['fee_percent'] = 0;
    }

    /**
     * Returns all HTML markup required to render an authorization and capture payment form
     *
     * @param array $contact_info An array of contact info including:
     *  - id The contact ID
     *  - client_id The ID of the client this contact belongs to
     *  - user_id The user ID this contact belongs to (if any)
     *  - contact_type The type of contact
     *  - contact_type_id The ID of the contact type
     *  - first_name The first name on the contact
     *  - last_name The last name on the contact
     *  - title The title of the contact
     *  - company The company name of the contact
     *  - address1 The address 1 line of the contact
     *  - address2 The address 2 line of the contact
     *  - city The city of the contact
     *  - state An array of state info including:
     *      - code The 2 or 3-character state code
     *      - name The local name of the country
     *  - country An array of country info including:
     *      - alpha2 The 2-character country code
     *      - alpha3 The 3-cahracter country code
     *      - name The english name of the country
     *      - alt_name The local name of the country
     *  - zip The zip/postal code of the contact
     * @param float $amount The amount to charge this contact
     * @param array $invoice_amounts An array of invoices, each containing:
     *  - id The ID of the invoice being processed
     *  - amount The amount being processed for this invoice (which is included in $amount)
     * @param array $options An array of options including:
     *  - description The Description of the charge
     *  - return_url The URL to redirect users to after a successful payment
     *  - recur An array of recurring info including:
     *      - amount The amount to recur
     *      - term The term to recur
     *      - period The recurring period (day, week, month, year, onetime) used in conjunction
     *          with term in order to determine the next recurring payment
     * @return string HTML markup required to render an authorization and capture payment form
     */
    public function buildProcess($contact_info, $amount, $invoice_amounts = null, $options = null)
    {

        if ($this->meta['fee_choice'] == 'payment_fee' && ($this->meta['fee_fix'] || $this->meta['fee_percent'])) {
            $amount = $this->calculateAmount($amount, $this->meta['fee_fix'], $this->meta['fee_percent'], true);
        }
        $amount = $this->formatAmount($amount, $this->currency, $direction = 'to');

        if (isset($invoice_amounts) && is_array($invoice_amounts)) {
            $invoices = $this->serializeInvoices($invoice_amounts);
        }

        $this->loadApi();

        $this->view = $this->makeView(
            'alipay',
            'default',
            str_replace(ROOTWEBDIR, '', dirname(__FILE__) . DS)
        );

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Find Client
        Loader::loadModels($this, ['Contacts']);
        $contact = $this->Contacts->get($contact_info['id']);

        try {
            $paymentIntentObj = [
                'payment_method_types' => ['alipay'],
                'amount' => $amount,
                'currency' => $this->currency,
                'receipt_email' => $contact->email,
                'description' => "Homura Network - Invoice Payment",
                'payment_method_data' => [
                    'type' => 'alipay',
                ],
                'return_url' => (isset($options['return_url']) ? $options['return_url'] : null),
                'confirm' => true,
                'metadata' => [
                    'client_id' => $contact_info['client_id'],
                    'invoices' => $invoices,
                ],
            ];

            $paymentIntent = $this->stripe->paymentIntents->create($paymentIntentObj);
        } catch (Exception $e) {
            $this->Input->setErrors(['gateway_error' => ['internal' => $e->getMessage()]]);
            return;
        }

        $this->view->set('payment_url', $paymentIntent['next_action']['alipay_handle_redirect']['url']);

        return $this->view->fetch();
    }

    /**
     * Validates the incoming POST/GET response from the gateway to ensure it is
     * legitimate and can be trusted.
     *
     * @param array $get The GET data for this request
     * @param array $post The POST data for this request
     * @return array An array of transaction data, sets any errors using Input if the data fails to validate
     *  - client_id The ID of the client that attempted the payment
     *  - amount The amount of the payment
     *  - currency The currency of the payment
     *  - invoices An array of invoices and the amount the payment should be applied to (if any) including:
     *      - id The ID of the invoice to apply to
     *      - amount The amount to apply to the invoice
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the gateway to identify this transaction
     *  - parent_transaction_id The ID returned by the gateway to identify this
     *      transaction's original transaction (in the case of refunds)
     */
    public function validate(array $get, array $post)
    {
        $this->loadApi();

        // Get event payload
        $payload = @file_get_contents('php://input');
        $decode_payload = json_decode($payload);
        $endpoint_secret = $this->meta['webhook_secret'];
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $event = null;

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sig_header,
                $endpoint_secret
            );
        } catch (\UnexpectedValueException $e) {
            $this->log($this->base_url, 'Stripe Alipay Gateway - invalid_payload :' . $e->getMessage());
            http_response_code(400);
            return [];
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            $this->log($this->base_url, 'Stripe Alipay Gateway  - invalid_signature :' . $e->getMessage());
            http_response_code(400);
            return [];
        } catch (Exception $e) {
            $this->log($this->base_url, "Stripe Alipay Gateway  - unknown :" . $e->getMessage());
            http_response_code(400);
            return [];
        }

        if ($decode_payload->data->object->object !== 'payment_intent') {
            return false;
        }

        $payment_intent_id = $decode_payload->data->object->id ?? $decode_payload->data->object->latest_charge ?? null;

        switch ($event->type) {
            case 'payment_intent.canceled':
            case 'payment_intent.payment_failed':
                return $this->handlePaymentIntent($payment_intent_id, 'declined');
                break;
            case 'payment_intent.succeeded':
                return $this->handlePaymentIntent($payment_intent_id, 'approved');
                break;
        };

        return [];
    }

    /**
     * Returns data regarding a success transaction. This method is invoked when
     * a client returns from the non-merchant gateway's web site back to Blesta.
     *
     * @param array $get The GET data for this request
     * @param array $post The POST data for this request
     * @return array An array of transaction data, may set errors using Input if the data appears invalid
     *  - client_id The ID of the client that attempted the payment
     *  - amount The amount of the payment
     *  - currency The currency of the payment
     *  - invoices An array of invoices and the amount the payment should be applied to (if any) including:
     *      - id The ID of the invoice to apply to
     *      - amount The amount to apply to the invoice
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - transaction_id The ID returned by the gateway to identify this transaction
     *  - parent_transaction_id The ID returned by the gateway to identify this transaction's original transaction
     */

    public function success(array $get, array $post)
    {

        $this->loadApi();

        if (array_key_exists('payment_intent', $get)) {

            $payment_intent_id = $get['payment_intent'];

            switch ($get['redirect_status']) {
                case 'succeeded':
                    return $this->handlePaymentIntent($payment_intent_id, 'approved');
                    break;
                case 'failed':
                    return $this->handlePaymentIntent($payment_intent_id, 'declined');
                    break;
                default:
                    $this->Input->setErrors([
                        "gateway_error" => [
                            'message' => Language::_('StripeAlipay.!error.payment_unkown_error', true),
                        ],
                    ]);
                    return [];
            }
        } else {
            $this->Input->setErrors([
                "gateway_error" => [
                    'message' => Language::_('StripeAlipay.!error.payment_intent_id_missing', true),
                ],
            ]);
        }
        return [];
    }

    /**
     * Captures a previously authorized payment.
     *
     * @param string $reference_id The reference ID for the previously authorized transaction
     * @param string $transaction_id The transaction ID for the previously authorized transaction.
     * @param $amount The amount.
     * @param array $invoice_amounts
     * @return array An array of transaction data including:
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function capture($reference_id, $transaction_id, $amount, array $invoice_amounts = null)
    {
        $this->Input->setErrors($this->getCommonError('unsupported'));
    }

    /**
     * Void a payment or authorization.
     *
     * @param string $reference_id The reference ID for the previously submitted transaction
     * @param string $transaction_id The transaction ID for the previously submitted transaction
     * @param string $notes Notes about the void that may be sent to the client by the gateway
     * @return array An array of transaction data including:
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function void($reference_id, $transaction_id, $notes = null)
    {
        $this->Input->setErrors($this->getCommonError('unsupported'));
    }

    /**
     * Refund a payment.
     *
     * @param string $reference_id The reference ID for the previously submitted transaction
     * @param string $transaction_id The transaction ID for the previously submitted transaction
     * @param float $amount The amount to refund this card
     * @param string $notes Notes about the refund that may be sent to the client by the gateway
     * @return array An array of transaction data including:
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function refund($reference_id, $transaction_id, $amount, $notes = null)
    {

        $this->loadApi();

        try {
            $payment_intent = $this->stripe->paymentIntents->retrieve($transaction_id, []);
            $payment_amount = $payment_intent['amount'];
            $payment_currency = $payment_intent['currency'];

            if ($payment_intent["status"] != "succeeded" & $payment_intent["amount_received"] === 0) {
                $this->Input->setErrors([
                    "gateway_error" => [
                        'message' => Language::_('StripeAlipay.!error.payment_unkown_error', true),
                    ],
                ]);
                return [];
            }

            if ($this->meta['fee_choice'] == 'refund_fee' && ($this->meta['fee_fix'] || $this->meta['fee_percent'])) {
                $amount = $this->calculateAmount($amount, $this->meta['fee_fix'], $this->meta['fee_percent'], false);
            }
            $amount = $this->formatAmount($amount, $this->currency);

            $refund_request = $this->stripe->refunds->create([
                'payment_intent' => $transaction_id,
                'amount' => $amount,
            ]);
        } catch (\Stripe\Exception\InvalidRequestException  | \Stripe\Exception\RateLimitException  | \Stripe\Exception\AuthenticationException  | \Stripe\Exception\ApiConnectionException  | \Stripe\Exception\ApiErrorException $e) {
            $this->log($this->base_url, "Stripe Alipay Gateway  - Refund Error: " . $e, 'output');
            $this->Input->setErrors([
                "gateway_error" => [
                    'message' => Language::_('StripeAlipay.!error.payment_error_with_message', true) . $e->getHttpStatus() . "| Message: " . $e->getError()->message,
                ],
            ]);
            return [];
        } catch (Exception $e) {
            $this->log($this->base_url, "Stripe Alipay Gateway  - Refund Error: " . $e, 'output');

            $this->Input->setErrors([
                "gateway_error" => [
                    'message' => Language::_('StripeAlipay.!error.payment_error_with_message', true) . $e->getMessage(),
                ],
            ]);
            return [];
        }

        if (isset($refund_request['error'])) {

            $this->log($this->base_url, "Stripe Alipay Gateway  - Refund Error: " . json_encode($refund_request), 'output');

            $this->Input->setErrors([
                "gateway_error" => [
                    'message' => Language::_('StripeAlipay.!error.payment_error_with_message' . $refund_request['message'], true),
                ],
            ]);
            return;
        }

        if ($refund_request['status'] != 'pending' && $refund_request['status'] != 'succeeded') {
            $this->log($this->base_url, "Stripe Alipay Gateway  - Refund Error: " . json_encode($refund_request), 'output');
            $this->Input->setErrors([
                "gateway_error" => [
                    'message' => Language::_('StripeAlipay.!error.payment_error_with_message' . $refund_request['failure_reason'], true),
                ],
            ]);
            return;
        }

        $refund_result = [
            'status' => 'refunded',
            'reference_id' => $refund_request['balance_transaction'],
            'transaction_id' => $refund_request['id'],
            'message' => null,
        ];

        $this->log($this->base_url, "Stripe Alipay Gateway  - Refund Result: " . json_encode($refund_result), 'output', ($refund_request['status'] == ('pending' | 'succeeded')));

        return $refund_result;
    }

    /**
     * Checks whether a key can be used to connect to the Stripe API
     *
     * @param string $secret_key The API to connect with
     * @return boolean True if a successful API call was made, false otherwise
     */
    public function validateConnection($secret_key, $currency)
    {
        $success = true;
        // Skip test if test key is given
        if (substr($secret_key, 0, 7) == '0sk_test') {
            return $success;
        }

        try {
            $stripetest = new \Stripe\StripeClient([
                'api_key' => $secret_key,
                'stripe_version' => '2024-06-20',
            ]);

            $stripetest->balance->retrieve([]);

            $paymentIntentObj = [
                'payment_method_types' => ['alipay'],
                'amount' => 1000,
                'currency' => $currency,
                'description' => "TEST",
                'payment_method_data' => [
                    'type' => 'alipay',
                ],
            ];

            $paymentIntent = $stripetest->paymentIntents->create($paymentIntentObj);
            $stripetest->paymentIntents->cancel($paymentIntent['id']);
        } catch (Exception $e) {
            $this->Input->setErrors([
                'gateway_error' => [
                    'message' => $e, // Test API call
                ],
            ]);
            $success = false;
        }

        return $success;
    }

    private function handlePaymentIntent($payment_intent_id, $status = "pending")
    {

        $this->loadApi();

        json_decode($payment_intent);
        $payment_intent = $this->stripe->paymentIntents->retrieve($payment_intent_id, []);
        $metadata = $payment_intent["metadata"];

        if ($metadata === (null | false) | !$metadata['client_id']) {
            $this->Input->setErrors([
                'gateway_error' => [
                    'message' => Language::_('StripeAlipay.!error.metadata_missing', true),
                ],
            ]);

            return false;
        }

        switch ($status) {
            case 'pending':
                if ($payment_intent["status"] === "succeeded") {
                    $status = "approved";
                } else {
                    $this->Input->setErrors([
                        "gateway_error" => [
                            'message' => Language::_('StripeAlipay.!error.payment_canceled', true),
                        ],
                    ]);
                    break;
                }
            case 'approved':
                if ($payment_intent["status"] === "succeeded" & $payment_intent["amount_received"] === $payment_intent["amount"]) {
                    $status = "approved";
                } else {
                    $status = "error";
                }
                break;
            case 'declined':
                if ($payment_intent["status"] != "succeeded" & $payment_intent["amount_received"] === 0) {
                    $status = "declined";
                } else {
                    $status = "error";
                }
                break;
            default:
                $status = "error";
                $this->Input->setErrors([
                    "gateway_error" => [
                        'message' => Language::_('StripeAlipay.!error.payment_unkown_error', true),
                    ],
                ]);
                break;
        }

        $amount = $payment_intent["amount"];

        $amount = $this->formatAmount($amount, strtoupper($payment_intent["currency"]), 'from');

        if ($this->meta['fee_choice'] == 'payment_fee' && ($this->meta['fee_fix'] || $this->meta['fee_percent'])) {
            $amount = $this->calculateAmount($amount, $this->meta['fee_fix'], $this->meta['fee_percent'], false);
        }

        $payment_result = [
            'client_id' => $metadata['client_id'],
            'amount' => $amount,
            'currency' => strtoupper($payment_intent["currency"]),
            'status' => $status,
            'reference_id' => $payment_intent["client_secret"],
            'transaction_id' => $payment_intent["id"],
            'invoices' => $this->unserializeInvoices($metadata['invoices']),
            'parent_transaction_id' => null,
        ];
        $this->log($this->base_url, "Stripe Alipay Gateway  - Result: " . json_encode($payment_result), 'output', ($status = "approved"));
        return $payment_result;
    }

    /*
     * Loads the API if not already loaded, can only be called after constructor has done its work
     */
    private function loadApi()
    {
        //Check if SDK is exists
        if (!class_exists('\Stripe\StripeClient', false)) {
            Loader::load(dirname(__FILE__) . DS . 'vendor' . DS . 'stripe' . DS . 'stripe-php' . DS . 'init.php');
        }

        //Load the API
        $this->stripe = new \Stripe\StripeClient([
            'api_key' => (isset($this->meta['secret_key']) ? $this->meta['secret_key'] : null),
            'app_info' => [
                'name' => 'Blesta ' . $this->getName(),
                'version' => $this->getVersion(),
                'url' => 'https://blesta.com',
            ],
            'stripe_version' => '2024-06-20',
        ]);
    }

    /**
     * Caculate the amount with progress fee
     */

    private function calculateAmount($amount, $fee_fix = 0, $fee_percent = 0, $addon = true)
    {

        if ($addon == true) {
            $fee = ($amount * $fee_percent / 100) + $fee_fix;
            $amount = $amount + $fee;
        } else {
            $fee = ($amount * $fee_percent / 100) + $fee_fix;
            $amount = $amount - $fee;
        }
        return $amount;
    }

    /**
     * Convert amount from decimal value to integer representation of cents
     *
     * @param float $amount
     * @param string $currency
     * @param string $direction
     * @return int The amount in cents
     */
    private function formatAmount($amount, $currency, $direction = 'to')
    {
        $non_decimal_currencies = [
            'BIF', 'CLP', 'DJF', 'GNF', 'JPY',
            'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'VUV', 'XAF', 'XOF', 'XPF',
        ];

        if (is_numeric($amount) && !in_array($currency, $non_decimal_currencies)) {
            if ($direction === 'to') {
                $amount *= 100;
            } else {
                $amount /= 100;
                return round($amount, 2);
            }
        }

        return (int) round($amount);
    }

    /**
     * Serializes an array of invoice info into a string.
     *
     * @param array A numerically indexed array invoices info including:
     *  - id The ID of the invoice
     *  - amount The amount relating to the invoice
     * @return string A serialized string of invoice info in the format of key1=value1|key2=value2
     */

    private function serializeInvoices(array $invoices)
    {
        $str = '';
        foreach ($invoices as $i => $invoice) {
            $str .= ($i > 0 ? '|' : '') . $invoice['id'] . '=' . $invoice['amount'];
        }
        return $str;
    }
    /**
     * Unserializes a string of invoice info into an array.
     *
     * @param string $str A serialized string of invoice info in the format of key1=value1|key2=value2
     * @return array A numerically indexed array invoices info including:
     *  - id The ID of the invoice
     *  - amount The amount relating to the invoice
     */

    private function unserializeInvoices($str)
    {
        $invoices = [];
        $temp = explode('|', $str);
        foreach ($temp as $pair) {
            $pairs = explode('=', $pair, 2);
            if (count($pairs) != 2) {
                continue;
            }
            $invoices[] = ['id' => $pairs[0], 'amount' => $pairs[1]];
        }

        return $invoices;
    }
}
