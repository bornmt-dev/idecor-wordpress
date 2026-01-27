<?php 

include_once plugin_dir_path(__FILE__) . 'class/csv.php';

// Add a manual page refresh trigger
add_action('admin_init', function() {
    if (isset($_GET['run_csv_download']) && current_user_can('manage_options')) { 
        $iDecorCSV = new iDecorCSV();
        $downloadCSV = $iDecorCSV->downloadCSV(); 
    }
});