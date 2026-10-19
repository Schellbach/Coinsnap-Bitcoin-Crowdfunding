<?php
/*
 * Plugin Name:        Coinsnap Bitcoin Crowdfunding
 * Plugin URI:         https://coinsnap.io/wp-plugins/wp-bitcoin-crowdfunding/
 * Description:        Easy Bitcoin crowdfundings on a WordPress website
 * Version:            1.2.1
 * Author:             Coinsnap
 * Author URI:         https://coinsnap.io/
 * Text Domain:        coinsnap-bitcoin-crowdfunding
 * Domain Path:         /languages
 * Tested up to:        6.9
 * License:             GPL2
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 *
 * Network:             true
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'COINSNAP_BITCOIN_CROWDFUNDING_REFERRAL_CODE' ) ) {
    define( 'COINSNAP_BITCOIN_CROWDFUNDING_REFERRAL_CODE', 'D2SXIW' );
}
if ( ! defined( 'COINSNAP_BITCOIN_CROWDFUNDING_VERSION' ) ) {
    define( 'COINSNAP_BITCOIN_CROWDFUNDING_VERSION', '1.2.1' );
}
if ( ! defined( 'COINSNAP_BITCOIN_CROWDFUNDING_PHP_VERSION' ) ) {
    define( 'COINSNAP_BITCOIN_CROWDFUNDING_PHP_VERSION', '8.0' );
}
if( ! defined( 'COINSNAP_BITCOIN_CROWDFUNDING_PLUGIN_DIR' ) ){
    define('COINSNAP_BITCOIN_CROWDFUNDING_PLUGIN_DIR',plugin_dir_url(__FILE__));
}

if(!defined('COINSNAP_CURRENCIES')){define( 'COINSNAP_CURRENCIES', array("EUR","USD","SATS","BTC","CAD","JPY","GBP","CHF","RUB") );}
if(!defined('COINSNAP_SERVER_URL')){define( 'COINSNAP_SERVER_URL', 'https://app.coinsnap.io' );}
if(!defined('COINSNAP_API_PATH')){define( 'COINSNAP_API_PATH', '/api/v1/');}
if(!defined('COINSNAP_SERVER_PATH')){define( 'COINSNAP_SERVER_PATH', 'stores' );}

if (!defined('ABSPATH')) {
    exit;
}

// Plugin settings
require_once plugin_dir_path(__FILE__) . 'includes/class-coinsnap-bitcoin-crowdfunding-client.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-coinsnap-bitcoin-crowdfunding-list.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-coinsnap-bitcoin-crowdfunding-public-donors.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-coinsnap-bitcoin-crowdfunding-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-coinsnap-bitcoin-crowdfunding-shortcode.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-coinsnap-bitcoin-crowdfunding-webhooks.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-coinsnap-bitcoin-crowdfunding-shoutouts-list.php';

register_activation_hook(__FILE__, 'coinsnap_bitcoin_crowdfunding_create_crowdfunding_payments_table');
register_deactivation_hook(__FILE__, 'coinsnap_bitcoin_crowdfunding_deactivate');

// Permalink structure fix
add_filter('rest_url_prefix', 'coinsnap_bitcoin_crowdfunding_rest_url_prefix');

function coinsnap_bitcoin_crowdfunding_rest_url_prefix($prefix){
    return 'wp-json';
}

function coinsnap_bitcoin_crowdfunding_deactivate(){
    flush_rewrite_rules();
}

function coinsnap_bitcoin_crowdfunding_create_crowdfunding_payments_table(){
    global $wpdb;
    $table_name = $wpdb->prefix . 'crowdfunding_payments';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        payment_id VARCHAR(255) NOT NULL,
        crowdfunding_id VARCHAR(255) NOT NULL,
        amount INT(20) NOT NULL,
        name VARCHAR(255),
        message VARCHAR(511),
        status VARCHAR(50) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

class Coinsnap_Bitcoin_Crowdfunding
{
    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'coinsnap_bitcoin_crowdfunding_enqueue_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'coinsnap_bitcoin_crowdfunding_enqueue_admin_styles']);
        add_action('wp_ajax_coinsnap_bitcoin_crowdfunding_btcpay_apiurl_handler', [$this, 'btcpayApiUrlHandler']);
        add_action('wp_ajax_coinsnap_bitcoin_crowdfunding_connection_handler', [$this, 'coinsnapConnectionHandler']);
        add_action('wp_ajax_coinsnap_bitcoin_crowdfunding_amount_check', [$this, 'coinsnapAmountCheck']);
        add_action('wp_ajax_nopriv_coinsnap_bitcoin_crowdfunding_amount_check', [$this, 'coinsnapAmountCheck']);
    }
    
    public function coinsnapAmountCheck(){
        
        $_nonce = filter_input(INPUT_POST,'apiNonce',FILTER_SANITIZE_STRING);
        if ( !wp_verify_nonce( $_nonce, 'coinsnap-ajax-nonce' ) ) {
            wp_die('Unauthorized!', '', ['response' => 401]);
        }
        
        $client = new Coinsnap_Bitcoin_Crowdfunding_Client();
        $amount = filter_input(INPUT_POST,'apiAmount',FILTER_SANITIZE_STRING);
        $currency = filter_input(INPUT_POST,'apiCurrency',FILTER_SANITIZE_STRING);
        
        try {
            $_provider = $this->getPaymentProvider();
            if($_provider === 'btcpay'){
                try {
                    $storePaymentMethods = $client->getStorePaymentMethods($this->getApiUrl(), $this->getApiKey(), $this->getStoreId());

                    if ($storePaymentMethods['code'] === 200) {
                        if(!$storePaymentMethods['result']['onchain'] && !$storePaymentMethods['result']['lightning']){
                            $errorMessage = __( 'No payment method is configured on BTCPay server', 'coinsnap-bitcoin-crowdfunding' );
                            $checkInvoice = array('result' => false,'error' => esc_html($errorMessage));
                        }
                    }
                    else {
                        $errorMessage = __( 'Error store loading. Wrong or empty Store ID', 'coinsnap-bitcoin-crowdfunding' );
                        $checkInvoice = array('result' => false,'error' => esc_html($errorMessage));
                    }

                    if($storePaymentMethods['result']['onchain'] && !$storePaymentMethods['result']['lightning']){
                        $checkInvoice = $client->checkPaymentData((float)$amount,strtoupper( $currency ),'bitcoin');
                    }
                    elseif($storePaymentMethods['result']['lightning']){
                        $checkInvoice = $client->checkPaymentData((float)$amount,strtoupper( $currency ),'lightning');
                    }
                }
                catch (\Throwable $e){
                    $errorMessage = __( 'API connection is not established', 'coinsnap-bitcoin-crowdfunding' );
                    $checkInvoice = array('result' => false,'error' => esc_html($errorMessage));
                }
            }
            else {
                $checkInvoice = $client->checkPaymentData((float)$amount,strtoupper( $currency ));
                if($checkInvoice['error'] === 'currencyError'){
                    $checkInvoice['error'] = sprintf( 
                        /* translators: 1: Currency */
                        __( 'Currency %1$s is not supported by Coinsnap', 'coinsnap-bitcoin-crowdfunding' ), strtoupper( $currency ));
                }
                elseif($checkInvoice['error'] === 'amountError'){
                    $checkInvoice['error'] = sprintf( 
                        /* translators: 1: Amount, 2: Currency */
                        __( 'Invoice amount cannot be less than %1$s %2$s', 'coinsnap-bitcoin-crowdfunding' ), $checkInvoice['min_value'], strtoupper( $currency ));
                }
            }
        }
        catch (\Throwable $e){
            $errorMessage = __( 'API connection is not established', 'coinsnap-bitcoin-crowdfunding' );
            $checkInvoice = array('result' => false,'error' => esc_html($errorMessage));
        }
        return $this->sendJsonResponse($checkInvoice);
    }
    
    public function coinsnapConnectionHandler(){
        $_nonce = filter_input(INPUT_POST,'apiNonce',FILTER_SANITIZE_STRING);
        if ( !wp_verify_nonce( $_nonce, 'coinsnap-ajax-nonce' ) ) {
            wp_die('Unauthorized!', '', ['response' => 401]);
        }
        
        $response = [
            'result' => false,
            'message' => __('Empty gateway URL or API Key', 'coinsnap-bitcoin-crowdfunding')
        ];
        
        
        $coinsnap_bitcoin_crowdfunding_data = get_option('coinsnap_bitcoin_crowdfunding_options', []);
        
        $_provider = $this->getPaymentProvider();
        $currency = ('' !== filter_input(INPUT_POST,'apiPost',FILTER_SANITIZE_STRING))? get_post_meta(filter_input(INPUT_POST,'apiPost',FILTER_SANITIZE_STRING), '_coinsnap_bitcoin_crowdfunding_default_currency', true) : 'EUR';
        $client = new Coinsnap_Bitcoin_Crowdfunding_Client();
        
        if($_provider === 'btcpay'){
            try {
                
                $storePaymentMethods = $client->getStorePaymentMethods($this->getApiUrl(), $this->getApiKey(), $this->getStoreId());

                if ($storePaymentMethods['code'] === 200) {
                    if($storePaymentMethods['result']['onchain'] && !$storePaymentMethods['result']['lightning']){
                        $checkInvoice = $client->checkPaymentData(0,$currency,'bitcoin','calculation');
                    }
                    elseif($storePaymentMethods['result']['lightning']){
                        $checkInvoice = $client->checkPaymentData(0,$currency,'lightning','calculation');
                    }
                }
            }
            catch (\Exception $e) {
                $response = [
                        'result' => false,
                        'message' => __('Coinsnap Bitcoin Crowdfunding: API connection is not established', 'coinsnap-bitcoin-crowdfunding')
                ];
                $this->sendJsonResponse($response);
            }
        }
        else {
            $checkInvoice = $client->checkPaymentData(0,$currency,'coinsnap','calculation');
        }
        
        if(isset($checkInvoice) && $checkInvoice['result']){
            $connectionData = __('Min donation amount is', 'coinsnap-bitcoin-crowdfunding') .' '. $checkInvoice['min_value'].' '.$currency;
        }
        else {
            $connectionData = __('No payment method is configured', 'coinsnap-bitcoin-crowdfunding');
        }
        
        $_message_disconnected = ($_provider !== 'btcpay')? 
            __('Coinsnap Bitcoin Crowdfunding: Coinsnap server is disconnected', 'coinsnap-bitcoin-crowdfunding') :
            __('Coinsnap Bitcoin Crowdfunding: BTCPay server is disconnected', 'coinsnap-bitcoin-crowdfunding');
        $_message_connected = ($_provider !== 'btcpay')?
            __('Coinsnap Bitcoin Crowdfunding: Coinsnap server is connected', 'coinsnap-bitcoin-crowdfunding') : 
            __('Coinsnap Bitcoin Crowdfunding: BTCPay server is connected', 'coinsnap-bitcoin-crowdfunding');
        
        if( wp_verify_nonce($_nonce,'coinsnap-ajax-nonce') ){
            $response = ['result' => false,'message' => $_message_disconnected];

            try {
                $this_store = $client->getStore($this->getApiUrl(), $this->getApiKey(), $this->getStoreId());
                
                if ($this_store['code'] !== 200) {
                    $this->sendJsonResponse($response);
                }
                
                else {
                    $response = ['result' => true,'message' => $_message_connected.' ('.$connectionData.')'];
                    $this->sendJsonResponse($response);
                }
            }
            catch (\Exception $e) {
                $response['message'] =  __('Coinsnap Bitcoin Crowdfunding: API connection is not established', 'coinsnap-bitcoin-crowdfunding');
            }

            $this->sendJsonResponse($response);
        }            
    }
    
    public function sendJsonResponse(array $response): void {
        echo wp_json_encode($response);
        exit();
    }
    
    private function getPaymentProvider() {
        $coinsnap_bitcoin_crowdfunding_data = get_option('coinsnap_bitcoin_crowdfunding_options', []);
        return ($coinsnap_bitcoin_crowdfunding_data['provider'] === 'btcpay')? 'btcpay' : 'coinsnap';
    }

    private function getApiKey() {
        $coinsnap_bitcoin_crowdfunding_data = get_option('coinsnap_bitcoin_crowdfunding_options', []);
        return ($this->getPaymentProvider() === 'btcpay')? $coinsnap_bitcoin_crowdfunding_data['btcpay_api_key']  : $coinsnap_bitcoin_crowdfunding_data['coinsnap_api_key'];
    }
    
    private function getStoreId() {
	$coinsnap_bitcoin_crowdfunding_data = get_option('coinsnap_bitcoin_crowdfunding_options', []);
        return ($this->getPaymentProvider() === 'btcpay')? $coinsnap_bitcoin_crowdfunding_data['btcpay_store_id'] : $coinsnap_bitcoin_crowdfunding_data['coinsnap_store_id'];
    }
    
    public function getApiUrl() {
        $coinsnap_bitcoin_crowdfunding_data = get_option('coinsnap_bitcoin_crowdfunding_options', []);
        return ($this->getPaymentProvider() === 'btcpay')? $coinsnap_bitcoin_crowdfunding_data['btcpay_url'] : COINSNAP_SERVER_URL;
    }
    
    function btcpayApiUrlHandler(){
            $_nonce = filter_input(INPUT_POST,'apiNonce',FILTER_SANITIZE_STRING);
            if ( !wp_verify_nonce( $_nonce, 'coinsnap-ajax-nonce' ) ) {
                wp_die('Unauthorized!', '', ['response' => 401]);
            }

            if ( current_user_can( 'manage_options' ) ) {
                $host = filter_var(filter_input(INPUT_POST,'host',FILTER_SANITIZE_STRING), FILTER_VALIDATE_URL);

                if ($host === false || (substr( $host, 0, 7 ) !== "http://" && substr( $host, 0, 8 ) !== "https://")) {
                    wp_send_json_error("Error validating BTCPayServer URL.");
                }

                $permissions = array_merge([
                    'btcpay.store.canviewinvoices',
                    'btcpay.store.cancreateinvoice',
                    'btcpay.store.canviewstoresettings',
                    'btcpay.store.canmodifyinvoices'
                ],
                [
                    'btcpay.store.cancreatenonapprovedpullpayments',
                    'btcpay.store.webhooks.canmodifywebhooks',
                ]);

                try {
                    // Create the redirect url to BTCPay instance.
                    $url = $this->getAuthorizeUrl(
                        $host,
                        $permissions,
                        'CoinsnapBitcoinCrowdfunding',
                        true,
                        true,
                        home_url('?crowdfunding-btcpay-settings-callback'),
                        null
                    );

                    // Store the host to options before we leave the site.
                    coinsnap_settings_update('coinsnap_bitcoin_crowdfunding_options',['btcpay_url' => $host]);

                    // Return the redirect url.
                    wp_send_json_success(['url' => $url]);
                }

                catch (\Throwable $e) {

                }
            }
            wp_send_json_error("Error processing Ajax request.");
    }

    function coinsnap_bitcoin_crowdfunding_enqueue_scripts(){
        
        global $post;
    
        if ( is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'coinsnap_bitcoin_crowdfunding') ) {
        
            $coinsnap_bitcoin_crowdfunding_data = get_option('coinsnap_bitcoin_crowdfunding_options', []);
            wp_enqueue_style('coinsnap-bitcoin-crowdfunding-style', plugin_dir_url(__FILE__) . 'assets/css/style.css', [], COINSNAP_BITCOIN_CROWDFUNDING_VERSION);
            wp_enqueue_style('coinsnap-bitcoin-crowdfunding-style-wide', plugin_dir_url(__FILE__) . 'assets/css/style-wide.css', [], COINSNAP_BITCOIN_CROWDFUNDING_VERSION);
            wp_enqueue_style('coinsnap-bitcoin-crowdfunding-shoutouts', plugin_dir_url(__FILE__) . 'assets/css/shoutouts.css', [], COINSNAP_BITCOIN_CROWDFUNDING_VERSION);

            wp_enqueue_script('coinsnap-bitcoin-crowdfunding-script', plugin_dir_url(__FILE__) . 'assets/js/crowdfunding.js', ['jquery'], COINSNAP_BITCOIN_CROWDFUNDING_VERSION, true);

            wp_localize_script('coinsnap-bitcoin-crowdfunding-script', 'coinsnap_bitcoin_crowdfunding_ajax', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce'  => wp_create_nonce( 'coinsnap-ajax-nonce' ),
                    'post' => $post_id
                ));

            // Define defaults for forms options
            $forms_defaults = [
                'redirect_url' => home_url(),
            ];

            // Localize script for sharedData
            wp_enqueue_script('coinsnap-bitcoin-crowdfunding-shared-script', plugin_dir_url(__FILE__) . 'assets/js/shared.js', ['jquery'], COINSNAP_BITCOIN_CROWDFUNDING_VERSION, true);
            wp_localize_script('coinsnap-bitcoin-crowdfunding-shared-script', 'Coinsnap_Bitcoin_Crowdfunding_sharedData', [
                'provider' => $coinsnap_bitcoin_crowdfunding_data['provider'],
                'coinsnapStoreId' => isset($coinsnap_bitcoin_crowdfunding_data['coinsnap_store_id'])? $coinsnap_bitcoin_crowdfunding_data['coinsnap_store_id'] : '',
                'coinsnapApiKey' => isset($coinsnap_bitcoin_crowdfunding_data['coinsnap_api_key'])? $coinsnap_bitcoin_crowdfunding_data['coinsnap_api_key'] : '',
                'btcpayStoreId' => isset($coinsnap_bitcoin_crowdfunding_data['btcpay_store_id'])? $coinsnap_bitcoin_crowdfunding_data['btcpay_store_id'] : '',
                'btcpayApiKey' => isset($coinsnap_bitcoin_crowdfunding_data['btcpay_api_key'])? $coinsnap_bitcoin_crowdfunding_data['btcpay_api_key'] : '',
                'btcpayUrl' => isset($coinsnap_bitcoin_crowdfunding_data['btcpay_url'])? $coinsnap_bitcoin_crowdfunding_data['btcpay_url'] : '',
                'redirectUrl' => isset($coinsnap_bitcoin_crowdfunding_data['redirect_url'])? $coinsnap_bitcoin_crowdfunding_data['redirect_url'] : '',
                'nonce' => wp_create_nonce('wp_rest')
            ]);

            wp_enqueue_script('coinsnap-bitcoin-crowdfunding-popup-script', plugin_dir_url(__FILE__) . 'assets/js/popup.js', ['jquery'], COINSNAP_BITCOIN_CROWDFUNDING_VERSION, true);
        }
    }

    function coinsnap_bitcoin_crowdfunding_enqueue_admin_styles($hook){
        $post_id = (filter_input(INPUT_GET,'post',FILTER_SANITIZE_FULL_SPECIAL_CHARS ))? filter_input(INPUT_GET,'post',FILTER_SANITIZE_FULL_SPECIAL_CHARS ) : '';
        $post_type = (filter_input(INPUT_GET,'post_type',FILTER_SANITIZE_FULL_SPECIAL_CHARS ))? filter_input(INPUT_GET,'post_type',FILTER_SANITIZE_FULL_SPECIAL_CHARS ) : 
            ((!empty($post_id))? get_post_type($post_id) : '');
        $page = (filter_input(INPUT_GET,'page',FILTER_SANITIZE_FULL_SPECIAL_CHARS ))? filter_input(INPUT_GET,'page',FILTER_SANITIZE_FULL_SPECIAL_CHARS ) : '';
        
        wp_enqueue_style('coinsnap-bitcoin-crowdfunding-admin-style', plugin_dir_url(__FILE__) . 'assets/css/admin-style.css', [], COINSNAP_BITCOIN_CROWDFUNDING_VERSION);
        
        if($page === 'coinsnap-bitcoin-crowdfunding' || $post_type === 'coinsnap-cfs'){            
            wp_enqueue_script('coinsnap-bitcoin-crowdfunding-admin-script', plugin_dir_url(__FILE__) . 'assets/js/admin.js', ['jquery'], COINSNAP_BITCOIN_CROWDFUNDING_VERSION, true);
            wp_localize_script('coinsnap-bitcoin-crowdfunding-admin-script', 'coinsnap_bitcoin_crowdfunding_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'  => wp_create_nonce( 'coinsnap-ajax-nonce' ),
                'post' => $post_id
            ));
        }
    }

    function coinsnap_bitcoin_crowdfunding_verify_nonce($nonce, $action)
    {
        if (!wp_verify_nonce($nonce, $action)) {
            wp_die(esc_html__('Security check failed', 'coinsnap-bitcoin-crowdfunding'));
        }
    }
    
    public function getAuthorizeUrl(string $baseUrl, array $permissions, ?string $applicationName, ?bool $strict, ?bool $selectiveStores, ?string $redirectToUrlAfterCreation, ?string $applicationIdentifier): string
    {
        $url = rtrim($baseUrl, '/') . '/api-keys/authorize';

        $params = [];
        $params['permissions'] = $permissions;
        $params['applicationName'] = $applicationName;
        $params['strict'] = $strict;
        $params['selectiveStores'] = $selectiveStores;
        $params['redirect'] = $redirectToUrlAfterCreation;
        $params['applicationIdentifier'] = $applicationIdentifier;

        // Take out NULL values
        $params = array_filter($params, function ($value) {
            return $value !== null;
        });

        $queryParams = [];

        foreach ($params as $param => $value) {
            if ($value === true) {
                $value = 'true';
            }
            if ($value === false) {
                $value = 'false';
            }

            if (is_array($value)) {
                foreach ($value as $item) {
                    if ($item === true) {
                        $item = 'true';
                    }
                    if ($item === false) {
                        $item = 'false';
                    }
                    $queryParams[] = $param . '=' . urlencode((string)$item);
                }
            } else {
                $queryParams[] = $param . '=' . urlencode((string)$value);
            }
        }

        $queryParams = implode("&", $queryParams);
        $url .= '?' . $queryParams;
        return $url;
    }
}
new Coinsnap_Bitcoin_Crowdfunding();

