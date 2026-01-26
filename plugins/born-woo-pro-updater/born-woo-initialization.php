<?php
// Add an admin menu page
add_action('admin_menu', 'born_woo_pro_updater');

function born_woo_pro_updater() {
    add_menu_page(
        'Born MT WooCommerce Product Updater',
        'Born MT WooCommerce Product Updater',
        'manage_options',
        'born-woo-pro-updater',
        'born_woo_pro_updater_page'
    );
}

function born_woo_pro_updater_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['born_woo_save_settings'])) {
        update_option('BORN_WOO_FTP_IMAGES_IP', sanitize_text_field($_POST['BORN_WOO_FTP_IMAGES_IP']));
        update_option('BORN_WOO_FTP_IMAGES_PORT', sanitize_text_field($_POST['BORN_WOO_FTP_IMAGES_PORT']));
        update_option('BORN_WOO_FTP_IMAGES_USERNAME', sanitize_text_field($_POST['BORN_WOO_FTP_IMAGES_USERNAME']));
        update_option('BORN_WOO_FTP_IMAGES_PASSWORD', sanitize_text_field($_POST['BORN_WOO_FTP_IMAGES_PASSWORD']));
        update_option('BORN_WOO_FTP_IMAGES_PATH', sanitize_text_field($_POST['BORN_WOO_FTP_IMAGES_PATH']));
        update_option('BORN_WOO_FTP_CSV_IP', sanitize_text_field($_POST['BORN_WOO_FTP_CSV_IP']));
        update_option('BORN_WOO_FTP_CSV_PORT', sanitize_text_field($_POST['BORN_WOO_FTP_CSV_PORT']));
        update_option('BORN_WOO_FTP_CSV_USERNAME', sanitize_text_field($_POST['BORN_WOO_FTP_CSV_USERNAME']));
        update_option('BORN_WOO_FTP_CSV_PASSWORD', sanitize_text_field($_POST['BORN_WOO_FTP_CSV_PASSWORD']));
        update_option('BORN_WOO_FTP_CSV_ARTICULO', sanitize_text_field($_POST['BORN_WOO_FTP_CSV_ARTICULO']));
        update_option('BORN_WOO_FTP_CSV_STOCKS', sanitize_text_field($_POST['BORN_WOO_FTP_CSV_STOCKS']));
        update_option('BORN_WOO_FTP_IMAGES_PROTOCOL', sanitize_text_field($_POST['BORN_WOO_FTP_IMAGES_PROTOCOL']));
        update_option('BORN_WOO_FTP_CSV_PROTOCOL', sanitize_text_field($_POST['BORN_WOO_FTP_CSV_PROTOCOL']));
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    $BORN_WOO_FTP_IMAGES_IP = get_option('BORN_WOO_FTP_IMAGES_IP', '');
    $BORN_WOO_FTP_IMAGES_PORT = get_option('BORN_WOO_FTP_IMAGES_PORT', '');
    $BORN_WOO_FTP_IMAGES_USERNAME = get_option('BORN_WOO_FTP_IMAGES_USERNAME', '');
    $BORN_WOO_FTP_IMAGES_PASSWORD = get_option('BORN_WOO_FTP_IMAGES_PASSWORD', '');
    $BORN_WOO_FTP_IMAGES_PATH = get_option('BORN_WOO_FTP_IMAGES_PATH', '');
    $BORN_WOO_FTP_CSV_IP = get_option('BORN_WOO_FTP_CSV_IP', '');
    $BORN_WOO_FTP_CSV_PORT = get_option('BORN_WOO_FTP_CSV_PORT', '');
    $BORN_WOO_FTP_CSV_USERNAME = get_option('BORN_WOO_FTP_CSV_USERNAME', '');
    $BORN_WOO_FTP_CSV_PASSWORD = get_option('BORN_WOO_FTP_CSV_PASSWORD', '');
    $BORN_WOO_FTP_CSV_ARTICULO = get_option('BORN_WOO_FTP_CSV_ARTICULO', '');
    $BORN_WOO_FTP_CSV_STOCKS = get_option('BORN_WOO_FTP_CSV_STOCKS', '');
    $BORN_WOO_FTP_IMAGES_PROTOCOL = get_option('BORN_WOO_FTP_IMAGES_PROTOCOL', '');
    $BORN_WOO_FTP_CSV_PROTOCOL = get_option('BORN_WOO_FTP_CSV_PROTOCOL', '');
    
    echo "<div class='wrap'>
            <h1>Born MT WooCommerce Product Updater Plugin</h1>
            <p>The Born MT WooCommerce Product Updater Plugin plugin is designed to streamline WooCommerce product updates by integrating CSV-based data imports and automated category, tag, and attribute management. This plugin uses scheduled cron jobs to periodically fetch data from predefined CSV files, updating stock levels, product attributes, pricing, and images. Ideal for stores handling large catalogs, this plugin ensures WooCommerce products are regularly updated without manual intervention, enhancing store efficiency and data accuracy.</p>
            <form method='post' action=''>
                <h1>IMAGES Protocol</h1>
                <table class='form-table'>
                    <tr><th><label for='BORN_WOO_FTP_IMAGES_PROTOCOL'>Protocol: (sftp or ftp)</label></th>
                    <td><input type='text' name='BORN_WOO_FTP_IMAGES_PROTOCOL' value='" . esc_attr($BORN_WOO_FTP_IMAGES_PROTOCOL) . "' class='regular-text' /></td></tr>
                </table>
                <br/>
                <h1>FTP/SFTP For Images</h1>
                <table class='form-table'>
                    <tr><th><label for='BORN_WOO_FTP_IMAGES_IP'>IP</label></th>
                    <td><input type='text' name='BORN_WOO_FTP_IMAGES_IP' value='" . esc_attr($BORN_WOO_FTP_IMAGES_IP) . "' class='regular-text' /></td></tr>
                    <tr><th><label for='BORN_WOO_FTP_IMAGES_PORT'>PORT</label></th>
                    <td><input type='text'  name='BORN_WOO_FTP_IMAGES_PORT' value='" . esc_attr($BORN_WOO_FTP_IMAGES_PORT) . "' class='regular-text' /></td></tr>
                    <tr><th><label for='BORN_WOO_FTP_IMAGES_USERNAME'>USERNAME</label></th>
                    <td><input type='text' name='BORN_WOO_FTP_IMAGES_USERNAME' value='" . esc_attr($BORN_WOO_FTP_IMAGES_USERNAME) . "' class='regular-text' /></td></tr>
                    <tr><th><label for='BORN_WOO_FTP_IMAGES_PASSWORD'>PASSWORD</label></th>
                    <td><input type='text'  name='BORN_WOO_FTP_IMAGES_PASSWORD' value='" . esc_attr($BORN_WOO_FTP_IMAGES_PASSWORD) . "' class='regular-text' /></td></tr>
                    <tr><th><label for='BORN_WOO_FTP_IMAGES_PATH'>IMAGES PATH</label></th>
                    <td><input type='text'  name='BORN_WOO_FTP_IMAGES_PATH' value='" . esc_attr($BORN_WOO_FTP_IMAGES_PATH) . "' class='regular-text' /></td></tr>
                </table>
                <h1>CSV Protocol</h1>
                <table class='form-table'>
                    <tr><th><label for='BORN_WOO_FTP_CSV_PROTOCOL'>Protocol: (sftp or ftp)</label></th>
                    <td><input type='text' name='BORN_WOO_FTP_CSV_PROTOCOL' value='" . esc_attr($BORN_WOO_FTP_CSV_PROTOCOL) . "' class='regular-text' /></td></tr>
                </table>
                <h1>FTP/SFTP For CSV</h1>
                <table class='form-table'>
                    <tr><th><label for='BORN_WOO_FTP_CSV_IP'>IP</label></th>
                    <td><input type='text' name='BORN_WOO_FTP_CSV_IP' value='" . esc_attr($BORN_WOO_FTP_CSV_IP) . "' class='regular-text' /></td></tr>
                    <tr><th><label for='BORN_WOO_FTP_CSV_PORT'>PORT</label></th>
                    <td><input type='text'  name='BORN_WOO_FTP_CSV_PORT' value='" . esc_attr($BORN_WOO_FTP_CSV_PORT) . "' class='regular-text' /></td></tr>
                    <tr><th><label for='BORN_WOO_FTP_CSV_USERNAME'>USERNAME</label></th>
                    <td><input type='text' name='BORN_WOO_FTP_CSV_USERNAME' value='" . esc_attr($BORN_WOO_FTP_CSV_USERNAME) . "' class='regular-text' /></td></tr>
                    <tr><th><label for='BORN_WOO_FTP_CSV_PASSWORD'>PASSWORD</label></th>
                    <td><input type='text'  name='BORN_WOO_FTP_CSV_PASSWORD' value='" . esc_attr($BORN_WOO_FTP_CSV_PASSWORD) . "' class='regular-text' /></td></tr>
                    <tr><th><label for='BORN_WOO_FTP_CSV_ARTICULO'>ARTICULO CSV</label></th>
                    <td><input type='text'  name='BORN_WOO_FTP_CSV_ARTICULO' value='" . esc_attr($BORN_WOO_FTP_CSV_ARTICULO) . "' class='regular-text' /></td></tr>
                    <tr><th><label for='BORN_WOO_FTP_CSV_STOCKS'>STOCKS CSV</label></th>
                    <td><input type='text'  name='BORN_WOO_FTP_CSV_STOCKS' value='" . esc_attr($BORN_WOO_FTP_CSV_STOCKS) . "' class='regular-text' /></td></tr>
                </table>
                <p><input type='submit' name='born_woo_save_settings' class='button-primary' value='Save Settings' /></p>
            </form>
        </div>";

        if (isset($_POST['born_woo_reset_syncing'])){
            $sku = $_POST['BORN_WOO_RESYNC_SKU'];
            onclickProductResetSynching($sku);
            echo '<div class="updated"><p>Product SKU: ' . $sku . ' has been successfully added to the queue for integration resync.</p></div>';
        }
    
        echo "
        <br/><br/>
        <div class='wrap'>
            <form method='post' action=''>
                <h1>Resync Product</h1>
                <p>Enter the product SKU to resync images (This will only resync images, price, and quantity).</p>
                <table class='form-table'>
                    <tr><th><label for='BORN_WOO_RESYNC_SKU'>Product SKU:</label></th>
                    <td><input type='text' name='BORN_WOO_RESYNC_SKU'  class='regular-text' required /></td></tr>
                </table>
                <p><input type='submit' name='born_woo_reset_syncing' class='button-primary' value='Submit' /></p>
            </form>
        ";
}

