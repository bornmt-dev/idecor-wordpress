<?php
use League\Csv\Reader;
use League\Csv\Statement;

class iDecorResyncProducts {

    public function __construct() {}

    public function createResyncDatabase() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'csv_resync_data';

        $query = $wpdb->prepare("SHOW TABLES LIKE %s", $table_name);
        $table_exists = $wpdb->get_var($query);

        if ($table_exists === null) {
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
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }

        // Remove duplicate SKUs before adding UNIQUE constraint
        $duplicates = $wpdb->get_results("
            SELECT sku, MIN(id) AS keep_id
            FROM $table_name
            GROUP BY sku
            HAVING COUNT(*) > 1
        ");

        if (!empty($duplicates)) {
            foreach ($duplicates as $dup) {
                $wpdb->query("
                    DELETE FROM $table_name 
                    WHERE sku = '{$dup->sku}' AND id != {$dup->keep_id}
                ");
            }
        }

        // Ensure UNIQUE index on SKU
        $index_exists = $wpdb->get_var("
            SELECT COUNT(1) 
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE table_schema = DATABASE() 
              AND table_name = '{$table_name}' 
              AND index_name = 'unique_sku'
        ");
        if (!$index_exists) {
            $wpdb->query("ALTER TABLE $table_name ADD UNIQUE KEY unique_sku (sku)");
        }

        $wpdb->flush();
        $wpdb->queries = array();
    }

    public function bw_add_data_to_resync_db() {
        global $wpdb;

        error_log("bw_add_data_to_resync_db - START OF EXECUTION");

        $table_name = $wpdb->prefix . 'csv_resync_data';
        $BORN_WOO_FTP_CSV_ARTICULO = ABSPATH . "csv_files/CLEANED_ARTICULO_ING.csv";

        if (!file_exists($BORN_WOO_FTP_CSV_ARTICULO)) {
            error_log("CSV file not found: $BORN_WOO_FTP_CSV_ARTICULO");
            return;
        }

        $csv = Reader::createFromPath($BORN_WOO_FTP_CSV_ARTICULO, 'r');
        $csv->setHeaderOffset(0);
        $csv->setDelimiter(';');

        $chunkSize = 500;
        $offset = 0;
        $total_inserted = 0;

        while (true) {
            $statement = (new Statement())->offset($offset)->limit($chunkSize);
            $rows = iterator_to_array($statement->process($csv), false);
            if (empty($rows)) break;

            $values = [];

            foreach ($rows as $row) {
                if (empty($row['Reference'])) continue;

                $sku = sanitize_text_field($row['Reference']."ID");
                $wc_product_id = 0;

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

                $is_sync = 0;

                $values[] = $wpdb->prepare(
                    "(%s, %s, %s, %d, NOW(), NULL)",
                    $sku, $wc_product_id, $row_json, $is_sync
                );
            }

            if (!empty($values)) {
                $wpdb->query('START TRANSACTION');

                $query = "
                    INSERT INTO $table_name
                    (sku, wc_product_id, row_json, is_sync, created_date, synched_date)
                    VALUES " . implode(',', $values) . "
                    ON DUPLICATE KEY UPDATE
                        wc_product_id = VALUES(wc_product_id),
                        row_json = VALUES(row_json),
                        is_sync = VALUES(is_sync),
                        synched_date = NULL
                ";

                $result = $wpdb->query($query);

                if ($result === false) {
                    error_log("MySQL Error: " . $wpdb->last_error);
                    $wpdb->query('ROLLBACK');
                } else {
                    $wpdb->query('COMMIT');
                    $total_inserted += count($values);
                    error_log("Processed chunk: offset={$offset}, rows=" . count($values));
                }
            }

            $offset += $chunkSize;
        }

        error_log("Total processed rows: $total_inserted");
        error_log("bw_add_data_to_resync_db = END OF EXECUTION");
    }

    public function bw_pair_ids_to_resync () { 
        error_log("bw_pair_ids_to_resync = START OF EXECUTION");

        global $wpdb;
        $wp = $wpdb->prefix;
        $csv_resync_data = $wp . 'csv_resync_data';
        $posts = $wp . 'posts';
        $postmeta = $wp . 'postmeta';

        // Get SKUs that do not have a WooCommerce product ID yet
        $articulo_query = "
            SELECT sku 
            FROM $csv_resync_data 
            WHERE wc_product_id = 0
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
            and post_type = 'product'
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
            UPDATE $csv_resync_data
            SET wc_product_id = CASE sku
                $case_sql
            END
            WHERE sku IN ($sku_in)
        ";

        $rows = $wpdb->query($update_sql);
        
        error_log("bw_pair_ids_to_resync = END OF EXECUTION");
    }


    public function bw_update_woo_pro_to_resync() {
        error_log("bw_update_woo_pro_to_resync = START OF EXECUTION");

        global $wpdb;
        $table_name = $wpdb->prefix . 'csv_resync_data';
        $limit = 50; // Only process 100 products per run

        // Fetch products that are not yet synced
        $sql_query = "
            SELECT SQL_NO_CACHE row_json, sku, wc_product_id
            FROM $table_name
            WHERE is_sync = '0'
            AND wc_product_id != '0'
            ORDER BY id ASC
            LIMIT $limit
        ";

        $DatabaseData = $wpdb->get_results($sql_query);
        if (empty($DatabaseData)) {
            error_log("No products to process.");
            return;
        }

 
        $created_products = []; // Store SKU → WooCommerce product ID mappings

        foreach ($DatabaseData as $data) {
            $sku = $data->sku;

 

            $json_data = json_decode($data->row_json, true);
            if (!$json_data) continue;

            $product = wc_get_product($data->wc_product_id);
            if (!$product) {
                error_log("Skipping invalid product ID: {$data->wc_product_id}");
                continue;
            }
            
            $product->set_sku($sku);
            $product->set_name($json_data['product_name'] ?? '');
            $product->set_description($json_data['product_description'] ?? '');
            $product->set_weight($json_data['dimensions']['weight'] ?? '');
            $product->set_length($json_data['dimensions']['length'] ?? '');
            $product->set_width($json_data['dimensions']['width'] ?? '');
            $product->set_height($json_data['dimensions']['height'] ?? '');

            $final_price = function_exists('calculate_price') ? calculate_price($json_data['price'] ?? 0) : $json_data['price'] ?? 0;
            if ($final_price) {
                $product->set_price($final_price);
                $product->set_regular_price($final_price);
            }

            // --- Stock handling ---
            $available_stock = (int)($json_data['quantity'] ?? 0);
            $upcoming_stocks = $json_data['upcoming_stocks'] ?? 0;
            $inbound = $json_data['inbound'] ?? '';

            $inbound_qty = function_exists('is_inbound_stock') ? is_inbound_stock($available_stock, $upcoming_stocks, $inbound) : 0;

            if ($inbound_qty) {
                $product->set_manage_stock(true);
                $product->set_stock_quantity($inbound_qty);
                update_post_meta($product->get_id(), 'stock_inbound_date', sanitize_text_field($inbound));
            } elseif ($available_stock) {
                $product->set_manage_stock(true);
                $product->set_stock_quantity($available_stock);
                update_post_meta($product->get_id(), 'stock_inbound_date', null);
            } else {
                $product->set_manage_stock(false);
            }

            // --- Tags ---
            $tags = array_filter($json_data['tags'] ?? []);
            $product->set_tag_ids(array_map('wc_get_tag_id_by_name', $tags));

            // --- Categories ---
            $category_value = $json_data['categories'] ?? '';
            if (!empty($category_value)) {
                $category_id = wc_get_category_id_by_name($category_value);
                if ($category_id) $product->set_category_ids([$category_id]);
            }

            // --- Attributes ---
            $existing_attributes = $product->get_attributes();
            $attribute_data = $this->prepare_attributes($json_data);
            foreach ($attribute_data as $name => $value) {
                if (!empty($value)) {
                    $attribute = new WC_Product_Attribute();
                    $attribute->set_name($name);
                    $attribute->set_options((array)$value);
                    $attribute->set_visible(true);
                    $attribute->set_variation(false);
                    $existing_attributes[$name] = $attribute;
                }
            }
            $product->set_attributes($existing_attributes);

            // --- Save product ---
            $product->save();

            $created_products[$sku] = $product->get_id();

            // --- Mark as synced in CSV table ---
            $wpdb->update(
                $table_name,
                [
                    'is_sync' => 1,
                    'wc_product_id' => $product->get_id(),
                    'synched_date' => current_time('mysql')
                ],
                ['sku' => $sku]
            );
        }

        error_log("bw_update_woo_pro_to_resync = END OF EXECUTION");
    }

    /**
     * Helper function to prepare attributes
     */
    private function prepare_attributes($json_data) {
        $attrs = $json_data['attributes'] ?? [];
        return [
            'ASSORTMENT' => $attrs['attributes']['assortment'],
            'SET' => $attrs['attributes']['set'], 
            'VMM' => $attrs['attributes']['vmm'],
            'UNITS IN DISPLAY' => $attrs['attributes']['units_in_display'],
            'UNIT BOX WAREHOUSE' => $attrs['attributes']['unit_box_warehouse'],
            'UNIT BOX CLIENT' => $attrs['attributes']['unit_box_client'],
            'U/C' => $attrs['attributes']['u_c'],
            'PIECES' => $attrs['attributes']['pieces'],
            'Bar Code' => $attrs['attributes']['bar_code'],
            'Division' => $attrs['attributes']['division'],
            'MATERIAL 1' => $attrs['attributes']['materials']['material_1'],
            'PERCENT MATERIAL 1' => $attrs['attributes']['materials']['percent_material_1'],
            'MATERIAL 2' => $attrs['attributes']['materials']['material_2'],
            'PERCENT MATERIAL 2' => $attrs['attributes']['materials']['percent_material_2'],
            'MATERIAL 3' => $attrs['attributes']['materials']['material_3'],
            'PERCENT MATERIAL 3' => $attrs['attributes']['materials']['percent_material_3'],
            'MATERIAL 4' => $attrs['attributes']['materials']['material_4'],
            'PERCENT MATERIAL 4' => $attrs['attributes']['materials']['percent_material_4'],
            'PRODUCT FINISH // FINISHING' => $attrs['attributes']['product_finish'],
            'Others Width Product (cm)' => $attrs['attributes']['others_width_product_cm'],
            'Others Product Length (cm)' => $attrs['attributes']['others_product_lenght_cm'],
            'Others High Product (cm)' => $attrs['attributes']['others_height_product_cm'],
            'Product diameter and other measures (cm) as sets of baskets etc .' => $attrs['attributes']['product_diameterand_other_etc'],
            'CAPACITY' => $attrs['attributes']['capacity'],
            'Product weight (Kg)' => $attrs['attributes']['product_weight_kg'],
            'Gross product weight (Kg)' => $attrs['attributes']['gross_weight'], 
            'Hecho a mano' => $attrs['attributes']['hecho_a_mano'],
            'RECICLABLE' => $attrs['attributes']['reciclable'],
            'SEAT HIGH' => $attrs['attributes']['seat_high'],
            'Adjustable' => $attrs['attributes']['adjustable'],
            'Num.Drawers' => $attrs['attributes']['mum_drawers'],
            'Width drawer(cm)' => $attrs['attributes']['width_drawer_cm'],
            'Length drawer(cm)' => $attrs['attributes']['lenght_drawer_cm'],
            'High drawer(cm)' => $attrs['attributes']['high_drawer_cm'],
            'Num.Doors' => $attrs['attributes']['num_doors'],
            'Width door (cm)' => $attrs['attributes']['width_door_cm'],
            'Length door (cm)' => $attrs['attributes']['lenght_door_cm'],
            'High door (cm)' => $attrs['attributes']['high_door_cm'],
            'Num.Shelves' => $attrs['attributes']['num_shelves'],
            'Distance Shelves' => $attrs['attributes']['distance_shelves'],
            'Removable' => $attrs['attributes']['removable'],
            'Doble cara' => $attrs['attributes']['doble_cara'],
            'Two men handing' => $attrs['attributes']['two_men_handling'],
            'BULB INCLUDED' => $attrs['attributes']['bulb_included'],
            'bulb type' => $attrs['attributes']['bulb_type'],
            'SHADE dimension' => $attrs['attributes']['shade_dimension'],
            'SHADE material' => $attrs['attributes']['shade_material'],
            'Power(watts)' => $attrs['attributes']['power_watts'],
            'Efficiency' => $attrs['attributes']['efficiency'],
            'Volts' => $attrs['attributes']['volts'],
            'WIRE length(cm)' => $attrs['attributes']['wire_lenght_cm'],
            'Num.bulbs' => $attrs['attributes']['num_bulbs'],
            'Instructions' => $attrs['attributes']['instructions'],
            'Dimensions: photo / mirror' => $attrs['attributes']['dimensions_photo_mirror'],
            'Width frame' => $attrs['attributes']['width_frame'],
            'Fragrance candle / air freshener' => $attrs['attributes']['fragrance_candle_air_freshener'],
            'Antimosquito' => $attrs['attributes']['antimosquito'],
            'Burning time candle / air freshener' => $attrs['attributes']['burning_time_candle_air_freshener'],
            'Battery incl.' => $attrs['attributes']['battery_incl'],
            'Battery type' => $attrs['attributes']['battery_type'],
            'Num.battery' => $attrs['attributes']['num_battery'],
            'LED' => $attrs['attributes']['led'],
            'Caja regalo' => $attrs['attributes']['caja_regalo'],
            'Anti-stain' => $attrs['attributes']['anti_stain'],
            'Fabric / FILLING' => $attrs['attributes']['fabric_filing'],
            'ZIP' => $attrs['attributes']['zip'],
            'Gramaje' => $attrs['attributes']['gramaje'],
            'OVEN SAFE' => $attrs['attributes']['oven_safe'],
            'MICROWAVE SAFE' => $attrs['attributes']['microwave_safe'],
            'VITRO SAFE' => $attrs['attributes']['vitro_safe'],
            'INDUCTION SAFE' => $attrs['attributes']['induction_safe'],
            'Hermetic closure' => $attrs['attributes']['hermetic_closure'],
            'GAS SAFE' => $attrs['attributes']['gas_safe'],
            'Movimiento' => $attrs['attributes']['movimiento'],
            'Peluche' => $attrs['attributes']['peluche'],
            'DISHWASHER SAFE' => $attrs['attributes']['dishwasher_safe'],
            'Color' => array_filter($json_data['colors'] ?? []),
            'Details' => array_filter($json_data['details'] ?? [])
        ];
    }
}
