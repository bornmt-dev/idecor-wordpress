<?php
/**
 * Theme functions and definitions.
 */

 include 'inc/shortcodes/portfolio-masonry.php';
 add_action("wp_enqueue_scripts", "enqueues", 10);

 function enqueues(){
    //CS
    wp_enqueue_style("header-style", get_stylesheet_directory_uri(). "/assets/css/header.css", array(), time(),"all" );
    wp_enqueue_style("custom-style", get_stylesheet_directory_uri(). "/assets/css/style.css", array(), time(),"all" );
    wp_enqueue_style("custom-style-new", get_stylesheet_directory_uri(). "/assets/css/custom-style.css", array(), time(),"all" );
    //JS
    wp_enqueue_script("header-script", get_stylesheet_directory_uri(). "/assets/js/header.js",array("jquery"),time(), true);
    wp_enqueue_script("masonry-script", get_stylesheet_directory_uri(). "/assets/js/masonry.js",array("jquery"),time(), true);
    wp_enqueue_script("portmas-script", get_stylesheet_directory_uri(). "/assets/js/portfolio-masonry.js",array("jquery"),time(), true);
    wp_enqueue_script("custom-script", get_stylesheet_directory_uri(). "/assets/js/custom.js",array("jquery"),time(), true);
    
 }



 function add_custom_product_tab($tabs) {
   $acf_field_value = get_field('specifications');

   if (!empty($acf_field_value)) {
       $tabs['specifications'] = array(
           'title' => 'Specifications',
           'callback' => 'your_tab_content_callback',
           'priority' => 50,
       );
   }

   return $tabs;
}
add_filter('woocommerce_product_tabs', 'add_custom_product_tab');

function your_tab_content_callback() {
   $acf_field_value = get_field('specifications');
   echo $acf_field_value;
}

add_action( 'woocommerce_product_meta_start', 'custom_product_sku', 10 );

function custom_product_sku() {
  global $product;
  if ( wc_product_sku_enabled() && ( $product->get_sku() || $product->is_type( 'variable' ) ) ) : ?>
    <span class="sku_wrapper">SKU: <span class="sku"><?php echo ( $sku = $product->get_sku() ) ? $sku : esc_html__( 'N/A', 'woocommerce' ); ?></span></span>
    <script>
            jQuery('.product_after_title .sku_wrapper').remove();
    </script>
  <?php endif;
}

// function custom_breadcrumbs_my_account() {
//    if (is_account_page()) {
//        echo '<nav class="woocommerce-breadcrumb" style="margin-bottom: 20px;">';
//        echo '<a href="' . home_url() . '">Home</a> / My Account';
//        echo '</nav>';
//    }
// }
// add_action('woocommerce_before_customer_login_form', 'custom_breadcrumbs_my_account');


function single_post_breadcrumbs() {
   $breadcrumbs = '<a href="' . home_url() . '">Home</a> > ';
   if (is_single()) {
      
       $blog_page = get_post_type_archive_link('post');
       $breadcrumbs .= '<a href="' . $blog_page . '">Blog</a> > ';
       $breadcrumbs .= '<span>' . get_the_title() . '</span>';
   }

   return $breadcrumbs;
}
add_shortcode('single_breadcrumbs', 'single_post_breadcrumbs');

function custom_woocommerce_checkout_fields($fields) {
    // Define placeholders for billing fields
    $placeholders = array(
        'billing_first_name'  => 'First Name *',
        'billing_last_name'   => 'Last Name *',
        'billing_company'     => 'Company Name (Optional)',
        'billing_address_1'   => 'Street Address Line 1 *',
        'billing_address_2'   => 'Street Address Line 2',
        'billing_city'        => 'Town / City *',
        'billing_postcode'    => 'ZIP Code *',
        'billing_country'     => 'Country / Region *',
        'billing_phone'       => 'Phone *',
        'billing_email'       => 'Email Address *'
    );

    // Set placeholders for billing fields
    foreach ($placeholders as $field_key => $placeholder) {
        if (isset($fields['billing'][$field_key])) {
            $fields['billing'][$field_key]['placeholder'] = esc_attr($placeholder);
            $fields['billing'][$field_key]['label'] = esc_attr($placeholder);  // Update the label as well
        }
    }

    // Check if Order Notes field exists and modify it
    if (isset($fields['order']['order_comments'])) {
        $fields['order']['order_comments']['placeholder'] = 'Order Notes (Optional) *';
    } else {
        error_log('Order Notes field does not exist.');
    }

    return $fields;
}
add_filter('woocommerce_checkout_fields', 'custom_woocommerce_checkout_fields', 20, 1);