// Allow additional MIME types for uploads
function my_custom_mime_types($mimes) {
    $mimes['JPG']  = 'image/jpeg';
    $mimes['jpg']  = 'image/jpeg';
    $mimes['jpeg'] = 'image/jpeg';
    return $mimes;
}
add_filter('upload_mimes', 'my_custom_mime_types');


function calculate_price($raw_price) {
    $clean_raw_price = floatval(str_replace(',', '.', $raw_price));
    // Check for empty or zero value
    if (empty($clean_raw_price) || $clean_raw_price == 0) {
        $price = null; 
        return $price;
    }
    else {
        // error_log("clean_raw_price: ". $clean_raw_price);
        if ($clean_raw_price >= 0.01 && $clean_raw_price <= 94.99) {
            $price = $clean_raw_price * 3;
        }  
        elseif ($clean_raw_price >= 95.00) {
            $price = $clean_raw_price * 2.5;
        }
        else {
            $price = 0;
            return $price;
        }

    //    error_log("price: ". $price);

        if ( $price > 0 ) {
            // Limit the result to 2 decimal places
            $price = round($price, 2);
            // Handle prices less than 100 (single and two-digit numbers)
            if ($price < 100) {
                // Round to nearest odd number
                $rounded = ceil($price);
                if ($rounded % 2 == 0) {
                    // If the rounded number is even, add 1 to make it odd
                    $rounded++;
                }
                // Return the price with .99 as required
                $return_price = number_format($rounded, 0) . '.99';
            }
            // Handle prices with three digits (100 or more)
            else {
                // Round to the nearest whole number
                $rounded = round($price);
                // Make sure it's an odd number
                if ($rounded % 2 == 0) {
                    $rounded++;
                }
                // Return the price as a whole number without decimals
                $return_price = number_format($rounded, 0);
            }

            // error_log("return_price: ". $return_price);
            return $return_price;
        }

    }
}

