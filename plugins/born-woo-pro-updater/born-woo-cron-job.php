<?php 
// Register custom intervals for 4 and 24-hour schedules
add_filter('cron_schedules', 'born_woo_custom_intervals');
function born_woo_custom_intervals($schedules) {

    $schedules['every_1_minute'] = array(
        'interval' => 1 * MINUTE_IN_SECONDS,
        'display' => __('Every 1 Minute')
    );

    $schedules['every_2_minutes'] = array(
        'interval' => 2 * MINUTE_IN_SECONDS,
        'display' => __('Every 2 Minutes')
    );

    $schedules['every_3_minutes'] = array(
        'interval' => 3 * MINUTE_IN_SECONDS,
        'display' => __('Every 3 Minutes')
    );

    $schedules['every_5_minutes'] = array(
        'interval' => 5 * MINUTE_IN_SECONDS,
        'display' => __('Every 5 Minutes')
    );

    $schedules['every_7_minutes'] = array(
        'interval' => 7 * MINUTE_IN_SECONDS,
        'display' => __('Every 7 Minutes')
    );

    $schedules['every_8_minutes'] = array(
        'interval' => 8 * MINUTE_IN_SECONDS,
        'display' => __('Every 8 Minutes')
    );

    $schedules['every_10_minutes'] = array(
        'interval' => 10 * MINUTE_IN_SECONDS,
        'display' => __('Every 10 Minutes')
    );

    $schedules['every_20_minutes'] = array(
        'interval' => 20 * MINUTE_IN_SECONDS,
        'display' => __('Every 20 Minutes')
    );

    $schedules['every_30_minutes'] = array(
        'interval' => 30 * MINUTE_IN_SECONDS,
        'display' => __('Every 30 Minutes')
    );

    $schedules['every_1_hour'] = array(
        'interval' => 1 * HOUR_IN_SECONDS,
        'display' => __('Every 1 Hour')
    );

    $schedules['every_3_hours'] = array(
        'interval' => 3 * HOUR_IN_SECONDS,
        'display' => __('Every 3 Hours')
    );

    $schedules['every_4_hours'] = array(
        'interval' => 4 * HOUR_IN_SECONDS,
        'display' => __('Every 4 Hours')
    );

    $schedules['every_10_hours'] = array(
        'interval' => 10 * HOUR_IN_SECONDS,
        'display' => __('Every 10 Hours')
    );

    $schedules['every_12_hours'] = array(
        'interval' => 12 * HOUR_IN_SECONDS,
        'display' => __('Every 12 Hours')
    );

    $schedules['every_24_hours'] = array(
        'interval' => 24 * HOUR_IN_SECONDS,
        'display' => __('Every 24 Hours')
    );

    $schedules['every_48_hours'] = array(
        'interval' => 48 * HOUR_IN_SECONDS,
        'display' => __('Every 48 Hours')
    );

    return $schedules;
}


