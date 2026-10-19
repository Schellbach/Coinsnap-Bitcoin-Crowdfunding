<?php
if (!defined('ABSPATH')){ exit; }
if (!defined('WP_UNINSTALL_PLUGIN')) { exit; }

global $wpdb;
$coinsnap_bitcoin_crowdfunding_tables = array(
    $wpdb->prefix . 'voting_payments',
);

foreach ($coinsnap_bitcoin_crowdfunding_tables as $coinsnap_bitcoin_crowdfunding_table) {
    $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %s",$coinsnap_bitcoin_crowdfunding_table));
}

$coinsnap_bitcoin_crowdfunding_options = array(
    'coinsnap_bitcoin_crowdfunding_options',
    'bitcoin_donation_forms_options',
    'coinsnap_webhook_secret',
    'coinsnap_webhook_resync_required',
    'coinsnap_bitcoin_crowdfunding_db_version'
);

foreach ($coinsnap_bitcoin_crowdfunding_options as $coinsnap_bitcoin_crowdfunding_option) {
    delete_option($coinsnap_bitcoin_crowdfunding_option);
}