function if_product_exist($sku) {
    global $wpdb;
    // Retrieve the product ID if it exists
    $product_id = $wpdb->get_var($wpdb->prepare("
        SELECT post_id FROM {$wpdb->postmeta} 
        WHERE meta_key = '_sku' AND meta_value = %s 
        LIMIT 1
    ", $sku));

    $wpdb->flush();   
    $wpdb->queries = array();

    return $product_id ? $product_id : false; 
}

/**
 * Get or create a tag ID by name
 */
function wc_get_tag_id_by_name($tag_name) {
    $term = term_exists($tag_name, 'product_tag');
    if ($term !== 0 && $term !== null) {
        return $term['term_id'];
    } else {
        $new_term = wp_insert_term($tag_name, 'product_tag');
        if (!is_wp_error($new_term)) {
            return $new_term['term_id'];
        }
    }
    return 0;
}

/**
 * Get or create a category ID by name
 */
function wc_get_category_id_by_name($category_name) {
    $term = term_exists($category_name, 'product_cat');
    if ($term !== 0 && $term !== null) {
        return $term['term_id'];
    } else {
        $new_term = wp_insert_term($category_name, 'product_cat');
        if (!is_wp_error($new_term)) {
            return $new_term['term_id'];
        }
    }
    return 0;
}

/**
 * Sanitize CSV Headers
 */
function sanitizeCSVHeaders($headers) {
    $uniqueHeaders = [];
    $counts = [];
    foreach ($headers as $header) {
        $header = trim($header);
        if (isset($counts[$header])) {
            $counts[$header]++;
            $uniqueHeaders[] = $header . '_' . $counts[$header]; // Append a unique suffix
        } else {
            $counts[$header] = 0;
            $uniqueHeaders[] = $header;
        }
    }
    return $uniqueHeaders;
}

function formatMemory($memory)
{
    if ($memory < 1024) {
        return $memory . ' bytes';
    } elseif ($memory < 1048576) {
        return round($memory / 1024, 2) . ' KB';
    } else {
        return round($memory / 1048576, 2) . ' MB';
    }
}

function get_DB_table_CSV_sku_array($table) {
    global $wpdb;
    $prefix_table_name = $wpdb->prefix . $table;

    // Fetch only SKU column
    $product_skus = $wpdb->get_col("SELECT sku FROM $prefix_table_name");

    return !empty($product_skus) ? array_flip($product_skus) : []; // Convert to associative array
}

function get_WooCommerce_sku_array() {
    global $wpdb;
    $wp = $wpdb->prefix;

    // Fetch only SKU column
    $product_skus = $wpdb->get_col("
        SELECT pm.meta_value 
        FROM {$wp}postmeta pm
        INNER JOIN {$wp}posts p ON pm.post_id = p.ID
        WHERE p.post_type = 'product' 
        AND pm.meta_key = '_sku'
    ");

    return !empty($product_skus) ? array_flip($product_skus) : []; // Convert to associative array
}

function resetCSVtablesSynching() {
    global $wpdb;
    $csv_stocks = $wpdb->prefix . "csv_stocks";
    $stocks_query = $wpdb->get_col(
        "SELECT COUNT(is_sync) FROM $csv_stocks WHERE is_sync = 0"
    );
    if ($stocks_query[0] == 0) {
        $wpdb->query("UPDATE $csv_stocks SET is_sync = 0");
    }
}

function onclickProductResetSynching($sku) {
    global $wpdb;
    $csv_product_images = $wpdb->prefix . "csv_product_images";
    $wpdb->query("UPDATE $csv_product_images SET is_sync = 0 where sku = '$sku' ");
    $csv_stocks = $wpdb->prefix . "csv_stocks";
    $wpdb->query("UPDATE $csv_stocks SET is_sync = 0 where sku = '$sku' ");
}