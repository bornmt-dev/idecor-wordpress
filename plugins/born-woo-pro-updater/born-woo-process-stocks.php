<?php 
use League\Csv\Reader;
use League\Csv\Statement;

function check_and_create_db_csv_stocks_table() {
    global $wpdb;
    // Get the table name with the correct prefix
    $table_name = $wpdb->prefix . 'csv_stocks';
    // Check if the table exists
    $query = $wpdb->prepare(
        "SHOW TABLES LIKE %s",
        $table_name
    );
    $table_exists = $wpdb->get_var($query);

    if ($table_exists === null) {
        // SQL statement to create the table
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            row_json JSON NOT NULL,
            is_sync TINYINT(1) NOT NULL DEFAULT 0,
            sku VARCHAR(255) NOT NULL,
            created_date DATETIME NULL,
            updated_date DATETIME NULL,
            synched_date DATETIME NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        // Include the upgrade file to use dbDelta
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        // Create the table
        dbDelta($sql);
    } 

    $wpdb->flush();   
    $wpdb->queries = array();
}
// Call the function
check_and_create_db_csv_stocks_table();


function add_stocks_to_db() { 
}

function same_stocks_value($sku, $assortment, $price, $quantity, $upcoming_stocks, $inbound) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'csv_stocks';
    $limit = 3;   
    $sql_query = "
        SELECT row_json, sku
        FROM $table_name
        WHERE sku = '$sku'
        LIMIT $limit
    ";

    $DatabaseData = $wpdb->get_results($sql_query);
    foreach ($DatabaseData as $data) {

        $json_data = json_decode($data->row_json, true); 

        if ( 
            $sku == $data->sku && 
            $assortment == $json_data['assortment']&&
            $price == $json_data['price'] &&
            $inbound == $json_data['inbound'] &&
            $upcoming_stocks == $json_data['upcoming_stocks'] &&
            $quantity == $json_data['quantity']
            ) {
            return true;
        }
        else {
            return false;
        }
    } 
    $wpdb->flush();   
    $wpdb->queries = array();
}


function update_woocommerce_stocks () {

    error_log("update_woocommerce_stocks = START OF EXECUTION");
    global $wpdb;
    $wp_ = $wpdb->prefix;

    $csv_stocks    = $wp_ . 'csv_stocks';
    $table_posts   = $wp_ . 'posts';
    $table_postmeta= $wp_ . 'postmeta';

    $limit = 200; 
    $sql_query = "
        SELECT {$table_posts}.ID AS pid, {$csv_stocks}.row_json, {$table_postmeta}.meta_value
        FROM {$table_posts} 
        LEFT JOIN {$table_postmeta} 
            ON {$table_posts}.ID = {$table_postmeta}.post_id
        LEFT JOIN {$csv_stocks} 
            ON {$table_postmeta}.meta_value = {$csv_stocks}.sku
        WHERE {$table_postmeta}.meta_key = '_sku'
        AND {$csv_stocks}.is_sync = '0' 
        ORDER BY {$csv_stocks}.id ASC
        LIMIT $limit
    ";


    $DatabaseData = $wpdb->get_results($sql_query);

    $update_values = [];
    
    foreach ($DatabaseData as $data) { 

        $update_values[] = $data->meta_value;

        $product = wc_get_product($data->pid);

        if (!$product) {
            error_log("Skipping invalid product ID: {$data->pid}");
            continue;
        }

        $json_data = json_decode($data->row_json, true);  

        $price = isset($json_data['price']) ? $json_data['price'] : null;
        $quantity = isset($json_data['quantity']) ? $json_data['quantity'] : null;      
        
        $inbound = isset($json_data['inbound']) ? $json_data['inbound'] : null;    
        $upcoming_stocks = isset($json_data['upcoming_stocks']) ? $json_data['upcoming_stocks'] : null;    
 
        if ($price !== "0" && $price !== null) {
            $final_price = calculate_price($price);
            $product->set_regular_price($final_price);
            $product->set_price($final_price);
            $product->set_status('publish');
        }
 
        if ($price === "0" || $price === null) {
            $product->set_status('draft');
        } 

        $product->set_manage_stock(true);

        if ( $inbound && $upcoming_stocks && $quantity == 0) {
            $product->set_stock_quantity($upcoming_stocks);
        }
        else {
            $product->set_stock_quantity($quantity);
        }
      

        // Retrieve and update attributes
        $existing_attributes = $product->get_attributes();
        $attribute_data = [
            'ASSORTMENT' => $json_data['assortment']
        ];

        foreach ($attribute_data as $name => $value) {
            if (!empty($value)) {  // Only add non-empty attributes
                $attribute = new WC_Product_Attribute();
                $attribute->set_name($name);
                $attribute->set_options((array)$value);
                $attribute->set_visible(true);
                $attribute->set_variation(false);
                $existing_attributes[$name] = $attribute;
            }
        } 

        $product->set_attributes($existing_attributes);
        $product->save();

        if ( $inbound && $upcoming_stocks && $quantity == 0) {
            update_post_meta($product->get_id(), 'stock_inbound_date', sanitize_text_field($inbound));
        }
        else {
            update_post_meta($product->get_id(), 'stock_inbound_date', null);
        }
        

        unset($existing_attributes, $product);
    } 
    

    // Update the CSV stocks table for the processed SKUs.
    if (!empty($update_values)) {
        // Create a list of placeholders for each SKU.
        $placeholders = implode(', ', array_fill(0, count($update_values), '%s'));
        $update_sql = $wpdb->prepare(
            "UPDATE $csv_stocks SET is_sync = '1', synched_date = NOW() WHERE sku IN ($placeholders)",
            $update_values
        );
        $wpdb->query($update_sql);
    }
    
    unset($update_values, $placeholders);
    gc_collect_cycles();
    error_log("update_woocommerce_stocks = END OF EXECUTION");
}