add_action( 'wp_footer', 'force_update_checkout_placeholders_with_delay' );
function force_update_checkout_placeholders_with_delay() {
    if ( is_checkout() ) : ?>
    <script>
    jQuery(document).ready(function($) {
        
        setTimeout(function() {
            $('#billing_address_1').attr('placeholder', 'Street Address Line 1 *');
            $('#billing_address_2').attr('placeholder', 'Street Address Line 2');
        }, 1000); 
    });
    </script>
    <?php
    endif;
}

// function include_custom_cron() {
//    $php_directory = get_stylesheet_directory() . '/inc/cronjobs/';
//    $php_files = glob($php_directory . '*.php');
//    foreach ($php_files as $php_file) {
//        include_once $php_file;
//    }
// }
// add_action('after_setup_theme', 'include_custom_cron');



function display_subcategories_of_subcategory_by_first_in($atts) {
   $atts = shortcode_atts(array(
       'subcategory' => '', 
       'exclude' => '', 
       'include' => '', 
   ), $atts);

   $subcategory_slug = sanitize_text_field($atts['subcategory']);
   $exclude = sanitize_text_field($atts['exclude']);
   $exclude_ids = explode(',', $exclude); 

   $include = sanitize_text_field($atts['include']);
   $include_ids = explode(',', $include); 

   if (!$subcategory_slug) {
       return 'Please provide a valid subcategory slug.';
   }

   $subcategory = get_term_by('slug', $subcategory_slug, 'product_cat');
   if (!$subcategory) {
       return 'No subcategory found with the provided slug.';
   }

   $child_subcategories = get_terms(array(
       'taxonomy' => 'product_cat',
       'parent' => $subcategory->term_id,
       'hide_empty' => false, 
       'exclude'    => $exclude_ids,  // Exclude these category IDs
   ));

   foreach ($include_ids as $include_id) {
        $term = get_term($include_id, 'product_cat');
        
        if (is_wp_error($term)) {
            // Handle error if term doesn't exist or there is an issue
        } else {
            // Get category URL and name
            $category_url = get_term_link($term);
            $category_name = $term->name;

            if (!is_wp_error($category_url)) {
                // Merge or array push the term into the $child_subcategories array
                $child_subcategories[] = $term; // Add the new term object to the array
            }
        }
    }

   // Initialize output
   $output = '<ul class="subcategory-list">';

   if (!empty($child_subcategories)) {

        // Sort the $child_subcategories array alphabetically by category name
        usort($child_subcategories, function($a, $b) {
            return strcmp($a->name, $b->name); // Compare the names of the terms
        });

       foreach ($child_subcategories as $child_subcategory) {
           $output .= '<li><a href="' . get_term_link($child_subcategory) . '">' . $child_subcategory->name . '</a></li>';
       }
   } 
   else {
       $output .= '<li>No subcategories available.</li>';
   }



   $output .= '</ul>';

   return $output;
}
add_shortcode('subcategories_of_subcategory', 'display_subcategories_of_subcategory_by_first_in');

// Add a custom specifications tab for product attributes
add_filter('woocommerce_product_tabs', 'add_custom_product_specifications_tab');
function add_custom_product_specifications_tab($tabs) {
    // Add a new tab
    $tabs['specifications'] = array(
        'title'    => __('Specifications', 'carafity-child'),
        'priority' => 50, 
        'callback' => 'custom_product_specifications_tab_content'
    );
    return $tabs;
}

