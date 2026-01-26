<?php 
use League\Csv\Reader;
use League\Csv\Statement;

include_once plugin_dir_path(__FILE__) . 'Class/resync.php';

$iDecorResyncProducts = new iDecorResyncProducts();
$iDecorResyncProducts->createResyncDatabase();

function bw_add_data_to_resync_db () {
    require_once dirname(__FILE__, 4) . '/wp-load.php'; 
    include_once plugin_dir_path(__FILE__) . 'Class/resync.php';
    if (!class_exists('WooCommerce')) {
        error_log( "WooCommerce is not active or not properly loaded." ); 
        return;
    } 
    $iDecorResyncProducts = new iDecorResyncProducts();
    $iDecorResyncProducts->bw_add_data_to_resync_db();
}


function bw_pair_ids_to_resync () {
    require_once dirname(__FILE__, 4) . '/wp-load.php'; 
    include_once plugin_dir_path(__FILE__) . 'Class/resync.php';
    if (!class_exists('WooCommerce')) {
        error_log( "WooCommerce is not active or not properly loaded." ); 
        return;
    } 
    $iDecorResyncProducts = new iDecorResyncProducts();
    $iDecorResyncProducts->bw_pair_ids_to_resync();
}

function bw_update_woo_pro_to_resync () {
    require_once dirname(__FILE__, 4) . '/wp-load.php'; 
    include_once plugin_dir_path(__FILE__) . 'Class/resync.php';
    if (!class_exists('WooCommerce')) {
        error_log( "WooCommerce is not active or not properly loaded." ); 
        return;
    } 
    $iDecorResyncProducts = new iDecorResyncProducts();
    $iDecorResyncProducts->bw_update_woo_pro_to_resync();
}