/**
 * Regenerate a disclosed webhook secret on upgrade and ask an administrator
 * to register it with the payment provider.
 */
function coinsnap_bitcoin_crowdfunding_maybe_upgrade() {
    $version_key = 'coinsnap_bitcoin_crowdfunding_db_version';
    $installed = get_option($version_key, '');
    if ($installed === COINSNAP_BITCOIN_CROWDFUNDING_VERSION) {
        return;
    }

    $existing_secret = get_option('coinsnap_webhook_secret', '');
    $had_secret = is_string($existing_secret) && $existing_secret !== '';
    $is_upgrade = (is_string($installed) && $installed !== '' && version_compare($installed, '1.2.1', '<'))
        || ($installed === '' && $had_secret);

    if ($is_upgrade && $had_secret) {
        Coinsnap_Bitcoin_Crowdfunding_Webhooks::rotate_webhook_secret();
        Coinsnap_Bitcoin_Crowdfunding_Webhooks::sync_stored_webhook();
        update_option('coinsnap_webhook_resync_required', '1', false);
    }

    if (get_option($version_key, null) === null) {
        add_option($version_key, COINSNAP_BITCOIN_CROWDFUNDING_VERSION, '', false);
    } else {
        update_option($version_key, COINSNAP_BITCOIN_CROWDFUNDING_VERSION, false);
    }
}
add_action('init', 'coinsnap_bitcoin_crowdfunding_maybe_upgrade');

