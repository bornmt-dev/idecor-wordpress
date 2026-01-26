<?php 
use League\Csv\Reader;
use League\Csv\Statement;

function check_and_create_db_csv_articulo_table() {
    global $wpdb;
    // Get the table name with the correct prefix
    $table_name = $wpdb->prefix . 'csv_articulo';
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
            wc_product_id VARCHAR(255) NULL,
            created_date DATETIME NULL,
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
check_and_create_db_csv_articulo_table();


// GET CSV RECORDS
function add_products_to_articulo_db() {
    
    error_log("add_products_to_articulo_db - START OF EXECUTION");
  
    $BORN_WOO_FTP_CSV_ARTICULO = ABSPATH . "csv_files/CLEANED_ARTICULO_ING.csv";

    // Open the CSV file
    $csv = Reader::createFromPath($BORN_WOO_FTP_CSV_ARTICULO, 'r');
    $csv->setHeaderOffset(0);  // If your CSV has headers, set the header offset
    $csv->setDelimiter(';'); // Set the delimiter to semicolon
    
    // Set up a statement to process in chunks (number of rows per chunk)
    $chunkSize = 30;  // Adjust this value based on your memory limitations
    
    $offset = 0;

    $csv_table_existing_skus = get_DB_table_CSV_sku_array('csv_articulo');
    // $woocomerce_existing_skus = get_WooCommerce_sku_array();

    global $wpdb;
    $batch_size = 100; // Adjust as needed
    $insert_values = [];
    $insert_placeholders = [];
    $insert_types = [];
    $table_name = $wpdb->prefix . "csv_articulo"; // Update table name

    while (true) {
        $statement = (new Statement())->offset($offset)->limit($chunkSize);
        $rows = $statement->process($csv);
    
        // Check if rows exist
        if (iterator_count($rows) === 0) {
            break; // Exit the loop when no more rows exist
        }

        // error_log( print_r($rows, true) );
        foreach ($rows as $row) {

            $sku = sanitize_text_field($row['Reference']);
            if ( $sku ) {
              
                $sku_with_ID = sanitize_text_field($row['Reference']) . "ID";

                // if (isset($csv_table_existing_skus[$sku_with_ID]) || isset($woocomerce_existing_skus[$sku_with_ID])) 
                if ( isset($csv_table_existing_skus[$sku_with_ID]) ) 
                {
                    // error_log("ALREADY ADDED: ".$sku_with_ID);
                    continue;
                } 
                else {
                    // error_log("NOT ADDED: ".$sku_with_ID);
                    $raw_name = sanitize_text_field($row['Product description']);
                    $input = $raw_name;
                    $name = preg_replace('/\d.*$/', '', $input);
                    $name = trim($name);

                    if ( $name == "" ) {
                        $name = sanitize_text_field($row['Product description']);
                    }

                    // Prepare the JSON data
                    $row_json = json_encode(array(
                        'product_name' => $name,
                        'product_description' => $raw_name,
                        'price' => sanitize_text_field($row['Price in Euro']), 

                        'quantity' => sanitize_text_field($row['AVAILABLE STOCK']),
                        'upcoming_stocks' => sanitize_text_field($row['Available S.']),
                        'inbound' => sanitize_text_field($row['INBOUND']),
        
                        'tags' => array_filter([
                            sanitize_text_field($row['FAMILY']),
                            sanitize_text_field($row['AMBIENTE'])
                        ]),

                        'categories' => sanitize_text_field($row['TIPO DE ARTICULO']),
                        'dimensions' => [
                            'weight' => sanitize_text_field($row['Product weight (Kg)']),
                            'length' => sanitize_text_field($row['Product Length (cm)']),
                            'width' => sanitize_text_field($row['Width Product (cm)']),
                            'height' => sanitize_text_field($row['High Product (cm)']),
                            'other_dimensions' => [
                                'width' => sanitize_text_field($row['Others Width Product (cm)']),
                                'length' => sanitize_text_field($row['Others Product Length (cm)']),
                                'height' => sanitize_text_field($row['Others High Product (cm)']),
                            ],
                            'diameter' => sanitize_text_field($row['Product diameter and other measures (cm) as sets of baskets etc .'])
                        ],
                        'attributes' => array(
                            'assortment' => sanitize_text_field($row['ASSORTMENT']),
                            'set' => sanitize_text_field($row['SET']),
                            'vmm' => sanitize_text_field($row['VMM']),
                            'units_in_display' => sanitize_text_field($row['UNITS IN DISPLAY']),
                            'unit_box_warehouse' => sanitize_text_field($row['UNIT BOX WAREHOUSE']),
                            'unit_box_client' => sanitize_text_field($row['UNIT BOX CLIENT']),
                            'u_c' => sanitize_text_field($row['U/C']),
                            'pieces' => sanitize_text_field($row['PIECES']),
                            'bar_code' => sanitize_text_field($row['Bar Code']),
                            'division' => sanitize_text_field($row['Division']),
                            'materials' => [
                                'material_1' => sanitize_text_field($row['MATERIAL 1']),
                                'percent_material_1' => sanitize_text_field($row['%MATERIAL 1']),
                                'material_2' => sanitize_text_field($row['MATERIAL 2']),
                                'percent_material_2' => sanitize_text_field($row['%MATERIAL 2']),
                                'material_3' => sanitize_text_field($row['MATERIAL 3']),
                                'percent_material_3' => sanitize_text_field($row['%MATERIAL 3']),
                                'material_4' => sanitize_text_field($row['MATERIAL 4']),
                                'percent_material_4' => sanitize_text_field($row['%MATERIAL 4'])
                            ],
                            'product_finish' => sanitize_text_field($row['PRODUCT FINISH // FINISHING']),
                            'others_width_product_cm' => sanitize_text_field($row['Others Width Product (cm)']),
                            'others_product_lenght_cm' => sanitize_text_field($row['Others Product Length (cm)']),
                            'others_height_product_cm' => sanitize_text_field($row['Others High Product (cm)']),
                            'product_diameterand_other_etc' => sanitize_text_field($row['Product diameter and other measures (cm) as sets of baskets etc .']),
                            'product_weight_kg' => sanitize_text_field($row['Product weight (Kg)']),
                            'capacity' => sanitize_text_field($row['CAPACITY']),
                            'gross_weight' => sanitize_text_field($row['Gross product weight (Kg)']),
                            'hecho_a_mano' => sanitize_text_field($row['Hecho a mano']),
                            'reciclable' => sanitize_text_field($row['RECICLABLE']),
                            'oven_safe' => sanitize_text_field($row['OVEN SAFE']),
                            'microwave_safe' => sanitize_text_field($row['MICROWAVE SAFE']),
                            'vitro_safe' => sanitize_text_field($row['VITRO SAFE']),
                            'induction_safe' => sanitize_text_field($row['INDUCTION SAFE']),
                            'dishwasher_safe' => sanitize_text_field($row['DISHWASHER SAFE']),
                            'seat_high' => sanitize_text_field($row['SEAT HIGH']),
                            'adjustable' => sanitize_text_field($row['Adjustable']),
                            'mum_drawers' => sanitize_text_field($row['Num.Drawers']),
                            'width_drawer_cm' => sanitize_text_field($row['Width drawer(cm)']),
                            'lenght_drawer_cm' => sanitize_text_field($row['Length drawer(cm)']),
                            'high_drawer_cm' => sanitize_text_field($row['High drawer(cm)']),
                            'num_doors' => sanitize_text_field($row['Num.Doors']),
                            'width_door_cm' => sanitize_text_field($row['Width door (cm)']),
                            'lenght_door_cm' => sanitize_text_field($row['Length door (cm)']),
                            'high_door_cm' => sanitize_text_field($row['High door (cm)']),
                            'num_shelves' => sanitize_text_field($row['Num.Shelves']),
                            'distance_shelves' => sanitize_text_field($row['Distance Shelves']),
                            'removable' => sanitize_text_field($row['Removable']),
                            'doble_cara' => sanitize_text_field($row['Doble cara']),
                            'two_men_handling' => sanitize_text_field($row['Two men handing']),
                            'bulb_included' => sanitize_text_field($row['BULB INCLUDED']),
                            'bulb_type' => sanitize_text_field($row['bulb type']),
                            'shade_dimension' => sanitize_text_field($row['SHADE dimension']),
                            'shade_material' => sanitize_text_field($row['SHADE material']),
                            'power_watts' => sanitize_text_field($row['Power(watts)']),
                            'efficiency' => sanitize_text_field($row['Efficiency']),
                            'volts' => sanitize_text_field($row['Volts']),
                            'wire_lenght_cm' => sanitize_text_field($row['WIRE length(cm)']),
                            'num_bulbs' => sanitize_text_field($row['Num.bulbs']),
                            'instructions' => sanitize_text_field($row['Instructions']),
                            'dimensions_photo_mirror' => sanitize_text_field($row['Dimensions: photo / mirror']),
                            'width_frame' => sanitize_text_field($row['Width frame']),
                            'fragrance_candle_air_freshener' => sanitize_text_field($row['Fragrance candle / air freshener']),
                            'antimosquito' => sanitize_text_field($row['Antimosquito']),
                            'burning_time_candle_air_freshener' => sanitize_text_field($row['Burning time candle / air freshener']),
                            'battery_incl' => sanitize_text_field($row['Battery incl.']),
                            'battery_type' => sanitize_text_field($row['Battery type']),
                            'num_battery' => sanitize_text_field($row['Num.battery']),
                            'led' => sanitize_text_field($row['LED']),
                            'caja_regalo' => sanitize_text_field($row['Caja regalo']),
                            'anti_stain' => sanitize_text_field($row['Anti-stain']),
                            'fabric_filing' => sanitize_text_field($row['Fabric / FILLING']),
                            'zip' => sanitize_text_field($row['ZIP']),
                            'gramaje' => sanitize_text_field($row['Gramaje']),
                            'hermetic_closure' => sanitize_text_field($row['Hermetic closure']),
                            'gas_safe' => sanitize_text_field($row['GAS SAFE']),
                            'movimiento' => sanitize_text_field($row['Movimiento']),
                            'peluche' => sanitize_text_field($row['Peluche'])
                        ),
                        'colors' => array_filter([
                            sanitize_text_field($row['Color 1']),
                            sanitize_text_field($row['Color 2']),
                            sanitize_text_field($row['Color 3']),
                            sanitize_text_field($row['Color 4'])
                        ]),
                        'details' => array_filter([
                            sanitize_text_field($row['Detail 1']),
                            sanitize_text_field($row['Detail 2']),
                            sanitize_text_field($row['Detail 3']),
                            sanitize_text_field($row['Detail 4'])
                        ])
                    ));
                     
                     // Collect values for batch insert
                    $insert_values = array_merge($insert_values, [$row_json, $sku_with_ID, current_time('mysql')]);
                    $insert_placeholders[] = "(%s, %s, %s)";
                    $insert_types[] = "%s";
                    $insert_types[] = "%s";
                    $insert_types[] = "%s";

                    // Execute batch insert
                    if (count($insert_values) / 3 >= $batch_size) {
                        $query = "INSERT INTO $table_name (row_json, sku, created_date) VALUES " . implode(", ", $insert_placeholders);
                        $wpdb->query($wpdb->prepare($query, ...$insert_values));

                        // Reset batch
                        $insert_values = [];
                        $insert_placeholders = [];
                    }
    
                    unset($row_json);
                    gc_collect_cycles();
                }
            }
        }
    
        // Increment offset for the next batch
        $offset += $chunkSize;
    }

    // Insert remaining rows
    if (!empty($insert_values)) {
        $query = "INSERT INTO $table_name (row_json, sku, created_date) VALUES " . implode(", ", $insert_placeholders);
        $wpdb->query($wpdb->prepare($query, ...$insert_values));
    }
    
    $wpdb->flush();   
    $wpdb->queries = array();
    unset($table_name, $insert_values, $insert_placeholders, $wpdb, $rows, $row);
    gc_collect_cycles();

    error_log("add_products_to_articulo_db = END OF EXECUTION");
}


function add_articulo_products_to_woocommerce() {
    error_log("add_articulo_products_to_woocommerce = START OF EXECUTION");
    global $wpdb;
    $table_name = $wpdb->prefix . 'csv_articulo';
    $limit = 50;

    $sql_query = "
    SELECT SQL_NO_CACHE row_json, sku
    FROM $table_name
    WHERE is_sync = '0'
    and wc_product_id IS NULL
    ORDER BY id ASC
    LIMIT $limit
    ";

    $woocomerce_existing_skus = get_WooCommerce_sku_array();


    $created_products = []; // Store SKU → WooCommerce product ID mappings

    // Fetch results
    $DatabaseData = $wpdb->get_results($sql_query);
    foreach ($DatabaseData as $data) {

        $sku = $data->sku;
   

        if ( !isset($woocomerce_existing_skus[$sku]) ) {
            error_log("NOT Exist Products: ".$sku);
     
      
            $json_data = json_decode($data->row_json, true); 
            $product = new WC_Product_Simple();

            $product->set_sku($sku);
            $product->set_name( $json_data['product_name'] ); 
            $product->set_description( $json_data['product_description'] ); 
            $product->set_weight( $json_data['dimensions']['weight'] );
            $product->set_length( $json_data['dimensions']['length'] );
            $product->set_width( $json_data['dimensions']['width'] );
            $product->set_height( $json_data['dimensions']['height'] );
      
            $final_price = calculate_price($json_data['price']);
            if ( $final_price ) {
                $product->set_price( $final_price );
                $product->set_regular_price( $final_price );
            }
     
            $available_stock = isset($json_data['quantity']) ? (string) $json_data['quantity'] : ''; 

            $upcoming_stocks = $json_data['upcoming_stocks'];
            $inbound = $json_data['inbound'];

            if ( is_inbound_stock ( $available_stock, $upcoming_stocks, $inbound ) ) {

                $product->set_manage_stock(true);
                $product->set_stock_quantity(is_inbound_stock ( $available_stock, $upcoming_stocks, $inbound ));

            }
            else {
                if ( $available_stock ) {

                    $product->set_manage_stock(true);
                    $product->set_stock_quantity($available_stock);
                    
                }
            }

            $tags = array_filter($json_data['tags']);
            $product->set_tag_ids(array_map('wc_get_tag_id_by_name', $tags));

            $category_value = $json_data['categories'];
            if (!empty($category_value)) {
                $category_id = wc_get_category_id_by_name($category_value);
                if ($category_id) {
                    $product->set_category_ids([$category_id]);
                }
            }

            // Retrieve existing attributes
            $existing_attributes = $product->get_attributes();
            // Define and sanitize product attributes from CSV columns
            $attribute_data = [
                'ASSORTMENT' => $json_data['attributes']['assortment'],
                'SET' => $json_data['attributes']['set'], 
                // 'PPS' => $json_data['attributes']['pps'],
                // 'VMD:MIN UNITS' => $json_data['attributes']['vmd_min_units'],
                'VMM' => $json_data['attributes']['vmm'],
                // 'INBOUND' => $json_data['attributes']['inbound'],
                'UNITS IN DISPLAY' => $json_data['attributes']['units_in_display'],
                'UNIT BOX WAREHOUSE' => $json_data['attributes']['unit_box_warehouse'],
                'UNIT BOX CLIENT' => $json_data['attributes']['unit_box_client'],
                'U/C' => $json_data['attributes']['u_c'],
                'PIECES' => $json_data['attributes']['pieces'],
                'Bar Code' => $json_data['attributes']['bar_code'],
                'Division' => $json_data['attributes']['division'],
                'MATERIAL 1' => $json_data['attributes']['materials']['material_1'],
                'PERCENT MATERIAL 1' => $json_data['attributes']['materials']['percent_material_1'],
                'MATERIAL 2' => $json_data['attributes']['materials']['material_2'],
                'PERCENT MATERIAL 2' => $json_data['attributes']['materials']['percent_material_2'],
                'MATERIAL 3' => $json_data['attributes']['materials']['material_3'],
                'PERCENT MATERIAL 3' => $json_data['attributes']['materials']['percent_material_3'],
                'MATERIAL 4' => $json_data['attributes']['materials']['material_4'],
                'PERCENT MATERIAL 4' => $json_data['attributes']['materials']['percent_material_4'],
                'PRODUCT FINISH // FINISHING' => $json_data['attributes']['product_finish'],
                'Others Width Product (cm)' => $json_data['attributes']['others_width_product_cm'],
                'Others Product Length (cm)' => $json_data['attributes']['others_product_lenght_cm'],
                'Others High Product (cm)' => $json_data['attributes']['others_height_product_cm'],
                'Product diameter and other measures (cm) as sets of baskets etc .' => $json_data['attributes']['product_diameterand_other_etc'],
                'CAPACITY' => $json_data['attributes']['capacity'],
                'Product weight (Kg)' => $json_data['attributes']['product_weight_kg'],
                'Gross product weight (Kg)' => $json_data['attributes']['gross_weight'], 
                'Hecho a mano' => $json_data['attributes']['hecho_a_mano'],
                'RECICLABLE' => $json_data['attributes']['reciclable'],
                'SEAT HIGH' => $json_data['attributes']['seat_high'],
                'Adjustable' => $json_data['attributes']['adjustable'],
                'Num.Drawers' => $json_data['attributes']['mum_drawers'],
                'Width drawer(cm)' => $json_data['attributes']['width_drawer_cm'],
                'Length drawer(cm)' => $json_data['attributes']['lenght_drawer_cm'],
                'High drawer(cm)' => $json_data['attributes']['high_drawer_cm'],
                'Num.Doors' => $json_data['attributes']['num_doors'],
                'Width door (cm)' => $json_data['attributes']['width_door_cm'],
                'Length door (cm)' => $json_data['attributes']['lenght_door_cm'],
                'High door (cm)' => $json_data['attributes']['high_door_cm'],
                'Num.Shelves' => $json_data['attributes']['num_shelves'],
                'Distance Shelves' => $json_data['attributes']['distance_shelves'],
                'Removable' => $json_data['attributes']['removable'],
                'Doble cara' => $json_data['attributes']['doble_cara'],
                'Two men handing' => $json_data['attributes']['two_men_handling'],
                'BULB INCLUDED' => $json_data['attributes']['bulb_included'],
                'bulb type' => $json_data['attributes']['bulb_type'],
                'SHADE dimension' => $json_data['attributes']['shade_dimension'],
                'SHADE material' => $json_data['attributes']['shade_material'],
                'Power(watts)' => $json_data['attributes']['power_watts'],
                'Efficiency' => $json_data['attributes']['efficiency'],
                'Volts' => $json_data['attributes']['volts'],
                'WIRE length(cm)' => $json_data['attributes']['wire_lenght_cm'],
                'Num.bulbs' => $json_data['attributes']['num_bulbs'],
                'Instructions' => $json_data['attributes']['instructions'],
                'Dimensions: photo / mirror' => $json_data['attributes']['dimensions_photo_mirror'],
                'Width frame' => $json_data['attributes']['width_frame'],
                'Fragrance candle / air freshener' => $json_data['attributes']['fragrance_candle_air_freshener'],
                'Antimosquito' => $json_data['attributes']['antimosquito'],
                'Burning time candle / air freshener' => $json_data['attributes']['burning_time_candle_air_freshener'],
                'Battery incl.' => $json_data['attributes']['battery_incl'],
                'Battery type' => $json_data['attributes']['battery_type'],
                'Num.battery' => $json_data['attributes']['num_battery'],
                'LED' => $json_data['attributes']['led'],
                'Caja regalo' => $json_data['attributes']['caja_regalo'],
                'Anti-stain' => $json_data['attributes']['anti_stain'],
                'Fabric / FILLING' => $json_data['attributes']['fabric_filing'],
                'ZIP' => $json_data['attributes']['zip'],
                'Gramaje' => $json_data['attributes']['gramaje'],
                'OVEN SAFE' => $json_data['attributes']['oven_safe'],
                'MICROWAVE SAFE' => $json_data['attributes']['microwave_safe'],
                'VITRO SAFE' => $json_data['attributes']['vitro_safe'],
                'INDUCTION SAFE' => $json_data['attributes']['induction_safe'],
                'Hermetic closure' => $json_data['attributes']['hermetic_closure'],
                'GAS SAFE' => $json_data['attributes']['gas_safe'],
                'Movimiento' => $json_data['attributes']['movimiento'],
                'Peluche' => $json_data['attributes']['peluche'],
                'DISHWASHER SAFE' => $json_data['attributes']['dishwasher_safe'],
                'Color' => array_filter($json_data['colors']),   
                'Details' => array_filter($json_data['details'])  
            ];

            // Loop through each attribute to update or add it to existing attributes
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

            // Update product with all attributes
            $product->set_attributes($existing_attributes);
            $product->save();

            $wc_product_id = $product->get_id(); //  Get WooCommerce product ID
            $created_products[$sku] = $wc_product_id; //  Store for later batch update

            if ( is_inbound_stock ( $available_stock, $upcoming_stocks, $inbound ) ) {
                update_post_meta($product->get_id(), 'stock_inbound_date', sanitize_text_field($inbound));
            }
            else {
                update_post_meta($product->get_id(), 'stock_inbound_date', null );
            }
            
            unset($product, $existing_attributes, $json_data, $attribute_data);

            error_log(" ADDED: ".$sku);
        }
        else {
            error_log(" Exist Products: ".$sku);
            continue;
        }
      
    }
    unset($DatabaseData, $data);

    // Perform efficient bulk update for wc_product_id + is_sync + synched_date
    if (!empty($created_products)) {
        $case_statements = [];
        $sku_list = [];

        foreach ($created_products as $sku => $wc_product_id) {
            $sku_escaped = esc_sql($sku);
            $case_statements[] = "WHEN sku = '{$sku_escaped}' THEN {$wc_product_id}";
            $sku_list[] = "'{$sku_escaped}'";
        }

        $case_sql = implode(' ', $case_statements);
        $sku_list_str = implode(',', $sku_list);

        $sql = "
            UPDATE $table_name
            SET 
                wc_product_id = CASE $case_sql END,
                is_sync = '1',
                synched_date = NOW()
            WHERE sku IN ($sku_list_str)
        ";

        $wpdb->query($sql);
    }

    $wpdb->flush();   
    $wpdb->queries = array();
    unset($wpdb);   
    gc_collect_cycles();

    error_log("add_articulo_products_to_woocommerce = END OF EXECUTION");
}


function update_articulo_products_to_woocommerce() {
    error_log("update_articulo_products_to_woocommerce = END OF EXECUTION");

    error_log("update_articulo_products_to_woocommerce = END OF EXECUTION");
}


function is_inbound_stock ( $available_stock, $upcoming_stocks, $inbound ) {
    if ( (int)$upcoming_stocks > 0 && !empty($inbound) && $available_stock != 0) {
        return $upcoming_stocks;
    }
    else {
        return false;
    }
}


function bw_pair_product_id() {
    error_log("bw_pair_product_id = START OF EXECUTION");

    global $wpdb;
    $wp = $wpdb->prefix;
    $csv_articulo = $wp . 'csv_articulo';
    $posts = $wp . 'posts';
    $postmeta = $wp . 'postmeta';

    // Get SKUs that do not have a WooCommerce product ID yet
    $articulo_query = "
        SELECT sku 
        FROM $csv_articulo 
        WHERE wc_product_id IS NULL
        LIMIT 5000
    ";

    $articulo_sku = $wpdb->get_col($articulo_query);

    if (empty($articulo_sku)) {
        error_log("bw_pair_product_id = No SKUs found to process");
        return;
    }

    // Properly escape and prepare SKU list for the IN() clause
    $placeholders = implode(',', array_fill(0, count($articulo_sku), '%s'));

    $woocommerce_query = $wpdb->prepare("
        SELECT p.ID, pm.meta_value AS sku
        FROM $posts p
        INNER JOIN $postmeta pm ON p.ID = pm.post_id
        WHERE pm.meta_key = '_sku'
        AND pm.meta_value IN ($placeholders)
    ", ...$articulo_sku);

    $results = $wpdb->get_results($woocommerce_query);

    if (empty($results)) {
        error_log("bw_pair_product_id = No matching WooCommerce products found");
        return;
    }

    // Build CASE-based bulk update
    $cases = [];
    $sku_list = [];
    foreach ($results as $row) {
        $sku = esc_sql($row->sku);
        $product_id = (int) $row->ID;
        $cases[] = "WHEN '$sku' THEN $product_id";
        $sku_list[] = "'$sku'";
        error_log("Matched SKU: $sku with Product ID: $product_id");
    }

    $case_sql = implode(' ', $cases);
    $sku_in = implode(',', $sku_list);

    // Single efficient bulk update query
    $update_sql = "
        UPDATE $csv_articulo
        SET wc_product_id = CASE sku
            $case_sql
        END,
        is_sync = 1,
        synched_date = now()
        WHERE sku IN ($sku_in)
    ";

    $rows = $wpdb->query($update_sql);
    error_log("bw_pair_product_id = Bulk update executed. Rows updated: $rows");

    error_log("bw_pair_product_id = END OF EXECUTION");
}


function bw_update_woocomerce_category() {
    error_log("bw_update_woocomerce_category = START OF EXECUTION");

    global $wpdb;
    $table = $wpdb->prefix . 'csv_articulo';

    // Fetch only the necessary fields
    $results = $wpdb->get_results("
        SELECT sku, wc_product_id,
               JSON_UNQUOTE(JSON_EXTRACT(row_json, '$.categories')) AS categories,
               JSON_UNQUOTE(JSON_EXTRACT(row_json, '$.tags')) AS tags
        FROM $table
        WHERE wc_product_id IS NOT NULL
          AND is_sync = 0
        LIMIT 200
    ");

    if (empty($results)) {
        error_log("No unsynced rows found.");
        return;
    }

    $processed_skus = [];
    $term_cache = [
        'product_cat' => [],
        'product_tag' => [],
    ];

    foreach ($results as $row) {
        $product_id = (int)$row->wc_product_id;
        if (!$product_id) continue;

        $processed_skus[] = "'" . esc_sql($row->sku) . "'";

        // --- Categories ---
        $category_name = trim($row->categories);
        $category_ids = [];

        if (!empty($category_name)) {
            if (!isset($term_cache['product_cat'][$category_name])) {
                $term = term_exists($category_name, 'product_cat');
                if (!$term) {
                    $term = wp_insert_term($category_name, 'product_cat');
                }
                if (!is_wp_error($term)) {
                    $term_cache['product_cat'][$category_name] = (int)$term['term_id'];
                }
            }
            $category_ids[] = $term_cache['product_cat'][$category_name];
        }

        // --- Tags ---
        $tags = json_decode($row->tags, true);
        $tag_ids = [];

        if (is_array($tags)) {
            foreach ($tags as $tag_name) {
                $tag_name = trim($tag_name);
                if ($tag_name === '') continue;

                if (!isset($term_cache['product_tag'][$tag_name])) {
                    $term = term_exists($tag_name, 'product_tag');
                    if (!$term) {
                        $term = wp_insert_term($tag_name, 'product_tag');
                    }
                    if (!is_wp_error($term)) {
                        $term_cache['product_tag'][$tag_name] = (int)$term['term_id'];
                    }
                }
                $tag_ids[] = $term_cache['product_tag'][$tag_name];
            }
        }

        // --- Assign directly (faster than wc_get_product + ->save()) ---
        if (!empty($category_ids)) {
            wp_set_object_terms($product_id, $category_ids, 'product_cat', false);
        }
        if (!empty($tag_ids)) {
            wp_set_object_terms($product_id, $tag_ids, 'product_tag', false);
        }
    }

    // --- Bulk mark as synced ---
    if (!empty($processed_skus)) {
        $sku_list = implode(',', $processed_skus);
        $wpdb->query("
            UPDATE $table
            SET is_sync = 1,
                synched_date = NOW()
            WHERE sku IN ($sku_list)
        ");
    }

    error_log("bw_update_woocomerce_category = END OF EXECUTION. Processed " . count($processed_skus) . " items.");
}
