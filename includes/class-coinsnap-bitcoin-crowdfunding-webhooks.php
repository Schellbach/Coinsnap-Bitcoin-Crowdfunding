<?php
if (!defined('ABSPATH')){ exit; }

class Coinsnap_Bitcoin_Crowdfunding_Webhooks {

    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_webhook_endpoint']);
        add_action('rest_api_init', [$this, 'register_poll_check_endpoint']);
        add_action('rest_api_init', [$this, 'register_poll_results_endpoint']);
        add_action('rest_api_init', [$this, 'register_check_payment_endpoint']);
        add_action('wp_ajax_coinsnap_bitcoin_crowdfunding_register_webhook', [$this, 'ajax_register_webhook']);
    }

    public function register_poll_results_endpoint()
    {
        register_rest_route('crowdfunding/v1', '/voting_results/(?P<poll_id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_results'],
            'permission_callback' => '__return_true', // TODO: Add proper permissions later
            'args' => [
                'poll_id' => [
                    'required' => true,
                    'validate_callback' => function ($param) {
                        return is_numeric($param) && $param > 0;
                    }
                ]
            ]
        ]);
    }

    public function register_poll_check_endpoint()
    {
        register_rest_route('crowdfunding/v1', '/payment-status-long-poll/(?P<payment_id>[a-zA-Z0-9]+)/(?P<poll_id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_payment_status_long_poll'],
            'permission_callback' => '__return_true', // TODO: Add proper permissions later
            'args' => [
                'payment_id' => [
                    'required' => true,
                    'validate_callback' => function ($param) {
                        return !empty($param);
                    }
                ],
                'poll_id' => [
                    'required' => true,
                    'validate_callback' => function ($param) {
                        return is_numeric($param) && $param > 0;
                    }
                ]
            ]
        ]);
    }

    public function register_check_payment_endpoint()
    {
        register_rest_route('crowdfunding/v1', '/check-payment-status/(?P<payment_id>[a-zA-Z0-9]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_check_payment_status'],
            'permission_callback' => '__return_true', // TODO: Add proper permissions later
            'args' => [
                'payment_id' => [
                    'required' => true,
                    'validate_callback' => function ($param) {
                        return !empty($param);
                    }
                ]
            ]
        ]);
    }

    function get_results($request)
    {
        $poll_id = $request['poll_id'];

        global $wpdb;
        $tab_name = "{$wpdb->prefix}crowdfunding_payments";
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM %i WHERE status = 'completed' AND poll_id = %d",
            $tab_name, $poll_id
        ));

        return ['results' => $results];
    }

    function get_payment_status_long_poll($request)
    {
        $payment_id = $request['payment_id'];
        $poll_id = $request['poll_id'];
        $start_time = time();
        $timeout = 5;

        while (time() - $start_time < $timeout) {
            global $wpdb;
            $table_name = $wpdb->prefix."crowdfunding_payments";
            
            $status = $wpdb->get_var($wpdb->prepare(
                "SELECT status FROM %i WHERE payment_id = %s",$table_name,$payment_id
            ));
            if ($status === 'completed') {
                $results = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM %i WHERE status = 'completed' AND poll_id = %d",$table_name,$poll_id
                ));

                return ['status' => 'completed', 'results' => $results];
            }
            sleep(1);
        }
        // Timeout
        return ['status' => 'pending'];
    }

    function get_check_payment_status($request)
    {
        $payment_id = $request['payment_id'];
        $start_time = time();
        $timeout = 5;

        while (time() - $start_time < $timeout) {
            global $wpdb;
            $status = $wpdb->get_var($wpdb->prepare(
                "SELECT status FROM {$wpdb->prefix}crowdfunding_payments WHERE payment_id = %s",
                $payment_id
            ));
            if ($status === 'completed') {
                return ['status' => 'completed'];
            }
            sleep(1);
        }
        // Timeout
        return ['status' => 'pending'];
    }


    /**
     * Read the stored webhook secret. Unauthenticated requests must not create one.
     */
    private function read_webhook_secret()
    {
        $secret = get_option('coinsnap_webhook_secret', '');
        return is_string($secret) ? $secret : '';
    }

    public static function rotate_webhook_secret()
    {
        try {
            $secret = bin2hex(random_bytes(32));
        } catch (Exception $e) {
            return false;
        }

        if (get_option('coinsnap_webhook_secret', null) === null) {
            add_option('coinsnap_webhook_secret', $secret, '', false);
        } else {
            update_option('coinsnap_webhook_secret', $secret, false);
        }

        return true;
    }

    private static function ensure_webhook_secret()
    {
        $secret = get_option('coinsnap_webhook_secret', '');
        if (!is_string($secret) || $secret === '') {
            self::rotate_webhook_secret();
            $secret = get_option('coinsnap_webhook_secret', '');
        }
        return is_string($secret) ? $secret : '';
    }

    public static function signature_is_valid($payload, $secret, $signature_header)
    {
        if (!is_string($payload) || !is_string($secret) || $secret === '' || !is_string($signature_header) || $signature_header === '') {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $signature_header);
    }

    public function register_webhook_endpoint()
    {
        register_rest_route('coinsnap-bitcoin-crowdfunding/v1', '/webhook', [
            'methods'  => ['POST'],
            'callback' => [$this, 'handle_webhook'],
            'permission_callback' => [$this, 'verify_webhook_request']
        ]);
    }

    function verify_webhook_request($request)
    {
        $secret = $this->read_webhook_secret();
        $coinsnap_sig = $request->get_header('X-Coinsnap-Sig');
        $btcpay_sig = $request->get_header('BTCPay-Sig');
        $signature_header = (is_string($coinsnap_sig) && $coinsnap_sig !== '') ? $coinsnap_sig : $btcpay_sig;
        if (!is_string($signature_header) || $signature_header === '') {
            return false;
        }

        return self::signature_is_valid($request->get_body(), $secret, trim($signature_header));
    }

    public function handle_webhook(WP_REST_Request $request)
    {
        $payload_data = $request->get_json_params();
        if (!is_array($payload_data)) {
            return new WP_REST_Response('Invalid payload.', 400);
        }

        $type = isset($payload_data['type']) ? (string) $payload_data['type'] : '';
        if ($type !== 'Settled' && $type !== 'InvoiceSettled') {
            return new WP_REST_Response('Webhook type not handled.', 200);
        }

        $invoice_id = isset($payload_data['invoiceId']) ? (string) $payload_data['invoiceId'] : '';
        $confirmed = $this->confirm_settled_invoice($invoice_id);
        if ($confirmed instanceof WP_REST_Response) {
            return $confirmed;
        }

        $metadata = $this->metadata_from_confirmed_invoice($confirmed, $payload_data);
        $amount = isset($metadata['amount']) ? $metadata['amount'] : 0;

        // Crowdfunding
        if (isset($metadata['type']) && $metadata['type'] == "Bitcoin Crowdfunding") {
            global $wpdb;
            $table_name = $wpdb->prefix . 'crowdfunding_payments';
            $crowdfunding_id = isset($metadata['crowdfunding_id']) ? sanitize_text_field($metadata['crowdfunding_id']) : '';
            $name = isset($metadata['name']) ? sanitize_text_field($metadata['name']) : '';
            $message = isset($metadata['message']) ? sanitize_text_field($metadata['message']) : '';

            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM %i WHERE payment_id = %s",
                $table_name,
                $invoice_id
            ));
            if (!$existing) {
                $wpdb->insert(
                    $table_name,
                    [
                        'payment_id' => $invoice_id,
                        'crowdfunding_id' => $crowdfunding_id,
                        'amount' => $amount,
                        'status'     => 'completed',
                        'name' => $name,
                        'message' => $message
                    ],
                    [
                        '%s',
                        '%s',
                        '%d',
                        '%s',
                        '%s',
                        '%s'
                    ]
                );
            }
        }
        if (isset($metadata['modal'])) {
            // Public donor
            if (isset($metadata['publicDonor']) && $metadata['publicDonor'] == '1') {
                $existing_donor = get_posts([
                    'post_type' => 'coinsnap-cf-donors',
                    'meta_key' => '_coinsnap_bitcoin_crowdfunding_payment_id',
                    'meta_value' => $invoice_id,
                    'posts_per_page' => 1,
                    'fields' => 'ids',
                    'post_status' => 'any',
                ]);
                if (empty($existing_donor)) {
                    $name = isset($metadata['donorName']) ? $metadata['donorName'] : '';
                    $email = isset($metadata['donorEmail']) ? $metadata['donorEmail'] : '';
                    $address = isset($metadata['donorAddress']) ? $metadata['donorAddress'] : '';
                    $message = isset($metadata['donorMessage']) ? $metadata['donorMessage'] : '';
                    $opt_out = isset($metadata['donorOptOut']) ? $metadata['donorOptOut'] : '';
                    $custom = isset($metadata['donorCustom']) ? $metadata['donorCustom'] : '';
                    $type = isset($metadata['formType']) ? $metadata['formType'] : '';
                    $shoutoutName = isset($metadata['name']) ? $metadata['name'] : '';
                    $shoutoutMessage = isset($metadata['message']) ? $metadata['message'] : '';
                    $message = !empty($message) ? $message : $shoutoutMessage;
                    if(empty($name)){
                        if(!empty($shoutoutName) || !empty($shoutoutMessage)){
                            $name = empty($shoutoutName) ? 'Shoutout from Anonymous' : 'Shoutout from ' . $shoutoutName;
                        } else {
                            $name = 'Anonymous';
                        }
                    }

                    $opt_out_value = filter_var($opt_out, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
                    $post_data = array(
                        'post_title'    => $name,
                        'post_status'   => 'publish',
                        'post_type'     => 'coinsnap-cf-donors',
                        'post_content'  => $message
                    );

                    $post_id = wp_insert_post($post_data);

                    if ($post_id) {
                        update_post_meta($post_id, '_coinsnap_bitcoin_crowdfunding_donor_name', sanitize_text_field($name));
                        update_post_meta($post_id, '_coinsnap_bitcoin_crowdfunding_amount', sanitize_text_field($amount));
                        update_post_meta($post_id, '_coinsnap_bitcoin_crowdfunding_message', sanitize_text_field($message));
                        update_post_meta($post_id, '_coinsnap_bitcoin_crowdfunding_form_type', sanitize_text_field($type));
                        update_post_meta($post_id, '_coinsnap_bitcoin_crowdfunding_dont_show', $opt_out_value);
                        update_post_meta($post_id, '_coinsnap_bitcoin_crowdfunding_email', sanitize_email($email));
                        update_post_meta($post_id, '_coinsnap_bitcoin_crowdfunding_address', sanitize_text_field($address));
                        update_post_meta($post_id, '_coinsnap_bitcoin_crowdfunding_payment_id', sanitize_text_field($invoice_id));
                        update_post_meta($post_id, '_coinsnap_bitcoin_crowdfunding_custom_field', sanitize_text_field($custom));
                    }
                }
            }
        }

        return new WP_REST_Response('OK', 200);
    }

    /**
     * Confirm the invoice with the configured provider. The webhook body is not trusted for status or amount.
     *
     * @return array|WP_REST_Response
     */
    private function confirm_settled_invoice($invoice_id)
    {
        if (!is_string($invoice_id) || !preg_match('/^[A-Za-z0-9_-]{1,128}$/', $invoice_id)) {
            return new WP_REST_Response('Invalid invoice.', 400);
        }

        $options = get_option('coinsnap_bitcoin_crowdfunding_options', []);
        if (!is_array($options)) {
            $options = [];
        }
        $provider = (isset($options['provider']) && $options['provider'] === 'btcpay') ? 'btcpay' : 'coinsnap';
        if ($provider === 'btcpay') {
            $base = isset($options['btcpay_url']) ? $options['btcpay_url'] : '';
            $store_id = isset($options['btcpay_store_id']) ? $options['btcpay_store_id'] : '';
            $api_key = isset($options['btcpay_api_key']) ? $options['btcpay_api_key'] : '';
        } else {
            $base = COINSNAP_SERVER_URL;
            $store_id = isset($options['coinsnap_store_id']) ? $options['coinsnap_store_id'] : '';
            $api_key = isset($options['coinsnap_api_key']) ? $options['coinsnap_api_key'] : '';
        }

        if (!self::is_http_url($base) || $store_id === '' || $api_key === '') {
            return new WP_REST_Response('Invoice could not be confirmed.', 503);
        }

        $url = untrailingslashit($base) . '/api/v1/stores/' . rawurlencode($store_id) . '/invoices/' . rawurlencode($invoice_id);
        $response = self::provider_http('GET', $url, self::provider_headers($provider === 'btcpay', $api_key));
        if ($response === null || $response['code'] !== 200 || !is_array($response['body'])) {
            return new WP_REST_Response('Invoice could not be confirmed.', 503);
        }

        $invoice = $response['body'];
        $status = isset($invoice['status']) ? (string) $invoice['status'] : '';
        if (strcasecmp($status, 'Settled') !== 0) {
            $pending = strcasecmp($status, 'New') === 0 || strcasecmp($status, 'Processing') === 0;
            $http_status = $pending ? 503 : 200;
            return new WP_REST_Response('Invoice is not settled.', $http_status);
        }
        if (isset($invoice['id']) && (string) $invoice['id'] !== $invoice_id) {
            return new WP_REST_Response('Invoice mismatch.', 400);
        }

        return $invoice;
    }

    private function metadata_from_confirmed_invoice(array $invoice, array $payload_data)
    {
        if (isset($invoice['metadata']) && is_array($invoice['metadata']) && $invoice['metadata'] !== []) {
            $metadata = $invoice['metadata'];
        } elseif (isset($payload_data['metadata']) && is_array($payload_data['metadata'])) {
            $metadata = $payload_data['metadata'];
        } else {
            $metadata = [];
        }

        if (isset($invoice['amount']) && $invoice['amount'] !== '') {
            $metadata['amount'] = $invoice['amount'];
        }

        return $metadata;
    }

    public function ajax_register_webhook()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized.', 'coinsnap-bitcoin-crowdfunding')], 403);
        }

        $nonce = isset($_POST['apiNonce']) ? sanitize_text_field(wp_unslash($_POST['apiNonce'])) : '';
        if (!wp_verify_nonce($nonce, 'coinsnap-ajax-nonce')) {
            wp_send_json_error(['message' => __('Unauthorized.', 'coinsnap-bitcoin-crowdfunding')], 401);
        }

        $store_id = isset($_POST['storeId']) ? sanitize_text_field(wp_unslash($_POST['storeId'])) : '';
        $api_key = isset($_POST['apiKey']) ? sanitize_text_field(wp_unslash($_POST['apiKey'])) : '';
        $btcpay_url = isset($_POST['btcpayUrl']) ? esc_url_raw(wp_unslash($_POST['btcpayUrl'])) : '';
        $webhook_url = isset($_POST['webhookUrl']) ? esc_url_raw(wp_unslash($_POST['webhookUrl'])) : '';

        if (!self::register_with_provider($store_id, $api_key, $btcpay_url, $webhook_url)) {
            wp_send_json_error(['message' => __('The payment provider did not accept the webhook update. Check the connection and try again.', 'coinsnap-bitcoin-crowdfunding')], 502);
        }

        delete_option('coinsnap_webhook_resync_required');
        wp_send_json_success(['registered' => true]);
    }

    public static function sync_stored_webhook()
    {
        $options = get_option('coinsnap_bitcoin_crowdfunding_options', []);
        if (!is_array($options)) {
            return false;
        }

        $provider = (isset($options['provider']) && $options['provider'] === 'btcpay') ? 'btcpay' : 'coinsnap';
        if ($provider === 'btcpay') {
            $store_id = isset($options['btcpay_store_id']) ? $options['btcpay_store_id'] : '';
            $api_key = isset($options['btcpay_api_key']) ? $options['btcpay_api_key'] : '';
            $base = isset($options['btcpay_url']) ? $options['btcpay_url'] : '';
        } else {
            $store_id = isset($options['coinsnap_store_id']) ? $options['coinsnap_store_id'] : '';
            $api_key = isset($options['coinsnap_api_key']) ? $options['coinsnap_api_key'] : '';
            $base = '';
        }

        return self::register_with_provider($store_id, $api_key, $base, self::webhook_callback_url($options));
    }

    private static function webhook_callback_url($options)
    {
        if (is_array($options) && !empty($options['ngrok_url']) && self::is_http_url($options['ngrok_url'])) {
            return untrailingslashit($options['ngrok_url']) . '/wp-json/coinsnap-bitcoin-crowdfunding/v1/webhook';
        }
        return rest_url('coinsnap-bitcoin-crowdfunding/v1/webhook');
    }

    private static function register_with_provider($store_id, $api_key, $btcpay_url, $webhook_url)
    {
        $store_id = is_string($store_id) ? $store_id : '';
        $api_key = is_string($api_key) ? $api_key : '';
        $btcpay_url = is_string($btcpay_url) ? $btcpay_url : '';
        $webhook_url = is_string($webhook_url) ? $webhook_url : '';
        $is_btcpay = $btcpay_url !== '';
        $base = $is_btcpay ? $btcpay_url : COINSNAP_SERVER_URL;

        if ($store_id === '' || $api_key === '' || !self::is_http_url($base) || !self::is_http_url($webhook_url)) {
            return false;
        }

        $secret = self::ensure_webhook_secret();
        if ($secret === '') {
            return false;
        }

        $headers = self::provider_headers($is_btcpay, $api_key);
        $endpoint = untrailingslashit($base) . '/api/v1/stores/' . rawurlencode($store_id) . '/webhooks';
        $list = self::provider_http('GET', $endpoint, $headers);
        if ($list === null || $list['code'] < 200 || $list['code'] >= 300 || !is_array($list['body'])) {
            return false;
        }

        $webhooks = $list['body'];
        if (isset($webhooks['webhooks']) && is_array($webhooks['webhooks'])) {
            $webhooks = $webhooks['webhooks'];
        }

        $existing_id = '';
        foreach ($webhooks as $webhook) {
            if (!is_array($webhook) || empty($webhook['id']) || empty($webhook['url'])) {
                continue;
            }
            if (untrailingslashit((string) $webhook['url']) === untrailingslashit($webhook_url)) {
                $existing_id = (string) $webhook['id'];
                break;
            }
        }

        $body = wp_json_encode([
            'url' => $webhook_url,
            'events' => ['Settled'],
            'secret' => $secret,
        ]);
        $target = $existing_id !== '' ? $endpoint . '/' . rawurlencode($existing_id) : $endpoint;
        $method = $existing_id !== '' ? 'PUT' : 'POST';
        $saved = self::provider_http($method, $target, $headers, $body);

        return $saved !== null && $saved['code'] >= 200 && $saved['code'] < 300;
    }

    private static function provider_headers($is_btcpay, $api_key)
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
        if ($is_btcpay) {
            $headers['Authorization'] = 'token ' . $api_key;
        } else {
            $headers['x-api-key'] = $api_key;
        }
        return $headers;
    }

    private static function provider_http($method, $url, array $headers, $body = null)
    {
        $args = [
            'method' => $method,
            'timeout' => 15,
            'headers' => $headers,
        ];
        if ($body !== null) {
            $args['body'] = $body;
        }

        $response = wp_remote_request($url, $args);
        if (is_wp_error($response)) {
            return null;
        }

        return [
            'code' => (int) wp_remote_retrieve_response_code($response),
            'body' => json_decode(wp_remote_retrieve_body($response), true),
        ];
    }

    private static function is_http_url($url)
    {
        if (!is_string($url) || $url === '') {
            return false;
        }
        $parts = wp_parse_url($url);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }
        $scheme = strtolower($parts['scheme']);
        return $scheme === 'https' || $scheme === 'http';
    }
}
new Coinsnap_Bitcoin_Crowdfunding_Webhooks();