function coinsnap_bitcoin_crowdfunding_webhook_admin_notice() {
    if (!current_user_can('manage_options')) {
        return;
    }
    if (get_option('coinsnap_webhook_resync_required') !== '1') {
        return;
    }

    $url = admin_url('admin.php?page=coinsnap-bitcoin-crowdfunding');
    echo '<div class="notice notice-warning"><p>';
    echo esc_html__('Coinsnap Bitcoin Crowdfunding regenerated the payment webhook secret. Open the plugin settings and click Check so Coinsnap or BTCPay Server receives the new secret. Until then, new contributions may not be recorded.', 'coinsnap-bitcoin-crowdfunding');
    echo ' <a href="' . esc_url($url) . '">';
    echo esc_html__('Open settings', 'coinsnap-bitcoin-crowdfunding');
    echo '</a></p></div>';
}
add_action('admin_notices', 'coinsnap_bitcoin_crowdfunding_webhook_admin_notice');

add_action('init', function() {
    // Setting up and handling custom endpoint for api key redirect from BTCPay Server.
    add_rewrite_endpoint('crowdfunding-btcpay-settings-callback', EP_ROOT);
});

// To be able to use the endpoint without appended url segments we need to do this.
add_filter('request', function($vars) {
    if (isset($vars['crowdfunding-btcpay-settings-callback'])) {
        $vars['crowdfunding-btcpay-settings-callback'] = true;
        $vars['crowdfunding-btcpay-nonce'] = wp_create_nonce('coinsnap-bitcoin-crowdfunding-btcpay-nonce');
    }
    return $vars;
});