function add_inbound_data_to_stocks() {
    error_log("add_inbound_data_to_stocks = START OF EXECUTION");

    $BORN_WOO_FTP_CSV_ARTICULO = ABSPATH . "csv_files/CLEANED_ARTICULO_ING.csv";

    // Open CSV
    $csv = Reader::createFromPath($BORN_WOO_FTP_CSV_ARTICULO, 'r');
    $csv->setHeaderOffset(0);
    $csv->setDelimiter(';');

    $chunkSize = 500; // larger chunks are faster, adjust based on memory
    $offset = 0;

    global $wpdb;
    $wp_ = $wpdb->prefix;
    $csv_stocks = $wp_ . 'csv_stocks';

    // Prepare bulk inserts/updates
    $insert_data = [];
    $update_data = [];

    while (true) {
        $statement = (new Statement())->offset($offset)->limit($chunkSize);
        $rows = iterator_to_array($statement->process($csv));

        if (empty($rows)) {
            break;
        }

        // Collect SKUs in the chunk
        $skus = [];
        foreach ($rows as $row) {
            $sku = sanitize_text_field($row['Reference'])."ID";
            $assortment = sanitize_text_field($row['ASSORTMENT']);
            $quantity = sanitize_text_field($row['AVAILABLE STOCK']);
            $upcoming_stocks = sanitize_text_field($row['Available S.']);
            $inbound = sanitize_text_field($row['INBOUND']);
            $price = sanitize_text_field($row['Price in Euro']);

            if ($sku && !same_stocks_value($sku, $assortment, $price, $quantity, $upcoming_stocks, $inbound) ) {
                $skus[] = $sku;
            }
        }

        // Preload existing SKUs in the chunk
        $existing_skus = [];
        if (!empty($skus)) {
            $placeholders = implode(',', array_fill(0, count($skus), '%s'));
            $query = $wpdb->prepare(
                "SELECT sku, id FROM $csv_stocks WHERE sku IN ($placeholders)",
                ...$skus
            );
            $results = $wpdb->get_results($query);
            foreach ($results as $r) {
                $existing_skus[$r->sku] = $r->id;
            }
        }

        $now = current_time('mysql');

        foreach ($rows as $row) {
            $sku = sanitize_text_field($row['Reference'])."ID";
            $assortment = sanitize_text_field($row['ASSORTMENT']);
            $quantity = sanitize_text_field($row['AVAILABLE STOCK']);
            $upcoming_stocks = sanitize_text_field($row['Available S.']);
            $inbound = sanitize_text_field($row['INBOUND']);
            $price = sanitize_text_field($row['Price in Euro']);

            if ($sku && !same_stocks_value($sku, $assortment, $price, $quantity, $upcoming_stocks, $inbound) ) {

                $new_json = json_encode([
                    'assortment'      => $assortment,
                    'price'           => $price,
                    'quantity'        => $quantity,
                    'upcoming_stocks' => $upcoming_stocks,
                    'inbound'         => $inbound
                ]);

                if (isset($existing_skus[$sku])) {
                    // Prepare update
                    $update_data[] = [
                        'sku'       => $sku,   
                        'id'       => $existing_skus[$sku],
                        'row_json' => $new_json,
                        'is_sync'   => 0,
                        'updated'  => $now,
                    ];
                } else {
                    // Prepare insert
                    $insert_data[] = [
                        'sku'          => $sku,
                        'row_json'     => $new_json,
                        'is_sync'   => 0,
                        'created_date' => $now,
                        'updated_date' => $now,
                    ];
                }
            }
        }

        $offset += $chunkSize;
        gc_collect_cycles();
    }


    if (!empty($insert_data) || !empty($update_data)) {

        $values = [];
        $placeholders = [];
    
        foreach (array_merge($insert_data, $update_data) as $row) {
            $placeholders[] = "(%s, %s, %d, %s, %s)";
            $values[] = $row['sku'];
            $values[] = $row['row_json'];
            $values[] = $row['is_sync'];
            $values[] = $row['created_date'] ?? $row['updated']; // created_date if new, updated for updates
            $values[] = $row['updated_date'] ?? $row['updated'];
        }
    
        $query = "
            INSERT INTO $csv_stocks (sku, row_json, is_sync, created_date, updated_date)
            VALUES " . implode(',', $placeholders) . "
            ON DUPLICATE KEY UPDATE
                row_json = VALUES(row_json),
                is_sync = VALUES(is_sync),
                updated_date = VALUES(updated_date)
        ";
    
        $wpdb->query($wpdb->prepare($query, ...$values));
    }
    

    error_log("add_inbound_data_to_stocks = END OF EXECUTION");
}



