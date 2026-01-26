<?php 
use League\Csv\Reader;
use League\Csv\Statement;

function attach_images_from_ftp() {
    require_once dirname(__FILE__, 4) . '/wp-load.php'; 
    include_once plugin_dir_path(__FILE__) . 'Class/product-images.php';
    if (!class_exists('WooCommerce')) {
        error_log( "WooCommerce is not active or not properly loaded." ); 
        return;
    } 
    $iDecorProductImage = new iDecorProductImage();
    $iDecorProductImage->attach_images_from_ftp();
}