// Content for the specifications tab that displays product attributes, SKU, and title
function custom_product_specifications_tab_content() {
    global $product;

    // Get product attributes
    $attributes = $product->get_attributes();
    
    // Get the SKU and product title
    $sku = $product->get_sku() ? $product->get_sku() : 'N/A';
    $title = $product->get_name();

    // Limit the attributes to a maximum of 12
    $attributes = array_slice( $attributes, 0, 12 );
    $attribute_count = count( $attributes );

    // Prepare all data (SKU, title, attributes) for display
    $data = array();
    $data[] = array('Reference Number', $sku);
    $data[] = array('Description', $title);

    // Loop through attributes and add them to the data array
    foreach ( $attributes as $attribute ) {
        // Get attribute name and value
        $name = wc_attribute_label( $attribute->get_name() );
        if ( $attribute->is_taxonomy() ) {
            $terms = wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'names' ) );
            $value = implode( ', ', $terms );
        } else {
            $value = implode( ', ', $attribute->get_options() );
        }
        $data[] = array( $name, $value );
    }

    // Ensure we have at least 9 rows (if not, add empty rows)
    for ( $i = count( $data ); $i < 9; $i++ ) {
        $data[] = array('', '');
    }

    // Start the table
    echo '<table class="shop_attributes">';

    // Table headers: "Specifications" and "Details"
    echo '<tr>';
    echo '<th class="title">Specifications</th>';
    echo '<th class="title">Details</th>';
    echo '<th class="title">Specifications</th>';
    echo '<th class="title">Details</th>';
    echo '</tr>';

    // Loop to display 9 rows in 4 columns (data in two sets of columns)
    for ( $i = 0; $i < 9; $i++ ) {
        echo '<tr>';

        // First two columns (first half of the data)
        if ( isset( $data[$i] ) ) {
            echo '<th>' . esc_html( $data[$i][0] ) . '</th>';
            echo '<td>' . esc_html( $data[$i][1] ) . '</td>';
        } else {
            echo '<th></th><td></td>';
        }

        // Second two columns (continue the data if more than 8 entries)
        if ( isset( $data[$i + 9] ) ) {
            echo '<th>' . esc_html( $data[$i + 9][0] ) . '</th>';
            echo '<td>' . esc_html( $data[$i + 9][1] ) . '</td>';
        } else {
            echo '<th></th><td></td>';
        }

        echo '</tr>';
    }

    echo '</table>';
}

function redirect_checkout_to_cart() {
    if (is_checkout() && !is_wc_endpoint_url()) {
        wp_redirect(wc_get_cart_url());
        exit; // Always call exit after wp_redirect
    }
}
add_action('template_redirect', 'redirect_checkout_to_cart');

// Show "Out of stock" if price = 0
add_filter('woocommerce_get_availability', function($availability, $product) {
    if ($product->get_price() == 0) {
        $availability['availability'] = __('Out of stock', 'woocommerce');
        $availability['class'] = 'out-of-stock';
    }
    return $availability;
}, 10, 2);

// // Hide price if product price is zero
// add_filter('woocommerce_get_price_html', function($price_html, $product) {
//     if ($product->get_price() == 0) {
//         return '';
//     }
//     return $price_html;
// }, 10, 2);

function custom_stock_message($availability, $product) {
    // Check if the product is in stock

    $stock_inbound_date = get_post_meta( $product->get_id(), 'stock_inbound_date', true );

    if ($product->is_in_stock()) {
        // Display "In Stock" instead of stock quantity
        if ( $stock_inbound_date ) {
            $availability['availability'] = __('Expected: '.format_stock_inbound_date($stock_inbound_date) , 'woocommerce');
        }
        else {
            $availability['availability'] = __('In Stock', 'woocommerce');
        }
    } else {
        // Display "Out of Stock" if the product is not available
        $availability['availability'] = __('Out of Stock', 'woocommerce');
    }
    return $availability;
}
add_filter('woocommerce_get_availability', 'custom_stock_message', 10, 2);