add_action('init', 'born_woo_check_and_reschedule_cron_jobs');
function born_woo_check_and_reschedule_cron_jobs() {

    $BORN_WOO_FTP_IMAGES_PATH = get_option('BORN_WOO_FTP_IMAGES_PATH', '');
    $BORN_WOO_FTP_CSV_ARTICULO = get_option('BORN_WOO_FTP_CSV_ARTICULO', '');
    $BORN_WOO_FTP_CSV_STOCKS = get_option('BORN_WOO_FTP_CSV_STOCKS', '');

    if ( 
        $BORN_WOO_FTP_IMAGES_PATH != "" && 
        $BORN_WOO_FTP_CSV_ARTICULO != "" && 
        $BORN_WOO_FTP_CSV_STOCKS != "" 
        ) {

        if (!wp_next_scheduled('bw_download_articulo_csv_cron_job_hook')) {
            wp_schedule_event(time(), 'every_10_hours', 'bw_download_articulo_csv_cron_job_hook');
        }
        add_action('bw_download_articulo_csv_cron_job_hook', 'download_articulo_csv_cron_job');

        if (!wp_next_scheduled('bw_add_products_to_articulo_db_hook')) {
            wp_schedule_event(time(), 'every_10_hours', 'bw_add_products_to_articulo_db_hook');
        }
        add_action('bw_add_products_to_articulo_db_hook', 'add_products_to_articulo_db');

        if (!wp_next_scheduled('bw_add_articulo_products_to_woocommerce_hook')) {
            wp_schedule_event(time(), 'every_3_minutes', 'bw_add_articulo_products_to_woocommerce_hook');
        }
        add_action('bw_add_articulo_products_to_woocommerce_hook', 'add_articulo_products_to_woocommerce');

        if (!wp_next_scheduled('bw_download_stocks_csv_cron_job_hook')) {
            wp_schedule_event(time(), 'every_3_hours', 'bw_download_stocks_csv_cron_job_hook');
        }
        add_action('bw_download_stocks_csv_cron_job_hook', 'download_stocks_csv_cron_job');
        
        if (!wp_next_scheduled('bw_add_inbound_data_to_stocks')) {
            wp_schedule_event(time(), 'every_4_hours', 'bw_add_inbound_data_to_stocks');
        }
        add_action('bw_add_inbound_data_to_stocks', 'add_inbound_data_to_stocks');

        if (!wp_next_scheduled('bw_update_woocommerce_stocks_hook')) {
            wp_schedule_event(time(), 'every_1_minute', 'bw_update_woocommerce_stocks_hook');
        }
        add_action('bw_update_woocommerce_stocks_hook', 'update_woocommerce_stocks');

        if (!wp_next_scheduled('attach_images_from_ftp_hook')) {
            wp_schedule_event(time(), 'every_3_minutes', 'attach_images_from_ftp_hook');
        }
        add_action('attach_images_from_ftp_hook', 'attach_images_from_ftp');

        if (!wp_next_scheduled('bw_set_products_to_draft_hook')) {
            wp_schedule_event(time(), 'every_3_hours', 'bw_set_products_to_draft_hook');
        }
        add_action('bw_set_products_to_draft_hook', 'bw_set_products_to_draft');

        if (!wp_next_scheduled('bw_pair_product_id_hook')) {
            wp_schedule_event(time(), 'every_10_minutes', 'bw_pair_product_id_hook');
        }
        add_action('bw_pair_product_id_hook', 'bw_pair_product_id');

        if (!wp_next_scheduled('bw_update_woocomerce_category_hook')) {
            wp_schedule_event(time(), 'every_1_minute', 'bw_update_woocomerce_category_hook');
        }
        add_action('bw_update_woocomerce_category_hook', 'bw_update_woocomerce_category');

    }
    else {
   
        wp_clear_scheduled_hook('bw_download_articulo_csv_cron_job_hook');

        wp_clear_scheduled_hook('bw_add_products_to_articulo_db_hook');
    
        wp_clear_scheduled_hook('bw_add_articulo_products_to_woocommerce_hook');

        wp_clear_scheduled_hook('bw_download_stocks_csv_cron_job_hook');

        wp_clear_scheduled_hook('bw_add_inbound_data_to_stocks');

        wp_clear_scheduled_hook('bw_update_woocommerce_stocks_hook');

        wp_clear_scheduled_hook('attach_images_from_ftp_hook');
        
        wp_clear_scheduled_hook('bw_set_products_to_draft_hook');
        
        wp_clear_scheduled_hook('bw_pair_product_id_hook');
        
        wp_clear_scheduled_hook('bw_update_woocomerce_category_hook');

        wp_clear_scheduled_hook('bw_add_data_to_resync_db_hook');
                
        wp_clear_scheduled_hook('bw_pair_ids_to_resync_hook');
                
        wp_clear_scheduled_hook('bw_update_woo_pro_to_resync_hook');
        
    }
} 