function bw_set_products_to_draft () {
    error_log("bw_set_products_to_draft = START OF EXECUTION");

    $BORN_WOO_FTP_CSV_ARTICULO = ABSPATH . "csv_files/STOCKS.csv";

    // Open CSV
    $csv = Reader::createFromPath($BORN_WOO_FTP_CSV_ARTICULO, 'r');
    $csv->setHeaderOffset(0);
    $csv->setDelimiter(';');

    $chunkSize = 500; // larger chunks are faster, adjust based on memory
    $offset = 0;

    $sku_from_CSV = [];

    while (true) {
        $statement = (new Statement())->offset($offset)->limit($chunkSize);
        $rows = iterator_to_array($statement->process($csv));

        if (empty($rows)) {
            break;
        }

        foreach ($rows as $row) {
            $sku = sanitize_text_field($row['REFERENCIA'])."ID";
            $sku_from_CSV[] = $sku;
        }

        $offset += $chunkSize;
    }

    global $wpdb;
    $wp_ = $wpdb->prefix;

    $table_posts    = $wp_ . 'posts';
    $table_postmeta = $wp_ . 'postmeta';


    $sql_query = "
        SELECT DISTINCT($table_postmeta.meta_value)
        FROM $table_posts
        LEFT JOIN $table_postmeta
            ON $table_posts.ID = $table_postmeta.post_id
        WHERE $table_posts.post_type = 'product'
        AND $table_postmeta.meta_key = '_sku'

    ";

    $sku_to_draft = [];
    $sku_to_publish = [];

    $DatabaseData = $wpdb->get_results($sql_query);

    foreach ($DatabaseData as $data) {
        if (!in_array($data->meta_value, $sku_from_CSV, true)) {
            $sku_to_draft[] = $data->meta_value;
        }
        else {
            $sku_to_publish[] = $data->meta_value;
        }
    }

    if (!empty($sku_to_draft)) {

        // Prepare SKUs for SQL IN() clause safely
        $placeholders = implode(',', array_fill(0, count($sku_to_draft), '%s'));

        $query = $wpdb->prepare("
            UPDATE $table_posts
            SET post_status = 'draft'
            WHERE ID IN (
                SELECT post_id
                FROM $table_postmeta
                WHERE meta_key = '_sku'
                AND meta_value IN ($placeholders)
            )
        ", $sku_to_draft);

        $wpdb->query($query);

        // error_log("Products set to draft: " . count($sku_to_draft));
        // error_log(print_r($sku_to_draft, true));

    } else {
        // error_log("No products to set to draft.");
    }


    // if (!empty($sku_to_publish)) {
    //     // Prepare SKUs for SQL IN() clause safely
    //     $placeholders = implode(',', array_fill(0, count($sku_to_publish), '%s'));
    //     $query = $wpdb->prepare("
    //         UPDATE $table_posts
    //         SET post_status = 'publish'
    //         WHERE ID IN (
    //             SELECT post_id
    //             FROM $table_postmeta
    //             WHERE meta_key = '_sku'
    //             AND meta_value IN ($placeholders)
    //         )
    //     ", $sku_to_publish);
    //     $wpdb->query($query);
    //     // error_log("Products set to publish: " . count($sku_to_publish));
    //     // error_log(print_r($sku_to_publish, true));
    // } else {
    //     // error_log("No products to set to publish.");
    // }

    error_log("bw_set_products_to_draft = END OF EXECUTION");
}