<?php
/**
 * Plugin Name: Born MT WooCommerce Product Updater Plugin
 * Description: The Born MT WooCommerce Product Updater Plugin plugin is designed to streamline WooCommerce product updates by integrating CSV-based data imports and automated category, tag, and attribute management. This plugin uses scheduled cron jobs to periodically fetch data from predefined CSV files, updating stock levels, product attributes, pricing, and images. Ideal for stores handling large catalogs, this plugin ensures WooCommerce products are regularly updated without manual intervention, enhancing store efficiency and data accuracy.
 * Version: 1.1
 * Author: Born MT
 * Author URI: https://born.mt
 * Text Domain: born-woo-pro-updater
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

include_once plugin_dir_path(__FILE__) . 'product-filter.php';
include_once plugin_dir_path(__FILE__) . 'product-image.php';
include_once plugin_dir_path(__FILE__) . 'download-csv.php';