add_filter( 'woocommerce_product_categories_widget_args', 'custom_woocommerce_product_subcategories_args' );
function custom_woocommerce_product_subcategories_args( $args ) {
    $args['exclude'] = get_option( 'default_product_cat' );
    return $args;
}


// // Hide products without thumbnail
// add_action( 'woocommerce_product_query', function( $q ) {
//     global $wpdb; 

//     $placeholder_url = wc_placeholder_img_src(); //default product thumbnail

//     $meta_query = $q->get( 'meta_query' ) ?: [];
//     $meta_query[] = [
//         'key'     => '_thumbnail_id',
//         'compare' => 'EXISTS',
//     ];
//     $q->set( 'meta_query', $meta_query );

//     // hide product w/ default thumbnail
//     add_filter( 'posts_where', function( $where, $query ) use ( $placeholder_url, $wpdb, $q ) {
//         if ( $query !== $q ) {
//             return $where;
//         }

//         $where .= $wpdb->prepare(
//             " AND (
//                 SELECT guid 
//                 FROM {$wpdb->posts} AS thumb 
//                 WHERE thumb.ID = {$wpdb->postmeta}.meta_value 
//                 LIMIT 1
//             ) != %s",
//             $placeholder_url
//         );

//         return $where;
//     }, 10, 2 );
// });


function format_stock_inbound_date( string $raw ): string {
    if ( empty( $raw ) ) {
        return '';
    }

    // Match format like 1QOCT-25 or 2QDEC-26
    if ( ! preg_match( '/^(1Q|2Q)([A-Z]{3})-(\d{2})$/i', $raw, $matches ) ) {
        return $raw; // Return original if format is invalid
    }

    $quarter   = strtoupper( $matches[1] ); // 1Q or 2Q
    $monthAbbr = strtoupper( $matches[2] ); // e.g. OCT
    $yearTwo   = $matches[3];               // e.g. 25

    // Convert year (assume 2000+)
    $year = (int) ( '20' . $yearTwo );

    // Map month abbreviation to number
    $months = [
        'JAN' => 1, 'ENE' => 1,
        'FEB' => 2,
        'MAR' => 3,
        'APR' => 4, 'ABR' => 4,
        'MAY' => 5,
        'JUN' => 6,
        'JUL' => 7,
        'AUG' => 8, 'AGO' => 8,
        'SEP' => 9, 'SET' => 9,
        'OCT' => 10,
        'NOV' => 11,
        'DEC' => 12, 'DIC' => 12,
    ];

    if ( ! isset( $months[ $monthAbbr ] ) ) {
        return $raw; // invalid month abbreviation
    }

    $month = $months[ $monthAbbr ];

    // Day rules
    $day = ( $quarter === '1Q' ) ? 15 : 30;

    // Build timestamp
    $timestamp = mktime( 0, 0, 0, $month, $day, $year );

    // Format date (localized)
    return date_i18n( 'F j, Y', $timestamp );  
}



// add_action( 'pre_get_posts', function( $query ) {
//     if ( ! $query->is_main_query() || is_admin() ) {
//         return;
//     }

//     if ( function_exists( 'is_woocommerce') && is_woocommerce() ) {
//         global $wpdb;

//         // Hide products with price = 0
//         $meta_query = $query->get( 'meta_query' ) ?: [];
//         $meta_query[] = [
//             'key'     => '_price',
//             'value'   => 0,
//             'compare' => '>',
//             'type'    => 'NUMERIC'
//         ];

//         // Require product to have a real thumbnail
//         $meta_query[] = [
//             'key'     => '_thumbnail_id',
//             'value'   => 0,
//             'compare' => '>',
//             'type'    => 'NUMERIC'
//         ];