if(!function_exists('coinsnap_settings_update')){
    function coinsnap_settings_update($option,$data){
        
        $form_data = get_option($option, []);
        
        foreach($data as $key => $value){
            $form_data[$key] = $value;
        }
        
        update_option($option,$form_data);
    }
}

// Adding template redirect handling for crowdfunding-btcpay-settings-callback.
add_action( 'template_redirect', function(){
    
    global $wp_query;
            
    // Only continue on a crowdfunding-btcpay-settings-callback request.    
    if (!isset( $wp_query->query_vars['crowdfunding-btcpay-settings-callback'])) {
        return;
    }
    
    if(!isset($wp_query->query_vars['crowdfunding-btcpay-nonce']) || !wp_verify_nonce($wp_query->query_vars['crowdfunding-btcpay-nonce'],'coinsnap-bitcoin-crowdfunding-btcpay-nonce')){
        return;
    }

    $CoinsnapBTCPaySettingsUrl = admin_url('/admin.php?page=coinsnap-bitcoin-crowdfunding');

    $client = new Coinsnap_Bitcoin_Crowdfunding_Client();
    
    
            $form_data = get_option('coinsnap_bitcoin_crowdfunding_options', []);

            $btcpay_server_url = $form_data['btcpay_url'];
            $btcpay_api_key  = filter_input(INPUT_POST,'apiKey',FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            $request_url = $btcpay_server_url.'/api/v1/stores';
            $request_headers = ['Accept' => 'application/json','Content-Type' => 'application/json','Authorization' => 'token '.$btcpay_api_key];
            $getstores = $client->remoteRequest('GET',$request_url,$request_headers);
            
            if(!isset($getstores['error'])){
                if (count($getstores['body']) < 1) {
                    $messageAbort = __('Error on verifiying redirected API Key with stored BTCPay Server url. Aborting API wizard. Please try again or continue with manual setup.', 'coinsnap-bitcoin-crowdfunding');
                    //$notice->addNotice('error', $messageAbort);
                    wp_redirect($CoinsnapBTCPaySettingsUrl);
                }
            }
                        
            // Data does get submitted with url-encoded payload, so parse $_POST here.
            if (!empty($_POST)) {
                $data['apiKey'] = filter_input(INPUT_POST,'apiKey',FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? null;
                if(isset($_POST['permissions'])){
                    $permissions = array_map('sanitize_text_field', wp_unslash($_POST['permissions']));
                    if(is_array($permissions)){
                        foreach ($permissions as $key => $value) {
                            $data['permissions'][$key] = sanitize_text_field($permissions[$key] ?? null);
                        }
                    }
                }
            }
    
            if (isset($data['apiKey']) && isset($data['permissions'])) {

                $REQUIRED_PERMISSIONS = [
                    'btcpay.store.canviewinvoices',
                    'btcpay.store.cancreateinvoice',
                    'btcpay.store.canviewstoresettings',
                    'btcpay.store.canmodifyinvoices'
                ];
                $OPTIONAL_PERMISSIONS = [
                    'btcpay.store.cancreatenonapprovedpullpayments',
                    'btcpay.store.webhooks.canmodifywebhooks',
                ];
                
                $btcpay_server_permissions = $data['permissions'];
                
                $permissions = array_reduce($btcpay_server_permissions, static function (array $carry, string $permission) {
			return array_merge($carry, [explode(':', $permission)[0]]);
		}, []);

		// Remove optional permissions so that only required ones are left.
		$permissions = array_diff($permissions, $OPTIONAL_PERMISSIONS);

		$hasRequiredPermissions = (empty(array_merge(array_diff($REQUIRED_PERMISSIONS, $permissions), array_diff($permissions, $REQUIRED_PERMISSIONS))))? true : false;
                
                $hasSingleStore = true;
                $storeId = null;
		foreach ($btcpay_server_permissions as $perms) {
                    if (2 !== count($exploded = explode(':', $perms))) { return false; }
                    if (null === ($receivedStoreId = $exploded[1])) { $hasSingleStore = false; }
                    if ($storeId === $receivedStoreId) { continue; }
                    if (null === $storeId) { $storeId = $receivedStoreId; continue; }
                    $hasSingleStore = false;
		}
                
                if ($hasSingleStore && $hasRequiredPermissions) {

                    coinsnap_settings_update('coinsnap_bitcoin_crowdfunding_options',
                        [
                        'btcpay_api_key' => $data['apiKey'],
                        'btcpay_store_id' => explode(':', $btcpay_server_permissions[0])[1],
                        'provider' => 'btcpay'
                        ]);
                    
                    wp_redirect($CoinsnapBTCPaySettingsUrl);
                    exit();
                }
                else {
                    //$notice->addNotice('error', __('Please make sure you only select one store on the BTCPay API authorization page.', 'coinsnap-bitcoin-crowdfunding'));
                    wp_redirect($CoinsnapBTCPaySettingsUrl);
                    exit();
                }
            }

    //$notice->addNotice('error', __('Error processing the data from Coinsnap. Please try again.', 'coinsnap-bitcoin-crowdfunding'));
    wp_redirect($CoinsnapBTCPaySettingsUrl);
    exit();
});
