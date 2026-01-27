<?php
add_action( 'pmxi_saved_post', 'bwpu_woocommerce_after_save', 10, 1 );

function bwpu_woocommerce_after_save( $post_id ) {
    // Only WooCommerce products
    if ( get_post_type( $post_id ) !== 'product' ) {
        return;
    }

    include_once plugin_dir_path(__FILE__) . 'class/helpers.php';
    $iDecorHelpers = new iDecorHelpers();

    // ----- Clean Product Name -----
    $original_name = get_the_title( $post_id );
    $clean_name = $iDecorHelpers->formatProductName( $original_name );

    // Update only if different to avoid extra saves
    if ( $clean_name !== $original_name ) {
        wp_update_post([
            'ID'         => $post_id,
            'post_title' => $clean_name,
            'post_name'  => sanitize_title( $clean_name ),
        ]);
    }

    // ----- Update Price -----
    $original_price = get_post_meta( $post_id, '_regular_price', true );
    $new_price = $iDecorHelpers->calculatePrice( $original_price );

    if ( $new_price != $original_price ) {
        update_post_meta( $post_id, '_regular_price', $new_price );
        update_post_meta( $post_id, '_price', $new_price );
    }
 
    // ----- Stocks -----
    $current_woo_stocks = get_post_meta( $post_id, '_stocks', true ); 
    $upcomming_stocks = get_post_meta( $post_id, '_upcoming_stock', true ); 
    $inbound = get_post_meta( $post_id, '_inbound', true );  
    $isInboundStocks = $iDecorHelpers->isInboundStocks( $current_woo_stocks,  $upcomming_stocks, $inbound );

    if (  $isInboundStocks  ) {
        update_post_meta( $post_id , 'stock_inbound_date', sanitize_text_field($inbound));
    }
    else {
        update_post_meta( $post_id , 'stock_inbound_date', null );
    }

}