//         // Hide out-of-stock or 0 stock products
//         $meta_query[] = [
//             'relation' => 'AND',
//             [
//                 'key'     => '_stock_status',
//                 'value'   => 'instock',
//                 'compare' => '=',
//             ],
//             [
//                 'relation' => 'OR',
//                 [
//                     'key'     => '_manage_stock',
//                     'compare' => 'NOT EXISTS'
//                 ],
//                 [
//                     'key'     => '_stock',
//                     'value'   => 0,
//                     'compare' => '>',
//                     'type'    => 'NUMERIC'
//                 ]
//             ]
//         ];

//         $query->set( 'meta_query', $meta_query );

//         // Optional extra: exclude explicit placeholder URL (safety net)
//         $placeholder_url = wc_placeholder_img_src();
//         add_filter( 'posts_where', function( $where, $q ) use ( $query, $wpdb, $placeholder_url ) {
//             if ( $q !== $query ) {
//                 return $where;
//             }

//             // Exclude posts where thumbnail guid = placeholder
//             $where .= $wpdb->prepare(
//                 " AND NOT EXISTS (
//                     SELECT 1 
//                     FROM {$wpdb->posts} AS thumb
//                     WHERE thumb.ID = {$wpdb->postmeta}.meta_value
//                     AND thumb.guid = %s
//                 )",
//                 $placeholder_url
//             );

//             return $where;
//         }, 10, 2 );
//     }
// });

add_filter( 'wc_get_price_decimals', function( $decimals ) {
    return 2; // always show 2 decimals
});

add_filter( 'woocommerce_price_rounding_precision', function() {
    return 2; // prevent WooCommerce from rounding to nearest integer
});

/**
 * Include SKU, title, description, short description, categories, and tags in WooCommerce search
 */
add_filter('posts_search', 'ac_include_sku_and_taxonomy_in_search', 10, 2);

function ac_include_sku_and_taxonomy_in_search($search, $query) {
    global $wpdb;

    // Only modify the main WooCommerce product search query
    if (!is_admin() && $query->is_main_query() && is_search() && isset($query->query_vars['post_type']) && $query->query_vars['post_type'] === 'product') {

        $search_term = $query->get('s');
        if (empty($search_term)) {
            return $search;
        }

        // Clear default search to prevent double LIKE matches
        $query->set('s', '');

        $like = '%' . esc_sql($wpdb->esc_like($search_term)) . '%';

        $search = "
            AND (
                {$wpdb->posts}.post_title LIKE '{$like}'
                OR {$wpdb->posts}.post_content LIKE '{$like}'
                OR {$wpdb->posts}.post_excerpt LIKE '{$like}'
                OR {$wpdb->posts}.ID IN (
                    SELECT post_id FROM {$wpdb->postmeta}
                    WHERE meta_key = '_sku' AND meta_value LIKE '{$like}'
                )
                OR {$wpdb->posts}.ID IN (
                    SELECT object_id
                    FROM {$wpdb->term_relationships} AS tr
                    INNER JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                    INNER JOIN {$wpdb->terms} AS t ON tt.term_id = t.term_id
                    WHERE tt.taxonomy IN ('product_cat','product_tag') AND t.name LIKE '{$like}'
                )
            )
        ";
    }

    return $search;
}


add_action( 'pre_get_posts', 'hide_products_without_image' );
function hide_products_without_image( $query ) {
    if ( ! is_admin() && $query->is_main_query() && ( is_shop() || is_product_category() || is_product_tag() ) ) {
        // Get all product IDs that have a thumbnail
        $args = array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'meta_query'     => array(
                array(
                    'key'     => '_thumbnail_id',
                    'compare' => 'EXISTS'
                )
            ),
            'fields'         => 'ids',
            'posts_per_page' => -1,
        );
        $products_with_image = get_posts( $args );

        if ( ! empty( $products_with_image ) ) {
            $query->set( 'post__in', $products_with_image );
        } else {
            // Prevent showing any products if none have images
            $query->set( 'post__in', array(0) );
        }
    }
}